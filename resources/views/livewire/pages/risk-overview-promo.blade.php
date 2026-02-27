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
                            <!-- Mockup Card -->
                            <div class="rounded-2xl p-6" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3 p-3 rounded-lg" style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.15);">
                                        <span class="w-2.5 h-2.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium" style="color: hsl(0, 0%, 95%);">Sicherheitswarnung - Istanbul</p>
                                            <p class="text-xs" style="color: hsl(220, 10%, 50%);">Betrifft: Geschäftsreise Türkei (12.03 - 15.03)</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 rounded-lg" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.15);">
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 flex-shrink-0"></span>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium" style="color: hsl(0, 0%, 95%);">Streik Flughafen - London</p>
                                            <p class="text-xs" style="color: hsl(220, 10%, 50%);">Betrifft: Kundenbesuch UK (20.03 - 22.03)</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 rounded-lg" style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.15);">
                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium" style="color: hsl(0, 0%, 95%);">Reisehinweis aktualisiert - Japan</p>
                                            <p class="text-xs" style="color: hsl(220, 10%, 50%);">Betrifft: Messe Tokio (05.04 - 10.04)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Länder-Ansicht -->
                        <div class="grid md:grid-cols-2 gap-12 items-center mb-20">
                            <!-- Mockup Card (links) -->
                            <div class="rounded-2xl p-6 order-2 md:order-1" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between p-3 rounded-lg" style="border: 1px solid hsl(220, 13%, 20%);">
                                        <div class="flex items-center gap-3">
                                            <span class="text-xl">🇹🇷</span>
                                            <div>
                                                <p class="text-sm font-medium" style="color: hsl(0, 0%, 95%);">Türkei</p>
                                                <p class="text-xs" style="color: hsl(220, 10%, 50%);">3 aktive Ereignisse</p>
                                            </div>
                                        </div>
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">Hoch</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 rounded-lg" style="border: 1px solid hsl(220, 13%, 20%);">
                                        <div class="flex items-center gap-3">
                                            <span class="text-xl">🇬🇧</span>
                                            <div>
                                                <p class="text-sm font-medium" style="color: hsl(0, 0%, 95%);">Vereinigtes Königreich</p>
                                                <p class="text-xs" style="color: hsl(220, 10%, 50%);">1 aktives Ereignis</p>
                                            </div>
                                        </div>
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">Mittel</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 rounded-lg" style="border: 1px solid hsl(220, 13%, 20%);">
                                        <div class="flex items-center gap-3">
                                            <span class="text-xl">🇯🇵</span>
                                            <div>
                                                <p class="text-sm font-medium" style="color: hsl(0, 0%, 95%);">Japan</p>
                                                <p class="text-xs" style="color: hsl(220, 10%, 50%);">1 aktives Ereignis</p>
                                            </div>
                                        </div>
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">Info</span>
                                    </div>
                                </div>
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
                            <!-- Mockup Card -->
                            <div class="rounded-2xl p-6" style="background: hsl(220, 13%, 13%); border: 1px solid hsl(220, 13%, 20%);">
                                <p class="text-xs font-medium mb-3" style="color: hsl(220, 10%, 50%);">Priorität</p>
                                <div class="flex flex-wrap gap-2 mb-5">
                                    <span class="px-3 py-1.5 text-xs font-medium rounded-full" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">Kritisch</span>
                                    <span class="px-3 py-1.5 text-xs font-medium rounded-full" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">Hoch</span>
                                    <span class="px-3 py-1.5 text-xs font-medium rounded-full" style="background: rgba(234, 179, 8, 0.15); color: #facc15;">Mittel</span>
                                    <span class="px-3 py-1.5 text-xs font-medium rounded-full" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">Info</span>
                                </div>
                                <p class="text-xs font-medium mb-3" style="color: hsl(220, 10%, 50%);">Labels</p>
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full" style="background: rgba(139, 92, 246, 0.15); color: #a78bfa;">
                                        <i class="fa-regular fa-tag text-[10px]"></i> VIP-Reisen
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                                        <i class="fa-regular fa-tag text-[10px]"></i> EMEA
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full" style="background: rgba(14, 165, 233, 0.15); color: #38bdf8;">
                                        <i class="fa-regular fa-tag text-[10px]"></i> Vorstand
                                    </span>
                                </div>
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

        <!-- Footer -->
        <x-public-footer />
    </div>
</body>

</html>
