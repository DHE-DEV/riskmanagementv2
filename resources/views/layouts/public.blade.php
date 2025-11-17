<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title>@yield('title', 'Global Travel Monitor - Weltweites Reiserisiko-Monitoring & Sicherheitsinformationen')</title>
    <meta name="description" content="@yield('description', 'Global Travel Monitor bietet Echtzeit-Informationen zu weltweiten Reiserisiken, Sicherheitswarnungen und Ereignissen. Umfassende Länder-Risikoanalysen, Destination Manager und Live-Statistiken für sicheres Reisen.')">
    <meta name="keywords" content="@yield('keywords', 'Reiserisiko, Travel Risk Management, Destination Manager, Länderrisiken, Sicherheitswarnungen, Business Travel, Reisesicherheit, Risk Map, Krisenmanagement, Weltweite Ereignisse')">
    <meta name="author" content="Global Travel Monitor">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <!-- Open Graph Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Global Travel Monitor">
    <meta property="og:title" content="@yield('og_title', 'Global Travel Monitor - Weltweites Reiserisiko-Monitoring')">
    <meta property="og:description" content="@yield('og_description', 'Echtzeit-Informationen zu weltweiten Reiserisiken, Sicherheitswarnungen und Ereignissen für sicheres Reisen.')">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:image" content="@yield('og_image', asset('android-chrome-192x192.png'))">
    <meta property="og:locale" content="de_DE">
    <meta property="og:locale:alternate" content="en_US">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'Global Travel Monitor - Weltweites Reiserisiko-Monitoring')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Echtzeit-Informationen zu weltweiten Reiserisiken und Sicherheitswarnungen.')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('android-chrome-192x192.png'))">

    <!-- Language Alternates -->
    <link rel="alternate" hreflang="de" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="en" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet CSS (if needed) -->
    @stack('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <!-- Font Awesome -->
    @php($faKit = config('services.fontawesome.kit'))
    @if(!empty($faKit))
        <script src="https://kit.fontawesome.com/{{ e($faKit) }}.js" crossorigin="anonymous" onload="window.__faKitOk=true" onerror="window.__faKitOk=false"></script>
        <script>
        (function(){
            function addCss(href){
                var l=document.createElement('link'); l.rel='stylesheet'; l.href=href; document.head.appendChild(l);
            }
            var fallbackHref = '{{ file_exists(public_path('vendor/fontawesome/css/all.min.css')) ? asset('vendor/fontawesome/css/all.min.css') : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' }}';
            window.addEventListener('DOMContentLoaded', function(){
                setTimeout(function(){ if(!window.__faKitOk){ addCss(fallbackHref); } }, 800);
            });
        })();
        </script>
    @elseif (file_exists(public_path('vendor/fontawesome/css/all.min.css')))
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" />
    @else
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    @endif

    {{-- RSS/Atom Feed Discovery --}}
    <link rel="alternate" type="application/rss+xml" title="All Events (RSS)" href="{{ route('feed.events.rss') }}">
    <link rel="alternate" type="application/atom+xml" title="All Events (Atom)" href="{{ route('feed.events.atom') }}">
    <link rel="alternate" type="application/rss+xml" title="Critical Events (RSS)" href="{{ route('feed.critical') }}">

    <style>
        /* Basis-Layout */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f9fafb;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            z-index: 10000;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: white;
            color: black;
            z-index: 9999;
            border-top: 1px solid #e5e7eb;
        }

        .navigation {
            position: fixed;
            left: 0;
            top: 64px;
            bottom: 56px;
            width: 64px;
            background: black;
            color: white;
            z-index: 10;
        }

        .main-content {
            margin-top: 64px;
            margin-left: 64px;
            margin-bottom: 56px;
            height: calc(100vh - 120px);
            overflow-y: auto;
            position: relative;
            z-index: 10;
        }

        @yield('additional-styles')
    </style>

    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "Organization",
                "@id": "{{ url('/') }}#organization",
                "name": "Global Travel Monitor",
                "url": "{{ url('/') }}",
                "logo": {
                    "@type": "ImageObject",
                    "url": "{{ asset('android-chrome-192x192.png') }}",
                    "width": 192,
                    "height": 192
                },
                "sameAs": []
            },
            {
                "@type": "WebSite",
                "@id": "{{ url('/') }}#website",
                "url": "{{ url('/') }}",
                "name": "Global Travel Monitor",
                "description": "Weltweites Reiserisiko-Monitoring & Sicherheitsinformationen",
                "publisher": {
                    "@id": "{{ url('/') }}#organization"
                },
                "potentialAction": {
                    "@type": "SearchAction",
                    "target": {
                        "@type": "EntryPoint",
                        "urlTemplate": "{{ url('/') }}?search={search_term_string}"
                    },
                    "query-input": "required name=search_term_string"
                },
                "inLanguage": "de-DE"
            },
            {
                "@type": "WebPage",
                "@id": "{{ url()->current() }}#webpage",
                "url": "{{ url()->current() }}",
                "name": "@yield('title', 'Global Travel Monitor - Weltweites Reiserisiko-Monitoring & Sicherheitsinformationen')",
                "isPartOf": {
                    "@id": "{{ url('/') }}#website"
                },
                "about": {
                    "@id": "{{ url('/') }}#organization"
                },
                "description": "@yield('description', 'Global Travel Monitor bietet Echtzeit-Informationen zu weltweiten Reiserisiken, Sicherheitswarnungen und Ereignissen.')",
                "inLanguage": "de-DE"
            }
        ]
    }
    </script>

    @stack('head-scripts')
</head>
<body>
<div class="app-container">
    <!-- Fixed Header -->
    <x-public-header />

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Black Navigation Bar -->
        <x-public-navigation :active="$active ?? 'dashboard'" />

        <!-- Page Content -->
        @yield('content')
    </div>

    <!-- Footer -->
    <x-public-footer />
</div>

@stack('scripts')
</body>
</html>
