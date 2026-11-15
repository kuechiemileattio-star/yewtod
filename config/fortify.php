<?php

use Laravel\Fortify\Features;

/*
 * Fortify (cahier § 8.1–8.3). Les routes de Fortify sont désactivées
 * (Fortify::ignoreRoutes) et redéclarées dans routes/auth.php afin de
 * contrôler finement la limitation de débit et la non-énumération.
 */
return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'lowercase_usernames' => true,
    'home' => '/dashboard',
    'prefix' => '',
    'domain' => null,
    'middleware' => ['web'],
    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],
    'views' => true,
    'features' => [
        // Pas d'inscription publique : on n'entre que sur invitation (§ 5.7).
        Features::resetPasswords(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
            'window' => 1,
        ]),
    ],
];
