<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Twig\Environment;

class TwigAuthServiceProvider extends ServiceProvider {
    public function boot(Environment $twig) {
        View::composer('*', function ($view) use ($twig) {
            $user = Auth::user();
            $twig->addGlobal('auth', [
                'user' => $user,
                'isAuthenticated' => Auth::check(),
            ]);
            $twig->addGlobal('error', Session::get('error'));
            $twig->addGlobal('success', Session::get('success'));
        });
    }
}
