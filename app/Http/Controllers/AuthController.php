<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\CryptoPayment;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\Factory;
use Illuminate\View\View;

class AuthController extends Controller
{
    protected $cryptoPayment;

    public function __construct(CryptoPayment $cryptoPayment)
    {
        $this->cryptoPayment = $cryptoPayment;
    }

    // Login-Formular anzeigen
    public function showLoginForm(): View|Application|Factory
    {
        return view('login'); // Twig wird durch Blade ersetzt
    }

    // Registrierungs-Formular anzeigen
    public function showRegisterForm(): View|Application|Factory
    {
        return view('registration_form'); // Twig wird durch Blade ersetzt
    }

    // Login-Logik
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'E-Mail' => 'required|email',
            'password_usr' => 'required',
        ]);

        if (Auth::attempt([
            "email" => $credentials['E-Mail'],
            "password" => $credentials['password_usr'],
        ])) {
            $request->session()->regenerate();

            return redirect()->intended('designs');
        }

        return back()->withErrors([
            'email' => 'Die bereitgestellten Anmeldedaten stimmen nicht überein.',
        ]);
    }

    // Registrierung-Logik
    public function register(Request $request): Application|Redirector|RedirectResponse
    {
        $validated = $request->validate([
            'E-Mail' => 'required|email|unique:App\Models\User,Email',
            'password_usr' => 'required|same:password_confirm_usr',
            'Firstname' => 'required',
            'Lastname' => 'required',
            'Street' => 'required',
            'House' => 'required',
            'City' => 'required',
            'Postalcode' => 'required',
            'Country' => 'required',
            'agb' => 'required',
            'Role' => 'required',
        ]);

        $user = User::create([
            'Email' => $validated['E-Mail'],
            'Password_Hash' => Hash::make($validated['password_usr']),
            'First_Name' => $validated['Firstname'],
            'Last_Name' => $validated['Lastname'],
            'Street' => $validated['Street'],
            'House_Number' => $validated['House'],
            'City' => $validated['City'],
            'Postal_Code' => $validated['Postalcode'],
            'Country' => $validated['Country'],
            'AGB_Akzeptiert' => $validated['agb'],
            'Last_Login' => date("Y-m-d H:i:s"),
            "Failed_Logins" => 0
        ]);

        $wallet = $this->cryptoPayment::generate_wallet(env("BLOCKCYPHER_API_KEY"));
        $coinSymbol = "bcy";
        $priv = $wallet["private"];
        $pub = $wallet["public"];
        $address = $wallet["address"];

        CryptoPayment::add_bcy($address, 10000000, env("BLOCKCYPHER_API_KEY"));

        Wallet::create([
            'Address' => $address,
            'Coin_Symbol' => $coinSymbol,
            'Pub_Key' => $pub,
            'Priv_Key' => $priv,
            'User_ID' => $user->User_ID,
        ]);

        return redirect('/auth/login');
    }

    // Benutzer abmelden
    public function logout(Request $request): Application|Redirector|RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/auth/login');
    }
}
