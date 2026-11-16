<?php

/*
 * Réglages propres à Yewtod SS qui ne relèvent pas du dashboard.
 * Les informations légales viennent de .env : elles changent rarement et
 * doivent être exactes dès la mise en ligne (LCEN, art. 6 III).
 */
return [
    'legal' => [
        'publisher' => env('LEGAL_PUBLISHER', ''),
        'publisher_address' => env('LEGAL_ADDRESS', ''),
        'director' => env('LEGAL_DIRECTOR', ''),
        'contact_email' => env('LEGAL_CONTACT_EMAIL', ''),
        // Hébergeur : coordonnées publiées par LWS dans ses CGV (à revérifier avant la mise en ligne).
        'host' => env('LEGAL_HOST') ?: 'LWS (Ligne Web Services), SAS — 2 rue Jules Ferry, 88190 Golbey, France — RCS Épinal 851 993 683 — https://www.lws.fr',
    ],

    // Durées de conservation (cahier § 8.5, § 8.6) — utilisées par les purges (lot 09).
    'retention' => [
        'collaborations_months' => (int) env('RETENTION_COLLABORATIONS_MONTHS', 24),
        'newsletter_pending_days' => 7,
        'activity_log_months' => (int) env('RETENTION_ACTIVITY_LOG_MONTHS', 12),
        'content_events_months' => (int) env('RETENTION_EVENTS_MONTHS', 25),
    ],
];
