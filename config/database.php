<?php

use Pdo\Mysql;

/*
 * Connexions à la base (cahier § 8.6, décision 12).
 *
 * - « mariadb » : connexion de l'application. En local elle utilise le compte
 *   yewtod_app (CRUD seulement). En ligne (LWS), c'est l'utilisateur unique.
 * - « migrate » : connexion réservée aux migrations. Elle n'utilise un compte
 *   distinct que si DB_MIGRATE_USERNAME est renseigné ; sinon elle retombe sur
 *   l'utilisateur unique (limite du mutualisé LWS). Le basculement se fait dans
 *   AppServiceProvider::useMigrationConnection().
 *
 * Moteur (décision D74) : DB_DRIVER=mariadb (par défaut, local) ou
 * DB_DRIVER=mysql (LWS Perso, qui fournit MySQL). Le nom de la connexion
 * reste « mariadb » ; seul le pilote Laravel (grammaire SQL) change. Aucune
 * fonctionnalité propre à l'un des deux moteurs n'est utilisée : la suite de
 * tests passe sur MariaDB 10.11 et sur MySQL 8.
 */

$driver = in_array(env('DB_DRIVER'), ['mysql', 'mariadb'], true) ? env('DB_DRIVER') : 'mariadb';

$mariadb = static fn (?string $user, ?string $password): array => [
    'driver' => $driver,
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'yewtod'),
    'username' => $user,
    'password' => $password,
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    // Mode SQL strict : une valeur tronquée ou invalide lève une erreur au
    // lieu d'être corrigée silencieusement.
    'strict' => true,
    'engine' => 'InnoDB',
    // Toutes les dates sont stockées en UTC (affichage converti en Europe/Paris).
    'timezone' => '+00:00',
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        // Pas d'émulation des requêtes préparées : les paramètres liés sont
        // envoyés séparément du SQL par le serveur.
        PDO::ATTR_EMULATE_PREPARES => false,
        // PHP 8.4+ : Pdo\Mysql::ATTR_SSL_CA (l'ancienne constante est dépréciée en 8.5).
        (class_exists(Mysql::class) ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
    ], static fn ($v) => $v !== null) : [],
];

$migrateUser = env('DB_MIGRATE_USERNAME');

return [

    'default' => env('DB_CONNECTION', 'mariadb'),

    'connections' => [

        'mariadb' => $mariadb(env('DB_USERNAME', 'yewtod_app'), env('DB_PASSWORD', '')),

        'migrate' => filled($migrateUser)
            ? $mariadb($migrateUser, env('DB_MIGRATE_PASSWORD', ''))
            : $mariadb(env('DB_USERNAME', 'yewtod_app'), env('DB_PASSWORD', '')),

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    // Redis n'existe pas sur le mutualisé LWS : cache, sessions et file
    // d'attente passent par la base de données.
    'redis' => [],

];
