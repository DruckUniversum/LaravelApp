<?php
namespace Tests;

use App\Models\Category;
use App\Models\Design;
use App\Models\Order;
use App\Models\Tender;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CryptoPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class TenderControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testet, ob die Tender‑Übersicht korrekt angezeigt wird.
     */
    public function testIndexDisplaysTenders()
    {
        // Dummy-Tenderer und Provider anlegen
        $tenderer = User::create([
            'First_Name' => 'Tina',
            'Last_Name'  => 'Tenderer',
            'Email'      => 'tina@example.com',
        ]);

        $provider = User::create([
            'First_Name' => 'Paul',
            'Last_Name'  => 'Provider',
            'Email'      => 'paul@example.com',
        ]);

        // Dummy-Order erstellen, an die die Tender gebunden werden
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
            'Designer_ID'          => $provider->User_ID,
        ]);
        $order = Order::create([
            'User_ID'          => $tenderer->User_ID,
            'Design_ID'        => $design->Design_ID, // Dummy-Wert
            'Paid_Price'       => 80.0,
            'Payment_Status'   => 'OPEN',
            'Order_Date'       => now(),
            'Transaction_Hash' => 'order_tx_dummy',
        ]);

        // Zwei Dummy-Tender anlegen
        Tender::create([
            'Status'            => 'OPEN',
            'Bid'               => 60.0,
            'Infill'            => 20,
            'Filament'          => 'PLA',
            'Description'       => 'Erster Tender',
            'Tenderer_ID'       => $tenderer->User_ID,
            'Provider_ID'       => $provider->User_ID,
            'Order_ID'          => $order->Order_ID,
            'Tender_Date'       => now(),
            'Shipping_Provider' => 'DHL',
            'Shipping_Number'   => 'SH12345',
            'Transaction_Hash'  => 'tender_tx_1'
        ]);

        Tender::create([
            'Status'            => 'OPEN',
            'Bid'               => 70.0,
            'Infill'            => 25,
            'Filament'          => 'ABS',
            'Description'       => 'Zweiter Tender',
            'Tenderer_ID'       => $tenderer->User_ID,
            'Provider_ID'       => $provider->User_ID,
            'Order_ID'          => $order->Order_ID,
            'Tender_Date'       => now(),
            'Shipping_Provider' => 'Hermes',
            'Shipping_Number'   => 'SH67890',
            'Transaction_Hash'  => 'tender_tx_2'
        ]);

        // Angenommen, die Route "/tenders" zeigt eine Übersicht der Tender an
        // Authentifizieren des Tenderers (oder eines beliebigen Nutzers)
        $this->be($tenderer);

        $response = $this->get('/tenders');

        $response->assertStatus(200);
        $response->assertViewHas('publicTenders');
    }

    /**
     * Testet das Erstellen eines Tenders, wenn das Wallet-Guthaben nicht ausreicht.
     */
    public function testCreateTenderInsufficientBalance()
    {
        // Dummy-Tenderer erstellen
        $tenderer = User::create([
            'First_Name' => 'Clara',
            'Last_Name'  => 'Klein',
            'Email'      => 'clara@example.com',
            'Street'      => 'Test',
            'House_Number'      => 'Test',
            'City'      => 'Test',
            'Postal_Code'      => 'Test',
            'Country'      => 'Test',
        ]);
        $this->be($tenderer);

        // Dummy-Order erstellen, an die der Tender gebunden wird
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
            'Designer_ID'          => $tenderer->User_ID,
        ]);
        $order = Order::create([
            'User_ID'          => $tenderer->User_ID,
            'Design_ID'        => $design->Design_ID, // Dummy-Wert
            'Paid_Price'       => 90.0,
            'Payment_Status'   => 'OPEN',
            'Order_Date'       => now(),
            'Transaction_Hash' => 'order_tx_dummy_2',
        ]);

        // Dummy-Wallet für den Tenderer mit zu niedrigem Guthaben anlegen
        Wallet::create([
            'Address'     => 'dummy_address_clara',
            'Coin_Symbol' => 'BTC',
            'Pub_Key'     => 'dummy_pub',
            'Priv_Key'    => 'dummy_priv',
            'User_ID'     => $tenderer->User_ID,
        ]);

        // CryptoPayment::get_balance wird so gemockt, dass ein zu niedriges Guthaben zurückgegeben wird.
        $cryptoPaymentMock = Mockery::mock('alias:' . CryptoPayment::class);
        $cryptoPaymentMock->shouldReceive('get_balance')->andReturn(30.0);

        // POST-Daten für die Tender-Erstellung, analog zu den notwendigen Feldern
        $postData = [
            'order_id'          => $order->Order_ID,
            'bid'               => 50.0,
            'infill'            => 15,
            'filament'          => 'PLA',
            'description'       => 'Test Tender – unzureichendes Guthaben',
            'shipping_provider' => 'DPD',
            'shipping_number'   => 'SHIP123',
            // Weitere Felder können hier ergänzt werden
        ];

        // Angenommen, die Route "/tenders/create" verarbeitet die Tender-Erstellung
        $response = $this->post('/tenders/create', $postData);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Nicht genügend Guthaben im Wallet.');
    }

    /**
     * Testet das erfolgreiche Erstellen eines Tenders.
     */
    public function testCreateTenderSuccessful()
    {
        // Dummy-Tenderer erstellen
        $tenderer = User::create([
            'First_Name' => 'Clara',
            'Last_Name'  => 'Klein',
            'Email'      => 'clara@example.com',
            'Street'      => 'Test',
            'House_Number'      => 'Test',
            'City'      => 'Test',
            'Postal_Code'      => 'Test',
            'Country'      => 'Test',
        ]);
        $this->be($tenderer);

        // Dummy-Order erstellen
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
            'Designer_ID'          => $tenderer->User_ID,
        ]);
        $order = Order::create([
            'User_ID'          => $tenderer->User_ID,
            'Design_ID'        => $design->Design_ID,
            'Paid_Price'       => 120.0,
            'Payment_Status'   => 'OPEN',
            'Order_Date'       => now(),
            'Transaction_Hash' => 'order_tx_success',
        ]);

        // Dummy-Wallet für den Tenderer anlegen
        Wallet::create([
            'Address'     => 'dummy_address_markus',
            'Coin_Symbol' => 'BTC',
            'Pub_Key'     => 'dummy_pub',
            'Priv_Key'    => 'dummy_priv',
            'User_ID'     => $tenderer->User_ID,
        ]);

        // Dummy-Provider anlegen, der später dem Tender zugeordnet wird
        $provider = User::create([
            'First_Name' => 'Stefan',
            'Last_Name'  => 'Provider',
            'Email'      => 'stefan@example.com',
        ]);

        // Dummy-Wallet für den Provider anlegen
        Wallet::create([
            'Address'     => 'dummy_address_stefan',
            'Coin_Symbol' => 'BTC',
            'Pub_Key'     => 'designer_pub',
            'Priv_Key'    => 'designer_priv',
            'User_ID'     => $provider->User_ID,
        ]);

        // CryptoPayment-Service so mocken, dass ausreichend Guthaben vorhanden ist und die Transaktion gelingt
        $cryptoPaymentMock = Mockery::mock('alias:' . CryptoPayment::class);
        $cryptoPaymentMock->shouldReceive('get_balance')->andReturn(200.0);

        // POST-Daten für die erfolgreiche Tender-Erstellung
        $postData = [
            'order_id'          => $order->Order_ID,
            'bid'               => 80.0,
            'infill'            => 20,
            'filament'          => 'ABS',
            'description'       => 'Erfolgreiche Ausschreibung'
            // Weitere Felder können hier ergänzt werden
        ];

        // Angenommen, die Route "/tenders/create" verarbeitet die Tender-Erstellung
        $response = $this->post('/tenders/create', $postData);

        $response->assertRedirect();

        // Prüfen, ob in der Datenbank eine Ausschreibung mit den erwarteten Werten angelegt wurde
        $this->assertDatabaseHas('Tender', [
            'Order_ID'    => $order->Order_ID,
            'Tenderer_ID' => $tenderer->User_ID,
            'Bid'         => 80.0,
            'Status'      => 'OPEN'
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
