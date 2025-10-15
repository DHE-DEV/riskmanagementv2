<?php

return [
    'feeds' => [
        /*
         * =================================================================
         * HAUPT-FEEDS - Alle aktiven Events
         * =================================================================
         */

        'main' => [
            'items' => [\App\Models\Event::class, 'getFeedItems'],
            'url' => '/feeds/events',
            'title' => 'Risikomanagement - Alle Events',
            'description' => 'Alle aktiven Risiko-Events und Sicherheitsinformationen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'rss' => [
            'items' => [\App\Models\Event::class, 'getFeedItems'],
            'url' => '/feeds/events.rss',
            'title' => 'Risikomanagement - Alle Events (RSS)',
            'description' => 'Alle aktiven Risiko-Events und Sicherheitsinformationen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        /*
         * =================================================================
         * KRITISCHE EVENTS - Hohe Priorität
         * =================================================================
         */

        'critical' => [
            'items' => [\App\Models\Event::class, 'getCriticalFeedItems'],
            'url' => '/feeds/events/critical',
            'title' => 'Risikomanagement - Kritische Events',
            'description' => 'Hochprioritäre Risiko-Events und dringende Sicherheitswarnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'critical_rss' => [
            'items' => [\App\Models\Event::class, 'getCriticalFeedItems'],
            'url' => '/feeds/events/critical.rss',
            'title' => 'Risikomanagement - Kritische Events (RSS)',
            'description' => 'Hochprioritäre Risiko-Events und dringende Sicherheitswarnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        /*
         * =================================================================
         * EVENT-TYP SPEZIFISCHE FEEDS
         * =================================================================
         */

        'type_safety' => [
            'items' => [\App\Models\Event::class, 'getTypeFeedItems', 'type' => 'safety'],
            'url' => '/feeds/events/type/safety',
            'title' => 'Risikomanagement - Sicherheits-Events',
            'description' => 'Sicherheitsrelevante Risiko-Events und Warnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'type_safety_rss' => [
            'items' => [\App\Models\Event::class, 'getTypeFeedItems', 'type' => 'safety'],
            'url' => '/feeds/events/type/safety.rss',
            'title' => 'Risikomanagement - Sicherheits-Events (RSS)',
            'description' => 'Sicherheitsrelevante Risiko-Events und Warnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        'type_health' => [
            'items' => [\App\Models\Event::class, 'getTypeFeedItems', 'type' => 'health'],
            'url' => '/feeds/events/type/health',
            'title' => 'Risikomanagement - Gesundheits-Events',
            'description' => 'Gesundheitsrelevante Risiko-Events und Informationen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'type_health_rss' => [
            'items' => [\App\Models\Event::class, 'getTypeFeedItems', 'type' => 'health'],
            'url' => '/feeds/events/type/health.rss',
            'title' => 'Risikomanagement - Gesundheits-Events (RSS)',
            'description' => 'Gesundheitsrelevante Risiko-Events und Informationen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        'type_travel' => [
            'items' => [\App\Models\Event::class, 'getTypeFeedItems', 'type' => 'travel'],
            'url' => '/feeds/events/type/travel',
            'title' => 'Risikomanagement - Reise-Events',
            'description' => 'Reiserelevante Risiko-Events und Reisewarnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'type_travel_rss' => [
            'items' => [\App\Models\Event::class, 'getTypeFeedItems', 'type' => 'travel'],
            'url' => '/feeds/events/type/travel.rss',
            'title' => 'Risikomanagement - Reise-Events (RSS)',
            'description' => 'Reiserelevante Risiko-Events und Reisewarnungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        'type_environment' => [
            'items' => [\App\Models\Event::class, 'getTypeFeedItems', 'type' => 'environment'],
            'url' => '/feeds/events/type/environment',
            'title' => 'Risikomanagement - Umwelt-Events',
            'description' => 'Umweltrelevante Risiko-Events und Naturkatastrophen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'type_environment_rss' => [
            'items' => [\App\Models\Event::class, 'getTypeFeedItems', 'type' => 'environment'],
            'url' => '/feeds/events/type/environment.rss',
            'title' => 'Risikomanagement - Umwelt-Events (RSS)',
            'description' => 'Umweltrelevante Risiko-Events und Naturkatastrophen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        'type_political' => [
            'items' => [\App\Models\Event::class, 'getTypeFeedItems', 'type' => 'political'],
            'url' => '/feeds/events/type/political',
            'title' => 'Risikomanagement - Politik-Events',
            'description' => 'Politisch relevante Risiko-Events und Entwicklungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'atom',
            'view' => 'feed::atom',
            'type' => '',
            'contentType' => '',
        ],

        'type_political_rss' => [
            'items' => [\App\Models\Event::class, 'getTypeFeedItems', 'type' => 'political'],
            'url' => '/feeds/events/type/political.rss',
            'title' => 'Risikomanagement - Politik-Events (RSS)',
            'description' => 'Politisch relevante Risiko-Events und Entwicklungen',
            'language' => 'de-DE',
            'image' => '',
            'format' => 'rss',
            'view' => 'feed::rss',
            'type' => '',
            'contentType' => '',
        ],

        /*
         * =================================================================
         * HINWEIS: Länderspezifische Feeds (Dynamisch)
         * =================================================================
         *
         * Länderspezifische Feeds werden dynamisch über Route-Parameter generiert.
         *
         * Implementierung in routes/web.php erforderlich:
         *
         * Route::get('/feeds/events/country/{countryCode}', function($countryCode) {
         *     $config = [
         *         'items' => [\App\Models\Event::class, 'getCountryFeedItems', 'countryCode' => $countryCode],
         *         'url' => "/feeds/events/country/{$countryCode}",
         *         'title' => "Risikomanagement - Events für " . strtoupper($countryCode),
         *         'description' => "Risiko-Events spezifisch für " . strtoupper($countryCode),
         *         'language' => 'de-DE',
         *         'format' => 'atom',
         *         'view' => 'feed::atom',
         *     ];
         *     return response()->view($config['view'], ['config' => $config])
         *                      ->header('Content-Type', 'application/atom+xml');
         * });
         *
         * URL-Pattern: /feeds/events/country/{countryCode}
         * Beispiele:
         *   - /feeds/events/country/de (Deutschland)
         *   - /feeds/events/country/fr (Frankreich)
         *   - /feeds/events/country/us (USA)
         */
    ],

    /*
     * =================================================================
     * CACHE-KONFIGURATION
     * =================================================================
     * Cache-Dauer für Feed-Generierung: 1 Stunde (3600 Sekunden)
     */
    'cache_duration' => 3600,
];
