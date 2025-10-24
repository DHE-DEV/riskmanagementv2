<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Global Travel Monitor')</title>

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
            width: 80px;
            background: black;
            color: white;
            z-index: 10;
        }

        .main-content {
            margin-top: 64px;
            margin-left: 80px;
            margin-bottom: 56px;
            height: calc(100vh - 120px);
            overflow-y: auto;
            position: relative;
            z-index: 10;
        }

        @yield('additional-styles')
    </style>

    @stack('head-scripts')
</head>
<body>
<div class="app-container">
    <!-- Fixed Header -->
    <x-public-header />

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Black Navigation Bar -->
        <nav class="navigation flex flex-col items-center py-4 space-y-3">
            <a href="{{ route('home') }}" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Dashboard">
                <i class="fas fa-globe text-xl"></i>
            </a>
            @if(config('app.entry_conditions_enabled', true))
                <a href="{{ route('entry-conditions') }}" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Einreisebestimmungen">
                    <i class="fas fa-passport text-xl"></i>
                </a>
            @endif
            @if(config('app.dashboard_booking_enabled', true))
                <a href="{{ route('booking') }}" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Buchung">
                    <i class="fas fa-calendar-check text-xl"></i>
                </a>
            @endif
        </nav>

        <!-- Page Content -->
        @yield('content')
    </div>

    <!-- Footer -->
    <x-public-footer />
</div>

@stack('scripts')
</body>
</html>
