<?php

// Cahier § 8.4 : en-têtes HTTP de sécurité sur toutes les pages.

it('pose une CSP stricte avec nonce, sans unsafe-inline pour les scripts', function () {
    $response = $this->get('/');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("frame-ancestors 'none'")
        ->and($csp)->toContain("object-src 'none'")
        ->and($csp)->toMatch("/script-src 'self' 'nonce-[A-Za-z0-9+\\/=]+'/");

    preg_match('/script-src ([^;]+)/', $csp, $m);
    expect($m[1])->not->toContain('unsafe-inline')->not->toContain('unsafe-eval');
});

it('pose les en-têtes anti-clickjacking et anti-sniffing', function () {
    $this->get('/')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeaderMissing('X-Powered-By');
    expect($this->get('/')->headers->get('Permissions-Policy'))->toContain('camera=()');
});

it('ajoute HSTS en HTTPS', function () {
    $this->get('https://localhost/')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('régénère un nonce différent à chaque requête', function () {
    $a = $this->get('/')->headers->get('Content-Security-Policy');
    $b = $this->get('/')->headers->get('Content-Security-Policy');
    expect($a)->not->toBe($b);
});
