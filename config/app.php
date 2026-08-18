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
    | Event Translations (Mehrsprachige Events)
    |--------------------------------------------------------------------------
    |
    | Steuert, in welche Sprachen Event-Titel und -Beschreibungen übersetzt
    | werden können. EVENT_LANGUAGES ist eine kommagetrennte Liste von
    | Sprach-/Länderkürzeln (2-stellige ISO-Codes, z. B. "de,en,nl").
    | EVENT_SOURCE_LANGUAGE ist die Ausgangssprache, aus der DeepL übersetzt
    | und auf die alle Anzeigen zurückfallen, wenn keine Übersetzung existiert.
    |
    */

    'event_languages' => env('EVENT_LANGUAGES', 'de,en,nl'),
    'event_source_language' => env('EVENT_SOURCE_LANGUAGE', 'de'),

    // Sichtbarer Sprachumschalter im Frontend-Header (an/aus per .env).
    'event_language_switcher_enabled' => env('EVENT_LANGUAGE_SWITCHER_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Feature Toggles
    |--------------------------------------------------------------------------
    |
    | These options control which features are enabled on the dashboard.
    |
    */

    'dashboard_airports_enabled' => env('DASHBOARD_AIRPORTS_ENABLED', true),

    // Abschnitt "Mobilität & Transport" im Flughafen-Detailfenster (/airports)
    'airports_mobility_enabled' => env('AIRPORTS_MOBILITY_ENABLED', false),

    // Doctors Network (myBakup) in der linken Navigationsleiste
    'navigation_doctors_network_enabled' => env('NAVIGATION_DOCTORS_NETWORK_ENABLED', true),

    // Datums-Badge auf den Event-Karten in der Sidebar des Global Travel Monitor
    'dashboard_event_date_badge_enabled' => env('DASHBOARD_EVENT_DATE_BADGE_ENABLED', true),

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

    // Self-Registrierung standardmaessig deaktiviert: Kunden entstehen ueber SSO
    // (JIT-Provisioning). Per CUSTOMER_REGISTRATION_ENABLED=true reaktivierbar.
    'customer_registration_enabled' => env('CUSTOMER_REGISTRATION_ENABLED', false),
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

    'navigation_hamburger_enabled' => env('NAVIGATION_HAMBURGER_ENABLED', true),
    // Optionale externe URL fuer das 1. Symbol (Travel Requirements Service).
    // Wenn gesetzt, oeffnet das Symbol diese URL in einem neuen Tab (_blank),
    // sonst wird die interne Route travel-requirements-service verwendet.
    'navigation_trs_external_url' => env('NAVIGATION_TRS_EXTERNAL_URL'),
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
    'navigation_travel_data_enabled' => env('NAVIGATION_TRAVEL_DATA_ENABLED', true),
    'navigation_travel_links_enabled' => env('NAVIGATION_TRAVEL_LINKS_ENABLED', true),
    'navigation_customer_events_enabled' => env('NAVIGATION_CUSTOMER_EVENTS_ENABLED', true),
    'navigation_visumpoint_enabled' => env('NAVIGATION_VISUMPOINT_ENABLED', false),
    'navigation_guest_auth_enabled' => env('NAVIGATION_GUEST_AUTH_ENABLED', true),
    'navigation_bottom_buttons_enabled' => env('NAVIGATION_BOTTOM_BUTTONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | TravelAlert Preishinweis
    |--------------------------------------------------------------------------
    |
    | Hinweis zur Gratisphase auf der TravelAlert-Promoseite (Bestellmodal,
    | Zusammenfassung und Structured Data). Einfaches HTML ist erlaubt.
    | Ist der Wert leer, entfaellt der Hinweis komplett – ohne Leerzeile und
    | ohne Kostenlos-Angebot in den Structured Data. Die Preisangaben selbst
    | bleiben davon unberuehrt.
    |
    */

    'travel_alert_price_notice' => env('TRAVEL_ALERT_PRICE_NOTICE', 'Die Zusatzleistung Travel<span class="text-[#cee741]">Alert</span> wird <strong>bis zum 30.06.2026 kostenlos</strong> zur Verfügung gestellt. In diesem Zeitraum kann jederzeit per Mail an <a href="mailto:info@passolution.de" class="text-blue-600 underline">info@passolution.de</a> der Vertrag gekündigt werden.'),

    /*
    |--------------------------------------------------------------------------
    | TravelAlert Bestellfreigabe
    |--------------------------------------------------------------------------
    |
    | Jede Bestellung muss der Kunde per Mail bestaetigen. Was danach passiert,
    | steuert TRAVEL_ALERT_AUTO_ACTIVATION:
    |
    |   true  – der Zugang ist mit der Bestaetigung sofort freigeschaltet
    |   false – ein Mitarbeiter schaltet die Bestellung im Backend frei
    |
    | TRAVEL_ALERT_CONFIRMATION_EXPIRE_DAYS bestimmt, wie lange der Link aus
    | der Bestaetigungsmail gueltig bleibt.
    |
    */

    'travel_alert_auto_activation' => env('TRAVEL_ALERT_AUTO_ACTIVATION', true),
    'travel_alert_confirmation_expire_days' => env('TRAVEL_ALERT_CONFIRMATION_EXPIRE_DAYS', 7),

    // Ziel des "Kunde im Admin-Bereich oeffnen"-Links in der internen Bestellmail.
    // Leer  -> lokaler Filament-Bereich (url('/admin/customers/{id}')).
    // Gesetzt: entweder eine Basis-URL (die Kunden-ID wird angehaengt) oder eine
    // Vorlage mit dem Platzhalter {id}, z. B.
    //   TRAVEL_ALERT_ADMIN_CUSTOMER_URL=https://admin.example.com/customers
    //   TRAVEL_ALERT_ADMIN_CUSTOMER_URL=https://admin.example.com/kunde?id={id}
    'travel_alert_admin_customer_url' => env('TRAVEL_ALERT_ADMIN_CUSTOMER_URL'),

    // Ziel der "Jetzt bestellen"-Buttons auf der TravelAlert-Promoseite.
    // Leer     -> das eingebaute Bestell-Popup wird geoeffnet (Standard).
    // Gesetzt  -> die URL wird stattdessen in einem neuen Tab geoeffnet, z. B.
    //   TRAVEL_ALERT_ORDER_URL=https://platform.passolution.de/travel-alert?user-state=logged-in
    'travel_alert_order_url' => env('TRAVEL_ALERT_ORDER_URL'),

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

    'customer_product_tours_enabled' => env('CUSTOMER_PRODUCT_TOURS_ENABLED', true),
    'customer_notifications_enabled' => env('CUSTOMER_NOTIFICATIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | API Subdomain
    |--------------------------------------------------------------------------
    |
    | Domain for the dedicated API subdomain (e.g. api.global-travel-monitor.de).
    | When set, API routes are also available without the /api prefix.
    |
    */

    'api_domain' => env('API_DOMAIN', 'api.global-travel-monitor.de'),

    /*
    |--------------------------------------------------------------------------
    | Super Admin Emails
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of customer email addresses that are allowed to
    | switch to any customer account without explicit access grants.
    |
    */

    'agentur_super_admin_emails' => array_filter(array_map('trim', explode(',', env('AGENTUR_SUPER_ADMIN_EMAILS', '')))),

];
