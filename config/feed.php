<?php

return [
    /*
     * =================================================================
     * FEED-KATEGORIEN
     * =================================================================
     *
     * URL-Struktur: /feed/{kategorie}/...
     *
     * Aktuelle Kategorien:
     *   - events: Ereignisse (Sicherheit, Gesundheit, Reise, Umwelt, Politik)
     *   - countries: Länderverzeichnis mit Details
     *
     * Geplante Kategorien:
     *   - news: Nachrichten und Artikel
     *   - advisories: Offizielle Reisewarnungen
     *   - topics: Themenbezogene Feeds
     *
     * =================================================================
     */

    'feeds' => [
        /*
         * =================================================================
         * EVENT-FEEDS: /feed/events/...
         * =================================================================
         */

        'events_main' => [
            'items' => [\App\Models\CustomEvent::class, 'getFeedItems'],
            'url' => '/feed/events/all.atom',
            'title' => 'Risikomanagement - Alle Ereignisse',
            'description' => 'Alle aktiven Risiko-Ereignisse und Sicherheitsinformationen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'events_rss' => [
            'items' => [\App\Models\CustomEvent::class, 'getFeedItems'],
            'url' => '/feed/events/all.xml',
            'title' => 'Risikomanagement - Alle Ereignisse (RSS)',
            'description' => 'Alle aktiven Risiko-Ereignisse und Sicherheitsinformationen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        /*
         * Kritische Ereignisse
         */
        'events_critical' => [
            'items' => [\App\Models\CustomEvent::class, 'getCriticalFeedItems'],
            'url' => '/feed/events/critical.atom',
            'title' => 'Risikomanagement - Kritische Ereignisse',
            'description' => 'Hochprioritäre Risiko-Ereignisse und dringende Sicherheitswarnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'events_critical_rss' => [
            'items' => [\App\Models\CustomEvent::class, 'getCriticalFeedItems'],
            'url' => '/feed/events/critical.xml',
            'title' => 'Risikomanagement - Kritische Ereignisse (RSS)',
            'description' => 'Hochprioritäre Risiko-Ereignisse und dringende Sicherheitswarnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        /*
         * =================================================================
         * EVENT-TYP SPEZIFISCHE FEEDS: /feed/events/types/{type}.xml
         * =================================================================
         */

        'events_type_safety' => [
            'items' => [\App\Models\CustomEvent::class, 'getTypeFeedItems', 'type' => 'safety'],
            'url' => '/feed/events/types/safety',
            'title' => 'Risikomanagement - Sicherheits-Ereignisse',
            'description' => 'Sicherheitsrelevante Risiko-Ereignisse und Warnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'events_type_safety_rss' => [
            'items' => [\App\Models\CustomEvent::class, 'getTypeFeedItems', 'type' => 'safety'],
            'url' => '/feed/events/types/safety.xml',
            'title' => 'Risikomanagement - Sicherheits-Ereignisse (RSS)',
            'description' => 'Sicherheitsrelevante Risiko-Ereignisse und Warnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        'events_type_health' => [
            'items' => [\App\Models\CustomEvent::class, 'getTypeFeedItems', 'type' => 'health'],
            'url' => '/feed/events/types/health',
            'title' => 'Risikomanagement - Gesundheits-Ereignisse',
            'description' => 'Gesundheitsrelevante Risiko-Ereignisse und Informationen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'events_type_health_rss' => [
            'items' => [\App\Models\CustomEvent::class, 'getTypeFeedItems', 'type' => 'health'],
            'url' => '/feed/events/types/health.xml',
            'title' => 'Risikomanagement - Gesundheits-Ereignisse (RSS)',
            'description' => 'Gesundheitsrelevante Risiko-Ereignisse und Informationen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        'events_type_travel' => [
            'items' => [\App\Models\CustomEvent::class, 'getTypeFeedItems', 'type' => 'travel'],
            'url' => '/feed/events/types/travel',
            'title' => 'Risikomanagement - Reise-Ereignisse',
            'description' => 'Reiserelevante Risiko-Ereignisse und Reisewarnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'events_type_travel_rss' => [
            'items' => [\App\Models\CustomEvent::class, 'getTypeFeedItems', 'type' => 'travel'],
            'url' => '/feed/events/types/travel.xml',
            'title' => 'Risikomanagement - Reise-Ereignisse (RSS)',
            'description' => 'Reiserelevante Risiko-Ereignisse und Reisewarnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        'events_type_environment' => [
            'items' => [\App\Models\CustomEvent::class, 'getTypeFeedItems', 'type' => 'environment'],
            'url' => '/feed/events/types/environment',
            'title' => 'Risikomanagement - Umwelt-Ereignisse',
            'description' => 'Umweltrelevante Risiko-Ereignisse und Naturkatastrophen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'events_type_environment_rss' => [
            'items' => [\App\Models\CustomEvent::class, 'getTypeFeedItems', 'type' => 'environment'],
            'url' => '/feed/events/types/environment.xml',
            'title' => 'Risikomanagement - Umwelt-Ereignisse (RSS)',
            'description' => 'Umweltrelevante Risiko-Ereignisse und Naturkatastrophen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        'events_type_political' => [
            'items' => [\App\Models\CustomEvent::class, 'getTypeFeedItems', 'type' => 'political'],
            'url' => '/feed/events/types/political',
            'title' => 'Risikomanagement - Politik-Ereignisse',
            'description' => 'Politisch relevante Risiko-Ereignisse und Entwicklungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'events_type_political_rss' => [
            'items' => [\App\Models\CustomEvent::class, 'getTypeFeedItems', 'type' => 'political'],
            'url' => '/feed/events/types/political.xml',
            'title' => 'Risikomanagement - Politik-Ereignisse (RSS)',
            'description' => 'Politisch relevante Risiko-Ereignisse und Entwicklungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        /*
         * =================================================================
         * DYNAMISCHE EVENT-FEEDS (Route-Parameter basiert)
         * =================================================================
         *
         * Länderspezifische Event-Feeds: /feed/events/countries/{code}.xml
         * Beispiele:
         *   - /feed/events/countries/de.xml (Ereignisse in Deutschland)
         *   - /feed/events/countries/fr.xml (Ereignisse in Frankreich)
         *
         * Regionsspezifische Feeds: /feed/events/regions/{id}.xml
         *
         * =================================================================
         */

        /*
         * =================================================================
         * COUNTRY-FEEDS: /feed/countries/...
         * =================================================================
         * Länderverzeichnis mit Details (Name, ISO-Codes, EU, Schengen,
         * Kontinent, Währung, Vorwahl, Hauptstadt mit Koordinaten)
         */

        'countries_all' => [
            'url' => '/feed/countries/names/all.xml',
            'title' => 'Länderverzeichnis - Alle Länder',
            'description' => 'Vollständiges Verzeichnis aller Länder mit Details',
            'language' => 'de-DE',
            'format' => 'rss',
        ],

        'countries_eu' => [
            'url' => '/feed/countries/eu.xml',
            'title' => 'Länderverzeichnis - EU-Mitgliedsstaaten',
            'description' => 'Alle Mitgliedsstaaten der Europäischen Union',
            'language' => 'de-DE',
            'format' => 'rss',
        ],

        'countries_schengen' => [
            'url' => '/feed/countries/schengen.xml',
            'title' => 'Länderverzeichnis - Schengen-Staaten',
            'description' => 'Alle Mitgliedsstaaten des Schengen-Raums',
            'language' => 'de-DE',
            'format' => 'rss',
        ],

        /*
         * Dynamische Kontinent-Feeds: /feed/countries/continent/{code}.xml
         * Beispiele:
         *   - /feed/countries/continent/EU.xml (Europa)
         *   - /feed/countries/continent/AS.xml (Asien)
         *   - /feed/countries/continent/AF.xml (Afrika)
         *   - /feed/countries/continent/NA.xml (Nordamerika)
         *   - /feed/countries/continent/SA.xml (Südamerika)
         *   - /feed/countries/continent/OC.xml (Ozeanien)
         *   - /feed/countries/continent/AN.xml (Antarktis)
         */
    ],

    /*
     * =================================================================
     * CACHE-KONFIGURATION
     * =================================================================
     * Cache-Dauer für Feed-Generierung in Sekunden.
     * Kann über .env konfiguriert werden: FEED_CACHE_DURATION=3600
     * Setze auf 0 um Caching zu deaktivieren (nützlich für Entwicklung).
     *
     * Default: 3600 (1 Stunde)
     */
    'cache_duration' => (int) env('FEED_CACHE_DURATION', 3600),

    /*
     * Maximale Anzahl von Items pro Feed.
     * Kann über .env konfiguriert werden: FEED_MAX_ITEMS=100
     */
    'max_items' => (int) env('FEED_MAX_ITEMS', 100),

    /*
     * =================================================================
     * DEBUG PANEL
     * =================================================================
     * Kommaseparierte E-Mail-Adressen, die das Debug-Panel auf
     * Visum- und Risk-Overview-Seiten sehen können.
     * .env: DEBUG_EMAILS=user@example.com,admin@example.com
     */
    'debug_emails' => array_filter(array_map('trim', explode(',', env('DEBUG_EMAILS', '')))),

    /**
     * Globaler An-/Aus-Schalter fuer das Debug-Panel (fuer ALLE Nutzer),
     * unabhaengig von debug_emails.
     * .env: TRAVEL_ALERT_DEBUG=true
     */
    'debug_enabled' => filter_var(env('TRAVEL_ALERT_DEBUG', false), FILTER_VALIDATE_BOOLEAN),

    /**
     * Globaler Schalter, um den Dashboard-Bereich "Profil vervollstaendigen"
     * fuer ALLE Nutzer auszublenden (zusaetzlich zum per-Kunde-Flag).
     * .env: HIDE_PROFILE_COMPLETION=true
     */
    'hide_profile_completion' => filter_var(env('HIDE_PROFILE_COMPLETION', false), FILTER_VALIDATE_BOOLEAN),

    /**
     * Dashboard-Bloecke ein-/ausblenden (true = anzeigen).
     * .env: DASHBOARD_STATUS_ENABLED / DASHBOARD_KONTO_FUNKTIONEN_ENABLED
     */
    'dashboard_status_enabled' => filter_var(env('DASHBOARD_STATUS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'dashboard_konto_funktionen_enabled' => filter_var(env('DASHBOARD_KONTO_FUNKTIONEN_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /**
     * Firmen-Boxen auf dem Dashboard (Firmenadresse / Rechnungsadresse /
     * Filialen & Standorte) – werden ohnehin nur Firmenkunden angezeigt.
     * .env: DASHBOARD_BUSINESS_BOXES_ENABLED
     */
    'dashboard_business_boxes_enabled' => filter_var(env('DASHBOARD_BUSINESS_BOXES_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
