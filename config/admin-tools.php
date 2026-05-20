<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Versteckte Admin-Tools (Keycloak User Management)
    |--------------------------------------------------------------------------
    |
    | Das Tool ist NUR erreichbar, wenn:
    |   1. ADMIN_TOOLS_ENABLED=true
    |   2. ADMIN_TOOLS_TOKEN gesetzt ist und in URL/Header übergeben wird
    |   3. Ein Admin (User mit is_admin=true) eingeloggt ist
    |
    */

    'enabled' => env('ADMIN_TOOLS_ENABLED', false),

    'token' => env('ADMIN_TOOLS_TOKEN'),

    'path' => env('ADMIN_TOOLS_PATH', '_sys/kc-tools'),
];
