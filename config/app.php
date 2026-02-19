<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Europe/Berlin'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | GDACS Integration
    |--------------------------------------------------------------------------
    |
    | This option controls whether GDACS (Global Disaster Alert and Coordination System)
    | integration is enabled. When enabled, the system will fetch and display
    | GDACS events on the map and in the events list.
    |
    */

    'gdacs_enabled' => env('GDACS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Entry Conditions Configuration
    |--------------------------------------------------------------------------
    |
    | These options control the Entry Conditions (Einreisebestimmungen) feature.
    | You can enable/disable the feature entirely, enable/disable logging, and
    | specify which nationalities are available for searching (comma-separated
    | 2-letter ISO country codes).
    |
    */

    'entry_conditions_enabled' => env('ENTRY_CONDITIONS_ENABLED', true),
    'entry_conditions_logging_enabled' => env('ENTRY_CONDITIONS_LOGGING_ENABLED', false),
    'entry_conditions_available_nationalities' => env('ENTRY_CONDITIONS_AVAILABLE_NATIONALITIES', 'DE,AT,CH'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Feature Toggles
    |--------------------------------------------------------------------------
    |
    | These options control which features are enabled on the dashboard.
    |
    */

    'dashboard_airports_enabled' => env('DASHBOARD_AIRPORTS_ENABLED', true),
    'dashboard_booking_enabled' => env('DASHBOARD_BOOKING_ENABLED', true),
    'business_visa_enabled' => env('BUSINESS_VISA_ENABLED', true),
    'visumpoint_enabled' => env('VISUMPOINT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Customer Authentication
    |--------------------------------------------------------------------------
    |
    | Control whether customer registration and login buttons are visible
    | on public pages. Useful for closing registration or restricting access.
    |
    */

    'customer_registration_enabled' => env('CUSTOMER_REGISTRATION_ENABLED', true),
    'customer_login_enabled' => env('CUSTOMER_LOGIN_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Navigation Menu Configuration
    |--------------------------------------------------------------------------
    |
    | These options control which menu items are visible in the public navigation.
    | Each item can be individually enabled or disabled via environment variables.
    |
    */

    'navigation_menu_enabled' => env('NAVIGATION_MENU_ENABLED', true),
    'navigation_events_enabled' => env('NAVIGATION_EVENTS_ENABLED', true),
    'navigation_entry_conditions_enabled' => env('NAVIGATION_ENTRY_CONDITIONS_ENABLED', true),
    'navigation_booking_enabled' => env('NAVIGATION_BOOKING_ENABLED', true),
    'navigation_airports_enabled' => env('NAVIGATION_AIRPORTS_ENABLED', true),
    'navigation_branches_enabled' => env('NAVIGATION_BRANCHES_ENABLED', true),
    'navigation_my_travelers_enabled' => env('NAVIGATION_MY_TRAVELERS_ENABLED', true),
    'navigation_risk_overview_enabled' => env('NAVIGATION_RISK_OVERVIEW_ENABLED', true),
    'navigation_cruise_enabled' => env('NAVIGATION_CRUISE_ENABLED', true),
    'navigation_business_visa_enabled' => env('NAVIGATION_BUSINESS_VISA_ENABLED', true),
    'navigation_center_map_enabled' => env('NAVIGATION_CENTER_MAP_ENABLED', true),
    'navigation_visumpoint_enabled' => env('NAVIGATION_VISUMPOINT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Customer Dashboard Feature Visibility
    |--------------------------------------------------------------------------
    |
    | These options control which features are visible in the customer dashboard.
    | Each feature can be individually enabled or disabled via environment variables.
    |
    */

    'customer_dashboard_interfaces_enabled' => env('CUSTOMER_DASHBOARD_INTERFACES_ENABLED', true),
    'customer_dashboard_directory_enabled' => env('CUSTOMER_DASHBOARD_DIRECTORY_ENABLED', true),
    'customer_dashboard_branches_box_enabled' => env('CUSTOMER_DASHBOARD_BRANCHES_BOX_ENABLED', true),
    'customer_dashboard_branches_sidebar_enabled' => env('CUSTOMER_DASHBOARD_BRANCHES_SIDEBAR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | API Subdomain
    |--------------------------------------------------------------------------
    |
    | Domain for the dedicated API subdomain (e.g. api.global-travel-monitor.de).
    | When set, API routes are also available without the /api prefix.
    |
    */

    'api_domain' => env('API_DOMAIN'),

];
