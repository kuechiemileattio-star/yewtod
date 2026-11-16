<?php

use Database\Seeders\DemoBookSeeder;
use Database\Seeders\DemoContentSeeder;
use Database\Seeders\DemoPublicWritesSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/*
 * Tests d'architecture (lot 09) : exigences transversales du cahier § 8.3,
 * § 8.4 et § 8.6, et bug n° 17 (code mort à ne pas reprendre).
 */

it('§ 8.4 : n\'utilise {!! !!} que sur des sorties nettoyées ou générées par le serveur (liste fermée)', function () {
    // Chaque exception est justifiée : JSON-LD encodé (JSON_HEX_TAG), SVG construits et échappés
    // par SvgChart, HTML passé par HTML Purifier, QR code SVG généré par Fortify, en-tête XML.
    $allowed = [
        'components/layouts/public.blade.php' => 1, 'dashboard/home.blade.php' => 2, 'dashboard/security/two-factor.blade.php' => 1,
        'livewire/dashboard/content-editor.blade.php' => 1, 'site/seo/feed.blade.php' => 1, 'site/seo/sitemap.blade.php' => 1,
        'site/works/partials/chart.blade.php' => 3, 'site/works/partials/sections.blade.php' => 2,
    ];
    $found = [];
    foreach (File::allFiles(resource_path('views')) as $file) {
        $rel = str_replace('\\', '/', $file->getRelativePathname());
        if (str_starts_with($rel, 'vendor/')) {
            continue;
        }
        $n = substr_count($file->getContents(), '{!!');
        if ($n > 0) {
            $found[$rel] = $n;
        }
    }
    ksort($found);
    ksort($allowed);
    expect($found)->toBe($allowed);
});

it('§ 8.6 : chaque modèle déclare un $fillable explicite, jamais $guarded = []', function () {
    foreach (File::files(app_path('Models')) as $file) {
        $code = $file->getContents();
        expect($code)->toContain('protected $fillable')
            ->and(preg_match('/\$guarded\s*=\s*\[\s*\]/', $code))->toBe(0, $file->getFilename());
    }
});

it('§ 8.6 : aucune variable interpolée dans du SQL brut', function () {
    foreach (File::allFiles(app_path()) as $file) {
        // whereRaw/orderByRaw/selectRaw/DB::raw/statement avec une chaîne qui contient « $ » = interpolation.
        preg_match_all('/(?:Raw|DB::raw|DB::statement|DB::select)\(\s*"[^"]*\$[a-zA-Z_]/', $file->getContents(), $m);
        expect($m[0])->toBe([], $file->getRelativePathname());
    }
});

it('§ 8.3 : cookie de session HttpOnly, SameSite=Lax, 30 minutes d\'inactivité, Secure activable', function () {
    expect(config('session.http_only'))->toBeTrue()
        ->and(config('session.same_site'))->toBe('lax')
        ->and((int) config('session.lifetime'))->toBe(30)
        ->and(file_get_contents(config_path('session.php')))->toContain("env('SESSION_SECURE_COOKIE'");
});

it('§ 8.6 : bloque les commandes destructrices en production', function () {
    app()->detectEnvironment(fn () => 'production');
    DB::prohibitDestructiveCommands(true);
    $this->artisan('migrate:fresh', ['--force' => true])->assertFailed();
    DB::prohibitDestructiveCommands(false);
});

it('§ 12.4 : les seeders de démonstration refusent de tourner hors local', function (string $seeder) {
    app()->detectEnvironment(fn () => 'production');
    (new $seeder)->run();
})->with([
    DemoSeeder::class, DemoContentSeeder::class,
    DemoBookSeeder::class, DemoPublicWritesSeeder::class,
])->throws(RuntimeException::class);

it('bug n° 17 : pas de code de contournement « migration manquante » ni de react-markdown', function () {
    $code = collect(File::allFiles(app_path()))->map(fn ($f) => $f->getContents())->implode("\n");
    expect(mb_strtolower($code))->not->toContain('migration manquante')
        ->and(file_get_contents(base_path('package.json')))->not->toContain('react-markdown');
});

it('§ 8.4 : aucun secret dans .env.example', function () {
    foreach (file(base_path('.env.example')) as $line) {
        if (preg_match('/^(\w*(KEY|SECRET|PASSWORD|PASSPHRASE)\w*)=(.*)$/', trim($line), $m) && $m[1] !== 'TURNSTILE_SITE_KEY') {
            expect(trim($m[3], '" '))->toBe('', $m[1]);
        }
    }
});
