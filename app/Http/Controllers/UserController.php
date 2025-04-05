<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
            'user_id' => $user->id,
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
            'email'           => 'required|email',
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'street'          => 'required|string|max:255',
            'house_number'    => 'required|string|max:255',
            'city'            => 'required|string|max:255',
            'postal_code'     => 'required|string|max:255',
            'country'         => 'required|string|max:255',
            'password'        => 'nullable',
            'password_confirm'=> 'required_with:password|same:password',
        ]);

        // Aktualisiere die Standard-Benutzerdaten
        $res = $user->update([
            'E-Mail'      => $validated["email"],
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
                'user_id' => $user->id,
                'input'   => $validated
            ]);
            return back()->with('error', 'Einstellungen wurden nicht gespeichert.');
        }
        Log::info('Benutzerdaten erfolgreich aktualisiert.', [
            'user_id' => $user->id,
            'email'   => $validated["email"]
        ]);

        // Passwort aktualisieren, falls eingegeben
        if ($request->filled('password')) {
            $res = $user->update([
                'Password_Hash' => Hash::make($validated["password"])
            ]);
            if (!$res) {
                Log::error('Fehler beim Aktualisieren des Passwortes.', [
                    'user_id' => $user->id
                ]);
                return back()->with('error', 'Passwort wurde nicht gespeichert.');
            }
            Log::info('Passwort erfolgreich aktualisiert.', [
                'user_id' => $user->id
            ]);
        }

        Log::info('Benutzereinstellungen wurden erfolgreich gespeichert.', [
            'user_id' => $user->id
        ]);
        return redirect("/settings")->with('success', 'Einstellungen wurden erfolgreich gespeichert.');
    }
}
