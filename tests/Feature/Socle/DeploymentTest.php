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
        ->expectsOutputToContain('Newsletter Brevo (BREVO_API_KEY, BREVO_LIST_ID) — la fonction se désactive proprement')->assertFailed();
});

it('donne le code 2FA d\'un compte de démo en local uniquement', function () {
    member('editeur', true, ['email' => 'editeur@yewtod.test']);
    $this->artisan('yewtod:code-demo editeur@yewtod.test')->assertFailed(); // environnement « testing »
    app()->detectEnvironment(fn () => 'local');
    $this->artisan('yewtod:code-demo editeur@yewtod.test')->expectsOutputToContain('Code actuel')->assertSuccessful();
    $this->artisan('yewtod:code-demo quelquun@exemple.fr')->assertFailed();
});

it('ne laisse joignable que public/ sur LWS Perso : .htaccess racine (D73)', function () {
    $root = file_get_contents(base_path('.htaccess'));
    expect($root)->toContain('RewriteRule ^(.*)$ public/$1 [L]')        // couche 1 : tout vers public/
        ->toContain('RewriteRule (^|/)\. - [F,L]')                         // fichiers cachés (.env)
        ->toContain('Require all denied')                                  // couche 2 et repli sans mod_rewrite
        ->toContain('<IfModule !mod_rewrite.c>')
        ->not->toContain('DirectoryIndex disabled');                       // casserait l'accueil (public/index.php)

    $public = file_get_contents(public_path('.htaccess'));
    expect($public)->toContain('^(?:/public(?=/))?(.+)/$')                 // redirection sans « /public » ajouté
        ->toContain('m#^(/public)?/build/assets/#');

    $paquet = file_get_contents(base_path('scripts/preparer-paquet.sh'));
    expect($paquet)->toContain('for sub in app framework logs')            // couche 3, sans bloquer /storage/…
        ->toContain('Require all denied');
});

it('vérifie que DB_DRIVER correspond au moteur réel (MySQL chez LWS Perso, D74)', function () {
    $version = (string) DB::selectOne('SELECT VERSION() AS v')->v;
    $real = str_contains(strtolower($version), 'mariadb') ? 'mariadb' : 'mysql';

    $this->artisan('yewtod:verifier-production')->expectsOutputToContain('Moteur de base compatible');
    expect(DB::connection()->getDriverName())->toBe($real); // l'environnement de test est cohérent

    // Pilote inverse : la vérification le signale avec la valeur à mettre.
    config(['database.connections.mariadb.driver' => $real === 'mysql' ? 'mariadb' : 'mysql']);
    DB::purge('mariadb');
    $this->artisan('yewtod:verifier-production')->expectsOutputToContain("mettez DB_DRIVER={$real}");
});
