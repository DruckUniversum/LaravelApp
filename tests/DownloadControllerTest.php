<?php
namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\App;
use App\Models\Order;
use App\Models\Tender;
use App\Models\User;
use App\Models\Design;
use Exception;

class DownloadControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testet den erfolgreichen Download einer Datei über eine Bestellung.
     */
    public function testDownloadOrderSuccess()
    {
        // Erstelle einen Dummy-User und authentifiziere ihn.
        $user = User::create([
            'First_Name'    => 'Hans',
            'Last_Name'     => 'Muster',
            'Email'         => 'hans@example.com',
            // weitere Felder nach Bedarf...
        ]);
        $this->actingAs($user);

        // Erstelle ein Dummy-Design.
        $design = Design::create([
            "STL_File" => "design123",
            "Name" => "BeispielDesign",
            "Designer_ID" => $user->User_ID,
        ]);

        // Erstelle eine Dummy-Bestellung mit Bezug zum Design.
        $order = Order::create([
            'User_ID' => $user->User_ID,
            'Design_ID' => $design->Design_ID,
        ]);

        // Simuliere den Storage::download Aufruf.
        Storage::shouldReceive('download')
            ->once()
            ->with("stl/{$design->STL_File}.stl", "{$design->Name}.stl")
            ->andReturn('dummy-download-response');

        $response = $this->get("/download?order_id={$order->Order_ID}");

        $this->assertEquals('dummy-download-response', $response->getContent());
    }

    /**
     * Testet den Download via Bestellung, wenn der User nicht autorisiert ist.
     */
    public function testDownloadOrderUnauthorized()
    {
        // Erstelle einen Dummy-User (der den Download anfordert).
        $badUser = User::create([
            'First_Name'    => 'Uwe',
            'Last_Name'     => 'boese',
            'Email'         => 'uwe@example.com',
        ]);
        $this->actingAs($badUser);

        $user = User::create([
            'First_Name'    => 'Hans',
            'Last_Name'     => 'Peter',
            'Email'         => 'hans@example.com',
        ]);

        // Erstelle eine Bestellung eines anderen Users.
        $order = Order::create([
            'User_ID' => $user->User_ID
        ]);

        // Führe den Request durch und erwarte einen 403-Fehler.
        $response = $this->get("/download?order_id={$order->Order_ID}");
        $response->assertStatus(403);
    }

    /**
     * Testet den Download via Bestellung, wenn die Bestellung nicht gefunden wird.
     */
    public function testDownloadOrderNotFound()
    {
        // Erstelle einen Dummy-User und authentifiziere ihn.
        $user = User::create([
            'First_Name'    => 'Karl',
            'Last_Name'     => 'Muster',
            'Email'         => 'karl@example.com',
        ]);
        $this->actingAs($user);

        // Führe den Request mit einer nicht vorhandenen order_id durch und erwarte einen 404-Fehler.
        $response = $this->get("/download?order_id=99999");
        $response->assertStatus(404);
    }

    /**
     * Testet den erfolgreichen Download einer Datei über eine Ausschreibung (Tender).
     */
    public function testDownloadTenderSuccess()
    {
        // Erstelle einen Dummy-User (Provider) und authentifiziere ihn.
        $user = User::create([
            'First_Name'    => 'Clara',
            'Last_Name'     => 'Provider',
            'Email'         => 'clara@example.com',
        ]);
        $this->actingAs($user);

        // Erstelle ein Dummy-Design.
        $design = Design::create([
            "STL_File" => "design123",
            "Name" => "ProviderDesign",
        ]);

        // Erstelle eine Dummy-Bestellung, welche zum Tender gehört.
        $order = Order::create([
            'User_ID' => $user->User_ID, // Irrelevant; wird nur für den Bezug benötigt.
            'Design_ID' => $design->Design_ID,
        ]);

        // Erstelle eine Dummy-Ausschreibung (Tender) mit dem richtigen Provider_ID.
        $tender = Tender::create([
            'Provider_ID' => $user->User_ID,
            'Order_ID'    => $order->Order_ID,
        ]);

        // Erwarte, dass Storage::download aufgerufen wird.
        Storage::shouldReceive('download')
            ->once()
            ->with("stl/{$design->STL_File}.stl", "{$design->Name}.stl")
            ->andReturn('dummy-tender-download');

        // Führe den Request mit dem tender_id Parameter durch.
        $response = $this->get("/download?tender_id={$tender->Tender_ID}");

        // Erstelle eine neue Tender Instanz mit den neuen Werten aus der Datenbank
        $tender = Tender::find($tender->Tender_ID);

        // Der Tender-Status sollte nun auf "PROCESSING" gesetzt sein.
        $this->assertEquals('PROCESSING', $tender->Status);
        $this->assertEquals('dummy-tender-download', $response->getContent());
    }

    /**
     * Testet den Download via Ausschreibung, wenn der User nicht autorisiert ist.
     */
    public function testDownloadTenderUnauthorized()
    {
        // Erstelle einen Dummy-User (Provider) und authentifiziere ihn.
        $badUser = User::create([
            'First_Name'    => 'Uwe',
            'Last_Name'     => 'Boese',
            'Email'         => 'uwe@example.com',
        ]);
        $this->actingAs($badUser);

        $user = User::create([
            'First_Name'    => 'Hans',
            'Last_Name'     => 'Peter',
            'Email'         => 'hans@example.com',
        ]);

        // Erstelle eine Dummy-Ausschreibung (Tender) mit einer anderen Provider_ID.
        $tender = Tender::create([
            'Provider_ID' => $user->User_ID, // Anderer Provider
        ]);

        // Führe den Request durch und erwarte einen 403-Fehler.
        $response = $this->get("/download?tender_id={$tender->Tender_ID}");
        $response->assertStatus(403);
    }

    /**
     * Testet den Downloadaufruf ohne gültige Parameter.
     */
    public function testDownloadInvalidParameters()
    {
        // Erstelle einen Dummy-User und authentifiziere ihn.
        $user = User::create([
            'First_Name'    => 'Lukas',
            'Last_Name'     => 'Fehler',
            'Email'         => 'lukas@example.com',
        ]);
        $this->actingAs($user);

        // Führe den Request ohne order_id oder tender_id durch und erwarte einen 404-Fehler.
        $response = $this->get("/download");
        $response->assertStatus(404);
    }
}
