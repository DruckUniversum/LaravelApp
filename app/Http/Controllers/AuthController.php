<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
class AuthController extends Controller
{
    // Login-Formular anzeigen
    public function showSsoForm(Request $request): View
    {
        return view('sso');
    }
}
