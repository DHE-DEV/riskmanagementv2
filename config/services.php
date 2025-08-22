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
    ],

];
