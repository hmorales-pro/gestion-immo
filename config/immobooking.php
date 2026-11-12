<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public website
    |--------------------------------------------------------------------------
    | On the official site, "/" shows the marketing landing page, the guides and
    | the legal pages. On an agency's own installation, leave it off: "/" then
    | redirects to the admin panel.
    */
    'website' => (bool) env('IMMOBOOKING_WEBSITE', false),

    'github_url' => env('IMMOBOOKING_GITHUB_URL', 'https://github.com/hmorales-pro/gestion-immo'),

    // Shown in "Propulsé par ImmoBooking" links on booking pages.
    'site_url' => env('IMMOBOOKING_SITE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Public demo
    |--------------------------------------------------------------------------
    | Creates a demo agent whose data is reset automatically (no cron needed).
    | Notifications about demo data are never sent: no e-mail or SMS leaves the
    | server, whatever address a visitor types.
    */
    'demo' => [
        'enabled' => (bool) env('IMMOBOOKING_DEMO', false),
        'email' => 'demo@immobooking.test',
        'password' => 'demo',
        'reset_every_minutes' => (int) env('IMMOBOOKING_DEMO_RESET_MINUTES', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal notice (mentions légales) — required for a French website
    |--------------------------------------------------------------------------
    */
    'legal' => [
        'editor' => env('IMMOBOOKING_LEGAL_EDITOR', '[Nom ou raison sociale de l\'éditeur]'),
        'address' => env('IMMOBOOKING_LEGAL_ADDRESS', '[Adresse postale]'),
        'email' => env('IMMOBOOKING_LEGAL_EMAIL', '[E-mail de contact]'),
        'siret' => env('IMMOBOOKING_LEGAL_SIRET'),
        'publisher' => env('IMMOBOOKING_LEGAL_PUBLISHER', '[Directeur de la publication]'),
        'host' => env('IMMOBOOKING_LEGAL_HOST', '[Nom, adresse et téléphone de l\'hébergeur]'),
    ],
];
