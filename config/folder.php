<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Folder Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the folder management system (Reisemappen-Verwaltung).
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Proximity Settings
    |--------------------------------------------------------------------------
    |
    | Settings for proximity queries and traveler location tracking.
    |
    */
    'proximity' => [
        // Default radius in kilometers for proximity searches
        'default_radius_km' => env('FOLDER_PROXIMITY_RADIUS', 100),

        // Maximum radius allowed in kilometers
        'max_radius_km' => env('FOLDER_PROXIMITY_MAX_RADIUS', 1000),

        // Enable caching for proximity queries
        'cache_enabled' => env('FOLDER_PROXIMITY_CACHE_ENABLED', true),

        // Cache TTL in seconds
        'cache_ttl' => env('FOLDER_PROXIMITY_CACHE_TTL', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Settings
    |--------------------------------------------------------------------------
    |
    | Settings for importing folder data from external systems.
    |
    */
    'import' => [
        // Maximum file size in megabytes
        'max_file_size_mb' => env('FOLDER_IMPORT_MAX_SIZE', 10),

        // Allowed import formats
        'allowed_formats' => ['json', 'xml', 'csv'],

        // Queue name for import jobs
        'queue_name' => env('FOLDER_IMPORT_QUEUE', 'default'),

        // Import timeout in seconds
        'timeout_seconds' => env('FOLDER_IMPORT_TIMEOUT', 600),

        // Batch size for bulk imports
        'batch_size' => env('FOLDER_IMPORT_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Settings for caching folder data and statistics.
    |
    */
    'cache' => [
        // Folder statistics cache TTL in seconds
        'folder_stats_ttl' => env('FOLDER_CACHE_STATS_TTL', 3600),

        // Map locations cache TTL in seconds
        'map_locations_ttl' => env('FOLDER_CACHE_MAP_TTL', 600),

        // Timeline locations cache TTL in seconds
        'timeline_ttl' => env('FOLDER_CACHE_TIMELINE_TTL', 1800),

        // Cache tags prefix
        'tags_prefix' => 'folder',
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeline Settings
    |--------------------------------------------------------------------------
    |
    | Settings for timeline location tracking and rebuilding.
    |
    */
    'timeline' => [
        // Enable automatic timeline rebuild after service changes
        'auto_rebuild' => env('FOLDER_TIMELINE_AUTO_REBUILD', true),

        // Queue name for timeline rebuild jobs
        'queue_name' => env('FOLDER_TIMELINE_QUEUE', 'default'),

        // Rebuild in background (async) or foreground (sync)
        'async_rebuild' => env('FOLDER_TIMELINE_ASYNC', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Map Visualization Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the map visualization with clustering.
    |
    */
    'map' => [
        // Enable map visualization
        'enabled' => env('FOLDER_MAP_ENABLED', true),

        // Default map center coordinates
        'default_center' => [
            'lat' => env('FOLDER_MAP_DEFAULT_LAT', 50.1109),
            'lng' => env('FOLDER_MAP_DEFAULT_LNG', 8.6821),
        ],

        // Default map zoom level
        'default_zoom' => env('FOLDER_MAP_DEFAULT_ZOOM', 6),

        // Marker clustering settings
        'clustering' => [
            'enabled' => true,
            'max_cluster_radius' => 40,
            'disable_at_zoom' => 15,
        ],

        // Tile layer provider
        'tile_provider' => env('FOLDER_MAP_TILE_PROVIDER', 'openstreetmap'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Folder Number Generation
    |--------------------------------------------------------------------------
    |
    | Settings for generating unique folder numbers.
    |
    */
    'folder_number' => [
        // Format: {year}-{customer_id}-{sequence}
        'format' => 'Y-{customer_id}-{sequence}',

        // Sequence padding (number of digits)
        'sequence_padding' => 6,

        // Separator character
        'separator' => '-',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Validation settings for folder data.
    |
    */
    'validation' => [
        // Maximum participants per folder
        'max_participants' => env('FOLDER_MAX_PARTICIPANTS', 50),

        // Maximum itineraries per folder
        'max_itineraries' => env('FOLDER_MAX_ITINERARIES', 20),

        // Maximum services per itinerary
        'max_services_per_itinerary' => env('FOLDER_MAX_SERVICES', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features.
    |
    */
    'features' => [
        // Enable folder import functionality
        'import_enabled' => env('FOLDER_FEATURE_IMPORT', true),

        // Enable map visualization
        'map_enabled' => env('FOLDER_FEATURE_MAP', true),

        // Enable proximity queries
        'proximity_enabled' => env('FOLDER_FEATURE_PROXIMITY', true),

        // Enable timeline tracking
        'timeline_enabled' => env('FOLDER_FEATURE_TIMELINE', true),

        // Enable statistics
        'statistics_enabled' => env('FOLDER_FEATURE_STATISTICS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings for performance optimization.
    |
    */
    'performance' => [
        // Enable query result caching
        'query_cache_enabled' => env('FOLDER_QUERY_CACHE', true),

        // Enable eager loading optimization
        'eager_loading' => true,

        // Pagination default per page
        'pagination_per_page' => env('FOLDER_PAGINATION_PER_PAGE', 15),

        // Maximum pagination per page
        'pagination_max_per_page' => env('FOLDER_PAGINATION_MAX', 100),

        // Enable spatial index usage
        'use_spatial_index' => true,
    ],
];
