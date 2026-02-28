@php
$active = 'risk-overview';
$version = '1.1.0';
@endphp
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TravelAlert - Global Travel Monitor</title>

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
            <div class="promo-content">

                <!-- 1. Hero-Bereich -->
                <section class="relative min-h-[520px] flex items-center justify-center overflow-hidden" style="background: hsl(220, 13%, 10%);">
                    <!-- Glow blobs -->
                    <div class="glow-blob animate-pulse-slow" style="top: 15%; left: 20%; width: 450px; height: 450px; background: rgba(59, 130, 246, 0.12);"></div>
                    <div class="glow-blob animate-pulse-slow" style="top: 25%; left: 30%; width: 256px; height: 256px; background: rgba(59, 130, 246, 0.18); filter: blur(80px);"></div>
                    <div class="glow-blob animate-float" style="bottom: 15%; right: 20%; width: 320px; height: 320px; background: rgba(59, 130, 246, 0.08); filter: blur(60px);"></div>

                    <div class="max-w-5xl mx-auto px-6 py-20 text-center relative z-10">
                        <!-- Badge -->
                        <div class="animate-fade-up inline-flex items-center gap-2 px-4 py-2 rounded-full border mb-8" style="background: hsl(220, 13%, 18%); border-color: hsl(220, 13%, 25%);">
                            <i class="fa-regular fa-shield-exclamation text-blue-400"></i>
                            <span class="text-sm font-medium text-blue-400">Reisesicherheit in Echtzeit</span>
                        </div>

                        <!-- Headline -->
                        <h1 class="animate-fade-up-delay-1 text-5xl md:text-6xl lg:text-7xl font-extrabold leading-tight mb-6" style="font-family: Archivo, sans-serif;">
                            <span style="color: hsl(0, 0%, 98%);">Travel</span><span class="text-blue-400">Alert</span>
                        </h1>

                        <!-- Subtext -->
                        <p class="animate-fade-up-delay-2 text-lg md:text-xl max-w-3xl mx-auto mb-10 leading-relaxed" style="color: hsl(220, 10%, 60%);">
                            Ihre Reisen. Unsere Warnungen. Ihr Schutz.<br>
                            Behalten Sie weltweite Sicherheitsereignisse im Blick und schützen Sie Ihre Reisenden mit Echtzeit-Monitoring.
                        </p>

                        <!-- CTA Buttons -->
                        <div class="animate-fade-up-delay-3 flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                            <button onclick="document.dispatchEvent(new CustomEvent('open-order-modal'))"
                               class="inline-flex items-center px-8 py-3.5 font-semibold rounded-xl transition-all shadow-lg cursor-pointer"
                               style="background: #CEE741; color: #002742; box-shadow: 0 10px 25px -5px rgba(206, 231, 65, 0.3);"
                               onmouseover="this.style.opacity='0.9'; this.style.boxShadow='0 10px 30px -5px rgba(206, 231, 65, 0.5)'"
                               onmouseout="this.style.opacity='1'; this.style.boxShadow='0 10px 25px -5px rgba(206, 231, 65, 0.3)'"
                            >
                                <i class="fa-regular fa-cart-shopping mr-2"></i>
                                Jetzt bestellen
                            </button>
                            @if($isLoggedIn)
                                <a href="{{ route('customer.dashboard') }}"
                                   class="inline-flex items-center px-8 py-3.5 bg-blue-500 text-white font-semibold rounded-xl hover:bg-blue-400 transition-all shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50">
                                    <i class="fa-regular fa-unlock mr-2"></i>
                                    Jetzt freischalten
                                </a>
                            @else
                                <a href="{{ route('customer.login') }}"
                                   class="inline-flex items-center px-8 py-3.5 bg-blue-500 text-white font-semibold rounded-xl hover:bg-blue-400 transition-all shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50">
                                    <i class="fa-regular fa-right-to-bracket mr-2"></i>
                                    Jetzt anmelden
                                </a>
                            @endif
                        </div>

                        <!-- Stats row -->
                        <div class="animate-fade-up-delay-4 flex flex-wrap justify-center gap-6 lg:gap-10">
                            <div class="flex items-center gap-2 px-5 py-3 rounded-xl shadow-sm" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <i class="fa-regular fa-globe text-blue-400"></i>
                                <span class="text-sm font-medium" style="color: hsl(0, 0%, 98%);">200+ Länder</span>
                            </div>
                            <div class="flex items-center gap-2 px-5 py-3 rounded-xl shadow-sm" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <i class="fa-regular fa-bolt text-blue-400"></i>
                                <span class="text-sm font-medium" style="color: hsl(0, 0%, 98%);">Echtzeit-Warnungen</span>
                            </div>
                            <div class="flex items-center gap-2 px-5 py-3 rounded-xl shadow-sm" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <i class="fa-regular fa-clock text-blue-400"></i>
                                <span class="text-sm font-medium" style="color: hsl(0, 0%, 98%);">24/7 Monitoring</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. Feature-Grid (4 Spalten) -->
                <section class="py-20 px-6" style="background: hsl(220, 13%, 10%);">
                    <div class="max-w-5xl mx-auto">
                        <div class="text-center mb-12">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border mb-4" style="background: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.25);">
                                <span class="text-sm font-medium text-blue-400">Features</span>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: hsl(0, 0%, 98%); font-family: Archivo, sans-serif;">Was TravelAlert für Sie leistet</h2>
                            <p class="max-w-2xl mx-auto" style="color: hsl(220, 10%, 60%);">
                                Der Global Travel Monitor vereint alle relevanten Informationen für sicheres Reisen auf einer Plattform.
                            </p>
                        </div>
                        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            <!-- Interaktive Karte -->
                            <div class="p-6 rounded-2xl backdrop-blur-sm" style="background: hsl(220, 13%, 18%)/50; border: 1px solid hsl(220, 13%, 20%); background: hsla(220, 13%, 18%, 0.5);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(59, 130, 246, 0.15);">
                                    <i class="fa-regular fa-map text-xl text-blue-400"></i>
                                </div>
                                <h3 class="font-semibold mb-2 text-center" style="color: hsl(0, 0%, 98%);">Interaktive Karte</h3>
                                <p class="text-sm text-center" style="color: hsl(220, 10%, 60%);">Alle Events auf einer übersichtlichen Karte visualisiert</p>
                            </div>
                            <!-- Echtzeit-Warnungen -->
                            <div class="p-6 rounded-2xl backdrop-blur-sm" style="background: hsla(220, 13%, 18%, 0.5); border: 1px solid hsl(220, 13%, 20%);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(59, 130, 246, 0.15);">
                                    <i class="fa-regular fa-bell text-xl text-blue-400"></i>
                                </div>
                                <h3 class="font-semibold mb-2 text-center" style="color: hsl(0, 0%, 98%);">Echtzeit-Warnungen</h3>
                                <p class="text-sm text-center" style="color: hsl(220, 10%, 60%);">Aktuelle Sicherheitshinweise und Reisewarnungen</p>
                            </div>
                            <!-- Reisehinweise -->
                            <div class="p-6 rounded-2xl backdrop-blur-sm" style="background: hsla(220, 13%, 18%, 0.5); border: 1px solid hsl(220, 13%, 20%);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(59, 130, 246, 0.15);">
                                    <i class="fa-regular fa-shield-exclamation text-xl text-blue-400"></i>
                                </div>
                                <h3 class="font-semibold mb-2 text-center" style="color: hsl(0, 0%, 98%);">Sicherheitswarnungen</h3>
                                <p class="text-sm text-center" style="color: hsl(220, 10%, 60%);">Politische Unruhen, Terrorwarnungen und Sicherheitshinweise</p>
                            </div>
                            <!-- Echtzeit-Updates -->
                            <div class="p-6 rounded-2xl backdrop-blur-sm" style="background: hsla(220, 13%, 18%, 0.5); border: 1px solid hsl(220, 13%, 20%);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(59, 130, 246, 0.15);">
                                    <i class="fa-regular fa-globe text-xl text-blue-400"></i>
                                </div>
                                <h3 class="font-semibold mb-2 text-center" style="color: hsl(0, 0%, 98%);">Weltweite Abdeckung</h3>
                                <p class="text-sm text-center" style="color: hsl(220, 10%, 60%);">Ereignisse aus allen Ländern und Regionen der Welt</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 3. Feature-Showcase: Reisen-Monitoring -->
                <section class="py-20 px-6" style="background: hsl(220, 13%, 10%); border-top: 1px solid hsl(220, 13%, 17%);">
                    <div class="max-w-5xl mx-auto">
                        <div class="grid md:grid-cols-2 gap-12 items-center mb-20">
                            <div>
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border mb-4" style="background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.2);">
                                    <i class="fa-regular fa-suitcase-rolling text-blue-400 text-sm"></i>
                                    <span class="text-xs font-medium text-blue-400">Reisen-Monitoring</span>
                                </div>
                                <h3 class="text-2xl md:text-3xl font-bold mb-4" style="color: hsl(0, 0%, 98%); font-family: Archivo, sans-serif;">Automatische Zuordnung von Events zu Reisen</h3>
                                <p class="leading-relaxed mb-6" style="color: hsl(220, 10%, 60%);">
                                    Hinterlegen Sie Ihre Geschäftsreisen und lassen Sie diese automatisch überwachen. TravelAlert ordnet sicherheitsrelevante Ereignisse automatisch Ihren aktiven Reisen zu.
                                </p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-blue-400 mt-0.5 flex-shrink-0"></i>
                                        <span style="color: hsl(220, 10%, 70%);">Automatisches Reise-Event-Matching</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-blue-400 mt-0.5 flex-shrink-0"></i>
                                        <span style="color: hsl(220, 10%, 70%);">Sofortige Benachrichtigungen</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-blue-400 mt-0.5 flex-shrink-0"></i>
                                        <span style="color: hsl(220, 10%, 70%);">Priorisierte Darstellung nach Relevanz</span>
                                    </li>
                                </ul>
                            </div>
                            <!-- Screenshot -->
                            <div class="rounded-2xl overflow-hidden shadow-2xl" style="border: 1px solid hsl(220, 13%, 20%);">
                                <img src="{{ asset('images/travelalert/GTM-TA-01.png') }}" alt="TravelAlert Reisen-Monitoring" class="w-full h-auto" loading="lazy">
                            </div>
                        </div>

                        <!-- Länder-Ansicht -->
                        <div class="grid md:grid-cols-2 gap-12 items-center mb-20">
                            <!-- Screenshot (links) -->
                            <div class="rounded-2xl overflow-hidden shadow-2xl order-2 md:order-1" style="border: 1px solid hsl(220, 13%, 20%);">
                                <img src="{{ asset('images/travelalert/GMT-TA-02.png') }}" alt="TravelAlert Länder-Ansicht" class="w-full h-auto" loading="lazy">
                            </div>
                            <div class="order-1 md:order-2">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border mb-4" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.2);">
                                    <i class="fa-regular fa-earth-europe text-emerald-400 text-sm"></i>
                                    <span class="text-xs font-medium text-emerald-400">Länder-Ansicht</span>
                                </div>
                                <h3 class="text-2xl md:text-3xl font-bold mb-4" style="color: hsl(0, 0%, 98%); font-family: Archivo, sans-serif;">Länderweise Risikoanalyse</h3>
                                <p class="leading-relaxed mb-6" style="color: hsl(220, 10%, 60%);">
                                    Analysieren Sie Risiken länderweise mit detaillierten Informationen zu aktuellen Ereignissen, betroffenen Reisenden und Risikoeinschätzungen.
                                </p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-emerald-400 mt-0.5 flex-shrink-0"></i>
                                        <span style="color: hsl(220, 10%, 70%);">Detaillierte Länder-Risikoprofile</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-emerald-400 mt-0.5 flex-shrink-0"></i>
                                        <span style="color: hsl(220, 10%, 70%);">Betroffene Reisende pro Land</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-emerald-400 mt-0.5 flex-shrink-0"></i>
                                        <span style="color: hsl(220, 10%, 70%);">Interaktive Kartenansicht</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Filter & Labels -->
                        <div class="grid md:grid-cols-2 gap-12 items-center">
                            <div>
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border mb-4" style="background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.2);">
                                    <i class="fa-regular fa-tags text-violet-400 text-sm"></i>
                                    <span class="text-xs font-medium text-violet-400">Filter & Labels</span>
                                </div>
                                <h3 class="text-2xl md:text-3xl font-bold mb-4" style="color: hsl(0, 0%, 98%); font-family: Archivo, sans-serif;">Filterung nach Priorität, Zeitraum & Labels</h3>
                                <p class="leading-relaxed mb-6" style="color: hsl(220, 10%, 60%);">
                                    Filtern Sie Ereignisse nach Priorität, Zeitraum oder eigenen Labels. Organisieren Sie Ihre Reisen und Ereignisse mit individuellen Labels für maximale Übersicht.
                                </p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-violet-400 mt-0.5 flex-shrink-0"></i>
                                        <span style="color: hsl(220, 10%, 70%);">Frei definierbare Labels</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-violet-400 mt-0.5 flex-shrink-0"></i>
                                        <span style="color: hsl(220, 10%, 70%);">Prioritäts- und Zeitraumfilter</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-regular fa-check text-violet-400 mt-0.5 flex-shrink-0"></i>
                                        <span style="color: hsl(220, 10%, 70%);">Labels für Reisen, Trips und Events</span>
                                    </li>
                                </ul>
                            </div>
                            <!-- Screenshot -->
                            <div class="rounded-2xl overflow-hidden shadow-2xl" style="border: 1px solid hsl(220, 13%, 20%);">
                                <img src="{{ asset('images/travelalert/GTM-TA-Kalenderansicht.png') }}" alt="TravelAlert Kalenderansicht" class="w-full h-auto" loading="lazy">
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 4. So funktioniert's -->
                <section class="py-20 px-6" style="background: hsl(220, 13%, 10%); border-top: 1px solid hsl(220, 13%, 17%);">
                    <div class="max-w-5xl mx-auto">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: hsl(0, 0%, 98%); font-family: Archivo, sans-serif;">So funktioniert's</h2>
                            <p style="color: hsl(220, 10%, 60%);" class="max-w-2xl mx-auto">In drei einfachen Schritten zur Reisesicherheit</p>
                        </div>
                        <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                            <div class="p-6 rounded-2xl hover:border-blue-500/30 transition-all duration-300 group" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background: rgba(59, 130, 246, 0.1);">
                                    <span class="text-blue-400 font-bold text-lg">1</span>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: hsl(0, 0%, 98%);">Reisen anlegen</h3>
                                <p class="text-sm" style="color: hsl(220, 10%, 60%);">
                                    Hinterlegen Sie Ihre Geschäftsreisen mit Reisezielen und Zeiträumen - oder importieren Sie diese automatisch.
                                </p>
                            </div>
                            <div class="p-6 rounded-2xl hover:border-blue-500/30 transition-all duration-300 group" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background: rgba(59, 130, 246, 0.1);">
                                    <span class="text-blue-400 font-bold text-lg">2</span>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: hsl(0, 0%, 98%);">Automatische Analyse</h3>
                                <p class="text-sm" style="color: hsl(220, 10%, 60%);">
                                    TravelAlert analysiert kontinuierlich sicherheitsrelevante Ereignisse und ordnet diese Ihren Reisen zu.
                                </p>
                            </div>
                            <div class="p-6 rounded-2xl hover:border-blue-500/30 transition-all duration-300 group" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background: rgba(59, 130, 246, 0.1);">
                                    <span class="text-blue-400 font-bold text-lg">3</span>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: hsl(0, 0%, 98%);">Sofortige Warnung</h3>
                                <p class="text-sm" style="color: hsl(220, 10%, 60%);">
                                    Bei relevanten Ereignissen werden Sie sofort informiert - mit konkreten Handlungsempfehlungen.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 5. Zielgruppen -->
                <section class="py-20 px-6" style="background: hsl(220, 13%, 10%); border-top: 1px solid hsl(220, 13%, 17%);">
                    <div class="max-w-5xl mx-auto">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: hsl(0, 0%, 98%); font-family: Archivo, sans-serif;">Für wen ist TravelAlert?</h2>
                            <p style="color: hsl(220, 10%, 60%);" class="max-w-2xl mx-auto">Maßgeschneidert für Unternehmen, die Verantwortung für Reisende tragen</p>
                        </div>
                        <div class="grid md:grid-cols-3 gap-6">
                            <div class="p-6 rounded-2xl hover:border-blue-500/30 transition-all duration-300 hover:shadow-lg group" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 group-hover:scale-105 transition-transform" style="background: rgba(59, 130, 246, 0.1);">
                                    <i class="fa-regular fa-user-tie text-xl text-blue-400"></i>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: hsl(0, 0%, 98%);">Travel Manager</h3>
                                <p class="text-sm" style="color: hsl(220, 10%, 60%);">
                                    Behalten Sie alle Geschäftsreisen im Blick und erfüllen Sie Ihre Fürsorgepflicht gegenüber reisenden Mitarbeitern.
                                </p>
                            </div>
                            <div class="p-6 rounded-2xl hover:border-blue-500/30 transition-all duration-300 hover:shadow-lg group" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 group-hover:scale-105 transition-transform" style="background: rgba(245, 158, 11, 0.1);">
                                    <i class="fa-regular fa-shield-halved text-xl text-amber-400"></i>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: hsl(0, 0%, 98%);">Sicherheitsbeauftragte</h3>
                                <p class="text-sm" style="color: hsl(220, 10%, 60%);">
                                    Erhalten Sie fundierte Risikoeinschätzungen und treffen Sie Entscheidungen auf Basis aktueller Daten.
                                </p>
                            </div>
                            <div class="p-6 rounded-2xl hover:border-blue-500/30 transition-all duration-300 hover:shadow-lg group" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 group-hover:scale-105 transition-transform" style="background: rgba(16, 185, 129, 0.1);">
                                    <i class="fa-regular fa-building-columns text-xl text-emerald-400"></i>
                                </div>
                                <h3 class="text-lg font-semibold mb-2" style="color: hsl(0, 0%, 98%);">Geschäftsführung</h3>
                                <p class="text-sm" style="color: hsl(220, 10%, 60%);">
                                    Überblick über alle Reiserisiken auf einen Blick - für strategische Entscheidungen und Compliance.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 6. CTA-Abschluss -->
                <section class="relative py-20 px-6 overflow-hidden" style="background: hsl(220, 13%, 10%); border-top: 1px solid hsl(220, 13%, 17%);">
                    <!-- Glow -->
                    <div class="glow-blob" style="top: 30%; left: 35%; width: 400px; height: 400px; background: rgba(59, 130, 246, 0.1);"></div>

                    <div class="max-w-3xl mx-auto text-center relative z-10">
                        <div class="rounded-2xl p-10" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(59, 130, 246, 0.03)); border: 1px solid rgba(59, 130, 246, 0.15);">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6" style="background: rgba(59, 130, 246, 0.1);">
                                <i class="fa-regular fa-shield-exclamation text-3xl text-blue-400"></i>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: hsl(0, 0%, 98%); font-family: Archivo, sans-serif;">Starten Sie jetzt mit TravelAlert</h2>
                            <p class="mb-8" style="color: hsl(220, 10%, 60%);">
                                Schützen Sie Ihre Reisenden mit Echtzeit-Sicherheitsinformationen und automatischem Reise-Monitoring.
                            </p>
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                                <button onclick="document.dispatchEvent(new CustomEvent('open-order-modal'))"
                                   class="inline-flex items-center px-8 py-3.5 font-semibold rounded-xl transition-all shadow-lg cursor-pointer"
                                   style="background: #CEE741; color: #002742; box-shadow: 0 10px 25px -5px rgba(206, 231, 65, 0.3);"
                                   onmouseover="this.style.opacity='0.9'; this.style.boxShadow='0 10px 30px -5px rgba(206, 231, 65, 0.5)'"
                                   onmouseout="this.style.opacity='1'; this.style.boxShadow='0 10px 25px -5px rgba(206, 231, 65, 0.3)'"
                                >
                                    <i class="fa-regular fa-cart-shopping mr-2"></i>
                                    Jetzt bestellen
                                </button>
                                @if($isLoggedIn)
                                    <a href="{{ route('customer.dashboard') }}"
                                       class="inline-flex items-center px-8 py-3.5 bg-blue-500 text-white font-semibold rounded-xl hover:bg-blue-400 transition-all shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50">
                                        <i class="fa-regular fa-unlock mr-2"></i>
                                        Jetzt freischalten
                                    </a>
                                @else
                                    <a href="{{ route('customer.login') }}"
                                       class="inline-flex items-center px-8 py-3.5 bg-blue-500 text-white font-semibold rounded-xl hover:bg-blue-400 transition-all shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50">
                                        <i class="fa-regular fa-right-to-bracket mr-2"></i>
                                        Jetzt anmelden
                                    </a>
                                @endif
                                <a href="https://global-travel-monitor.eu" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center px-8 py-3.5 font-semibold rounded-xl border transition-all" style="background: hsl(220, 13%, 18%); border-color: hsl(220, 13%, 25%); color: hsl(0, 0%, 90%);">
                                    <i class="fa-regular fa-arrow-up-right-from-square mr-2"></i>
                                    Plattform ansehen
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
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
                                <h2 class="text-lg font-bold text-white" style="font-family: Archivo, sans-serif;">TravelAlert bestellen</h2>
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
                                    Die Zusatzleistung TravelAlert wird <strong>bis zum 30.06.2026 kostenlos</strong> zur Verfügung gestellt. In diesem Zeitraum kann jederzeit per Mail an
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

        <!-- Footer -->
        <x-public-footer />
    </div>
</body>

</html>
