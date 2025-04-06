<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    /**
     * Zeigt die Benutzereinstellungen an.
     */
    public function settings()
    {
        $user = Auth::user(); // Eingeloggter Benutzer
        Log::info('Benutzereinstellungen aufgerufen.', [
            'user_id' => $user->User_ID,
            'email'   => $user->{"E-Mail"}
        ]);
        if(Session::get('api_key')) {
            return view('settings', [
                "user" => compact('user'),
                "api_key" => Session::get('api_key')
            ]);
        }
        return view('settings', compact('user'));
    }

    /**
     * Aktualisiert die Benutzerdaten.
     *
     * Es werden schreibende Aktionen, systemische Fehler und sicherheitsrelevante Auffälligkeiten geloggt.
     *
     * @param Request $request
     */
    public function settingsUpdate(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'street'          => 'required|string|max:255',
            'house_number'    => 'required|string|max:255',
            'city'            => 'required|string|max:255',
            'postal_code'     => 'required|string|max:255',
            'country'         => 'required|string|max:255',
        ]);

        // Aktualisiere die Standard-Benutzerdaten
        $res = $user->update([
            'First_Name'  => $validated["first_name"],
            'Last_Name'   => $validated["last_name"],
            'Street'      => $validated["street"],
            'House_Number'=> $validated["house_number"],
            'City'        => $validated["city"],
            'Postal_Code' => $validated["postal_code"],
            'Country'     => $validated["country"],
        ]);
        if (!$res) {
            Log::error('Fehler beim Aktualisieren der Benutzerdaten.', [
                'user_id' => $user->User_ID,
                'input'   => $validated
            ]);
            return back()->with('error', 'Einstellungen wurden nicht gespeichert.');
        }
        Log::info('Benutzerdaten erfolgreich aktualisiert.', [
            'user_id' => $user->User_ID,
            'email'   => $user->Email
        ]);

        Log::info('Benutzereinstellungen wurden erfolgreich gespeichert.', [
            'user_id' => $user->User_ID
        ]);
        return redirect("/settings")->with('success', 'Einstellungen wurden erfolgreich gespeichert.');
    }

    /**
     * Initiiert Verifikations-Workflow für Designer.
     *
     * Es werden schreibende Aktionen, systemische Fehler und sicherheitsrelevante Auffälligkeiten geloggt.
     *
     * @param Request $request
     */
    public function verifyDesigner(Request $request)
    {
        $user = Auth::user();

        // TODO: Designer-Workflow implementieren

        // Rollenzuweisung
        $role = UserRole::create([
            "User_ID" => $user->User_ID,
            "Role"    => "Designer"
        ]);
        if (!$role) {
            Log::error('Fehler bei Rollenzuweisung.', [
                'user_id' => $user->User_ID,
                'role'   => "Designer"
            ]);
            return back()->with('error', 'Rolle wurden nicht zugewiesen.');
        }
        Log::info('Rolle erfolgreich zugewiesen.', [
            'user_id' => $user->User_ID,
            'role'   => "Designer"
        ]);

        return redirect("/settings")->with('success', 'Rolle wurde erfolgreich zugewiesen.');
    }

    /**
     * Initiiert Verifikations-Workflow für Druckdienstleister.
     *
     * Es werden schreibende Aktionen, systemische Fehler und sicherheitsrelevante Auffälligkeiten geloggt.
     *
     * @param Request $request
     */
    public function verifyProvider(Request $request)
    {
        $user = Auth::user();

        // TODO: Provider-Workflow implementieren

        // Rollenzuweisung
        $role = UserRole::create([
            "User_ID" => $user->User_ID,
            "Role"    => "Provider",
        ]);
        if (!$role) {
            Log::error('Fehler bei Rollenzuweisung.', [
                'user_id' => $user->User_ID,
                'role'   => "Designer"
            ]);
            return back()->with('error', 'Rolle wurden nicht zugewiesen.');
        }
        Log::info('Rolle erfolgreich zugewiesen.', [
            'user_id' => $user->User_ID,
            'role'   => "Designer"
        ]);

        return redirect("/settings")->with('success', 'Rolle wurde erfolgreich zugewiesen.');
    }

    /**
     * Erstellt Drucker-Instanz
     *
     * @param Request $request
     */
    public function createPrinter(Request $request)
    {
        $user = Auth::user();

        $hasRole = false;
        foreach ($user->roles as $role) {
            if ($role->Role === 'Provider') {
                $hasRole = true;
                break;
            }
        }
        if (!$hasRole) {
            Log::warning('Unberechtigter Versuch eine Drucker-Instanz hinzuzufügen/zu bearbeiten.', [
                'user_id'   => auth()->id(),
                'tender_id' => $request->tender_id
            ]);
            return redirect()->back()->with('error', 'Nicht berechtigt.');
        }

        $apiKey = bin2hex(openssl_random_pseudo_bytes(16));

        $printer = Printer::create([
            'API_Key' => Hash::make($apiKey),
            'User_ID' => $user->User_ID
        ]);
        if (!$printer) {
            Log::error('Fehler bei Druckererzeugung.', [
                'user_id' => $user->User_ID
            ]);
            return back()->with('error', 'Drucker wurde nicht erzeugt.');
        }

        Log::info('Drucker erfolgreich erstellt.', [
            'user_id' => $user->User_ID,
            'printer_id'   => $printer->Printer_ID
        ]);

        return redirect("/settings")->with([
            'success' => 'Drucker wurde erfolgreich erstellt.',
            'api_key' => $apiKey
        ]);
    }

    /**
     * Entfernt Drucker-Instanz
     *
     * @param Request $request
     */
    public function removePrinter(Request $request)
    {
        $validated = $request->validate([
            'printer_id' => 'required|exists:App\Models\Printer,Printer_ID',
        ]);

        $user = Auth::user();

        $hasRole = false;
        foreach ($user->roles as $role) {
            if ($role->Role === 'Provider') {
                $hasRole = true;
                break;
            }
        }
        if (!$hasRole) {
            Log::warning('Unberechtigter Versuch eine Drucker-Instanz hinzuzufügen/zu bearbeiten.', [
                'user_id'   => auth()->id(),
                'tender_id' => $request->tender_id
            ]);
            return redirect()->back()->with('error', 'Nicht berechtigt.');
        }

        $printer = Printer::find($validated['printer_id']);
        if (!$printer) {
            Log::warning('Drucker nicht gefunden.', [
                'user_id' => $user->User_ID,
                'printer_id' => $validated['printer_id']
            ]);
            return back()->with('error', 'Drucker wurde nicht gefunden.');
        }

        if($printer->User_ID != $user->User_ID) {
            Log::error('Sicherheitsrelevant: Nicht berechtigt diesen Drucker zu löschen.', [
                'user_id' => $user->User_ID,
                'printer_id' => $printer->Printer_ID
            ]);
            return back()->with('error', 'Nicht berechtigt, diesen Drucker zu löschen.');
        }

        if(!$printer->delete()) {
            Log::error('Fehler bei Drucker-Entfernung.', [
                'user_id' => $user->User_ID,
                'printer_id' => $printer->Printer_ID
            ]);
            return back()->with('error', 'Drucker konnte nicht gelöscht werden.');
        }

        return redirect("/settings")->with('success', 'Drucker wurde erfolgreich gelöscht.');
    }
}
