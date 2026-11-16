<?php

use App\Http\Controllers\Dashboard\CollaborationFileController;
use App\Http\Controllers\Dashboard\NewsletterExportController;
use App\Http\Controllers\Dashboard\OverviewController;
use App\Http\Controllers\Dashboard\ThemePreferenceController;
use App\Http\Controllers\Site;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

/*
 * Site public (cahier § 2). Lecture seule, rendue côté serveur.
 * Les paramètres de type et de slug sont contraints par expression régulière ;
 * le contrôleur revérifie le type dans le registre (liste blanche).
 */
Route::get('/', Site\HomeController::class)->name('home');
Route::get('/works', [Site\WorkController::class, 'index'])->name('works.index');
Route::pattern('workType', '[a-z-]{3,40}');
Route::pattern('workSlug', '[a-z0-9-]{1,320}');
Route::get('/works/{workType}/{workSlug}', [Site\WorkController::class, 'show'])->name('works.show');
Route::middleware('throttle:public-download')->group(function () {
    Route::get('/works/{workType}/{workSlug}/telecharger', [Site\WorkController::class, 'download'])->name('works.download');
    Route::get('/works/{workType}/{workSlug}/fichiers/{index}', [Site\WorkController::class, 'file'])->whereNumber('index')->name('works.file');
});
Route::get('/books', [Site\BookController::class, 'index'])->name('books.index');
Route::get('/books/{bookSlug}', [Site\BookController::class, 'show'])->where('bookSlug', '[a-z0-9-]{1,320}')->name('books.show');
Route::middleware('throttle:public-download')->where(['bookSlug' => '[a-z0-9-]{1,320}'])->group(function () {
    Route::get('/books/{bookSlug}/extraits/{index}', [Site\BookController::class, 'excerpt'])->whereNumber('index')->name('books.excerpt');
    Route::get('/books/{bookSlug}/livre-electronique', [Site\BookController::class, 'ebook'])->name('books.ebook');
});
// Écritures publiques : limitation par IP sur chacun des 4 formulaires (cahier § 8.4).
Route::get('/collaborations', [Site\PublicFormController::class, 'collaborationForm'])->name('collaborations');
Route::middleware('throttle:public-form')->group(function () {
    Route::post('/works/{workType}/{workSlug}/avis', [Site\PublicFormController::class, 'reviewWork'])->name('reviews.work');
    Route::post('/books/{bookSlug}/avis', [Site\PublicFormController::class, 'reviewBook'])->where('bookSlug', '[a-z0-9-]{1,320}')->name('reviews.book');
    Route::post('/collaborations', [Site\PublicFormController::class, 'collaborationStore'])->name('collaborations.store');
    Route::post('/newsletter', [Site\PublicFormController::class, 'newsletterSubscribe'])->name('newsletter.subscribe');
});
Route::middleware('throttle:public-feed')->where(['token' => '[A-Za-z0-9]{40}'])->group(function () {
    Route::get('/newsletter/confirmer/{token}', [Site\PublicFormController::class, 'newsletterConfirm'])->name('newsletter.confirm');
    Route::get('/newsletter/desinscription/{token}', [Site\PublicFormController::class, 'newsletterUnsubscribeForm'])->name('newsletter.unsubscribe');
    Route::post('/newsletter/desinscription/{token}', [Site\PublicFormController::class, 'newsletterUnsubscribe'])->name('newsletter.unsubscribe.confirm');
});
Route::get('/meet', [Site\PageController::class, 'meet'])->name('meet');
Route::get('/mentions-legales', [Site\PageController::class, 'legal'])->name('legal');
Route::get('/confidentialite', [Site\PageController::class, 'privacy'])->name('privacy');
Route::get('/recherche', Site\SearchController::class)->middleware('throttle:public-search')->name('search');
Route::middleware('throttle:public-feed')->group(function () {
    Route::get('/sitemap.xml', [Site\SeoController::class, 'sitemap'])->name('sitemap');
    Route::get('/feed', [Site\SeoController::class, 'feed'])->name('feed');
    Route::get('/robots.txt', [Site\SeoController::class, 'robots'])->name('robots');
});

// Aperçu réel d'un brouillon : URL signée (30 min) ET membre connecté avec 2FA.
Route::get('/apercu/{content}', [Site\WorkController::class, 'preview'])
    ->middleware(['signed', 'auth', 'member', '2fa'])->whereNumber('content')->name('content.preview');

/*
 * Back-office : connecté + membre actif + 2FA confirmée sur TOUTES les routes.
 * Chaque module ajoute son contrôle de permission côté serveur.
 */
Route::middleware(['auth', 'member', '2fa'])->prefix('dashboard')->name('dashboard')->group(function () {
    Route::get('/', OverviewController::class);

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

    // Livres (cahier § 4.4).
    Route::livewire('/livres', Dashboard\Books::class)->middleware('permission:manage_books')->name('.books');

    // Modération des avis (cahier § 4.6) : la permission fine est vérifiée par avis.
    Route::livewire('/moderation', Dashboard\Moderation::class)->middleware('permission:manage_articles|manage_books')->name('.moderation');

    // Collaborations (cahier § 4.5).
    Route::middleware('permission:manage_collaborations')->group(function () {
        Route::livewire('/collaborations', Dashboard\Collaborations::class)->name('.collaborations');
        Route::get('/collaborations/export', [CollaborationFileController::class, 'export'])->name('.collaborations.export');
        Route::get('/collaborations/fichiers/{file}', [CollaborationFileController::class, 'download'])->middleware('signed')->whereNumber('file')->name('.collaborations.file');
    });

    // Administration (cahier § 4.8, § 4.9, § 10 P2, § 13.1).
    Route::livewire('/parametres', Dashboard\Settings::class)->middleware('permission:manage_settings')->name('.settings');
    Route::livewire('/pages', Dashboard\Pages::class)->middleware('permission:manage_pages')->name('.pages');
    Route::livewire('/mediatheque', Dashboard\MediaManager::class)->middleware('permission:manage_media')->name('.media');
    Route::middleware('permission:manage_newsletter')->group(function () {
        Route::livewire('/newsletter', Dashboard\Newsletter::class)->name('.newsletter');
        Route::get('/newsletter/export', NewsletterExportController::class)->name('.newsletter.export');
    });

    // Guide de style du système de design (documentation vivante).
    Route::view('/design', 'dashboard.styleguide')->name('.styleguide');
});

// Toute URL inconnue : vraie page 404 (bug n° 14), rendue dans le groupe web.
Route::fallback([Site\PageController::class, 'notFound']);
