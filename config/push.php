<?php

/*
|--------------------------------------------------------------------------
| Push-Benachrichtigungen der Apps (TripData)
|--------------------------------------------------------------------------
|
| Geraete melden sich ueber /api/v1/devices an, die Plattform verschickt ueber
| APNs (iOS) und FCM (Android). Wichtige Aenderungen an Reise-Links erkennt
| push:scan-travel-changes ueber den internen Delta-Feed der pds-api.
| Anleitung: Admin v2 › Doku › Push-Benachrichtigungen (docs/admin/push-notifications.md).
|
*/

return [

    // Geraete je Versand-Job; zugleich die Zahl gleichzeitiger HTTP/2-Anfragen eines Jobs.
    'batch_size' => (int) env('PUSH_BATCH_SIZE', 500),

    // Queue der Versand-Jobs (eigene Worker: php artisan queue:work --queue=push).
    'queue' => env('PUSH_QUEUE', 'push'),

    'apns' => [
        // Inhalt des .p8-Schluessels (Base64 oder PEM) oder Pfad zur Datei.
        'key' => env('APNS_KEY'),
        'key_path' => env('APNS_KEY_PATH'),
        'key_id' => env('APNS_KEY_ID'),
        'team_id' => env('APNS_TEAM_ID', 'PFC8Z32F99'),
        // Bundle-ID der App = apns-topic.
        'topic' => env('APNS_TOPIC', 'de.passolution.tripdata.app'),
        'timeout' => (int) env('APNS_TIMEOUT', 15),
    ],

    'fcm' => [
        // Service-Account-JSON des Firebase-Projekts (Inhalt, Base64 oder Pfad).
        'credentials' => env('FCM_CREDENTIALS'),
        'credentials_path' => env('FCM_CREDENTIALS_PATH'),
        'project_id' => env('FCM_PROJECT_ID'),
        'timeout' => (int) env('FCM_TIMEOUT', 15),
    ],

    'travel_changes' => [
        // Scanner im Zeitplan (alle 2 Minuten) an/aus.
        'enabled' => (bool) env('PUSH_TRAVEL_CHANGES_ENABLED', false),
        'per_page' => (int) env('PUSH_TRAVEL_CHANGES_PER_PAGE', 1000),
        // Hoechstens so viele Seiten je Lauf; der Rest folgt im naechsten Lauf (Cursor bleibt erhalten).
        'max_pages' => (int) env('PUSH_TRAVEL_CHANGES_MAX_PAGES', 50),
    ],

    // Geraete, die so lange nichts von sich hoeren liessen, bekommen nichts mehr (Tage).
    'device_stale_days' => (int) env('PUSH_DEVICE_STALE_DAYS', 180),

];
