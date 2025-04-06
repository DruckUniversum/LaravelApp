<?php
namespace Tests;

use App\Models\Category;
use App\Models\Design;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CryptoPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testet, ob die Bestellübersicht korrekt angezeigt wird.
     */
    public function testIndexDisplaysOrders()
    {
        // Dummy-Daten direkt anlegen
        $user = User::create([
            'First_Name' => 'Max',
            'Last_Name'  => 'Mustermann',
            'Email'      => 'max@example.com',
            // Füge hier weitere notwendige Felder hinzu, falls erforderlich
        ]);

        // Nutzer authentifizieren
        $this->be($user);

        // Zwei Dummy-Bestellungen anlegen
        $category = Category::create([
            'Name'        => "Dummy",
        ]);
        $design = Design::create([
            'Name'        => "Dummy",
            'STL_FIle'        => "Dummy",
            'Price'       => 50.0,
            'Description'        => "Dummy",
            'Cover_Picture_File'        => "Dummy",
            'Category_ID'          => $category->Category_ID,
            'Designer_ID'          => $user->User_ID,
        ]);

        Order::create([
            'User_ID'          => $user->User_ID,
            'Design_ID'        => $design->Design_ID,
            'Paid_Price'       => 75.0,
            'Payment_Status'   => 'OPEN',
            'Order_Date'       => now(),
            'Transaction_Hash' => 'dummy_tx',
        ]);

        Order::create([
            'User_ID'          => $user->User_ID,
            'Design_ID'        => $design->Design_ID,
            'Paid_Price'       => 75.0,
            'Payment_Status'   => 'OPEN',
            'Order_Date'       => now(),
            'Transaction_Hash' => 'dummy_tx_2',
        ]);

        // Rufe die Order‑Übersichtsroute auf (angepasst an Deine Routen)
        $response = $this->get('/orders');

        $response->assertStatus(200);
        $response->assertViewHas('orders');
    }

    /**
     * Testet das Erstellen einer Bestellung, wenn das Wallet-Guthaben nicht ausreicht.
     */
    public function testCreateOrderInsufficientBalance()
    {
        // Dummy-Nutzer erstellen
        $user = User::create([
            'First_Name' => 'Anna',
            'Last_Name'  => 'Beispiel',
            'Email'      => 'anna@example.com',
        ]);
        $this->be($user);

        // Dummy-Design mit festgelegtem Preis erstellen
        $category = Category::create([
            'Name'        => "Dummy",
        ]);
        $design = Design::create([
            'Name'         => 'Beispiel Design',
            'File_Format'  => 'STL',
            'Price'        => 100.0,
            'Description'  => 'Ein Beispiel-Design',
            'Category_ID'  => $category->Category_ID,          // Dummy-Wert
            'Designer_ID'  => $user->User_ID,        // Dummy Designer-ID
        ]);

        // Erstelle ein Dummy-Wallet für den Nutzer mit zu geringem Guthaben
        Wallet::create([
            'Address'     => 'dummy_address_user',
            'Coin_Symbol' => 'BTC',
            'Pub_Key'     => 'dummy_pub',
            'Priv_Key'    => 'dummy_priv',
            'User_ID'     => $user->User_ID,
        ]);

        // CryptoPayment::get_balance wird so gemockt, dass ein zu niedriges Guthaben zurückgegeben wird.
        $cryptoPaymentMock = Mockery::mock(CryptoPayment::class);
        $cryptoPaymentMock->shouldReceive('get_balance')->andReturn(50.0);

        $this->app->instance(CryptoPayment::class, $cryptoPaymentMock);

        // Angenommen, dass die POST-Route "/orders/create" die Bestellung verarbeitet
        $response = $this->post('/orders/create', [
            'design_id' => $design->Design_ID,
        ]);

        // Prüfung: Weiterleitung und Fehlermeldung in der Session
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Nicht genügend Guthaben im Wallet.');
    }

    /**
     * Testet das erfolgreiche Erstellen einer Bestellung.
     */
    public function testCreateOrderSuccessful()
    {
        // Dummy-Nutzer erstellen
        $user = User::create([
            'First_Name' => 'Laura',
            'Last_Name'  => 'Beispiel',
            'Email'      => 'laura@example.com',
        ]);
        $this->be($user);

        // Dummy-Design erstellen
        $category = Category::create([
            'Name'        => "Dummy",
        ]);
        $design = Design::create([
            'Name'         => 'Erfolgs Design',
            'File_Format'  => 'STL',
            'Price'        => 100.0,
            'Description'  => 'Erfolgreiches Design',
            'Category_ID'  => $category->Category_ID,          // Dummy-Wert
            'Designer_ID'  => $user->User_ID,          // Hier wird ein anderer Nutzer angenommen
        ]);

        // Erstelle ein Dummy-Wallet für den Nutzer
        Wallet::create([
            'Address'     => 'dummy_address_user',
            'Coin_Symbol' => 'BTC',
            'Pub_Key'     => 'dummy_pub',
            'Priv_Key'    => 'dummy_priv',
            'User_ID'     => $user->User_ID,
        ]);

        // Erstelle ein Dummy-Wallet für den Designer
        Wallet::create([
            'Address'     => 'dummy_address_designer',
            'Coin_Symbol' => 'BTC',
            'Pub_Key'     => 'designer_pub',
            'Priv_Key'    => 'designer_priv',
            'User_ID'     => $design->Designer_ID,
        ]);

        // CryptoPayment-Service so mocken, dass ausreichend Guthaben vorhanden ist und die Transaktion gelingt
        $cryptoPaymentMock = Mockery::mock(CryptoPayment::class);
        $cryptoPaymentMock->shouldReceive('get_balance')->andReturn(150.0);
        $cryptoPaymentMock->shouldReceive('make_transaction')
            ->andReturn(['tx_hash' => 'dummy_tx_hash']);

        $this->app->instance(CryptoPayment::class, $cryptoPaymentMock);

        // Angenommen, dass die POST-Route "/orders/create" die Bestellung verarbeitet
        // Bestellung erstellen
        $response = $this->post('/orders/create', [
            'design_id' => $design->Design_ID,
        ]);

        $response->assertRedirect();

        // Prüfen, ob in der Datenbank eine Bestellung mit den erwarteten Werten angelegt wurde
        $this->assertDatabaseHas('Order', [
            'Design_ID'      => $design->Design_ID,
            'User_ID'        => $user->User_ID,
            'Payment_Status' => 'OPEN'
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
