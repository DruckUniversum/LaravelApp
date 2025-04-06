<?php
namespace Tests\Feature;

use App\Services\CryptoPayment;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Wallet;
use Mockery;

class WalletControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testet, ob die Wallet-Übersicht korrekt angezeigt wird und nutzt ein Mock
     * von CryptoPayment für den Balance-Check.
     */
    public function testWalletIndexDisplaysCorrectly()
    {
        // Erstelle einen Dummy-User mit einem bestimmten Wallet-Guthaben und Wallet-Adresse.
        $user = User::create([
            'First_Name'     => 'Marco',
            'Last_Name'      => 'Beispiel',
            'Email'          => 'marco@example.com'
        ]);
        $this->actingAs($user);

        // Erstelle inline die zugehörige Dummy-Wallet.
        Wallet::create([
            'Priv_Key' => 'dummy_private_key',
            'Pub_Key'  => 'dummy_public_key',
            'Address'     => "dummy_address",
            'Coin_Symbol' => 'BCY',
            'User_ID'     => $user->User_ID,
        ]);

        // Setze ein Mock für CryptoPayment, das den Balance-Check simuliert.
        $cryptoPaymentMock = Mockery::mock(CryptoPayment::class);
        $cryptoPaymentMock->shouldReceive('get_balance')
            ->once()
            ->with("dummy_address", Mockery::any())
            ->andReturn(100.0);

        $this->app->instance(CryptoPayment::class, $cryptoPaymentMock);

        // Angenommen, dass die POST-Route "/orders/create" die Bestellung verarbeitet
        // Rufe die Wallet-Übersicht auf.
        $response = $this->get('/wallet');
        $response->assertStatus(200);
        $response->assertSee(100.0 . " BCY");
    }

    /**
     * Testet eine erfolgreiche Auszahlung aus dem Wallet.
     */
    public function testWalletWithdrawalSuccessful()
    {
        // Erstelle einen Dummy-User.
        $user = User::create([
            'First_Name'     => 'Thomas',
            'Last_Name'      => 'Beispiel',
            'Email'          => 'thomas@example.com',
        ]);
        $this->actingAs($user);

        // Erstelle inline die zugehörige Dummy-Wallet.
        Wallet::create([
            'Priv_Key' => 'dummy_private_key',
            'Pub_Key'  => 'dummy_public_key',
            'Address'     => "dummy_address",
            'Coin_Symbol' => 'BCY',
            'User_ID'     => $user->User_ID,
        ]);

        $withdrawAmount = 75.00;

        // Simulation einer korrekten Balance und einer korrekten Transaktion
        $cryptoPaymentMock = Mockery::mock(CryptoPayment::class);
        $cryptoPaymentMock->shouldReceive('get_balance')
            ->once()
            ->with("dummy_address", Mockery::any())
            ->andReturn(100.0);

        $cryptoPaymentMock->shouldReceive('make_transaction')
            ->once()
            ->with("dummy_address", "dummy_destination_address", "dummy_private_key", "dummy_public_key", Mockery::any(), $withdrawAmount)
            ->andReturn(['tx_hash' => 'dummy_tx_hash']);

        $this->app->instance(CryptoPayment::class, $cryptoPaymentMock);

        // Angenommen, dass die POST-Route "/orders/create" die Bestellung verarbeitet
        $response = $this->post('/wallet/send', ['amount' => $withdrawAmount, 'address' => 'dummy_destination_address']);

        $response->assertRedirect('/wallet');
        $response->assertSessionHas('success', 'Transaktion erfolgreich.');
    }

    /**
     * Testet, ob eine Auszahlung fehlschlägt, wenn nicht genügend Guthaben vorhanden ist.
     */
    public function testWalletWithdrawalFailsDueToInsufficientFunds()
    {
        // Erstelle einen Dummy-User.
        $user = User::create([
            'First_Name'     => 'Markus',
            'Last_Name'      => 'Beispiel',
            'Email'          => 'markus@example.com',
            'wallet_balance' => 50.00,
            'wallet_address' => 'dummy-address-000',
        ]);
        $this->actingAs($user);

        // Erstelle inline die zugehörige Dummy-Wallet.
        Wallet::create([
            'Priv_Key' => 'dummy_private_key',
            'Pub_Key'  => 'dummy_public_key',
            'Address'     => "dummy_address",
            'Coin_Symbol' => 'BCY',
            'User_ID'     => $user->User_ID,
        ]);

        // Versuche, einen Betrag abzuheben, der das vorhandene Guthaben übersteigt.
        $withdrawAmount = 100.00;

        $cryptoPaymentMock = Mockery::mock(CryptoPayment::class);
        $cryptoPaymentMock->shouldReceive('get_balance')
            ->once()
            ->with("dummy_address", Mockery::any())
            ->andReturn(50.0);

        $this->app->instance(CryptoPayment::class, $cryptoPaymentMock);

        // Angenommen, dass die POST-Route "/orders/create" die Bestellung verarbeitet
        $response = $this->post('/wallet/send', ['amount' => $withdrawAmount, "address" => "dummy_destination_address"]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'Nicht genügend Guthaben.');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
