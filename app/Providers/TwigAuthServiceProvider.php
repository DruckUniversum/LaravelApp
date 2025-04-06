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
            $roles = [];
            if($user) {
                foreach ($user->roles as $role) {
                    $roles[] = $role->Role;
                }
            }
            $twig->addGlobal('auth', [
                'user' => $user,
                'roles' => $roles,
                'isAuthenticated' => Auth::check(),
            ]);
            $twig->addGlobal('error', Session::get('error'));
            $twig->addGlobal('success', Session::get('success'));
        });
    }
}
