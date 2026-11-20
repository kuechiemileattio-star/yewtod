<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

// Décision D77 : installateur d'une version côté serveur (archive unique, FTP bridé chez LWS).

function siteDeTest(): string
{
    $root = storage_path('framework/testing/installateur-'.bin2hex(random_bytes(4)));
    File::ensureDirectoryExists($root.'/site/_deploiement');
    copy(base_path('scripts/installer-version.php'), $root.'/site/_deploiement/installer.php');

    return $root;
}

/** Archive minimale d'une « version » ; renvoie son empreinte. */
function archiveDeTest(string $root, string $name, string $version): string
{
    $zip = new ZipArchive;
    $zip->open($root.'/site/_deploiement/'.$name, ZipArchive::CREATE);
    foreach (['artisan' => '<?php', 'public/index.php' => '<?php // '.$version, 'vendor/autoload.php' => '<?php', '.htaccess' => '# racine', 'VERSION' => "commit={$version}\n",
        'app/Modele.php' => '<?php // '.$version, 'storage/app/public/.htaccess' => '# public', 'storage/logs/.gitignore' => '*', 'storage/framework/.gitignore' => '*'] as $path => $content) {
        $zip->addFromString($path, $content);
    }
    $zip->close();

    return hash_file('sha256', $root.'/site/_deploiement/'.$name);
}

function installer(string $root, string ...$args)
{
    return Process::path($root.'/site')->run(array_merge([PHP_BINARY, '_deploiement/installer.php'], $args));
}

afterEach(fn () => File::deleteDirectory(storage_path('framework/testing')));

it('installe une version sans toucher au .env ni aux fichiers de storage, puis permet le retour arrière', function () {
    $root = siteDeTest();
    $site = $root.'/site';
    File::put($site.'/.env', 'APP_KEY=secret');
    File::put($site.'/default_index.html', 'page LWS');
    File::ensureDirectoryExists($site.'/storage/app/public');
    File::put($site.'/storage/app/public/couverture.webp', 'image');
    File::ensureDirectoryExists($site.'/.well-known');

    $hash = archiveDeTest($root, 'v1.zip', 'v1');
    expect(installer($root, 'v1.zip', $hash)->successful())->toBeTrue();
    expect(File::get($site.'/VERSION'))->toContain('v1')
        ->and(File::get($site.'/.env'))->toBe('APP_KEY=secret')                       // .env intact
        ->and(File::get($site.'/storage/app/public/couverture.webp'))->toBe('image')    // fichiers envoyés intacts
        ->and(File::exists($site.'/storage/logs/.gitignore'))->toBeTrue()              // squelette complété
        ->and(File::exists($site.'/default_index.html'))->toBeFalse()                  // page d'attente retirée
        ->and(File::isDirectory($site.'/.well-known'))->toBeTrue()                     // certificat intact
        ->and(File::exists($site.'/storage/framework/down'))->toBeTrue()               // reste en maintenance
        ->and(File::exists($site.'/_deploiement/v1.zip'))->toBeFalse();                // archive supprimée

    $hash2 = archiveDeTest($root, 'v2.zip', 'v2');
    installer($root, 'v2.zip', $hash2);
    expect(File::get($site.'/app/Modele.php'))->toContain('v2')
        ->and(File::get($site.'/_deploiement/precedente/app/Modele.php'))->toContain('v1');

    expect(installer($root, '--retour')->successful())->toBeTrue();
    expect(File::get($site.'/VERSION'))->toContain('v1')
        ->and(File::get($site.'/.env'))->toBe('APP_KEY=secret');

    installer($root, '--retour'); // le retour se refait dans l'autre sens
    expect(File::get($site.'/VERSION'))->toContain('v2');
});

it('refuse une archive dont l\'empreinte ne correspond pas, sans rien modifier', function () {
    $root = siteDeTest();
    $hash = archiveDeTest($root, 'v1.zip', 'v1');
    installer($root, 'v1.zip', $hash);

    archiveDeTest($root, 'v2.zip', 'v2');
    $result = installer($root, 'v2.zip', str_repeat('0', 64));
    expect($result->failed())->toBeTrue()
        ->and($result->errorOutput())->toContain('empreinte de l\'archive incorrecte')
        ->and(File::get($root.'/site/VERSION'))->toContain('v1');
});

it('refuse une archive incomplète, sans rien modifier', function () {
    $root = siteDeTest();
    $zip = new ZipArchive;
    $zip->open($root.'/site/_deploiement/vide.zip', ZipArchive::CREATE);
    $zip->addFromString('README', 'rien');
    $zip->close();
    $result = installer($root, 'vide.zip', hash_file('sha256', $root.'/site/_deploiement/vide.zip'));
    expect($result->failed())->toBeTrue()
        ->and($result->errorOutput())->toContain('archive incomplète')
        ->and(File::exists($root.'/site/artisan'))->toBeFalse();
});
