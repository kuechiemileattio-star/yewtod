<?php

/*
 * Services tiers (décision 8) : chaque clé peut manquer. Les classes qui les
 * utilisent vérifient `enabled()` et se désactivent proprement (message clair
 * dans le dashboard, jamais d'erreur 500).
 */

return [

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
        'timeout' => 60,
        'max_pdf_bytes' => 20 * 1024 * 1024,
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
        'list_id' => env('BREVO_LIST_ID'),
        'endpoint' => 'https://api.brevo.com/v3',
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'endpoint' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ],

    // Vérification « mot de passe compromis » (Have I Been Pwned, k-anonymat :
    // seuls les 5 premiers caractères du SHA-1 quittent le serveur).
    'hibp' => [
        'enabled' => (bool) env('HIBP_ENABLED', true),
    ],

    'backup' => [
        // Clé de chiffrement des sauvegardes (distincte d'APP_KEY).
        'passphrase' => env('BACKUP_PASSPHRASE'),
        'path' => env('BACKUP_PATH', storage_path('app/backups')),
    ],

];
