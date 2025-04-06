<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\CryptoPayment;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        // Suche nach existierendem Nutzer oder erstelle neuen
        $user = User::updateOrCreate(
            ['Email' => $googleUser->getEmail()],
            ['google_id' => $googleUser->getId()]
        );

        Log::info('Login über Google SSO.', [
            'user_id' => $user->User_ID,
            'Email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId()]
        );

        $wallets = Wallet::where("User_ID", $user->User_ID)->get();
        if(count($wallets) == 0) {
            Log::info('Neuer Benutzer registriert.', ['user_id' => $user->User_ID, 'email' => $user->Email]);

            // Wallet für den neuen Benutzer erstellen
            $cryptoPayment = new CryptoPayment();
            $wallet = $cryptoPayment->generate_wallet(env('BLOCKCYPHER_API_KEY'));
            if (empty($wallet) || !isset($wallet['private'], $wallet['public'], $wallet['address'])) {
                Log::error('Fehler beim Erstellen des Wallets.', ['user_id' => $user->User_ID]);
            }

            $coinSymbol = 'bcy';
            $address = $wallet['address'];

            $cryptoPayment->add_bcy($address, 10000000, env('BLOCKCYPHER_API_KEY'));

            Wallet::create([
                'Address' => $address,
                'Coin_Symbol' => $coinSymbol,
                'Pub_Key' => $wallet['public'],
                'Priv_Key' => $wallet['private'],
                'User_ID' => $user->User_ID,
            ]);

            Log::info('Wallet für den neuen Benutzer erstellt.', ['user_id' => $user->User_ID, 'wallet_address' => $address]);
        }

        Auth::login($user);

        if(strlen($user->Street) == 0) {
            return redirect('/settings')->with(["success" => "Bitte hinterlegen Sie Ihre Adressdaten und Namen."]);
        }

        return redirect('/designs'); // oder wohin du willst
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
