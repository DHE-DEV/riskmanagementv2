<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Travel Detail Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration options for the Travel Detail module.
    | The module can be enabled/disabled via the TD_FEATURE_ENABLED env variable.
    |
    */

    'enabled' => env('TD_FEATURE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'rate_limit' => env('TD_API_RATE_LIMIT', 100),
        'rate_limit_window' => env('TD_API_RATE_LIMIT_WINDOW', 60), // seconds
        'schema_versions' => ['1.0', '1.1'],
        'current_schema_version' => '1.1',
    ],

    /*
    |--------------------------------------------------------------------------
    | PDS API Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for integration with the Passolution PDS API
    | for generating travel detail share links.
    |
    */
    'pds' => [
        'api_url' => env('PDS_API_URL', 'https://api.passolution.eu'),
        'api_key' => env('PDS_KEY'),
        'timeout' => env('PDS_API_TIMEOUT', 30),
        'share_link_enabled' => env('TD_PDS_SHARE_LINK_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Archival Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for archiving completed trips.
    | Trips are considered archivable when their computed_end_at is in the past
    | and the specified number of days have passed.
    |
    */
    'archival' => [
        'enabled' => env('TD_ARCHIVE_ENABLED', true),
        'days_after_completion' => env('TD_ARCHIVE_DAYS', 30),
        'batch_size' => env('TD_ARCHIVE_BATCH_SIZE', 1000),
        'use_separate_database' => env('TD_ARCHIVE_USE_SEPARATE_DB', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Proximity Query Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for geo-proximity queries.
    | These settings control how travelers near events are detected.
    |
    */
    'proximity' => [
        'default_radius_km' => env('TD_PROXIMITY_DEFAULT_RADIUS_KM', 100),
        'max_radius_km' => env('TD_PROXIMITY_MAX_RADIUS_KM', 500),
        'batch_size' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Transfer Detection Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for detecting transfers between flight segments.
    |
    */
    'transfers' => [
        // Minimum layover time to consider it a valid transfer (minutes)
        'min_connection_minutes' => env('TD_TRANSFER_MIN_MINUTES', 30),

        // Maximum layover time to consider it a transfer (minutes)
        // Beyond this, it's considered a separate journey
        'max_connection_minutes' => env('TD_TRANSFER_MAX_MINUTES', 2880), // 48 hours

        // Threshold for tight connection warning (minutes)
        'tight_connection_minutes' => env('TD_TIGHT_CONNECTION_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'airport_ttl' => env('TD_CACHE_AIRPORT_TTL', 3600), // 1 hour
        'country_ttl' => env('TD_CACHE_COUNTRY_TTL', 86400), // 24 hours
        'prefix' => 'td_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('TD_LOGGING_ENABLED', true),
        'channel' => env('TD_LOG_CHANNEL', 'travel_detail'),
        'log_payloads' => env('TD_LOG_PAYLOADS', false), // Store full request payloads in logs
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Log Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep import logs before pruning.
    |
    */
    'import_logs' => [
        'retention_days' => env('TD_IMPORT_LOG_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention & Cleanup
    |--------------------------------------------------------------------------
    |
    | Multi-stage data retention strategy:
    | 1. Archive: Mark trips as archived after X days (archival.days_after_completion)
    | 2. Purge: Delete archived trips after Y years
    | 3. Logs: Delete import logs after Z days
    |
    | This prevents unlimited database growth over time.
    |
    */
    'retention' => [
        // Delete archived trips completely after this many years
        'purge_archived_after_years' => env('TD_PURGE_ARCHIVED_YEARS', 2),

        // Enable automatic scheduled cleanup
        'scheduled_cleanup_enabled' => env('TD_SCHEDULED_CLEANUP_ENABLED', true),

        // Run cleanup at this time daily (24h format)
        'cleanup_time' => env('TD_CLEANUP_TIME', '03:00'),
    ],
];
