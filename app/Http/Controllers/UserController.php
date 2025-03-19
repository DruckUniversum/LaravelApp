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
        return view('user.settings', compact('user')); // Blade: resources/views/user/settings.blade.php
    }

    /** Aktualisiert Benutzerdaten */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => 'nullable|confirmed|min:8',
        ]);

        $user->update($request->except(['password', 'password_confirmation']));

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return back()->with('success', 'Einstellungen wurden erfolgreich gespeichert.');
    }
}
