<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Dokumentation - Passolution Travel Information Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        html { scroll-behavior: smooth; }
        .api-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .api-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

    {{-- Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/docs/api" class="flex items-center space-x-2 text-gray-900 hover:text-brand-600 transition-colors">
                    <i class="fa-solid fa-book text-brand-600 text-lg"></i>
                    <span class="text-xl font-bold tracking-tight">Passolution</span>
                    <span class="text-sm text-gray-400 font-medium ml-2 hidden sm:inline">Docs</span>
                </a>
                <a href="/customer/dashboard" class="inline-flex items-center space-x-2 text-sm font-medium text-gray-600 hover:text-brand-600 transition-colors">
                    <span>Zur Plattform</span>
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="pt-16">
        <div class="bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">
                <div class="max-w-3xl">
                    <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">API Dokumentation</h1>
                    <p class="mt-3 text-brand-200 text-lg font-medium">Passolution Travel Information Platform</p>
                    <p class="mt-6 text-lg text-brand-100 leading-relaxed">
                        Umfassende API-Referenz für die Integration mit der Passolution Travel Information Platform.
                        Alle APIs nutzen JSON und sind über Bearer-Token geschützt.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- API Cards --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Events API (GTM) --}}
            <a href="/docs/api/gtm" class="api-card block bg-white rounded-xl border border-gray-200 shadow-sm p-6 group">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-shield-halved text-red-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-brand-600 transition-colors">Events API (GTM)</h3>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">7 Endpoints</span>
                </div>
                <p class="mt-4 text-sm text-gray-600 leading-relaxed">
                    Read-only Zugriff auf aktive Sicherheits- und Reiserisiko-Events mit Filtern nach Risikostufe, Land, Kategorie und Region.
                </p>
                <div class="mt-4 flex items-center text-xs text-gray-400">
                    <i class="fa-solid fa-lock mr-1.5"></i>
                    Bearer Token (Sanctum)
                </div>
            </a>

            {{-- Custom Event API --}}
            <a href="/docs/api/events" class="api-card block bg-white rounded-xl border border-gray-200 shadow-sm p-6 group">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-calendar-plus text-orange-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-brand-600 transition-colors">Custom Event API</h3>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-orange-700">8 Endpoints</span>
                </div>
                <p class="mt-4 text-sm text-gray-600 leading-relaxed">
                    REST API für API-Partner zum Erstellen, Aktualisieren und Löschen eigener Sicherheits-Events auf dem Dashboard.
                </p>
                <div class="mt-4 flex items-center text-xs text-gray-400">
                    <i class="fa-solid fa-lock mr-1.5"></i>
                    Bearer Token (API-Client)
                </div>
            </a>

            {{-- Feed API --}}
            <a href="/docs/api/feeds" class="api-card block bg-white rounded-xl border border-gray-200 shadow-sm p-6 group">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-rss text-green-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-brand-600 transition-colors">Feed API</h3>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">11 Endpoints</span>
                </div>
                <p class="mt-4 text-sm text-gray-600 leading-relaxed">
                    RSS/Atom-Feeds für aktuelle Events und Länderinformationen. Keine Authentifizierung erforderlich.
                </p>
                <div class="mt-4 flex items-center text-xs text-gray-400">
                    <i class="fa-solid fa-globe mr-1.5"></i>
                    Keine (öffentlich)
                </div>
            </a>

            {{-- Folder Import API --}}
            <a href="/docs/api/folders" class="api-card block bg-white rounded-xl border border-gray-200 shadow-sm p-6 group">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-folder-open text-blue-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-brand-600 transition-colors">Folder Import API</h3>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">4 Endpoints</span>
                </div>
                <p class="mt-4 text-sm text-gray-600 leading-relaxed">
                    Import von Reisedaten (Folders) mit Hotels, Flügen, Kreuzfahrten und Mietwagen. Queue-basierte Verarbeitung.
                </p>
                <div class="mt-4 flex items-center text-xs text-gray-400">
                    <i class="fa-solid fa-lock mr-1.5"></i>
                    Bearer Token (Sanctum)
                </div>
            </a>

            {{-- Customer Settings API --}}
            <a href="/docs/api/organisation" class="api-card block bg-white rounded-xl border border-gray-200 shadow-sm p-6 group">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-gear text-purple-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-brand-600 transition-colors">Customer Settings API</h3>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700">52 Endpoints</span>
                </div>
                <p class="mt-4 text-sm text-gray-600 leading-relaxed">
                    Verwaltung von Stammdaten, Adressen, Kontaktinformationen, Organisationsstruktur, Abteilungen, Benutzer und Gruppen.
                </p>
                <div class="mt-4 flex items-center text-xs text-gray-400">
                    <i class="fa-solid fa-lock mr-1.5"></i>
                    Bearer Token (Sanctum)
                </div>
            </a>

            {{-- Plugin Domain API --}}
            <a href="/docs/api/plugin" class="api-card block bg-white rounded-xl border border-gray-200 shadow-sm p-6 group">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-teal-50 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-puzzle-piece text-teal-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-brand-600 transition-colors">Plugin Domain API</h3>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700">7 Endpoints</span>
                </div>
                <p class="mt-4 text-sm text-gray-600 leading-relaxed">
                    Domain-Verwaltung für Plugin-Kunden. Einzel- und Massenoperationen für erlaubte Domains.
                </p>
                <div class="mt-4 flex items-center text-xs text-gray-400">
                    <i class="fa-solid fa-key mr-1.5"></i>
                    Plugin-Key (pk_live_*)
                </div>
            </a>

        </div>
    </section>

    {{-- Authentication Section --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-20" id="auth">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h2 class="text-2xl font-bold text-gray-900">Authentifizierung</h2>
                <p class="mt-1 text-sm text-gray-500">Drei Authentifizierungsmethoden werden in den APIs verwendet.</p>
            </div>
            <div class="divide-y divide-gray-100">
                {{-- Sanctum --}}
                <div class="px-6 py-5">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-brand-50 rounded-lg flex items-center justify-center mt-0.5">
                            <i class="fa-solid fa-shield-halved text-brand-600"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Sanctum Bearer Token</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Für Kunden-APIs (Events GTM, Folder Import, Customer Settings). Token wird über die Plattform generiert und im
                                <code class="bg-gray-100 text-gray-800 px-1.5 py-0.5 rounded text-xs font-mono">Authorization: Bearer &lt;token&gt;</code> Header gesendet.
                            </p>
                        </div>
                    </div>
                </div>
                {{-- API-Client --}}
                <div class="px-6 py-5">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center mt-0.5">
                            <i class="fa-solid fa-handshake text-orange-500"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">API-Client Bearer Token</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Für Event-Partner (Custom Event API). API-Clients erhalten einen dedizierten Token für das Erstellen und Verwalten eigener Events.
                                Ebenfalls als <code class="bg-gray-100 text-gray-800 px-1.5 py-0.5 rounded text-xs font-mono">Authorization: Bearer &lt;token&gt;</code> Header.
                            </p>
                        </div>
                    </div>
                </div>
                {{-- Plugin Key --}}
                <div class="px-6 py-5">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-teal-50 rounded-lg flex items-center justify-center mt-0.5">
                            <i class="fa-solid fa-key text-teal-500"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Plugin Key</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Für die Plugin Domain API. Schlüssel im Format
                                <code class="bg-gray-100 text-gray-800 px-1.5 py-0.5 rounded text-xs font-mono">pk_live_*</code>,
                                wird als Query-Parameter oder Header übergeben.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Base URLs Section --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10" id="base-urls">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h2 class="text-2xl font-bold text-gray-900">Basis-URLs</h2>
                <p class="mt-1 text-sm text-gray-500">Alle API-Anfragen verwenden eine der folgenden Basis-URLs.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-3 font-semibold text-gray-600 uppercase tracking-wider text-xs">Basis-URL</th>
                            <th class="px-6 py-3 font-semibold text-gray-600 uppercase tracking-wider text-xs">Beschreibung</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-6 py-4">
                                <code class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-mono">https://platform.passolution.de/api</code>
                            </td>
                            <td class="px-6 py-4 text-gray-600">Haupt-API</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4">
                                <code class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-mono">https://global-travel-monitor.eu/feed</code>
                            </td>
                            <td class="px-6 py-4 text-gray-600">Feed API</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="mt-20 border-t border-gray-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="md:flex md:items-start md:justify-between">
                <div>
                    <p class="text-base font-semibold text-gray-900">Passolution Travel Information Platform</p>
                    <p class="mt-1 text-sm text-gray-500">&copy; {{ date('Y') }} Passolution. Alle Rechte vorbehalten.</p>
                </div>
                <div class="mt-6 md:mt-0">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">API-Referenzen</p>
                    <ul class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                        <li><a href="/docs/api/gtm" class="text-gray-600 hover:text-brand-600 transition-colors">Events API (GTM)</a></li>
                        <li><a href="/docs/api/events" class="text-gray-600 hover:text-brand-600 transition-colors">Custom Event API</a></li>
                        <li><a href="/docs/api/feeds" class="text-gray-600 hover:text-brand-600 transition-colors">Feed API</a></li>
                        <li><a href="/docs/api/folders" class="text-gray-600 hover:text-brand-600 transition-colors">Folder Import API</a></li>
                        <li><a href="/docs/api/organisation" class="text-gray-600 hover:text-brand-600 transition-colors">Customer Settings API</a></li>
                        <li><a href="/docs/api/plugin" class="text-gray-600 hover:text-brand-600 transition-colors">Plugin Domain API</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
