<?php

use App\Models\SocialLink;
use App\Services\SiteSettings;

// Cahier § 9 (mode sombre) et § 10 (accessibilité) ; bug n° 2 (réglages lus côté serveur).

it('pose la classe dark côté serveur selon le cookie de préférence (pas de flash)', function () {
    $this->withUnencryptedCookie('theme', 'dark')->get('/')->assertSee('<html lang="fr" class="dark">', false)
        ->assertDontSee('prefers-color-scheme: dark)\').matches', false);
});

it('n\'ajoute le petit script à nonce qu\'en mode « système »', function () {
    $response = $this->get('/');
    preg_match("/'nonce-([^']+)'/", $response->headers->get('Content-Security-Policy'), $m);
    $response->assertSee('<script nonce="'.$m[1].'">', false);
});

it('ignore une valeur de cookie de thème invalide', function () {
    $this->withUnencryptedCookie('theme', '"><script>')->get('/')->assertSee('<html lang="fr" class="">', false);
});

it('propose un lien d\'évitement, une navigation et un pied de page accessibles', function () {
    $this->get('/')
        ->assertSee('href="#contenu"', false)->assertSee('Aller au contenu')
        ->assertSee('aria-label="Navigation principale"', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee('/works?categorie=visualisations-de-donnees', false)
        ->assertSee('Retour en haut');
});

it('lit le nom du site depuis les paramètres, avec cache (bug n° 2)', function () {
    app(SiteSettings::class)->set('site_name', 'Yewtod Test');
    $this->get('/')->assertSee('<title>Yewtod Test</title>', false);
});

it('pose le favicon enregistré dans les paramètres (bug n° 2)', function () {
    app(SiteSettings::class)->set('favicon_path', 'branding/favicon.png');
    $this->get('/')->assertSee('storage/branding/favicon.png', false);
});

it('affiche le guide de style aux membres', function () {
    $this->actingAs(member())->get('/dashboard/design')->assertOk()->assertSee('Système de design');
});

it('met en cache les réseaux sociaux sous forme sûre (cache en base, comme en production)', function () {
    config(['cache.default' => 'database']);
    SocialLink::create(['platform' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/yewtod', 'position' => 1]);
    $site = app(SiteSettings::class);
    $site->flush();
    $site->socialLinks();
    $links = (new SiteSettings)->socialLinks();
    expect($links->first()->icon())->toBe('linkedin');
    $this->get('/')->assertOk()->assertSee('rel="noopener noreferrer"', false);
});
