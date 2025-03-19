<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /** Zeigt die Benutzereinstellungen */
    public function settings()
    {
        $user = Auth::user(); // Eingeloggter Benutzer
        return view('settings', compact('user')); // Blade: resources/views/user/settings.blade.php
    }

    /** Aktualisiert Benutzerdaten */
    public function settingsUpdate(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'email' => 'required|email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'house_number' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'password' => 'nullable',
            'password_confirm' => 'required_with:password|same:password',
        ]);

        $res = $user->update([
            'E-Mail' => $validated["email"],
            'First_Name' => $validated["first_name"],
            'Last_Name' => $validated["last_name"],
            'Street' => $validated["street"],
            'House_Number' => $validated["house_number"],
            'City' => $validated["city"],
            'Postal_Code' => $validated["postal_code"],
            'Country' => $validated["country"],
        ]);
        if(!$res) return back()->with('error', 'Einstellungen wurden nicht gespeichert.');

        if ($request->filled('password')) {
            $res = $user->update(['Password_Hash' => Hash::make($validated["password"])]);
            if(!$res) return back()->with('error', 'Passwort wurde nicht gespeichert.');
        }

        return redirect("/settings")->with('success', 'Einstellungen wurden erfolgreich gespeichert.');
    }
}
