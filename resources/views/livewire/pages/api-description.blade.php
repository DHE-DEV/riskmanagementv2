<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Dokumentation - Global Travel Monitor</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 13px;
            overflow-x: auto;
            position: relative;
        }

        .code-block .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #475569;
            border: none;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .code-block:hover .copy-btn {
            opacity: 1;
        }

        .code-block .copy-btn:hover {
            background: #64748b;
        }

        .method-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            font-family: 'Monaco', 'Menlo', monospace;
            min-width: 60px;
            justify-content: center;
        }

        .method-get { background: #dcfce7; color: #166534; }
        .method-post { background: #dbeafe; color: #1e40af; }
        .method-put { background: #fef3c7; color: #92400e; }
        .method-delete { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-gray-400 to-gray-800 text-white py-12">
        <div class="max-w-5xl mx-auto px-6">
            <div class="flex items-center gap-4 mb-4">
                <img src="{{ asset('android-chrome-192x192.png') }}" alt="GTM Logo" class="w-12 h-12 rounded-lg">
                <div>
                    <h1 class="text-3xl font-bold">API Dokumentation</h1>
                    <p>Global Travel Monitor</p>
                </div>
            </div>
            <p class="max-w-2xl">
                RESTful API zur Integration von Reiseereignissen und Sicherheitsinformationen in Ihre Anwendungen.
                Alle Endpunkte erfordern eine Authentifizierung via Bearer Token.
            </p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-5xl mx-auto px-6 py-12">

        <!-- Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-globe text-green-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">GTM API</h3>
                </div>
                <p class="text-gray-600 text-sm mb-3">
                    Lese-API für Kunden. Zugriff auf aktive Events, Event-Details und Länder mit Event-Statistiken.
                </p>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-600">/v1/gtm/...</code>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-pen-to-square text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Event API</h3>
                </div>
                <p class="text-gray-600 text-sm mb-3">
                    CRUD-API für API-Clients. Erstellen, lesen, aktualisieren und löschen Sie eigene Events.
                </p>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-600">/v1/events/...</code>
            </div>
        </div>

        <!-- Authentication -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-12">
            <div class="px-6 py-4" style="background: linear-gradient(to right, #8e9299, #50514d);">
                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                    <i class="fas fa-key"></i>
                    Authentifizierung
                </h2>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">
                    Alle API-Anfragen erfordern einen Bearer Token im <code class="bg-gray-100 px-1.5 py-0.5 rounded text-sm">Authorization</code>-Header.
                    Tokens werden über Laravel Sanctum bereitgestellt.
                </p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)">
                        <i class="fas fa-copy"></i>
                    </button>
                    <code>Authorization: Bearer YOUR_API_TOKEN</code>
                </div>
                <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-sm text-amber-800">
                        <i class="fas fa-triangle-exclamation mr-2"></i>
                        <strong>Hinweis:</strong> Bewahren Sie Ihren API-Token sicher auf und geben Sie ihn nicht an Dritte weiter.
                        Bei Verdacht auf Missbrauch kann der Token jederzeit widerrufen werden.
                    </p>
                </div>
            </div>
        </div>

        <!-- Base URL -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-12">
            <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2">
                <i class="fas fa-server text-gray-400"></i>
                Base URL
            </h3>
            <div class="code-block">
                <code>https://api.global-travel-monitor.de/v1</code>
            </div>
            <p class="text-sm text-gray-500 mt-3">
                Alle Endpunkte sind relativ zu dieser Base URL angegeben. Antworten werden im JSON-Format zurückgegeben.
            </p>
        </div>

        <!-- GTM API Endpoints -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-12">
            <div class="px-6 py-4" style="background: linear-gradient(to right, #8e9299, #50514d);">
                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                    <i class="fas fa-globe"></i>
                    GTM API - Endpunkte
                </h2>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6">
                    Lese-API für Kunden zum Abrufen von Events und Länderdaten.
                </p>

                <!-- GET /gtm/events -->
                <div class="border border-gray-200 rounded-xl mb-6 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center gap-3 border-b border-gray-200">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm font-semibold text-gray-800">/gtm/events</code>
                        <span class="text-sm text-gray-500 ml-auto">Aktive Events abrufen</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-gray-600 mb-3">
                            Gibt eine paginierte Liste aller aktiven Events zurück. Unterstützt Filter nach Priorität, Kontinent, Eventtyp und Volltextsuche.
                        </p>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Query-Parameter</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th class="text-left py-1.5 px-2 text-gray-600">Parameter</th>
                                        <th class="text-left py-1.5 px-2 text-gray-600">Typ</th>
                                        <th class="text-left py-1.5 px-2 text-gray-600">Beschreibung</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    <tr class="border-b border-gray-50">
                                        <td class="py-1.5 px-2"><code class="text-xs bg-gray-100 px-1 rounded">page</code></td>
                                        <td class="py-1.5 px-2">integer</td>
                                        <td class="py-1.5 px-2">Seitennummer (Standard: 1)</td>
                                    </tr>
                                    <tr class="border-b border-gray-50">
                                        <td class="py-1.5 px-2"><code class="text-xs bg-gray-100 px-1 rounded">per_page</code></td>
                                        <td class="py-1.5 px-2">integer</td>
                                        <td class="py-1.5 px-2">Ergebnisse pro Seite (Standard: 15)</td>
                                    </tr>
                                    <tr class="border-b border-gray-50">
                                        <td class="py-1.5 px-2"><code class="text-xs bg-gray-100 px-1 rounded">priority</code></td>
                                        <td class="py-1.5 px-2">string</td>
                                        <td class="py-1.5 px-2">Filter nach Priorität (high, medium, low, info)</td>
                                    </tr>
                                    <tr class="border-b border-gray-50">
                                        <td class="py-1.5 px-2"><code class="text-xs bg-gray-100 px-1 rounded">continent</code></td>
                                        <td class="py-1.5 px-2">string</td>
                                        <td class="py-1.5 px-2">Filter nach Kontinent (EU, AS, AF, NA, SA, OC)</td>
                                    </tr>
                                    <tr class="border-b border-gray-50">
                                        <td class="py-1.5 px-2"><code class="text-xs bg-gray-100 px-1 rounded">event_type</code></td>
                                        <td class="py-1.5 px-2">integer</td>
                                        <td class="py-1.5 px-2">Filter nach Eventtyp-ID</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 px-2"><code class="text-xs bg-gray-100 px-1 rounded">search</code></td>
                                        <td class="py-1.5 px-2">string</td>
                                        <td class="py-1.5 px-2">Volltextsuche in Event-Titeln und Beschreibungen</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase mt-4 mb-2">Beispiel-Anfrage</h4>
                        <div class="code-block">
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                            <code>curl -X GET "https://api.global-travel-monitor.de/v1/gtm/events?priority=high&per_page=10" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</code>
                        </div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase mt-4 mb-2">Beispiel-Antwort</h4>
                        <div class="code-block">
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                            <code>{
  "data": [
    {
      "id": 1234,
      "title": "Erdbeben in der Türkei",
      "description": "Ein Erdbeben der Stärke 5.2 ...",
      "priority": "high",
      "event_type": "Umweltereignisse",
      "countries": ["TR"],
      "starts_at": "2025-01-15T08:00:00Z",
      "ends_at": "2025-01-20T23:59:00Z",
      "created_at": "2025-01-15T08:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 48
  }
}</code>
                        </div>
                    </div>
                </div>

                <!-- GET /gtm/events/{id} -->
                <div class="border border-gray-200 rounded-xl mb-6 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center gap-3 border-b border-gray-200">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm font-semibold text-gray-800">/gtm/events/{id}</code>
                        <span class="text-sm text-gray-500 ml-auto">Event-Details abrufen</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-gray-600 mb-3">
                            Gibt die vollständigen Details eines einzelnen Events zurück, inklusive betroffener Länder und Eventtypen.
                        </p>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Beispiel-Anfrage</h4>
                        <div class="code-block">
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                            <code>curl -X GET "https://api.global-travel-monitor.de/v1/gtm/events/1234" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</code>
                        </div>
                    </div>
                </div>

                <!-- GET /gtm/countries -->
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center gap-3 border-b border-gray-200">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm font-semibold text-gray-800">/gtm/countries</code>
                        <span class="text-sm text-gray-500 ml-auto">Länder mit Event-Anzahl</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-gray-600 mb-3">
                            Gibt eine Liste aller Länder zurück, für die aktive Events vorliegen, inklusive der jeweiligen Event-Anzahl.
                        </p>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Beispiel-Anfrage</h4>
                        <div class="code-block">
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                            <code>curl -X GET "https://api.global-travel-monitor.de/v1/gtm/countries" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event API Endpoints -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-12">
            <div class="px-6 py-4" style="background: linear-gradient(to right, #8e9299, #50514d);">
                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                    <i class="fas fa-pen-to-square"></i>
                    Event API - Endpunkte
                </h2>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6">
                    CRUD-API für API-Clients zum Verwalten eigener Events.
                </p>

                <!-- GET /events -->
                <div class="border border-gray-200 rounded-xl mb-6 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center gap-3 border-b border-gray-200">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm font-semibold text-gray-800">/events</code>
                        <span class="text-sm text-gray-500 ml-auto">Eigene Events auflisten</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-gray-600">
                            Gibt eine paginierte Liste Ihrer eigenen Events zurück.
                        </p>
                    </div>
                </div>

                <!-- POST /events -->
                <div class="border border-gray-200 rounded-xl mb-6 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center gap-3 border-b border-gray-200">
                        <span class="method-badge method-post">POST</span>
                        <code class="text-sm font-semibold text-gray-800">/events</code>
                        <span class="text-sm text-gray-500 ml-auto">Neues Event erstellen</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-gray-600 mb-3">
                            Erstellt ein neues Event. Erforderliche Felder werden im Request-Body als JSON übergeben.
                        </p>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Beispiel-Anfrage</h4>
                        <div class="code-block">
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                            <code>curl -X POST "https://api.global-travel-monitor.de/v1/events" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Streik am Flughafen Frankfurt",
    "description": "Warnstreik des Bodenpersonals ...",
    "priority": "medium",
    "event_type_id": 9,
    "country_codes": ["DE"],
    "starts_at": "2025-03-01T06:00:00Z",
    "ends_at": "2025-03-01T18:00:00Z"
  }'</code>
                        </div>
                    </div>
                </div>

                <!-- GET /events/{uuid} -->
                <div class="border border-gray-200 rounded-xl mb-6 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center gap-3 border-b border-gray-200">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm font-semibold text-gray-800">/events/{uuid}</code>
                        <span class="text-sm text-gray-500 ml-auto">Event abrufen</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-gray-600">
                            Gibt die Details eines einzelnen Events anhand seiner UUID zurück.
                        </p>
                    </div>
                </div>

                <!-- PUT /events/{uuid} -->
                <div class="border border-gray-200 rounded-xl mb-6 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center gap-3 border-b border-gray-200">
                        <span class="method-badge method-put">PUT</span>
                        <code class="text-sm font-semibold text-gray-800">/events/{uuid}</code>
                        <span class="text-sm text-gray-500 ml-auto">Event aktualisieren</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-gray-600">
                            Aktualisiert ein bestehendes Event. Es können einzelne oder alle Felder aktualisiert werden.
                        </p>
                    </div>
                </div>

                <!-- DELETE /events/{uuid} -->
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center gap-3 border-b border-gray-200">
                        <span class="method-badge method-delete">DELETE</span>
                        <code class="text-sm font-semibold text-gray-800">/events/{uuid}</code>
                        <span class="text-sm text-gray-500 ml-auto">Event löschen</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-gray-600">
                            Löscht ein Event anhand seiner UUID. Diese Aktion kann nicht rückgängig gemacht werden.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reference Data Endpoints -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-12">
            <div class="px-6 py-4" style="background: linear-gradient(to right, #8e9299, #50514d);">
                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                    <i class="fas fa-database"></i>
                    Referenzdaten
                </h2>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6">
                    Endpunkte für Stammdaten, die für die Event-Erstellung und -Filterung benötigt werden. Erfordern API-Client-Authentifizierung.
                </p>

                <!-- GET /event-types -->
                <div class="border border-gray-200 rounded-xl mb-6 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center gap-3 border-b border-gray-200">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm font-semibold text-gray-800">/event-types</code>
                        <span class="text-sm text-gray-500 ml-auto">Verfügbare Eventtypen</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-gray-600 mb-3">
                            Gibt alle verfügbaren Eventtypen zurück (z.B. Reiseverkehr, Sicherheit, Umweltereignisse).
                        </p>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Beispiel-Anfrage</h4>
                        <div class="code-block">
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                            <code>curl -X GET "https://api.global-travel-monitor.de/v1/event-types" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</code>
                        </div>
                    </div>
                </div>

                <!-- GET /countries -->
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center gap-3 border-b border-gray-200">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm font-semibold text-gray-800">/countries</code>
                        <span class="text-sm text-gray-500 ml-auto">Verfügbare Länder</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-gray-600 mb-3">
                            Gibt alle verfügbaren Länder mit ISO-Code und Name zurück.
                        </p>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Beispiel-Anfrage</h4>
                        <div class="code-block">
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                            <code>curl -X GET "https://api.global-travel-monitor.de/v1/countries" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HTTP Status Codes -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-12">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-circle-info text-gray-400"></i>
                HTTP-Statuscodes
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 font-semibold text-gray-700">Code</th>
                            <th class="text-left py-2 px-3 font-semibold text-gray-700">Bedeutung</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <tr class="border-b border-gray-100">
                            <td class="py-2 px-3"><code class="bg-green-100 text-green-700 px-2 py-0.5 rounded">200</code></td>
                            <td class="py-2 px-3">Erfolgreiche Anfrage</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2 px-3"><code class="bg-green-100 text-green-700 px-2 py-0.5 rounded">201</code></td>
                            <td class="py-2 px-3">Ressource erfolgreich erstellt</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2 px-3"><code class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">401</code></td>
                            <td class="py-2 px-3">Nicht authentifiziert - Token fehlt oder ungültig</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2 px-3"><code class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">403</code></td>
                            <td class="py-2 px-3">Keine Berechtigung für diese Ressource</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2 px-3"><code class="bg-red-100 text-red-700 px-2 py-0.5 rounded">404</code></td>
                            <td class="py-2 px-3">Ressource nicht gefunden</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2 px-3"><code class="bg-red-100 text-red-700 px-2 py-0.5 rounded">422</code></td>
                            <td class="py-2 px-3">Validierungsfehler - ungültige Eingabedaten</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-3"><code class="bg-red-100 text-red-700 px-2 py-0.5 rounded">429</code></td>
                            <td class="py-2 px-3">Rate-Limit überschritten - zu viele Anfragen</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rate Limiting -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-12">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-gauge-high text-gray-400"></i>
                Rate Limiting
            </h3>
            <p class="text-gray-600 mb-4">
                Alle API-Endpunkte unterliegen einem Rate-Limit, um die Stabilität des Dienstes zu gewährleisten.
                Bei Überschreitung erhalten Sie einen <code class="bg-gray-100 px-1.5 py-0.5 rounded text-sm">429</code>-Statuscode.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-5 bg-green-50 border border-green-200 rounded-lg">
                    <h4 class="font-semibold text-green-900 mb-2">GTM API</h4>
                    <p class="text-sm text-green-700 mb-2">
                        Standard: <code class="bg-green-100 px-1.5 py-0.5 rounded font-semibold">60 Anfragen / Minute</code>
                    </p>
                    <p class="text-sm text-green-700">
                        Das Limit wird pro Kunde angewendet und kann je nach Tarif individuell angepasst werden.
                    </p>
                </div>
                <div class="p-5 bg-blue-50 border border-blue-200 rounded-lg">
                    <h4 class="font-semibold text-blue-900 mb-2">Event API</h4>
                    <p class="text-sm text-blue-700 mb-2">
                        Standard: <code class="bg-blue-100 px-1.5 py-0.5 rounded font-semibold">60 Anfragen / Minute</code>
                    </p>
                    <p class="text-sm text-blue-700">
                        Das Limit wird pro API-Client angewendet und kann individuell konfiguriert werden.
                    </p>
                </div>
            </div>
            <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <p class="text-sm text-gray-600">
                    <i class="fas fa-circle-info text-gray-400 mr-2"></i>
                    Bei Bedarf an höheren Rate-Limits wenden Sie sich bitte an
                    <a href="mailto:support@passolution.de" class="text-blue-600 hover:underline">support@passolution.de</a>.
                </p>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-400 text-white py-8 mt-12">
        <div class="max-w-5xl mx-auto px-6 text-center">
            <div class="flex items-center justify-center gap-3 mb-4">
                <img src="{{ asset('favicon-32x32.png') }}" alt="GTM" class="w-6 h-6">
                <span class="text-gray-800 font-semibold">Global Travel Monitor</span>
            </div>
            <p class="text-gray-800 text-sm mb-2">
                Bei Fragen zur API wenden Sie sich bitte an
                <a href="mailto:support@passolution.de" class="underline hover:text-gray-600">support@passolution.de</a>
            </p>
            <p class="text-gray-800 text-sm">
                &copy; {{ date('Y') }} <a href="https://passolution.de" target="_blank">Passolution GmbH</a>. Alle Rechte vorbehalten.
            </p>
            <div class="flex items-center justify-center gap-4 mt-4 text-sm">
                <a href="https://www.passolution.de/impressum/" target="_blank" class="text-gray-800 hover:text-gray-600">Impressum</a>
                <a href="https://www.passolution.de/datenschutz/" target="_blank" class="text-gray-800 hover:text-gray-600">Datenschutz</a>
                <a href="https://global-travel-monitor.eu" target="_blank" class="text-gray-800 hover:text-gray-600">Global Travel Monitor</a>
            </div>
        </div>
    </footer>

    <script>
        function copyCode(button) {
            const codeBlock = button.parentElement;
            const code = codeBlock.querySelector('code').innerText;

            navigator.clipboard.writeText(code).then(() => {
                const originalHtml = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.style.background = '#22c55e';

                setTimeout(() => {
                    button.innerHTML = originalHtml;
                    button.style.background = '';
                }, 2000);
            });
        }
    </script>
</body>
</html>
