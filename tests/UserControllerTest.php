<?php
namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testet, ob das Aktualisieren der Benutzerdaten fehlschlägt, wenn Validierungsfehler vorliegen.
     */
    public function testSettingsUpdateValidationFails()
    {
        // Dummy-User erstellen und authentifizieren
        $user = User::create([
            'First_Name' => 'Max',
            'Last_Name'  => 'Mustermann',
            'Email'      => 'max@example.com',
            'Street'     => 'Musterstraße',
            'House_Number' => '1A',
            'City'       => 'Musterstadt',
            'Postal_Code'=> '12345',
            'Country'    => 'Deutschland',
        ]);
        $this->be($user);

        // Sende ungültige POST-Daten (z. B. fehlt "first_name")
        $postData = [
            // 'first_name' fehlt absichtlich
            'last_name'    => 'NeuerNachname',
            'street'       => 'Neue Straße',
            'house_number' => '5B',
            'city'         => 'Neue Stadt',
            'postal_code'  => '54321',
            'country'      => 'Deutschland'
        ];

        // Angenommen, die Route zum Aktualisieren der Einstellungen ist "/settings/update"
        $response = $this->post('/settings/update', $postData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['first_name']);
    }

    /**
     * Testet das erfolgreiche Aktualisieren der Benutzerdaten (Name und Adresse).
     */
    public function testSettingsUpdateSuccessful()
    {
        // Dummy-User erstellen und authentifizieren
        $user = User::create([
            'First_Name' => 'Anna',
            'Last_Name'  => 'Alt',
            'Email'      => 'anna@example.com',
            'Street'     => 'Alte Straße',
            'House_Number' => '10',
            'City'       => 'Altstadt',
            'Postal_Code'=> '11111',
            'Country'    => 'Deutschland',
        ]);
        $this->be($user);

        // Neue gültige Daten zum Aktualisieren der Einstellungen
        $postData = [
            'first_name'    => 'Anna',
            'last_name'     => 'Neu',
            'street'        => 'Neue Straße',
            'house_number'  => '20B',
            'city'          => 'Neustadt',
            'postal_code'   => '22222',
            'country'       => 'Deutschland'
        ];

        // Angenommen, die Route zum Aktualisieren der Einstellungen ist "/settings/update"
        $response = $this->post('/settings/update', $postData);
        $response->assertRedirect('/settings');
        $response->assertSessionHas('success', 'Einstellungen wurden erfolgreich gespeichert.');

        // Überprüfen, ob die Daten in der Datenbank aktualisiert wurden
        $this->assertDatabaseHas('User', [
            'User_ID'    => $user->User_ID,
            'First_Name' => 'Anna',
            'Last_Name'  => 'Neu',
            'Street'     => 'Neue Straße',
            'House_Number' => '20B',
            'City'       => 'Neustadt',
            'Postal_Code'=> '22222',
            'Country'    => 'Deutschland',
        ]);
    }

    /**
     * Testet den erfolgreichen Verifikations-Workflow für Designer.
     */
    public function testVerifyDesignerSuccessful()
    {
        // Dummy-User erstellen und authentifizieren
        $user = User::create([
            'First_Name'    => 'Dieter',
            'Last_Name'     => 'Beispiel',
            'Email'         => 'dieter@example.com',
            'Street'        => 'Beispielstraße',
            'House_Number'  => '3C',
            'City'          => 'Beispielstadt',
            'Postal_Code'   => '33333',
            'Country'       => 'Deutschland',
        ]);
        $this->be($user);

        // Angenommen, die Route für die Designer-Verifikation lautet "/settings/verify-designer"
        $response = $this->post('/settings/verify/designer');
        $response->assertRedirect('/settings');
        $response->assertSessionHas('success', 'Rolle wurde erfolgreich zugewiesen.');

        // Prüfe, ob in der Datenbank eine Rolle "Designer" für diesen User angelegt wurde
        $this->assertDatabaseHas('User_Roles', [
            'User_ID' => $user->User_ID,
            'Role'    => 'Designer',
        ]);
    }

    /**
     * Testet den erfolgreichen Verifikations-Workflow für Druckdienstleister (Provider).
     */
    public function testVerifyProviderSuccessful()
    {
        // Dummy-User erstellen und authentifizieren
        $user = User::create([
            'First_Name'    => 'Claudia',
            'Last_Name'     => 'Beispiel',
            'Email'         => 'claudia@example.com',
            'Street'        => 'Musterweg',
            'House_Number'  => '7D',
            'City'          => 'Musterstadt',
            'Postal_Code'   => '77777',
            'Country'       => 'Deutschland',
        ]);
        $this->be($user);

        // Angenommen, die Route für die Provider-Verifikation lautet "/settings/verify-provider"
        $response = $this->post('/settings/verify/provider');
        $response->assertRedirect('/settings');
        $response->assertSessionHas('success', 'Rolle wurde erfolgreich zugewiesen.');

        // Prüfe, ob in der Datenbank eine entsprechende Rolle (Provider) für diesen User angelegt wurde
        $this->assertDatabaseHas('User_Roles', [
            'User_ID' => $user->User_ID,
            'Role'    => 'Provider',
        ]);
    }


    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
