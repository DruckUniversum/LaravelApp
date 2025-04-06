<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Services\CryptoPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CryptoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testet das Erstellen eines Wallets inklusive Guthaben-Abfrage.
     */
    public function testWalletCreationAndBalanceCheckAndTransaction()
    {
        // 1. Nutzer erstellen (Feldnamen exakt wie in den Models)
        $user = User::create([
            'First_Name'      => 'John',
            'Last_Name'       => 'Doe',
            'Street'          => 'Street',
            'Postal_Code'     => '12345',
            'City'            => 'Berlin',
            'Country'         => 'DE',
            'Email'           => 'johndoe@example.com'
        ]);
        $this->assertNotNull($user, "Der Nutzer konnte nicht erstellt werden!");
        $this->assertEquals('John', $user->First_Name, "Der Vorname stimmt nicht überein!");
        $this->assertEquals('Doe', $user->Last_Name, "Der Nachname stimmt nicht überein!");
        $this->assertEquals('johndoe@example.com', $user->Email, "Die E-Mail-Adresse stimmt nicht überein!");

        // 2. Wallet für den Nutzer erstellen
        // Erzeuge Wallet-Daten mittels des CryptoPayment-Services
        $walletData = CryptoPayment::generate_wallet(env('BLOCKCYPHER_API_KEY'));
        $this->assertNotNull($walletData, "Das Wallet wurde nicht korrekt erstellt!");

        // Wallet in der Datenbank anlegen (Achtung: Felder exakt beachten)
        $wallet = Wallet::create([
            'Address'  => $walletData['address'],
            'Pub_Key'  => $walletData['public'],
            'Priv_Key' => $walletData['private'],
            'Coin_Symbol' => 'bcy',
            // Hier muss ggf. der zugehörige User-ID-Wert angegeben werden; als Platzhalter '1'
            'User_ID'  => $user->User_ID,
        ]);
        $this->assertNotNull($wallet, "Das Wallet konnte nicht erstellt werden!");

        // Überprüfen, ob das Wallet in der Datenbank vorhanden ist
        $fetchedWallet = Wallet::where('Address', $wallet->Address)->first();
        $this->assertNotNull($fetchedWallet, "Das Wallet wurde nicht korrekt gefunden!");

        // Guthaben hinzufügen und abfragen mit Wiederholungsversuchen (Polling)
        CryptoPayment::add_bcy($wallet->Address, 10000000, env('BLOCKCYPHER_API_KEY'));
        $balance = 0;
        $maxWaitTime = 60; // Sekunden
        $waitedTime = 0;
        $interval = 5; // Sekunden

        while ($balance <= 0 && $waitedTime < $maxWaitTime) {
            sleep($interval);
            $balance = CryptoPayment::get_balance($wallet->Address, env('BLOCKCYPHER_API_KEY'));
            $waitedTime += $interval;
            echo "Warte, bis Guthaben im Wallet eingegangen ist...\n";
        }

        $this->assertGreaterThan(0, $balance, "Nach {$maxWaitTime} Sekunden wurde kein Guthaben dem Wallet gutgeschrieben!");

        // 4. Transaktion vom Wallet zu sich selbst durchführen
        $txHash = CryptoPayment::make_transaction(
            $wallet->Address,
            $wallet->Address,
            $wallet->Priv_Key,
            $wallet->Pub_Key,
            env('BLOCKCYPHER_API_KEY'),
            500
        );
        $this->assertNotEmpty($txHash, "Die Transaktion konnte nicht durchgeführt werden!");
    }
}
