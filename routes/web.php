<?php

use App\Http\Controllers\Dashboard\ThemePreferenceController;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// Page provisoire ; remplacée par le site public au lot 05.
Route::view('/', 'welcome')->name('home');

/*
 * Back-office : connecté + membre actif + 2FA confirmée sur TOUTES les routes.
 * Chaque module ajoute son contrôle de permission côté serveur.
 */
Route::middleware(['auth', 'member', '2fa'])->prefix('dashboard')->name('dashboard')->group(function () {
    Route::view('/', 'dashboard.home');

    Route::put('/preferences/theme', ThemePreferenceController::class)->name('.preferences.theme');
    Route::livewire('/profil', Dashboard\Profile::class)->name('.profile');
    Route::livewire('/utilisateurs', Dashboard\Users::class)->middleware('permission:manage_users|invite_users')->name('.users');
    Route::livewire('/journal', Dashboard\AuditLog::class)->name('.audit');
    // Contenus (cahier § 4.2, 4.3) : permission vérifiée côté serveur.
    Route::middleware('permission:manage_articles')->group(function () {
        foreach (['articles', 'rapports', 'visualisations-de-donnees'] as $type) {
            Route::livewire('/contenus/'.$type, Dashboard\ContentIndex::class)->defaults('type', $type)->name('.contents.'.$type);
        }
        Route::livewire('/contenus/autres', Dashboard\ContentIndex::class)->name('.contents.others');
        Route::livewire('/contenus/{type}/nouveau', Dashboard\ContentEditor::class)->name('.contents.create');
        Route::livewire('/contenus/{type}/{content}/modifier', Dashboard\ContentEditor::class)->whereNumber('content')->name('.contents.edit');
    });

    // Guide de style du système de design (documentation vivante).
    Route::view('/design', 'dashboard.styleguide')->name('.styleguide');
});
