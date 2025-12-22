<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openweathermap' => [
        'api_key' => env('OPENWEATHERMAP_API_KEY', 'demo_key'),
        'base_url' => env('OPENWEATHERMAP_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
    ],

    // Font Awesome Kit
    'fontawesome' => [
        // Erwartet eine Kit-ID oder Lizenznummer in der .env unter FONT_AWESOME
        'kit' => env('FONT_AWESOME'),
    ],

    'passolution' => [
        'api_url' => env('PASSOLUTION_API_URL', 'https://api.passolution.eu/api/v2'),
        'api_key' => env('PASSOLUTION_API_KEY'),
        'api_secret' => env('PASSOLUTION_API_SECRET'),
        'client_id' => env('PASSOLUTION_OAUTH_CLIENT_ID'),
        'client_secret' => env('PASSOLUTION_OAUTH_CLIENT_SECRET'),
        // OAuth URLs - configurable for different environments
        'oauth_base_url' => env('PASSOLUTION_OAUTH_BASE_URL', 'https://web.passolution.eu'),
        'oauth_authorize_url' => env('PASSOLUTION_OAUTH_AUTHORIZE_URL', 'https://web.passolution.eu/oauth/authorize'),
        'oauth_token_url' => env('PASSOLUTION_OAUTH_TOKEN_URL', 'https://web.passolution.eu/oauth/token'),
        'oauth_refresh_url' => env('PASSOLUTION_OAUTH_REFRESH_URL', 'https://web.passolution.eu/oauth/token/refresh'),
    ],

    // PDS API - For SSO token-based API access
    // PDS API - Für SSO-Token-basierten API-Zugriff
    // Uses same base URL as passolution service (PASSOLUTION_API_URL)
    'pds_api' => [
        'base_url' => env('PASSOLUTION_API_URL', 'https://api.passolution.eu/api/v2'),
        'timeout' => env('PDS_API_TIMEOUT', 30),
    ],

    'openai' => [
        'key' => env('RISK_CHARGPT_KEY'),
    ],

    // Social Authentication Providers for Customers
    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/customer/auth/facebook/callback',
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/customer/auth/google/callback',
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/customer/auth/linkedin/callback',
    ],

    'twitter' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/customer/auth/twitter/callback',
    ],

    // Plugin Demo Key for documentation page
    'plugin' => [
        'demo_key' => env('PLUGIN_DEMO_KEY'),
    ],

];
