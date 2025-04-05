<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\CryptoPayment;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
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

    /**
     * Zeigt das Login-Formular an.
     */
    public function showLoginForm(): View|Application|Factory
    {
        return view('login');
    }

    /**
     * Zeigt das Registrierungsformular an.
     */
    public function showRegisterForm(): View|Application|Factory
    {
        return view('registration_form');
    }

    /**
     * Verarbeitet die Login-Anfrage des Benutzers.
     */
    public function login(Request $request): RedirectResponse
    {
        // Validierung der Eingabe
        $credentials = $request->validate([
            'E-Mail' => 'required|email',
            'password_usr' => 'required',
        ]);

        // Versuche, den Benutzer zu authentifizieren
        if (Auth::attempt([
            'email' => $credentials['E-Mail'],
            'password' => $credentials['password_usr'],
        ])) {
            $request->session()->regenerate();
            Log::info('Benutzer erfolgreich eingeloggt.', ['email' => $credentials['E-Mail']]);
            return redirect()->intended('designs');
        }

        Log::warning('Fehlgeschlagener Login-Versuch.', ['email' => $credentials['E-Mail']]);
        return back()->withErrors([
            'email' => 'Die bereitgestellten Anmeldedaten stimmen nicht überein.',
        ]);
    }

    /**
     * Verarbeitet die Registrierung eines neuen Benutzers und erstellt ein zugehöriges Wallet.
     */
    public function register(Request $request): Application|Redirector|RedirectResponse
    {
        // Validierung der Eingaben für die Registrierung
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

        // Neuen Benutzer erstellen
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
            'Failed_Logins' => 0,
        ]);

        Log::info('Neuer Benutzer registriert.', ['user_id' => $user->id, 'email' => $user->Email]);

        // Wallet für den neuen Benutzer erstellen
        $wallet = $this->cryptoPayment::generate_wallet(env('BLOCKCYPHER_API_KEY'));
        $coinSymbol = 'bcy';
        $address = $wallet['address'];

        CryptoPayment::add_bcy($address, 10000000, env('BLOCKCYPHER_API_KEY'));

        Wallet::create([
            'Address' => $address,
            'Coin_Symbol' => $coinSymbol,
            'Pub_Key' => $wallet['public'],
            'Priv_Key' => $wallet['private'],
            'User_ID' => $user->User_ID,
        ]);

        Log::info('Wallet für den neuen Benutzer erstellt.', ['user_id' => $user->id, 'wallet_address' => $address]);

        return redirect('/auth/login');
    }

    /**
     * Meldet den Benutzer ab und zerstört die Sitzung.
     */
    public function logout(Request $request): Application|Redirector|RedirectResponse
    {
        Log::info('Benutzer ausgeloggt.', ['user_id' => Auth::id()]);
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/auth/login');
    }
}
