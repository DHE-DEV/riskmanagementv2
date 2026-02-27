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
                <section class="relative overflow-hidden" style="background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);">
                    <div class="max-w-5xl mx-auto px-6 py-20 text-center relative z-10">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white/10 backdrop-blur-sm mb-8">
                            <i class="fa-regular fa-shield-exclamation text-4xl text-white"></i>
                        </div>
                        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">TravelAlert</h1>
                        <p class="text-xl text-blue-200 mb-10 max-w-2xl mx-auto">
                            Ihre Reisen. Unsere Warnungen. Ihr Schutz.
                        </p>
                        @if($isLoggedIn)
                            <a href="{{ route('customer.dashboard') }}"
                               class="inline-flex items-center px-8 py-3 bg-white text-blue-900 font-semibold rounded-lg hover:bg-blue-50 transition-colors shadow-lg">
                                <i class="fa-regular fa-unlock mr-2"></i>
                                Jetzt freischalten
                            </a>
                        @else
                            <a href="{{ route('customer.login') }}"
                               class="inline-flex items-center px-8 py-3 bg-white text-blue-900 font-semibold rounded-lg hover:bg-blue-50 transition-colors shadow-lg">
                                <i class="fa-regular fa-right-to-bracket mr-2"></i>
                                Jetzt anmelden
                            </a>
                        @endif
                    </div>
                    <!-- Decorative circles -->
                    <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/10 rounded-full -translate-y-1/2 translate-x-1/3"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-400/10 rounded-full translate-y-1/3 -translate-x-1/4"></div>
                </section>

                <!-- 2. Feature-Grid -->
                <section class="bg-white py-16 px-6">
                    <div class="max-w-5xl mx-auto">
                        <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">Was TravelAlert für Sie leistet</h2>
                        <div class="grid md:grid-cols-3 gap-8">
                            <!-- Weltweite Abdeckung -->
                            <div class="text-center p-6 rounded-xl bg-gray-50 hover:shadow-md transition-shadow">
                                <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-blue-100 text-blue-600 mb-4">
                                    <i class="fa-regular fa-globe text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Weltweite Abdeckung</h3>
                                <p class="text-gray-600 text-sm">
                                    Sicherheitsinformationen für über 200 Länder und Regionen weltweit - stets aktuell und zuverlässig.
                                </p>
                            </div>
                            <!-- Echtzeit-Warnungen -->
                            <div class="text-center p-6 rounded-xl bg-gray-50 hover:shadow-md transition-shadow">
                                <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-amber-100 text-amber-600 mb-4">
                                    <i class="fa-regular fa-bell text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Echtzeit-Warnungen</h3>
                                <p class="text-gray-600 text-sm">
                                    Sofortige Benachrichtigungen bei sicherheitsrelevanten Ereignissen in Ihren Reisezielen.
                                </p>
                            </div>
                            <!-- Interaktive Risikokarte -->
                            <div class="text-center p-6 rounded-xl bg-gray-50 hover:shadow-md transition-shadow">
                                <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-emerald-100 text-emerald-600 mb-4">
                                    <i class="fa-regular fa-map text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Interaktive Risikokarte</h3>
                                <p class="text-gray-600 text-sm">
                                    Visualisieren Sie Risiken auf einer interaktiven Weltkarte mit Echtzeit-Ereignissen und Risikozonen.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 3. Feature-Showcase -->
                <section class="bg-gray-50 py-16 px-6">
                    <div class="max-w-5xl mx-auto">
                        <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">TravelAlert im Detail</h2>

                        <!-- Reisen-Monitoring -->
                        <div class="flex flex-col md:flex-row items-center gap-8 mb-14">
                            <div class="md:w-1/2">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-100 text-blue-600 mb-3">
                                    <i class="fa-regular fa-suitcase-rolling text-lg"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 mb-3">Reisen-Monitoring</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    Hinterlegen Sie Ihre Geschäftsreisen und lassen Sie diese automatisch überwachen. TravelAlert ordnet sicherheitsrelevante Ereignisse automatisch Ihren aktiven Reisen zu und informiert Sie sofort, wenn Handlungsbedarf besteht.
                                </p>
                            </div>
                            <div class="md:w-1/2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg border border-red-100">
                                        <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">Sicherheitswarnung - Istanbul</p>
                                            <p class="text-xs text-gray-500">Betrifft: Geschäftsreise Türkei (12.03 - 15.03)</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-lg border border-amber-100">
                                        <span class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">Streik Flughafen - London</p>
                                            <p class="text-xs text-gray-500">Betrifft: Kundenbesuch UK (20.03 - 22.03)</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg border border-blue-100">
                                        <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">Reisehinweis aktualisiert - Japan</p>
                                            <p class="text-xs text-gray-500">Betrifft: Messe Tokio (05.04 - 10.04)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Länder-Ansicht -->
                        <div class="flex flex-col md:flex-row-reverse items-center gap-8 mb-14">
                            <div class="md:w-1/2">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 mb-3">
                                    <i class="fa-regular fa-earth-europe text-lg"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 mb-3">Länder-Ansicht</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    Analysieren Sie Risiken länderweise mit detaillierten Informationen zu aktuellen Ereignissen, betroffenen Reisenden und Risikoeinschätzungen. Die Kartenansicht zeigt Ihnen auf einen Blick, wo Handlungsbedarf besteht.
                                </p>
                            </div>
                            <div class="md:w-1/2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100">
                                        <div class="flex items-center gap-3">
                                            <span class="text-xl">🇹🇷</span>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Türkei</p>
                                                <p class="text-xs text-gray-500">3 aktive Ereignisse</p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-full">Hoch</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100">
                                        <div class="flex items-center gap-3">
                                            <span class="text-xl">🇬🇧</span>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Vereinigtes Königreich</p>
                                                <p class="text-xs text-gray-500">1 aktives Ereignis</p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium bg-amber-100 text-amber-700 rounded-full">Mittel</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100">
                                        <div class="flex items-center gap-3">
                                            <span class="text-xl">🇯🇵</span>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Japan</p>
                                                <p class="text-xs text-gray-500">1 aktives Ereignis</p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">Info</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter & Labels -->
                        <div class="flex flex-col md:flex-row items-center gap-8">
                            <div class="md:w-1/2">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-violet-100 text-violet-600 mb-3">
                                    <i class="fa-regular fa-tags text-lg"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 mb-3">Filter & Labels</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    Filtern Sie Ereignisse nach Priorität, Zeitraum oder eigenen Labels. Organisieren Sie Ihre Reisen und Ereignisse mit individuellen Labels für maximale Übersicht.
                                </p>
                            </div>
                            <div class="md:w-1/2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <span class="px-3 py-1.5 text-xs font-medium bg-red-100 text-red-700 rounded-full">Kritisch</span>
                                    <span class="px-3 py-1.5 text-xs font-medium bg-amber-100 text-amber-700 rounded-full">Hoch</span>
                                    <span class="px-3 py-1.5 text-xs font-medium bg-yellow-100 text-yellow-700 rounded-full">Mittel</span>
                                    <span class="px-3 py-1.5 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">Info</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-purple-100 text-purple-700 rounded-full">
                                        <i class="fa-regular fa-tag text-[10px]"></i> VIP-Reisen
                                    </span>
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-emerald-100 text-emerald-700 rounded-full">
                                        <i class="fa-regular fa-tag text-[10px]"></i> EMEA
                                    </span>
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-sky-100 text-sky-700 rounded-full">
                                        <i class="fa-regular fa-tag text-[10px]"></i> Vorstand
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 4. Statistik-Leiste -->
                <section style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);" class="py-10 px-6">
                    <div class="max-w-5xl mx-auto">
                        <div class="grid grid-cols-3 gap-8 text-center">
                            <div>
                                <p class="text-3xl font-bold text-white mb-1">200+</p>
                                <p class="text-sm text-slate-400">Länder</p>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-white mb-1">Echtzeit</p>
                                <p class="text-sm text-slate-400">Warnungen</p>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-white mb-1">24/7</p>
                                <p class="text-sm text-slate-400">Monitoring</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 5. So funktioniert's -->
                <section class="bg-white py-16 px-6">
                    <div class="max-w-5xl mx-auto">
                        <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">So funktioniert's</h2>
                        <div class="grid md:grid-cols-3 gap-8">
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-600 text-white font-bold text-lg mb-4">1</div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Reisen anlegen</h3>
                                <p class="text-gray-600 text-sm">
                                    Hinterlegen Sie Ihre Geschäftsreisen mit Reisezielen und Zeiträumen - oder importieren Sie diese automatisch.
                                </p>
                            </div>
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-600 text-white font-bold text-lg mb-4">2</div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Automatische Analyse</h3>
                                <p class="text-gray-600 text-sm">
                                    TravelAlert analysiert kontinuierlich sicherheitsrelevante Ereignisse und ordnet diese Ihren Reisen zu.
                                </p>
                            </div>
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-600 text-white font-bold text-lg mb-4">3</div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Sofortige Warnung</h3>
                                <p class="text-gray-600 text-sm">
                                    Bei relevanten Ereignissen werden Sie sofort informiert - mit konkreten Handlungsempfehlungen.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 6. Zielgruppen -->
                <section class="bg-gray-50 py-16 px-6">
                    <div class="max-w-5xl mx-auto">
                        <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">Für wen ist TravelAlert?</h2>
                        <div class="grid md:grid-cols-3 gap-8">
                            <div class="bg-white p-6 rounded-xl border border-gray-200 hover:shadow-md transition-shadow">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-blue-100 text-blue-600 mb-4">
                                    <i class="fa-regular fa-user-tie text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Travel Manager</h3>
                                <p class="text-gray-600 text-sm">
                                    Behalten Sie alle Geschäftsreisen im Blick und erfüllen Sie Ihre Fürsorgepflicht gegenüber reisenden Mitarbeitern.
                                </p>
                            </div>
                            <div class="bg-white p-6 rounded-xl border border-gray-200 hover:shadow-md transition-shadow">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-amber-100 text-amber-600 mb-4">
                                    <i class="fa-regular fa-shield-halved text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Sicherheitsbeauftragte</h3>
                                <p class="text-gray-600 text-sm">
                                    Erhalten Sie fundierte Risikoeinschätzungen und treffen Sie Entscheidungen auf Basis aktueller Daten.
                                </p>
                            </div>
                            <div class="bg-white p-6 rounded-xl border border-gray-200 hover:shadow-md transition-shadow">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 mb-4">
                                    <i class="fa-regular fa-building-columns text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Geschäftsführung</h3>
                                <p class="text-gray-600 text-sm">
                                    Überblick über alle Reiserisiken auf einen Blick - für strategische Entscheidungen und Compliance.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 7. CTA-Abschluss -->
                <section class="relative overflow-hidden" style="background: linear-gradient(135deg, #1e40af 0%, #1e3a5f 100%);">
                    <div class="max-w-5xl mx-auto px-6 py-16 text-center relative z-10">
                        <h2 class="text-3xl font-bold text-white mb-4">Starten Sie jetzt mit TravelAlert</h2>
                        <p class="text-blue-200 mb-8 max-w-xl mx-auto">
                            Schützen Sie Ihre Reisenden mit Echtzeit-Sicherheitsinformationen und automatischem Reise-Monitoring.
                        </p>
                        @if($isLoggedIn)
                            <a href="{{ route('customer.dashboard') }}"
                               class="inline-flex items-center px-8 py-3 bg-white text-blue-900 font-semibold rounded-lg hover:bg-blue-50 transition-colors shadow-lg">
                                <i class="fa-regular fa-unlock mr-2"></i>
                                Jetzt freischalten
                            </a>
                        @else
                            <a href="{{ route('customer.login') }}"
                               class="inline-flex items-center px-8 py-3 bg-white text-blue-900 font-semibold rounded-lg hover:bg-blue-50 transition-colors shadow-lg">
                                <i class="fa-regular fa-right-to-bracket mr-2"></i>
                                Jetzt anmelden
                            </a>
                        @endif
                    </div>
                    <div class="absolute top-0 left-0 w-72 h-72 bg-white/5 rounded-full -translate-x-1/3 -translate-y-1/2"></div>
                    <div class="absolute bottom-0 right-0 w-56 h-56 bg-white/5 rounded-full translate-x-1/4 translate-y-1/3"></div>
                </section>

            </div>
        </div>

        <!-- Footer -->
        <x-public-footer />
    </div>
</body>

</html>
