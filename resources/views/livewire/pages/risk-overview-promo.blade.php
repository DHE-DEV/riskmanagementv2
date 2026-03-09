@php
$active = 'travel-alert';
$version = '1.2.0';
@endphp
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TravelAlert – Reisesicherheit & Travel Risk Management | Global Travel Monitor</title>
    <meta name="description" content="TravelAlert: Reisewarnungen per E-Mail, automatisches Reise-Monitoring und Risikoanalysen für Reisen. 24/7 Sicherheitsmonitoring in über 200 Ländern. Jetzt kostenlos testen.">
    <meta name="keywords" content="TravelAlert, Reisesicherheit, Travel Risk Management, Reisewarnungen, Geschäftsreise Sicherheit, Duty of Care, Reiserisiko Monitoring, Passolution">
    <meta name="author" content="Passolution GmbH">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://global-travel-monitor.eu/travel-alert">

    <!-- Language Alternates -->
    <link rel="alternate" hreflang="de" href="https://global-travel-monitor.eu/travel-alert">
    <link rel="alternate" hreflang="x-default" href="https://global-travel-monitor.eu/travel-alert">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Global Travel Monitor">
    <meta property="og:title" content="TravelAlert – Reisesicherheit für Unternehmen">
    <meta property="og:description" content="Automatisches Reise-Monitoring, E-Mail-Warnungen und länderweise Risikoanalysen. TravelAlert schützt Ihre Reisenden in über 200 Ländern.">
    <meta property="og:url" content="https://global-travel-monitor.eu/travel-alert">
    <meta property="og:image" content="{{ asset('images/travelalert/GTM-TA-01.png') }}">
    <meta property="og:image:width" content="1758">
    <meta property="og:image:height" content="878">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:alt" content="TravelAlert Dashboard – Reisesicherheits-Monitoring von Passolution">
    <meta property="og:locale" content="de_DE">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="TravelAlert – Reisesicherheit für Unternehmen">
    <meta name="twitter:description" content="Automatisches Reise-Monitoring und E-Mail-Warnungen für Reisende. Jetzt kostenlos testen.">
    <meta name="twitter:image" content="{{ asset('images/travelalert/GTM-TA-01.png') }}">

    <!-- Archivo Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
    <!-- Performance Hints -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="preload" href="{{ asset('css/risk-overview-promo.css') }}?v={{ $version }}" as="style">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    @php($faKit = config('services.fontawesome.kit'))
    @if(!empty($faKit))
    <script src="https://kit.fontawesome.com/{{ e($faKit) }}.js" crossorigin="anonymous" onload="window.__faKitOk=true"
        onerror="window.__faKitOk=false"></script>
    <script>
        (function () {
            function addCss(href) {
                var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = href; document.head.appendChild(l);
            }
            var fallbackHref = '{{ file_exists(public_path('vendor/fontawesome/css/all.min.css')) ? asset('vendor/fontawesome/css/all.min.css') : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css' }}';
            window.addEventListener('DOMContentLoaded', function () {
                setTimeout(function () { if (!window.__faKitOk) { addCss(fallbackHref); } }, 800);
            });
        })();
    </script>
    @elseif (file_exists(public_path('vendor/fontawesome/css/all.min.css')))
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" />
    @else
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
    @endif

    <link rel="stylesheet" href="{{ asset('css/risk-overview-promo.css') }}?v={{ $version }}" />

    <!-- JSON-LD: SoftwareApplication -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "SoftwareApplication",
        "name": "TravelAlert",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "description": "Reisesicherheits-Monitoring für Unternehmen. Automatische Zuordnung von Sicherheitsereignissen zu Reisen in über 200 Ländern.",
        "url": "https://global-travel-monitor.eu/travel-alert",
        "offers": [
            {
                "@@type": "Offer",
                "price": "0",
                "priceCurrency": "EUR",
                "description": "Kostenlos bis 30.06.2026",
                "validThrough": "2026-06-30"
            },
            {
                "@@type": "Offer",
                "price": "5.00",
                "priceCurrency": "EUR",
                "description": "Ab 01.07.2026 – Monatliches Entgelt für Reisebüros mit Kooperation/Kette",
                "priceValidUntil": "2027-12-31"
            }
        ],
        "featureList": [
            "Interaktive Weltkarte mit Sicherheitsereignissen",
            "Automatisches Reise-Event-Matching",
            "E-Mail-Warnungen und Benachrichtigungen",
            "Länderweise Risikoanalysen",
            "Kalenderansicht aller Ereignisse",
            "Filter nach Priorität, Zeitraum und Labels",
            "24/7 Sicherheitsmonitoring in über 200 Ländern",
            "Individuelle Labels für Reisen und Events"
        ],
        "screenshot": [
            "{{ asset('images/travelalert/GTM-TA-01.png') }}",
            "{{ asset('images/travelalert/GTM-TA-02.png') }}",
            "{{ asset('images/travelalert/GTM-TA-Filter.png') }}",
            "{{ asset('images/travelalert/GTM-TA-Kalenderansicht.png') }}"
        ],
        "author": {
            "@@type": "Organization",
            "name": "Passolution GmbH",
            "url": "https://www.passolution.de"
        }
    }
    </script>

    <!-- JSON-LD: BreadcrumbList -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "https://global-travel-monitor.eu/"
            },
            {
                "@@type": "ListItem",
                "position": 2,
                "name": "TravelAlert",
                "item": "https://global-travel-monitor.eu/travel-alert"
            }
        ]
    }
    </script>

    <!-- JSON-LD: FAQPage -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "FAQPage",
        "mainEntity": [
            {
                "@@type": "Question",
                "name": "Wie funktioniert TravelAlert?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "TravelAlert funktioniert in drei Schritten: 1. Reisen anlegen – Hinterlegen Sie Ihre Reisen mit Reisezielen und Zeiträumen oder importieren Sie diese automatisch. 2. Automatische Analyse – TravelAlert analysiert kontinuierlich sicherheitsrelevante Ereignisse und ordnet diese Ihren Reisen zu. 3. Warnung per E-Mail – Bei relevanten Ereignissen werden Sie per E-Mail informiert, mit konkreten Handlungsempfehlungen."
                }
            },
            {
                "@@type": "Question",
                "name": "Für wen ist TravelAlert geeignet?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "TravelAlert richtet sich an Travel Manager, die Reisen im Blick behalten und ihre Fürsorgepflicht erfüllen möchten, an Sicherheitsbeauftragte, die fundierte Risikoeinschätzungen auf Basis aktueller Daten benötigen, sowie an die Geschäftsführung für strategische Entscheidungen und Compliance im Bereich Reisesicherheit."
                }
            },
            {
                "@@type": "Question",
                "name": "Was kostet TravelAlert?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "TravelAlert ist bis zum 30.06.2026 kostenlos. Ab dem 01.07.2026 beträgt das monatliche Entgelt für Reisebüros 7,00 EUR (ohne Kooperation/Kette) bzw. 5,00 EUR (mit Kooperation/Kette). Für Reiseveranstalter und OTAs gelten individuelle Konditionen."
                }
            },
            {
                "@@type": "Question",
                "name": "Welche Länder werden von TravelAlert abgedeckt?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "TravelAlert überwacht Sicherheitsereignisse in über 200 Ländern weltweit, rund um die Uhr (24/7). Die Abdeckung umfasst politische Unruhen, Terrorwarnungen, Naturkatastrophen und weitere sicherheitsrelevante Ereignisse."
                }
            }
        ]
    }
    </script>
</head>

<body>
    <div class="app-container">
        <!-- Header -->
        <x-public-header />

        <!-- Main Content -->
        <div class="main-content">
            <!-- Navigation -->
            <x-public-navigation :active="$active" />

            <!-- Promo Content -->
            <main class="promo-content">

                <!-- 1. Hero-Bereich -->
                <section id="hero" class="relative min-h-[520px] flex items-center justify-center overflow-hidden" style="background: #021a2b;">
                    <!-- Glow blobs -->
                    <div class="glow-blob animate-pulse-slow" style="top: 15%; left: 20%; width: 450px; height: 450px; background: rgba(206, 231, 65, 0.12);"></div>
                    <div class="glow-blob animate-pulse-slow" style="top: 25%; left: 30%; width: 256px; height: 256px; background: rgba(206, 231, 65, 0.18); filter: blur(80px);"></div>
                    <div class="glow-blob animate-float" style="bottom: 15%; right: 20%; width: 320px; height: 320px; background: rgba(206, 231, 65, 0.08); filter: blur(60px);"></div>

                    <div class="max-w-5xl mx-auto px-6 py-20 text-center relative z-10">
                        <!-- Badge -->
                         <!--
                        <div class="animate-fade-up inline-flex items-center gap-2 px-4 py-2 rounded-full border mb-8" style="background: #065272; border-color: rgba(145, 218, 242, 0.2);">
                            <i class="fa-regular fa-shield-exclamation text-[#cee741]"></i>
                            <span class="text-sm font-medium text-[#cee741]">Reisesicherheit per E-Mail</span>
                        </div>-->

                        <!-- Headline -->
                        <h1 class="animate-fade-up-delay-1 text-5xl md:text-6xl lg:text-7xl font-extrabold leading-tight mb-6" style="font-family: Archivo, sans-serif;">
                            <span style="color: #ffffff;">Travel</span><span class="text-[#cee741]">Alert</span>
                        </h1>

                        <!-- Subtext -->
                        <p class="animate-fade-up-delay-2 text-lg md:text-xl max-w-3xl mx-auto mb-10 leading-relaxed" style="color: #91daf2;">
                            Ihre Reisen im Blick - weltweit. <br>
                            Behalten Sie relevante Ereignisse weltweit im Blick und erkennen Sie sofort, ob Ihre Reisenden betroffen sein könnten. Travel Alert analysiert aktuelle Ereignisse und zeigt Ihnen, welche Reisen potenziell betroffen sind, damit Sie schnell reagieren können.
                        </p>

                        <!-- CTA Buttons -->
                        <div class="animate-fade-up-delay-3 flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                            <button onclick="document.dispatchEvent(new CustomEvent('open-order-modal'))"
                               class="inline-flex items-center px-8 py-3.5 font-semibold rounded-xl transition-all shadow-lg cursor-pointer"
                               style="background: #CEE741; color: #002742; box-shadow: 0 10px 25px -5px rgba(206, 231, 65, 0.3);"
                               onmouseover="this.style.opacity='0.9'; this.style.boxShadow='0 10px 30px -5px rgba(206, 231, 65, 0.5)'"
                               onmouseout="this.style.opacity='1'; this.style.boxShadow='0 10px 25px -5px rgba(206, 231, 65, 0.3)'"
                               aria-label="TravelAlert jetzt bestellen – Bestellformular öffnen"
                            >
                                <i class="fa-regular fa-cart-shopping mr-2"></i>
                                Jetzt bestellen
                            </button>
                            @if($isLoggedIn)
                                <a href="{{ route('customer.dashboard') }}"
                                   class="inline-flex items-center px-8 py-3.5 font-semibold rounded-xl transition-all shadow-lg"
                                   style="background: #91daf2; color: #043451; box-shadow: 0 10px 25px -5px rgba(145, 218, 242, 0.3);"
                                   onmouseover="this.style.background='#a8e2f5'; this.style.boxShadow='0 10px 30px -5px rgba(145, 218, 242, 0.5)'"
                                   onmouseout="this.style.background='#91daf2'; this.style.boxShadow='0 10px 25px -5px rgba(145, 218, 242, 0.3)'"
                                   aria-label="TravelAlert im Dashboard freischalten">
                                    <i class="fa-regular fa-unlock mr-2"></i>
                                    Jetzt freischalten
                                </a>
                            @else
                                <a href="{{ route('customer.login') }}"
                                   class="inline-flex items-center px-8 py-3.5 font-semibold rounded-xl transition-all shadow-lg"
                                   style="background: #91daf2; color: #043451; box-shadow: 0 10px 25px -5px rgba(145, 218, 242, 0.3);"
                                   onmouseover="this.style.background='#a8e2f5'; this.style.boxShadow='0 10px 30px -5px rgba(145, 218, 242, 0.5)'"
                                   onmouseout="this.style.background='#91daf2'; this.style.boxShadow='0 10px 25px -5px rgba(145, 218, 242, 0.3)'"
                                   aria-label="Anmelden um TravelAlert zu nutzen">
                                    <i class="fa-regular fa-right-to-bracket mr-2"></i>
                                    Jetzt anmelden
                                </a>
                            @endif
                        </div>

                        <!-- Stats row -->
                        <div class="animate-fade-up-delay-4 flex flex-wrap justify-center gap-6 lg:gap-10">
                            <div class="flex items-center gap-2 px-5 py-3 rounded-xl shadow-sm" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <i class="fa-regular fa-globe text-[#cee741]"></i>
                                <span class="text-sm font-medium" style="color: #ffffff;">200+ Länder</span>
                            </div>
                            <div class="flex items-center gap-2 px-5 py-3 rounded-xl shadow-sm" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <i class="fa-regular fa-bolt text-[#cee741]"></i>
                                <span class="text-sm font-medium" style="color: #ffffff;">E-Mail-Warnungen</span>
                            </div>
                            <div class="flex items-center gap-2 px-5 py-3 rounded-xl shadow-sm" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <i class="fa-regular fa-clock text-[#cee741]"></i>
                                <span class="text-sm font-medium" style="color: #ffffff;">24/7 Monitoring</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. Feature-Grid (4 Spalten) -->
                <section id="features" class="py-20 px-6" style="background: #021a2b;">
                    <div class="max-w-5xl mx-auto">
                        <div class="text-center mb-12">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border mb-4" style="background: rgba(206, 231, 65, 0.15); border-color: rgba(206, 231, 65, 0.25);">
                                <span class="text-sm font-medium text-[#cee741]">Features</span>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Was Travel<span class="text-[#cee741]">Alert</span> für Sie leistet</h2>
                            <p class="max-w-2xl mx-auto" style="color: #91daf2;">
                                Behalten Sie relevante Ereignisse weltweit im Blick und erkennen Sie sofort, ob Reisen Ihrer Kunden betroffen sein könnten. Travel Alert hilft Ihnen.
                            </p>
                        </div>
                        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            <!-- Interaktive Karte -->
                            <div class="p-6 rounded-2xl backdrop-blur-sm" style="background: rgba(6, 82, 114, 0.5); border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(206, 231, 65, 0.15);">
                                    <i class="fa-regular fa-map text-xl text-[#cee741]"></i>
                                </div>
                                <h3 class="font-semibold mb-2 text-center" style="color: #ffffff;">Übersicht betroffener Reisen</h3>
                                <p class="text-sm text-center" style="color: #91daf2;">Sehen Sie auf einen Blick, welche Reisen von aktuellen Ereignissen betroffen sein könnten und behalten Sie ihre Buchungen jederzeit im Blick. </p>
                            </div>
                            <!-- Echtzeit-Warnungen -->
                             <!--
                            <div class="p-6 rounded-2xl backdrop-blur-sm" style="background: rgba(6, 82, 114, 0.5); border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(206, 231, 65, 0.15);">
                                    <i class="fa-regular fa-bell text-xl text-[#cee741]"></i>
                                </div>
                                <h3 class="font-semibold mb-2 text-center" style="color: #ffffff;">E-Mail-Warnungen</h3>
                                <p class="text-sm text-center" style="color: #91daf2;">Lassen Sie sich automatisch informieren, sobald ein Ereignis Reisen Ihrer Kunden betreffen könnten</p>
                            </div>-->
                            <!-- Reisehinweise -->
                            <div class="p-6 rounded-2xl backdrop-blur-sm" style="background: rgba(6, 82, 114, 0.5); border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(206, 231, 65, 0.15);">
                                    <i class="fa-regular fa-shield-exclamation text-xl text-[#cee741]"></i>
                                </div>
                                <h3 class="font-semibold mb-2 text-center" style="color: #ffffff;">Globale Reiseereignisse</h3>
                                <p class="text-sm text-center" style="color: #91daf2;">Ereignisse zu Umwelt, Sicherheit, Einreisebestimmungen, Reiseverkehr und Gesundheit helfen Ihnen, mögliche Auswirkungen auf Reisen schnell zu erkennen.</p>
                            </div>
                            <!-- Echtzeit-Updates -->
                            <div class="p-6 rounded-2xl backdrop-blur-sm" style="background: rgba(6, 82, 114, 0.5); border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(206, 231, 65, 0.15);">
                                    <i class="fa-regular fa-globe text-xl text-[#cee741]"></i>
                                </div>
                                <h3 class="font-semibold mb-2 text-center" style="color: #ffffff;">Weltweite Abdeckung</h3>
                                <p class="text-sm text-center" style="color: #91daf2;">Greifen Sie auf Ereignisse auf über 200 Ländern zu und behalten Sie globale Entwicklungen im Blick.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 3. Feature-Showcase: Reisen-Monitoring -->
                <section id="reisen-monitoring" class="py-20 px-6" style="background: #021a2b; border-top: 1px solid rgba(145, 218, 242, 0.1);">
                    <div class="max-w-5xl mx-auto">
                        <div class="grid md:grid-cols-2 gap-12 items-center mb-20">
                            <div>
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border mb-4" style="background: rgba(206, 231, 65, 0.1); border-color: rgba(206, 231, 65, 0.2);">
                                    <i class="fa-regular fa-suitcase-rolling text-[#cee741] text-sm"></i>
                                    <span class="text-xs font-medium text-[#cee741]">Reisen-Monitoring</span>
                                </div>
                                <h3 class="text-2xl md:text-3xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Automatische Zuordnung von Events zu Reisen</h3>
                                <p class="leading-relaxed mb-6" style="color: #91daf2;">
                                    Hinterlegen Sie Ihre Reisen und lassen Sie diese automatisch überwachen. Travel<span class="text-[#cee741]">Alert</span> ordnet sicherheitsrelevante Ereignisse automatisch Ihren aktiven Reisen zu.
                                </p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#cee741] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Automatisches Reise-Event-Matching</span>
                                    </li>
                                    <!--
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#cee741] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">E-Mail-Benachrichtigungen</span>
                                    </li>-->
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#cee741] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Priorisierte Darstellung nach Relevanz</span>
                                    </li>
                                </ul>
                            </div>
                            <!-- Screenshot -->
                            <div class="rounded-2xl overflow-hidden shadow-2xl cursor-pointer promo-lightbox-trigger" style="border: 1px solid rgba(145, 218, 242, 0.15);">
                                <img src="{{ asset('images/travelalert/GTM-TA-01.png') }}" alt="TravelAlert Dashboard – automatisches Reise-Monitoring mit Echtzeit-Sicherheitsereignissen und Reisezuordnung" class="w-full h-auto" width="1758" height="878" loading="lazy">
                            </div>
                        </div>

                        <!-- Länder-Ansicht -->
                         <!--
                        <div class="grid md:grid-cols-2 gap-12 items-center mb-20">
                            <div class="rounded-2xl overflow-hidden shadow-2xl cursor-pointer promo-lightbox-trigger order-2 md:order-1" style="border: 1px solid rgba(145, 218, 242, 0.15);">
                                <img src="{{ asset('images/travelalert/GTM-TA-02.png') }}" alt="TravelAlert Länder-Risikoanalyse – detaillierte Sicherheitsbewertung pro Land mit Travel Risk Management Übersicht" class="w-full h-auto" width="1757" height="882" loading="lazy">
                            </div>
                            <div class="order-1 md:order-2">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border mb-4" style="background: rgba(145, 218, 242, 0.1); border-color: rgba(145, 218, 242, 0.2);">
                                    <i class="fa-regular fa-earth-europe text-[#91daf2] text-sm"></i>
                                    <span class="text-xs font-medium text-[#91daf2]">Länder-Ansicht</span>
                                </div>
                                <h3 class="text-2xl md:text-3xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Länderweise Risikoanalyse</h3>
                                <p class="leading-relaxed mb-6" style="color: #91daf2;">
                                    Analysieren Sie Risiken länderweise mit detaillierten Informationen zu aktuellen Ereignissen, betroffenen Reisenden und Risikoeinschätzungen.
                                </p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#91daf2] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Detaillierte Länder-Risikoprofile</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#91daf2] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Betroffene Reisende pro Land</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#91daf2] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Interaktive Kartenansicht</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
-->
                        <!-- Filter & Labels -->
                        <div class="grid md:grid-cols-2 gap-12 items-center">
                            <div>
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border mb-4" style="background: rgba(206, 231, 65, 0.1); border-color: rgba(206, 231, 65, 0.2);">
                                    <i class="fa-regular fa-tags text-[#cee741] text-sm"></i>
                                    <span class="text-xs font-medium text-[#cee741]">Filter & Labels</span>
                                </div>
                                <h3 class="text-2xl md:text-3xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Filterung nach Priorität, Zeitraum & Labels</h3>
                                <p class="leading-relaxed mb-6" style="color: #91daf2;">
                                    Filtern Sie Ereignisse nach Priorität, Zeitraum oder eigenen Labels. Organisieren Sie Ihre Reisen und Ereignisse mit individuellen Labels für maximale Übersicht.
                                </p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#cee741] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Frei definierbare Labels</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#cee741] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Prioritäts- und Zeitraumfilter</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#cee741] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Labels für Reisen, Trips und Events</span>
                                    </li>
                                </ul>
                            </div>
                            <!-- Screenshot -->
                            <div class="rounded-2xl overflow-hidden shadow-2xl cursor-pointer promo-lightbox-trigger" style="border: 1px solid rgba(145, 218, 242, 0.15);">
                                <img src="{{ asset('images/travelalert/GTM-TA-Filter.png') }}" alt="TravelAlert Filter und Labels – Priorisierung von Reisewarnungen nach Zeitraum, Priorität und benutzerdefinierten Labels" class="w-full h-auto" width="1755" height="881" loading="lazy">
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Kalenderansicht -->
                <section id="kalenderansicht" class="py-20 px-6" style="background: #021a2b; border-top: 1px solid rgba(145, 218, 242, 0.1);">
                    <div class="max-w-5xl mx-auto">
                        <div class="grid md:grid-cols-2 gap-12 items-center">
                            <!-- Screenshot (links) -->
                            <div class="rounded-2xl overflow-hidden shadow-2xl cursor-pointer promo-lightbox-trigger order-2 md:order-1" style="border: 1px solid rgba(145, 218, 242, 0.15);">
                                <img src="{{ asset('images/travelalert/GTM-TA-Kalenderansicht.png') }}" alt="TravelAlert Kalenderansicht – tagesgenaue Übersicht aller Sicherheitsereignisse mit Farbcodierung nach Priorität" class="w-full h-auto" width="1385" height="685" loading="lazy">
                            </div>
                            <div class="order-1 md:order-2">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border mb-4" style="background: rgba(145, 218, 242, 0.1); border-color: rgba(145, 218, 242, 0.2);">
                                    <i class="fa-regular fa-calendar-days text-[#91daf2] text-sm"></i>
                                    <span class="text-xs font-medium text-[#91daf2]">Kalenderansicht</span>
                                </div>
                                <h3 class="text-2xl md:text-3xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Alle Ereignisse im Kalender auf einen Blick</h3>
                                <p class="leading-relaxed mb-6" style="color: #91daf2;">
                                    Die praktische Kalenderansicht zeigt Ihnen tagesgenau, welche sicherheitsrelevanten Ereignisse Ihre Reisen betreffen. So erkennen Sie auf einen Blick kritische Zeiträume und können Reisen frühzeitig umplanen.
                                </p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#91daf2] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Tagesgenaue Darstellung aller Events</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#91daf2] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Farbcodierung nach Prioritätsstufe</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-[#91daf2] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Schnellnavigation zu Reisestart und aktuellem Tag</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 4. So funktioniert's -->
                <!--<section id="so-funktionierts" class="py-20 px-6" style="background: #021a2b; border-top: 1px solid rgba(145, 218, 242, 0.1);">
                    <div class="max-w-5xl mx-auto">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">So funktioniert's</h2>
                            <p style="color: #91daf2;" class="max-w-2xl mx-auto">In drei einfachen Schritten zur Reisesicherheit</p>
                        </div>
                        <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                            <div class="p-6 rounded-2xl hover:border-[#cee741]/30 transition-all duration-300 group" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background: rgba(206, 231, 65, 0.1);">
                                    <span class="text-[#cee741] font-bold text-lg">1</span>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: #ffffff;">Reisen anlegen</h3>
                                <p class="text-sm" style="color: #91daf2;">
                                    Hinterlegen Sie Ihre Reisen mit Reisezielen und Zeiträumen - oder importieren Sie diese automatisch.
                                </p>
                            </div>
                            <div class="p-6 rounded-2xl hover:border-[#cee741]/30 transition-all duration-300 group" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background: rgba(206, 231, 65, 0.1);">
                                    <span class="text-[#cee741] font-bold text-lg">2</span>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: #ffffff;">Automatische Analyse</h3>
                                <p class="text-sm" style="color: #91daf2;">
                                    Travel<span class="text-[#cee741]">Alert</span> analysiert kontinuierlich sicherheitsrelevante Ereignisse und ordnet diese Ihren Reisen zu.
                                </p>
                            </div>
                            <div class="p-6 rounded-2xl hover:border-[#cee741]/30 transition-all duration-300 group" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background: rgba(206, 231, 65, 0.1);">
                                    <span class="text-[#cee741] font-bold text-lg">3</span>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: #ffffff;">Warnung per E-Mail</h3>
                                <p class="text-sm" style="color: #91daf2;">
                                    Bei relevanten Ereignissen werden Sie per E-Mail informiert - mit konkreten Handlungsempfehlungen.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>-->

                <!-- 5. Zielgruppen -->
                <section id="zielgruppen" class="py-20 px-6" style="background: #021a2b; border-top: 1px solid rgba(145, 218, 242, 0.1);">
                    <div class="max-w-5xl mx-auto">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Für wen ist Travel<span class="text-[#cee741]">Alert</span>?</h2>
                            <p style="color: #91daf2;" class="max-w-2xl mx-auto">Entwickelt für Unternehmen in der Touristik, die Reisende betreuen und schnell erkennen müssen, wenn Ereignisse Reisen beeinflussen können.</p>
                        </div>
                        <div class="grid md:grid-cols-3 gap-6">
                            <div class="p-6 rounded-2xl hover:border-[#cee741]/30 transition-all duration-300 hover:shadow-lg group" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 group-hover:scale-105 transition-transform" style="background: rgba(206, 231, 65, 0.1);">
                                    <i class="fa-regular fa-user-tie text-xl text-[#cee741]"></i>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: #ffffff;">Reisebüros & Reiseberater</h3>
                                <p class="text-sm" style="color: #91daf2;">
                                    Behalten Sie die Reisen Ihrer Kunden im Blick und erkennen Sie schnell, wenn Ereignisse Auswirkungen auf gebuchte Reisen haben könnten.
                                </p>
                            </div>
                            <div class="p-6 rounded-2xl hover:border-[#cee741]/30 transition-all duration-300 hover:shadow-lg group" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 group-hover:scale-105 transition-transform" style="background: rgba(145, 218, 242, 0.1);">
                                    <i class="fa-regular fa-shield-halved text-xl text-[#91daf2]"></i>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: #ffffff;">Reiseveranstalter</h3>
                                <p class="text-sm" style="color: #91daf2;">
                                    Überblick über mögliche Auswirkungen globaler Ereignisse auf Ihre gebuchten Reisen, damit Sie frühzeitig reagieren können.
                                </p>
                            </div>
                            <div class="p-6 rounded-2xl hover:border-[#cee741]/30 transition-all duration-300 hover:shadow-lg group" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 group-hover:scale-105 transition-transform" style="background: rgba(145, 218, 242, 0.1);">
                                    <i class="fa-regular fa-building-columns text-xl text-[#91daf2]"></i>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: #ffffff;">Technologie- & Softwarepartner</h3>
                                <p class="text-sm" style="color: #91daf2;">
                                    Integrieren Sie Travel Alert über unsere API in Ihre eigenen Systeme und bieten Sie Ihren Kunden zusätzliche Mehrwerte rund um Reiseereignisse. 
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 6. CTA-Abschluss -->
                <section id="bestellen" class="relative py-20 px-6 overflow-hidden" style="background: #021a2b; border-top: 1px solid rgba(145, 218, 242, 0.1);">
                    <!-- Glow -->
                    <div class="glow-blob" style="top: 30%; left: 35%; width: 400px; height: 400px; background: rgba(206, 231, 65, 0.1);"></div>

                    <div class="max-w-3xl mx-auto text-center relative z-10">
                        <div class="rounded-2xl p-10" style="background: linear-gradient(135deg, rgba(206, 231, 65, 0.08), rgba(206, 231, 65, 0.03)); border: 1px solid rgba(206, 231, 65, 0.15);">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6" style="background: rgba(206, 231, 65, 0.1);">
                                <i class="fa-regular fa-shield-exclamation text-3xl text-[#cee741]"></i>
                            </div>
                            <!--
                            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Starten Sie jetzt mit Travel<span class="text-[#cee741]">Alert</span></h2>
                            <p class="mb-8" style="color: #91daf2;">
                                Schützen Sie Ihre Reisenden mit Sicherheitsinformationen per E-Mail und automatischem Reise-Monitoring.
                            </p>-->
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                                <button onclick="document.dispatchEvent(new CustomEvent('open-order-modal'))"
                                   class="inline-flex items-center px-8 py-3.5 font-semibold rounded-xl transition-all shadow-lg cursor-pointer"
                                   style="background: #CEE741; color: #002742; box-shadow: 0 10px 25px -5px rgba(206, 231, 65, 0.3);"
                                   onmouseover="this.style.opacity='0.9'; this.style.boxShadow='0 10px 30px -5px rgba(206, 231, 65, 0.5)'"
                                   onmouseout="this.style.opacity='1'; this.style.boxShadow='0 10px 25px -5px rgba(206, 231, 65, 0.3)'"
                                   aria-label="TravelAlert jetzt bestellen – Bestellformular öffnen"
                                >
                                    <i class="fa-regular fa-cart-shopping mr-2"></i>
                                    Jetzt bestellen
                                </button>
                                @if($isLoggedIn)
                                    <a href="{{ route('customer.dashboard') }}"
                                       class="inline-flex items-center px-8 py-3.5 font-semibold rounded-xl transition-all shadow-lg"
                                   style="background: #91daf2; color: #043451; box-shadow: 0 10px 25px -5px rgba(145, 218, 242, 0.3);"
                                   onmouseover="this.style.background='#a8e2f5'; this.style.boxShadow='0 10px 30px -5px rgba(145, 218, 242, 0.5)'"
                                   onmouseout="this.style.background='#91daf2'; this.style.boxShadow='0 10px 25px -5px rgba(145, 218, 242, 0.3)'"
                                   aria-label="TravelAlert im Dashboard freischalten">
                                        <i class="fa-regular fa-unlock mr-2"></i>
                                        Jetzt freischalten
                                    </a>
                                @else
                                    <a href="{{ route('customer.login') }}"
                                       class="inline-flex items-center px-8 py-3.5 font-semibold rounded-xl transition-all shadow-lg"
                                   style="background: #91daf2; color: #043451; box-shadow: 0 10px 25px -5px rgba(145, 218, 242, 0.3);"
                                   onmouseover="this.style.background='#a8e2f5'; this.style.boxShadow='0 10px 30px -5px rgba(145, 218, 242, 0.5)'"
                                   onmouseout="this.style.background='#91daf2'; this.style.boxShadow='0 10px 25px -5px rgba(145, 218, 242, 0.3)'"
                                   aria-label="Anmelden um TravelAlert zu nutzen">
                                        <i class="fa-regular fa-right-to-bracket mr-2"></i>
                                        Jetzt anmelden
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

            </main>
        </div>

        <!-- Order Modal -->
        <div x-data="orderForm()" x-cloak>
            <!-- Backdrop -->
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[20000]" style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"
                 @click.self="open = false">

                <!-- Modal -->
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                     class="relative mx-auto mt-[3vh] w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden"
                     style="max-height: 94vh;"
                     @keydown.escape.window="open = false">

                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4" style="background: #002742;">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: rgba(206, 231, 65, 0.2);">
                                <i class="fa-regular fa-cart-shopping" style="color: #CEE741;"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white" style="font-family: Archivo, sans-serif;">Travel<span class="text-[#cee741]">Alert</span> bestellen</h2>
                                <p class="text-xs" style="color: rgba(255,255,255,0.6);">Füllen Sie das Formular aus - wir melden uns umgehend.</p>
                            </div>
                        </div>
                        <button @click="open = false" class="p-2 rounded-lg transition-colors text-white/60 hover:text-white hover:bg-white/10">
                            <i class="fa-regular fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <!-- Success State -->
                    <div x-show="submitted" class="px-6 py-16 text-center">
                        <div class="w-20 h-20 rounded-full mx-auto mb-6 flex items-center justify-center bg-emerald-50">
                            <i class="fa-regular fa-check text-4xl text-emerald-500"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3 text-gray-900" style="font-family: Archivo, sans-serif;">Bestellung eingegangen!</h3>
                        <p class="mb-8 max-w-md mx-auto text-gray-500">
                            Vielen Dank für Ihre Bestellung. Wir werden uns in Kürze bei Ihnen melden, um die Details zu besprechen.
                        </p>
                        <button @click="open = false" class="inline-flex items-center px-6 py-2.5 font-semibold rounded-xl transition-all"
                                style="background: #002742; color: white;">
                            Schließen
                        </button>
                    </div>

                    <!-- Form -->
                    <form x-show="!submitted" @submit.prevent="submit" class="overflow-y-auto" style="max-height: calc(94vh - 73px);">
                        <div class="px-6 py-5 space-y-5">

                            <!-- Preisinformationen -->
                            <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <i class="fa-regular fa-tag text-sm" style="color: #002742;"></i>
                                    <span class="text-sm font-semibold" style="color: #002742;">Preis</span>
                                </div>
                                <p class="text-sm text-gray-700 mb-3">
                                    Die Zusatzleistung Travel<span class="text-[#cee741]">Alert</span> wird <strong>bis zum 30.06.2026 kostenlos</strong> zur Verfügung gestellt. In diesem Zeitraum kann jederzeit per Mail an
                                    <a href="mailto:info@passolution.de" class="text-blue-600 underline">info@passolution.de</a> der Vertrag gekündigt werden.
                                </p>
                                <p class="text-sm text-gray-700 font-medium mb-2">Ab dem 01.07.2026:</p>
                                <div class="space-y-2 ml-1">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">Für Reisebüros:</p>
                                        <ul class="text-sm text-gray-600 ml-4 list-disc">
                                            <li>Monatliches Entgelt <strong>7,00 EUR</strong> ohne Kooperation/Kette</li>
                                            <li>Monatliches Entgelt <strong>5,00 EUR</strong> mit Kooperation/Kette</li>
                                        </ul>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">Für Reiseveranstalter, OTA, o.Ä.:</p>
                                        <p class="text-sm text-gray-600 ml-4">
                                            Als Veranstalter, OTA, o.Ä. fallen andere Kosten an. Bitte melden Sie sich dafür an
                                            <a href="mailto:vertrieb@passolution.de" class="text-blue-600 underline">vertrieb@passolution.de</a>.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Firmendaten -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <i class="fa-regular fa-building text-sm text-blue-600"></i>
                                    <span class="text-sm font-semibold text-gray-700">Firmendaten</span>
                                </div>

                                <div class="space-y-3">
                                    <!-- Firmenname -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1 text-gray-700">
                                            Firmenname <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" x-model="form.company" required
                                               class="w-full px-4 py-2.5 rounded-xl text-sm outline-none transition-all border border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500"
                                               placeholder="Musterfirma GmbH">
                                        <p x-show="errors.company" x-text="errors.company" class="text-red-500 text-xs mt-1"></p>
                                    </div>

                                    <!-- Ansprechpartner -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium mb-1 text-gray-700">Vorname</label>
                                            <input type="text" x-model="form.first_name"
                                                   class="w-full px-4 py-2.5 rounded-xl text-sm outline-none transition-all border border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500"
                                                   placeholder="Max">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1 text-gray-700">Nachname</label>
                                            <input type="text" x-model="form.last_name"
                                                   class="w-full px-4 py-2.5 rounded-xl text-sm outline-none transition-all border border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500"
                                                   placeholder="Mustermann">
                                        </div>
                                    </div>

                                    <!-- E-Mail & Telefon -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium mb-1 text-gray-700">
                                                E-Mail <span class="text-red-500">*</span>
                                            </label>
                                            <input type="email" x-model="form.email" required
                                                   class="w-full px-4 py-2.5 rounded-xl text-sm outline-none transition-all border border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500"
                                                   placeholder="max@musterfirma.de">
                                            <p x-show="errors.email" x-text="errors.email" class="text-red-500 text-xs mt-1"></p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1 text-gray-700">
                                                Telefon <span class="text-red-500">*</span>
                                            </label>
                                            <input type="tel" x-model="form.phone" required
                                                   class="w-full px-4 py-2.5 rounded-xl text-sm outline-none transition-all border border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500"
                                                   placeholder="+49 123 456 789">
                                            <p x-show="errors.phone" x-text="errors.phone" class="text-red-500 text-xs mt-1"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-gray-200"></div>

                            <!-- Adresse -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <i class="fa-regular fa-location-dot text-sm text-emerald-600"></i>
                                    <span class="text-sm font-semibold text-gray-700">Adresse</span>
                                </div>

                                <div class="space-y-3">
                                    <!-- Straße -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1 text-gray-700">
                                            Straße & Hausnr. <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" x-model="form.street" required
                                               class="w-full px-4 py-2.5 rounded-xl text-sm outline-none transition-all border border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500"
                                               placeholder="Musterstraße 1">
                                        <p x-show="errors.street" x-text="errors.street" class="text-red-500 text-xs mt-1"></p>
                                    </div>

                                    <!-- PLZ & Stadt -->
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium mb-1 text-gray-700">
                                                PLZ <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" x-model="form.postal_code" required
                                                   class="w-full px-4 py-2.5 rounded-xl text-sm outline-none transition-all border border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500"
                                                   placeholder="12345">
                                            <p x-show="errors.postal_code" x-text="errors.postal_code" class="text-red-500 text-xs mt-1"></p>
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-sm font-medium mb-1 text-gray-700">
                                                Stadt <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" x-model="form.city" required
                                                   class="w-full px-4 py-2.5 rounded-xl text-sm outline-none transition-all border border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500"
                                                   placeholder="Musterstadt">
                                            <p x-show="errors.city" x-text="errors.city" class="text-red-500 text-xs mt-1"></p>
                                        </div>
                                    </div>

                                    <!-- Land -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1 text-gray-700">
                                            Land <span class="text-red-500">*</span>
                                        </label>
                                        <select x-model="form.country" required
                                                class="w-full px-4 py-2.5 rounded-xl text-sm outline-none transition-all appearance-none border border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500"
                                                style="background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 12 12%22%3E%3Cpath fill=%22%236b7280%22 d=%22M2 4l4 4 4-4%22/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                                            <option value="" disabled>Land auswählen...</option>
                                            <option value="Deutschland">Deutschland</option>
                                            <option value="Österreich">Österreich</option>
                                            <option value="Schweiz">Schweiz</option>
                                            <option value="" disabled>────────────</option>
                                            <option value="Belgien">Belgien</option>
                                            <option value="Dänemark">Dänemark</option>
                                            <option value="Finnland">Finnland</option>
                                            <option value="Frankreich">Frankreich</option>
                                            <option value="Griechenland">Griechenland</option>
                                            <option value="Irland">Irland</option>
                                            <option value="Italien">Italien</option>
                                            <option value="Liechtenstein">Liechtenstein</option>
                                            <option value="Luxemburg">Luxemburg</option>
                                            <option value="Niederlande">Niederlande</option>
                                            <option value="Norwegen">Norwegen</option>
                                            <option value="Polen">Polen</option>
                                            <option value="Portugal">Portugal</option>
                                            <option value="Schweden">Schweden</option>
                                            <option value="Spanien">Spanien</option>
                                            <option value="Tschechien">Tschechien</option>
                                            <option value="Ungarn">Ungarn</option>
                                            <option value="Vereinigtes Königreich">Vereinigtes Königreich</option>
                                            <option value="" disabled>────────────</option>
                                            <option value="Andere">Andere</option>
                                        </select>
                                        <p x-show="errors.country" x-text="errors.country" class="text-red-500 text-xs mt-1"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-gray-200"></div>

                            <!-- Abrechnung -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <i class="fa-regular fa-file-invoice text-sm text-violet-600"></i>
                                    <span class="text-sm font-semibold text-gray-700">Abrechnung</span>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2 text-gray-700">
                                        Bestehendes Abrechnungsverfahren nutzen? <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2 px-4 py-2.5 rounded-xl cursor-pointer transition-all border"
                                               :class="form.existing_billing === 'ja' ? 'bg-blue-50 border-blue-400' : 'bg-gray-50 border-gray-200'">
                                            <input type="radio" x-model="form.existing_billing" value="ja" class="sr-only">
                                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all"
                                                 :class="form.existing_billing === 'ja' ? 'border-blue-500' : 'border-gray-300'">
                                                <div x-show="form.existing_billing === 'ja'" class="w-2 h-2 rounded-full bg-blue-500"></div>
                                            </div>
                                            <span class="text-sm text-gray-700">Ja</span>
                                        </label>
                                        <label class="flex items-center gap-2 px-4 py-2.5 rounded-xl cursor-pointer transition-all border"
                                               :class="form.existing_billing === 'nein' ? 'bg-blue-50 border-blue-400' : 'bg-gray-50 border-gray-200'">
                                            <input type="radio" x-model="form.existing_billing" value="nein" class="sr-only">
                                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all"
                                                 :class="form.existing_billing === 'nein' ? 'border-blue-500' : 'border-gray-300'">
                                                <div x-show="form.existing_billing === 'nein'" class="w-2 h-2 rounded-full bg-blue-500"></div>
                                            </div>
                                            <span class="text-sm text-gray-700">Nein</span>
                                        </label>
                                    </div>
                                    <p x-show="errors.existing_billing" x-text="errors.existing_billing" class="text-red-500 text-xs mt-1"></p>
                                </div>
                            </div>

                            <!-- Bemerkung -->
                            <div>
                                <label class="block text-sm font-medium mb-1 text-gray-700">Bemerkung</label>
                                <textarea x-model="form.remarks" rows="3"
                                          class="w-full px-4 py-2.5 rounded-xl text-sm outline-none transition-all resize-none border border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500"
                                          placeholder="Optionale Anmerkungen zu Ihrer Bestellung..."></textarea>
                            </div>

                            <!-- Wichtige Informationen -->
                            <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                                <div class="flex items-start gap-2">
                                    <i class="fa-regular fa-circle-info text-sm text-amber-600 mt-0.5 flex-shrink-0"></i>
                                    <div>
                                        <span class="text-sm font-semibold text-amber-800">Wichtige Informationen</span>
                                        <p class="text-xs text-amber-700 mt-1">
                                            Es gelten die AGBs der Passolution GmbH und die Bedingungen aus dem vorab geschlossenen Vertrag. Mündliche Absprachen finden keine Anwendung.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div x-show="errorMessage" class="p-3 rounded-xl text-sm bg-red-50 border border-red-200 text-red-600">
                                <i class="fa-regular fa-circle-exclamation mr-1"></i>
                                <span x-text="errorMessage"></span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 flex items-center justify-end gap-3 bg-gray-50 border-t border-gray-200">
                            <button type="button" @click="open = false"
                                    class="px-5 py-2.5 text-sm font-medium rounded-xl transition-all text-gray-500 border border-gray-300 hover:bg-gray-100">
                                Abbrechen
                            </button>
                            <button type="submit" :disabled="loading"
                                    class="inline-flex items-center px-6 py-2.5 text-sm font-semibold rounded-xl transition-all disabled:opacity-50 text-white"
                                    style="background: #002742;">
                                <i x-show="!loading" class="fa-regular fa-paper-plane mr-2"></i>
                                <i x-show="loading" class="fa-regular fa-spinner-third fa-spin mr-2"></i>
                                <span x-text="loading ? 'Wird gesendet...' : 'Bestellung absenden'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function orderForm() {
            return {
                open: false,
                loading: false,
                submitted: false,
                errorMessage: '',
                errors: {},
                form: {
                    company: '',
                    first_name: '',
                    last_name: '',
                    email: '',
                    phone: '',
                    street: '',
                    postal_code: '',
                    city: '',
                    country: 'Deutschland',
                    existing_billing: '',
                    remarks: ''
                },

                init() {
                    document.addEventListener('open-order-modal', () => {
                        this.open = true;
                        this.submitted = false;
                        this.errorMessage = '';
                        this.errors = {};
                    });
                },

                async submit() {
                    this.errors = {};
                    this.errorMessage = '';

                    // Client-side validation
                    if (!this.form.company.trim()) this.errors.company = 'Firmenname ist erforderlich.';
                    if (!this.form.email.trim()) this.errors.email = 'E-Mail ist erforderlich.';
                    if (!this.form.phone.trim()) this.errors.phone = 'Telefon ist erforderlich.';
                    if (!this.form.street.trim()) this.errors.street = 'Straße ist erforderlich.';
                    if (!this.form.postal_code.trim()) this.errors.postal_code = 'PLZ ist erforderlich.';
                    if (!this.form.city.trim()) this.errors.city = 'Stadt ist erforderlich.';
                    if (!this.form.country) this.errors.country = 'Land ist erforderlich.';
                    if (!this.form.existing_billing) this.errors.existing_billing = 'Bitte wählen Sie eine Option.';

                    if (Object.keys(this.errors).length > 0) return;

                    this.loading = true;

                    try {
                        const response = await fetch('{{ route("risk-overview.order") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.submitted = true;
                            // Reset form
                            this.form = {
                                company: '', first_name: '', last_name: '', email: '', phone: '',
                                street: '', postal_code: '', city: '', country: 'Deutschland',
                                existing_billing: '', remarks: ''
                            };
                        } else if (response.status === 422 && data.errors) {
                            // Validation errors from server
                            for (const [key, messages] of Object.entries(data.errors)) {
                                this.errors[key] = messages[0];
                            }
                        } else {
                            this.errorMessage = data.message || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
                        }
                    } catch (e) {
                        this.errorMessage = 'Verbindungsfehler. Bitte prüfen Sie Ihre Internetverbindung.';
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }
        </script>

        <!-- Image Lightbox -->
        <div id="promo-lightbox" style="display:none; position:fixed; inset:0; z-index:30000; background:rgba(0,0,0,0.85); backdrop-filter:blur(6px); cursor:zoom-out; align-items:center; justify-content:center;"
             onclick="this.style.display='none'">
            <img id="promo-lightbox-img" src="" alt="" style="max-width:92vw; max-height:92vh; border-radius:12px; box-shadow:0 25px 60px rgba(0,0,0,0.5);">
            <button onclick="document.getElementById('promo-lightbox').style.display='none'" style="position:absolute; top:20px; right:20px; width:40px; height:40px; border-radius:50%; background:rgba(255,255,255,0.15); border:none; color:white; font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center;"
                    onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <i class="fa-regular fa-xmark"></i>
            </button>
        </div>
        <script>
        document.querySelectorAll('.promo-lightbox-trigger').forEach(function(el) {
            el.addEventListener('click', function() {
                var img = el.querySelector('img');
                if (img) {
                    var lb = document.getElementById('promo-lightbox');
                    document.getElementById('promo-lightbox-img').src = img.src;
                    document.getElementById('promo-lightbox-img').alt = img.alt;
                    lb.style.display = 'flex';
                }
            });
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') document.getElementById('promo-lightbox').style.display = 'none';
        });
        </script>

        <!-- Footer -->
        <x-public-footer />
    </div>
</body>

</html>
