<?php

/*
 * Lot 10 — préproduction protégée et vérification de la production (cahier § 12.3).
 */

it('protège la préproduction par authentification HTTP Basic, mot de passe haché', function () {
    config(['yewtod.preprod.user' => 'relecture', 'yewtod.preprod.password_hash' => password_hash('Secret-Preprod-2026', PASSWORD_DEFAULT)]);
    $this->get('/')->assertUnauthorized()->assertHeader('WWW-Authenticate')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->withHeader('Authorization', 'Basic '.base64_encode('relecture:mauvais'))->get('/')->assertUnauthorized();
    $this->withHeader('Authorization', 'Basic '.base64_encode('relecture:Secret-Preprod-2026'))->get('/')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get('/up')->assertOk(); // sonde de santé du script de déploiement
});

it('ne fait rien quand la préproduction n\'est pas configurée (production)', function () {
    $this->get('/')->assertOk()->assertHeaderMissing('WWW-Authenticate');
});

it('signale une configuration de production non conforme', function () {
    config(['app.debug' => true, 'app.url' => 'http://exemple.test', 'session.secure' => false]);
    $this->artisan('yewtod:verifier-production')
        ->expectsOutputToContain('APP_DEBUG=false')
        ->expectsOutputToContain('Analyse IA (GEMINI_API_KEY) — la fonction se désactive proprement')->assertFailed();
});
