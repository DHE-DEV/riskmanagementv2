<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDS Auth Integration - Role
    |--------------------------------------------------------------------------
    |
    | Definiert die Rolle dieses Services im SSO-Flow
    | 'sp' = Service Provider (empfängt Authentifizierungen)
    | 'idp' = Identity Provider (stellt Authentifizierungen bereit)
    |
    | Defines the role of this service in the SSO flow
    | 'sp' = Service Provider (receives authentication)
    | 'idp' = Identity Provider (provides authentication)
    |
    */
    'role' => 'sp',

    /*
    |--------------------------------------------------------------------------
    | Public Key
    |--------------------------------------------------------------------------
    |
    | Öffentlicher Schlüssel für die JWT-Signatur-Verifizierung
    | Der Public Key wird verwendet, um JWTs zu validieren, die vom IdP
    | (pds-homepage) signiert wurden
    |
    | Public key for JWT signature verification
    | The public key is used to validate JWTs signed by the IdP
    | (pds-homepage)
    |
    | Can be provided as:
    | - Environment variable (PASSPORT_PUBLIC_KEY or SSO_PUBLIC_KEY)
    | - File path (storage_path('app/sso/sso-public.key'))
    |
    */
    'public_key' => env('SSO_PUBLIC_KEY') ?: env('PASSPORT_PUBLIC_KEY') ?: storage_path('app/sso/sso-public.key'),

    /*
    |--------------------------------------------------------------------------
    | Use Environment Keys
    |--------------------------------------------------------------------------
    |
    | Verwende Umgebungsvariablen für Schlüssel
    | Wenn true, wird der Schlüssel direkt aus der Umgebungsvariable gelesen
    | Wenn false, wird der Schlüssel aus einem Dateipfad gelesen
    |
    | Use environment variables for keys
    | If true, the key is read directly from the environment variable
    | If false, the key is read from a file path
    |
    */
    'use_env_keys' => (bool) env('SSO_USE_ENV_KEYS', true),

    /*
    |--------------------------------------------------------------------------
    | JWT Issuer
    |--------------------------------------------------------------------------
    |
    | Erwarteter Aussteller (iss) des JWT Tokens
    | Muss mit dem IdP übereinstimmen
    |
    | Expected issuer (iss) of the JWT token
    | Must match the IdP
    |
    */
    'jwt_issuer' => 'pds-homepage',

    /*
    |--------------------------------------------------------------------------
    | JWT Audience
    |--------------------------------------------------------------------------
    |
    | Erwartetes Ziel (aud) des JWT Tokens
    | Identifiziert diesen Service eindeutig
    |
    | Expected audience (aud) of the JWT token
    | Uniquely identifies this service
    |
    */
    'jwt_audience' => 'riskmanagementv2',

    /*
    |--------------------------------------------------------------------------
    | One-Time Token TTL
    |--------------------------------------------------------------------------
    |
    | Time-to-Live für One-Time Tokens in Sekunden
    | Nach Ablauf dieser Zeit ist das OTT ungültig
    |
    | Time-to-Live for One-Time Tokens in seconds
    | After this time, the OTT becomes invalid
    |
    */
    'ott_ttl' => 60,

    /*
    |--------------------------------------------------------------------------
    | OTT Cache Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix für OTT-Cache-Keys
    | Verhindert Kollisionen mit anderen Cache-Einträgen
    |
    | Prefix for OTT cache keys
    | Prevents collisions with other cache entries
    |
    */
    'ott_cache_prefix' => 'sso_ott_',

    /*
    |--------------------------------------------------------------------------
    | Customer Guard
    |--------------------------------------------------------------------------
    |
    | Name des Authentication Guards für Kunden
    | Wird verwendet, um den Kunden nach erfolgreicher SSO einzuloggen
    |
    | Name of the authentication guard for customers
    | Used to log in the customer after successful SSO
    |
    */
    'customer_guard' => 'customer',

    /*
    |--------------------------------------------------------------------------
    | Customer Dashboard Route
    |--------------------------------------------------------------------------
    |
    | Name der Route zum Kunden-Dashboard
    | Der Kunde wird nach erfolgreicher Authentifizierung hierhin weitergeleitet
    |
    | Name of the route to the customer dashboard
    | The customer is redirected here after successful authentication
    |
    */
    'customer_dashboard_route' => 'customer.dashboard',
];
