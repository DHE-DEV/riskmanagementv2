<!DOCTYPE html>
<html lang="{{ request()->query('lang', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    {{-- Links/Formulare innerhalb der Chrome sollen das umgebende Fenster
         (platform.passolution.de) navigieren, nicht das schmale iframe. --}}
    <base target="_top">

    <title>@yield('title', 'Passolution Travel Information Platform')</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    @php
        $faKit = config('services.fontawesome.kit');
        $faFallback = file_exists(public_path('vendor/fontawesome/css/all.min.css'))
            ? asset('vendor/fontawesome/css/all.min.css')
            : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css';
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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
    @endif

    <style>
        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: transparent;
            overflow: hidden;
        }

        /*
         * In der normalen App sind diese Elemente per layouts/public.blade.php
         * fixed positioniert. Als iframe-Fragment sollen sie das iframe fuellen,
         * dessen Groesse die einbettende Seite vorgibt.
         */
        .header {
            position: relative;
            width: 100%;
            height: 64px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .footer {
            position: relative;
            width: 100%;
            height: 56px;
            background: #fff;
            color: #000;
            border-top: 1px solid #e5e7eb;
        }

        .navigation {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 100vh;
            background: #000;
            color: #fff;
        }

        @yield('fragment-styles')
    </style>

    @stack('head-scripts')
</head>
<body>
    @yield('content')

    @stack('scripts')
</body>
</html>
