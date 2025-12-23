<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Customer Dashboard - Global Travel Monitor')</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome Einbindung: 1) Kit per .env (bevorzugt), 2) lokal (Zip entpackt), 3) CDN-Fallback -->
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

    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: white;
            color: black;
            z-index: 9999;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
    </style>

    @stack('styles')
</head>
<body class="bg-gray-100">
    <!-- Header -->
    @include('components.public-header')

    <!-- Black Navigation Bar -->
    <x-public-navigation :active="$active ?? 'dashboard'" />

    <!-- Main Content -->
    <div class="main-content">
        @yield('content')
    </div>

    <!-- Footer -->
    @include('components.public-footer')

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('scripts')
</body>
</html>
