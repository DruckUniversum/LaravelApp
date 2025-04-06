<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
class AuthController extends Controller
{
    // Login-Formular anzeigen
    public function showSsoForm(): View
    {
        return view('sso');
    }
}
