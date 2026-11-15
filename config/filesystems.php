<?php

/*
 * Disques (cahier § 8.4, § 12.1) :
 * - « local » (privé) : pièces jointes des collaborations, PDF à analyser,
 *   sauvegardes. Hors de public/, JAMAIS servi directement (serve=false) :
 *   lecture uniquement par un contrôleur autorisé ou une URL signée de 10 min.
 * - « public » : couvertures, PDF publiés, avatars, médiathèque (noms aléatoires).
 */
return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim((string) env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
