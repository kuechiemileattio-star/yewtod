<?php

/*
 * Routes d'authentification (cahier § 5.7, § 8.1–8.3).
 * Les contrôleurs Fortify sont réutilisés, mais chaque route porte ici sa
 * propre limitation de débit. Pas de route d'inscription : on n'entre que
 * sur invitation.
 */

use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Dashboard\TwoFactorController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController as FortifyPasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login')->name('login.store');

    Route::get('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])->name('two-factor.login');
    Route::post('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])->middleware('throttle:two-factor')->name('two-factor.login.store');

    Route::get('/mot-de-passe-oublie', [FortifyPasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetLinkController::class, 'store'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [NewPasswordController::class, 'store'])->middleware('throttle:password-reset')->name('password.update');

    Route::get('/onboarding/{token}', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding/{token}', [OnboardingController::class, 'store'])->middleware('throttle:onboarding')->name('onboarding.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/confirmer-mot-de-passe', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirmer-mot-de-passe', [ConfirmablePasswordController::class, 'store'])->middleware('throttle:login')->name('password.confirm.store');

    // Configuration de la 2FA : accessible AVANT que la 2FA soit active
    // (sinon impossible de l'activer), mais réservée aux membres actifs.
    Route::middleware('member')->prefix('dashboard/securite/double-authentification')->name('security.')->group(function () {
        Route::get('/', [TwoFactorController::class, 'show'])->name('two-factor');
        Route::post('/code-email', [TwoFactorController::class, 'sendMailCode'])->middleware('throttle:3,10')->name('two-factor.mail-code');
        Route::post('/code-email/verifier', [TwoFactorController::class, 'verifyMailCode'])->middleware('throttle:10,10')->name('two-factor.mail-verify');
        Route::post('/activer', [TwoFactorController::class, 'enable'])->middleware('password.confirm')->name('two-factor.enable');
        Route::post('/confirmer', [TwoFactorController::class, 'confirm'])->middleware('throttle:10,1')->name('two-factor.confirm');
        Route::post('/codes-de-secours', [TwoFactorController::class, 'regenerate'])->middleware('password.confirm')->name('two-factor.regenerate');
        Route::delete('/', [TwoFactorController::class, 'destroy'])->middleware('password.confirm')->name('two-factor.disable');
    });
});
