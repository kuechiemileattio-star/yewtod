<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// Page provisoire ; remplacée par le site public au lot 05.
Route::view('/', 'welcome')->name('home');

// Back-office : connecté + membre actif + 2FA confirmée, sur toutes les routes.
Route::middleware(['auth', 'member', '2fa'])->prefix('dashboard')->name('dashboard')->group(function () {
    Route::view('/', 'dashboard.home');
});
