<?php
namespace Tests;

use App\Models\Category;
use App\Models\Design;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;

class DesignControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testet, ob die Design‑Übersicht korrekt angezeigt wird.
     */
    public function testIndexDisplaysDesigns()
    {
        // Dummy-Designer erstellen
        $designer = User::create([
            'First_Name' => 'Anna',
            'Last_Name'  => 'Designer',
            'Email'      => 'anna@example.com',
        ]);

        // Dummy-Kategorie anlegen
        $category = Category::create([
            'Name' => 'Beispielkategorie',
        ]);

        // Zwei Dummy-Designs erstellen
        Design::create([
            'Name'                => 'Design Eins',
            'STL_File'            => 'design1.stl',
            'Price'               => 45.0,
            'Description'         => 'Erstes Beispiel-Design',
            'Cover_Picture_File'  => 'cover1.jpg',
            'Category_ID'         => $category->Category_ID,
            'Designer_ID'         => $designer->User_ID,
        ]);

        Design::create([
            'Name'                => 'Design Zwei',
            'STL_File'            => 'design2.stl',
            'Price'               => 60.0,
            'Description'         => 'Zweites Beispiel-Design',
            'Cover_Picture_File'  => 'cover2.jpg',
            'Category_ID'         => $category->Category_ID,
            'Designer_ID'         => $designer->User_ID,
        ]);

        // Authentifizieren des Designers
        $this->be($designer);

        // Angenommen, die Route "/designs" zeigt die Liste der Designs an
        $response = $this->get('/designs');

        $response->assertStatus(200);
        $response->assertViewHas('designs');
    }

    /**
     * Testet, ob die Erstellung eines Designs fehlschlägt, wenn Validierungsfehler auftreten.
     */
    public function testCreateDesignValidationFails()
    {
        // Dummy-Designer erstellen
        $designer = User::create([
            'First_Name' => 'Max',
            'Last_Name'  => 'Mustermann',
            'Email'      => 'max@example.com',
        ]);
        $this->be($designer);

        // POST-Daten mit fehlenden Pflichtfeldern (z. B. fehlender Price oder STL_FIle)
        $postData = [
            'Name'               => 'Ungültiges Design',
            // 'STL_FIle' fehlt absichtlich
            'Price'              => null, // fehlender Preis
            'Description'        => 'Design ohne notwendige Felder',
            'Cover_Picture_File' => 'cover_invalid.jpg',
            'Category_ID'        => 1,  // Angenommen, diese Kategorie existiert nicht
        ];

        // Angenommen, die Route "/designs/create" verarbeitet die Design-Erstellung
        $response = $this->post('/designs/manage/create', $postData);

        // Es sollte ein Redirect erfolgen und Fehler in der Session abgelegt sein
        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    /**
     * Testet das erfolgreiche Erstellen eines Designs.
     */
    public function testCreateDesignSuccessful()
    {
        // Dummy-Designer erstellen
        $designer = User::create([
            'First_Name' => 'Eva',
            'Last_Name'  => 'Erfolgreich',
            'Email'      => 'eva@example.com',
        ]);
        $this->be($designer);

        // Dummy-Kategorie anlegen
        $category = Category::create([
            'Name' => 'Erfolgs-Kategorie',
        ]);

        $stlFile = UploadedFile::fake()->create('erfolgsdesign.stl', 100, "model/stl");
        $coverFile = UploadedFile::fake()->image('erfolg_cover.png');

        // POST-Daten für die erfolgreiche Design-Erstellung
        $postData = [
            'name'                => 'Erfolgsdesign',
            'stl_file'            => $stlFile,
            'price'               => 75.0,
            'description'         => 'Ein erfolgreich erstelltes Design',
            'cover_picture'  => $coverFile,
            'license'  => "dummy",
            'category'         => "" . $category->Category_ID,
            // Der Designer wird typischerweise aus der angemeldeten Session entnommen
        ];

        // Angenommen, die Route "/designs/create" verarbeitet die Design-Erstellung
        $response = $this->post('/designs/manage/create', $postData);
        $response->assertRedirect();

        // Prüfen, ob in der Datenbank ein Design mit den erwarteten Werten angelegt wurde
        $this->assertDatabaseHas('Design', [
            'Name'        => 'Erfolgsdesign',
            'Price'       => 75.0,
            'Designer_ID' => $designer->User_ID,
        ]);

        $design = Design::where('Name', 'Erfolgsdesign')->first();

        // Zusätzlich sicherstellen, dass die Dateien im Fake Storage vorhanden sind
        Storage::disk('private')->assertExists('stl/' . $design->STL_File . '.stl');
        Storage::disk('public')->assertExists('cover_picture/' . $design->Cover_Picture_File . '.png');

    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
