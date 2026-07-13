<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Customer Settings – Bereiche (Sidebar + Direktzugriff)
    |--------------------------------------------------------------------------
    |
    | Steuerung der Sichtbarkeit ganzer Settings-Bereiche auf
    | /customer/settings?section=...
    | Jeder Eintrag kann per ENV (true/false) ein- oder ausgeblendet werden.
    | Default: true (sichtbar).
    |
    */
    'sections' => [
        'general'               => env('CUSTOMER_SETTINGS_GENERAL_ENABLED', true),         // Mein Profil
        'master-data'           => env('CUSTOMER_SETTINGS_MASTER_DATA_ENABLED', true),     // Stammdaten
        'organization'          => env('CUSTOMER_SETTINGS_ORGANIZATION_ENABLED', true),    // Organisationsstruktur
        'users'                 => env('CUSTOMER_SETTINGS_USERS_ENABLED', true),           // Benutzerverwaltung
        'travel-requirements'   => env('CUSTOMER_SETTINGS_TRAVEL_REQUIREMENTS_ENABLED', true),
        'global-travel-monitor' => env('CUSTOMER_SETTINGS_GTM_ENABLED', true),
        'travel-alert'          => env('CUSTOMER_SETTINGS_TRAVEL_ALERT_ENABLED', true),
        'travel-data'           => env('CUSTOMER_SETTINGS_TRAVEL_DATA_ENABLED', true),
        'travel-link'           => env('CUSTOMER_SETTINGS_TRAVEL_LINK_ENABLED', true),
        'travel-information'    => env('CUSTOMER_SETTINGS_TRAVEL_INFORMATION_ENABLED', true),
        'connected-services'    => env('CUSTOMER_SETTINGS_CONNECTED_SERVICES_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Einzelne Blöcke (innerhalb einer Section)
    |--------------------------------------------------------------------------
    */
    'blocks' => [
        'password_change'    => env('CUSTOMER_SETTINGS_PASSWORD_CHANGE_ENABLED', true),
        // "Produktaktivierung" im Bereich Travel Requirements Service
        'product_activation' => env('CUSTOMER_SETTINGS_PRODUCT_ACTIVATION_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Einzelne Feature-Karten im Bereich Travel Requirements Service
    |--------------------------------------------------------------------------
    |
    | Pro Karte per ENV ein-/ausblendbar (Default: true = sichtbar). Keys, die
    | hier nicht stehen, sind immer sichtbar.
    |
    */
    'feature_cards' => [
        'customer.travel_detail_link.advert.manage' => env('CUSTOMER_SETTINGS_TRS_ADVERT_ENABLED', true), // Werbung verwalten
        'subscription'                              => env('CUSTOMER_SETTINGS_TRS_SUBSCRIPTION_ENABLED', true), // Abonnement
    ],
];
