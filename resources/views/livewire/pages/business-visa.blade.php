<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Business Visum - Global Travel Monitor</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Einbindung -->
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
        /* Basis-Layout */
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Header - feststehend */
        .header {
            flex-shrink: 0;
            height: 64px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            z-index: 10000;
        }

        /* Footer - feststehend */
        .footer {
            flex-shrink: 0;
            height: 32px;
            background: white;
            color: black;
            z-index: 9999;
            border-top: 1px solid #e5e7eb;
        }

        /* Hauptbereich - dynamisch */
        .main-content {
            flex: 1;
            display: flex;
            min-height: 0;
        }

        /* Navigation - feste Breite */
        .navigation {
            flex-shrink: 0;
            width: 64px;
            background: black;
        }

        /* Content Area */
        .content-area {
            flex: 1;
            overflow-y: auto;
            background: #f3f4f6;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <x-public-header />

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Black Navigation Sidebar -->
            <x-public-navigation active="business-visa" />

            <!-- Content Area -->
            <div class="content-area">
                <div class="max-w-4xl mx-auto py-8 px-4">
                    <!-- Page Header -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fa-regular fa-id-card text-blue-600 text-2xl"></i>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Business Visum</h1>
                                <p class="text-gray-600">Informationen und Services rund um Geschäftsvisa</p>
                            </div>
                        </div>
                    </div>

                    <!-- Coming Soon -->
                    <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-regular fa-clock text-gray-400 text-3xl"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">Demnächst verfügbar</h2>
                        <p class="text-gray-600 max-w-md mx-auto">
                            Diese Seite wird derzeit vorbereitet. Hier finden Sie bald umfassende Informationen
                            und Services rund um Business Visa für Ihre Geschäftsreisen.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <x-public-footer />
    </div>
</body>
</html>
