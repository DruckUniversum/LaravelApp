<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
        return view('settings', compact('user')); // Blade: resources/views/user/settings.blade.php
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
}
