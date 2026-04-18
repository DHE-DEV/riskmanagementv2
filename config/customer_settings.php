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
    | Einzelne Blöcke auf Section "general" (Mein Profil)
    |--------------------------------------------------------------------------
    */
    'blocks' => [
        'password_change' => env('CUSTOMER_SETTINGS_PASSWORD_CHANGE_ENABLED', true),
    ],
];
