<!DOCTYPE html>
<html lang="{{ request()->query('lang', 'de') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Global Travel Monitor - Events')</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet CSS -->
    @stack('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <!-- Font Awesome -->
    @php
        $faKit = config('services.fontawesome.kit');
        $faFallback = file_exists(public_path('vendor/fontawesome/css/all.min.css'))
            ? asset('vendor/fontawesome/css/all.min.css')
            : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
    @endphp
    @if(!empty($faKit))
        <script src="https://kit.fontawesome.com/{{ e($faKit) }}.js" crossorigin="anonymous" onload="window.__faKitOk=true" onerror="window.__faKitOk=false"></script>
        <script>
        (function(){
            function addCss(href){
                var l=document.createElement('link'); l.rel='stylesheet'; l.href=href; document.head.appendChild(l);
            }
            var fallbackHref = "{{ $faFallback }}";
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

    <style>
        /* Embed Layout - Full viewport usage */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f9fafb;
            overflow: hidden;
        }

        .embed-container {
            width: 100%;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        .embed-content {
            width: 100%;
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Powered by badge */
        .powered-by {
            position: fixed;
            background: rgba(255, 255, 255, 0.95);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            color: #6b7280;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            z-index: 9999;
            text-decoration: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .powered-by.top-right {
            top: 12px;
            right: 12px;
        }

        .powered-by.bottom-right {
            bottom: 8px;
            right: 8px;
        }

        .powered-by:hover {
            background: white;
            color: #374151;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .powered-by img {
            height: 18px;
            width: auto;
        }

        @yield('additional-styles')
    </style>

    @stack('head-scripts')
</head>
<body>
    <div class="embed-container">
        <div class="embed-content">
            @yield('content')
        </div>

        <!-- Powered by badge -->
        @if(!request()->query('hide_badge'))
        <a href="https://global-travel-monitor.eu" target="_blank" rel="noopener" class="powered-by @yield('badge_position', 'bottom-right')">
            <img src="{{ asset('favicon-32x32.png') }}" alt="GTM">
            <span>Powered by <strong>Global Travel Monitor</strong></span>
        </a>
        @endif
    </div>

    @stack('scripts')
</body>
</html>
