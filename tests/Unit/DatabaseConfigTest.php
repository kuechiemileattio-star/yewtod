<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

// Cahier § 8.6 : configuration de la base et repli sur l'utilisateur unique (LWS).

it('utilise MariaDB en mode strict, utf8mb4 et UTC', function () {
    $c = config('database.connections.mariadb');
    expect($c['driver'])->toBe('mariadb')
        ->and($c['strict'])->toBeTrue()
        ->and($c['charset'])->toBe('utf8mb4')
        ->and($c['timezone'])->toBe('+00:00')
        ->and($c['options'][PDO::ATTR_EMULATE_PREPARES])->toBeFalse();
});

it('retombe sur l\'utilisateur unique quand aucun compte de migration n\'est défini', function () {
    // phpunit.xml force DB_MIGRATE_USERNAME vide : situation du mutualisé LWS.
    expect(config('database.connections.migrate.username'))
        ->toBe(config('database.connections.mariadb.username'));
});

it('utilise un compte distinct pour les migrations quand il est renseigné', function () {
    $config = (function () {
        putenv('DB_MIGRATE_USERNAME=yewtod_migrate');
        $_ENV['DB_MIGRATE_USERNAME'] = $_SERVER['DB_MIGRATE_USERNAME'] = 'yewtod_migrate';
        $c = require base_path('config/database.php');
        putenv('DB_MIGRATE_USERNAME=');
        $_ENV['DB_MIGRATE_USERNAME'] = $_SERVER['DB_MIGRATE_USERNAME'] = '';

        return $c;
    })();
    expect($config['connections']['migrate']['username'])->toBe('yewtod_migrate')
        ->and($config['connections']['mariadb']['username'])->not->toBe('yewtod_migrate');
});

it('active le mode strict des modèles hors production', function () {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue();
});

it('hache les mots de passe en Argon2id', function () {
    expect(config('hashing.driver'))->toBe('argon2id')
        ->and(password_get_info(Hash::make('x'))['algoName'])->toBe('argon2id');
});
