<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Travel Requirements Service - Travel Information Platform</title>

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
    @php
        $faKit = config('services.fontawesome.kit');
    @endphp
    @if(!empty($faKit))
        <script src="https://kit.fontawesome.com/{{ e($faKit) }}.js" crossorigin="anonymous" onload="window.__faKitOk=true" onerror="window.__faKitOk=false"></script>
        <script>
        (function(){
            function addCss(href){
                var l=document.createElement('link'); l.rel='stylesheet'; l.href=href; document.head.appendChild(l);
            }
            var fallbackHref = '{{ file_exists(public_path('vendor/fontawesome/css/all.min.css')) ? asset('vendor/fontawesome/css/all.min.css') : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css' }}';
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

        .header {
            flex-shrink: 0;
            height: 64px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            z-index: 10000;
        }

        .footer {
            flex-shrink: 0;
            height: 32px;
            background: white;
            color: black;
            z-index: 9999;
            border-top: 1px solid #e5e7eb;
        }

        .main-content {
            flex: 1;
            display: flex;
            min-height: 0;
        }

        .navigation {
            flex-shrink: 0;
            width: 64px;
            background: black;
        }

        .content-area {
            flex: 1;
            position: relative;
            height: 100%;
            overflow: hidden;
        }

        .content-area iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <x-public-header />

        @auth('customer')
            <x-page-tour
                tourKey="trs"
                tourLabel="Travel Requirements Service"
                tourIcon="fa-regular fa-browser"
                :steps='json_encode([
                    ["target" => ".navigation", "title" => "Seitenleiste", "description" => "In der Seitenleiste finden Sie Länderauswahl und Filteroptionen, um schnell die gewünschten Einreisebestimmungen und Reiseanforderungen zu finden."],
                    ["target" => ".content-area", "title" => "Inhaltsbereich", "description" => "Im Hauptbereich werden die detaillierten Reiseanforderungen für das ausgewählte Land angezeigt. Hier finden Sie Pass- und Visabestimmungen, Gesundheitsvorschriften und weitere wichtige Informationen.", "forceBelow" => true],
                ])'
            />
        @endauth

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Black Navigation Sidebar -->
            <x-public-navigation active="travel-requirements-service" />

            <!-- Content: Travel Requirements Service -->
            <div class="content-area">
                @php
                    $iframeWebUrl = rtrim(config('services.passolution.web_url'), '/');
                    $iframeSecret = config('services.passolution.iframe_sso_secret');
                    // auth('customer')->user() kann (bei Employee- oder Provider-ID-Mapping)
                    // der Firmen-Datensatz sein. Echte Anmelde-Identität ergibt sich aus:
                    //   1. session('keycloak_email')              – aus KeycloakAuthController
                    //   2. session('logged_in_employee_email')    – Employee-Pfad
                    //   3. Customer-Mail                          – Fallback
                    $iframeEmail = session('keycloak_email')
                        ?: session('logged_in_employee_email')
                        ?: optional(auth('customer')->user())->email;

                    // Optionale UI-Hide/Locale-Parameter aus PASSOLUTION_IFRAME_UI_PARAMS
                    // (vorformatierter Query-String, z. B. "menu-hide=gtm,hc,is&ui-hide=is")
                    $iframeUiParams = ltrim((string) config('services.passolution.iframe_ui_params'), '?&');
                    // Anonymer Startbildschirm der Homepage (inkl. UI-Parameter).
                    $iframeAnonRoot = $iframeWebUrl.($iframeUiParams !== '' ? '?'.$iframeUiParams : '');

                    if ($iframeEmail && $iframeSecret) {
                        // Optionaler SSO-Handshake: nur wenn ein IFRAME_SSO_SECRET gesetzt
                        // ist UND die Gegenseite (pds-homepage) denselben Wert kennt, wird
                        // der eingeloggte Plattform-User per signiertem Token in den iframe
                        // durchgereicht. Ist KEIN Secret gesetzt, gilt die Cookie-Loesung
                        // unten (Standard).
                        $token = \App\Services\IframeAuthToken::create($iframeEmail, $iframeSecret);
                        // WICHTIG: UI-Parameter (menu-hide/ui-hide) ins return_to legen,
                        // NICHT direkt an sso/check haengen – sso/check verwirft seine
                        // eigene Query beim Redirect aufs Ziel. Ueber return_to landen sie
                        // auf der Zielseite und werden dort ausgewertet (+ in der Session
                        // gemerkt).
                        $iframeSrc = $iframeWebUrl.'/auth/sso/check?'.http_build_query([
                            'token' => $token,
                            'return_to' => $iframeAnonRoot,
                        ]);
                    } else {
                        // Standard (ohne IFRAME_SSO_SECRET): Homepage DIREKT einbetten.
                        // embed.<homepage> teilt sich die Session-Cookies mit der homepage-
                        // Domain; der iframe loggt den User also selbst ein bzw. zeigt ihn
                        // anonym – je nach vorhandenem Homepage-Cookie. KEIN aktives
                        // /auth/sso/logout mehr, das wuerde diese geteilte Session bei jedem
                        // Seitenaufruf wieder beenden.
                        $iframeSrc = $iframeAnonRoot;
                    }
                @endphp
                <iframe
                    src="{{ $iframeSrc }}"
                    allow="geolocation; clipboard-write; storage-access"
                    sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox allow-top-navigation allow-top-navigation-by-user-activation allow-modals"
                    loading="lazy"
                ></iframe>
            </div>
        </div>

        <!-- Footer -->
        <x-public-footer />
    </div>

    {{-- iframe-Login-Bridge: pds-homepage signalisiert per postMessage, wenn ein Login gebraucht wird.
         Parent navigiert dann das Top-Window zur Customer-Login-Seite und kehrt nach erfolgreichem
         Login hierher zurueck (Keycloak verweigert frame-ancestors, daher kein Login im iframe). --}}
    <script>
    (function () {
        const TRUSTED_ORIGIN = @json(rtrim(config('services.passolution.web_url'), '/'));
        const LOGIN_URL      = @json(route('auth.keycloak.start'));
        const RETURN_URL     = @json(route('travel-requirements-service'));

        window.addEventListener('message', function (event) {
            if (event.origin !== TRUSTED_ORIGIN) return;
            const data = event.data;
            if (!data || typeof data !== 'object') return;

            if (data.type === 'pds-login-required') {
                window.location.href = LOGIN_URL + '?redirect=' + encodeURIComponent(RETURN_URL);
            }
        });
    })();
    </script>
</body>
</html>
