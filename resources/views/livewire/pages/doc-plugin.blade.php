<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plugin Dokumentation - Global Travel Monitor</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

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

        .iframe-preview {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }

        .iframe-preview iframe {
            width: 100%;
            border: none;
        }

        .option-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .option-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-gray-400 to-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex items-center gap-4 mb-4">
                <img src="{{ asset('android-chrome-192x192.png') }}" alt="GTM Logo" class="w-12 h-12 rounded-lg">
                <div>
                    <h1 class="text-3xl font-bold">Plugin Dokumentation</h1>
                    <p class="">Global Travel Monitor - Integration</p>
                </div>
            </div>
            <p class="max-w-2xl">
                Integrieren Sie aktuelle Reiseereignisse und Sicherheitsinformationen direkt in Ihre Website.
                Wählen Sie aus drei verschiedenen Darstellungsoptionen.
            </p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-12">
        <!-- Option 1: Events List (1/3 width) -->
        <div class="mb-12 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Events List Preview -->
            <div class="option-card">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <span class="badge bg-blue-100 text-blue-700">
                            <i class="fas fa-list mr-1"></i> Liste
                        </span>
                        <span class="text-xs text-gray-500">1/3 Breite empfohlen</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Ereignisliste</h2>
                    <p class="text-gray-600 text-sm">
                        Kompakte Listenansicht aller aktuellen Ereignisse mit Suchfunktion und Filteroptionen.
                    </p>
                </div>

                <div class="p-4 bg-gray-50">
                    <div class="iframe-preview" style="height: 600px;">
                        <iframe src="/embed/events" height="600" loading="lazy"></iframe>
                    </div>
                </div>

                <div class="p-4">
                    <p class="text-xs text-gray-500 mb-2 font-medium">Embed-Code:</p>
                    <div class="code-block">
                        <button class="copy-btn" onclick="copyCode(this)">
                            <i class="fas fa-copy"></i>
                        </button>
                        <code>&lt;iframe
  src="{{ url('/embed/events') }}"
  width="100%"
  height="600"
  frameborder="0"&gt;
&lt;/iframe&gt;</code>
                    </div>
                </div>
            </div>

            <!-- Features & Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Features -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-star text-yellow-500"></i>
                        Features
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-search text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Volltextsuche</h4>
                                <p class="text-sm text-gray-500">Durchsuchen Sie alle Ereignisse nach Stichworten</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-filter text-green-600 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Filter</h4>
                                <p class="text-sm text-gray-500">Nach Priorität, Region und Ereignistyp filtern</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-clock text-purple-600 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Echtzeit-Updates</h4>
                                <p class="text-sm text-gray-500">Automatische Aktualisierung der Ereignisse</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-mobile-alt text-orange-600 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Responsive Design</h4>
                                <p class="text-sm text-gray-500">Optimiert für alle Bildschirmgrößen</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Use Cases -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-lightbulb text-amber-500"></i>
                        Anwendungsfälle
                    </h3>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 text-gray-700">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span>Sidebar-Widget auf Ihrer Unternehmenswebsite</span>
                        </li>
                        <li class="flex items-center gap-3 text-gray-700">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span>Integration in Ihr Intranet oder Mitarbeiterportal</span>
                        </li>
                        <li class="flex items-center gap-3 text-gray-700">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span>Reiseinformationen für Kunden auf Buchungsplattformen</span>
                        </li>
                        <li class="flex items-center gap-3 text-gray-700">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span>Travel Risk Management Dashboard</span>
                        </li>
                    </ul>
                </div>

                <!-- Quick Tip -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                        <div>
                            <h4 class="font-medium text-blue-900">Tipp</h4>
                            <p class="text-sm text-blue-700">
                                Die Ereignisliste eignet sich ideal für schmale Spalten (300-400px) in Ihrem Layout.
                                Für breitere Bereiche empfehlen wir das Dashboard oder die Kartenansicht.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Option 2: Map View (Full Width) -->
        <div class="mb-12">
            <div class="option-card">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <span class="badge bg-green-100 text-green-700">
                            <i class="fas fa-map mr-1"></i> Karte
                        </span>
                        <span class="text-xs text-gray-500">Volle Breite empfohlen</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Nur Kartenansicht</h2>
                    <p class="text-gray-600 text-sm">
                        Interaktive Weltkarte mit allen Ereignissen als Marker. Ideal für visuelle Darstellung.
                    </p>
                </div>

                <div class="p-4 bg-gray-50">
                    <div class="iframe-preview" style="height: 500px;">
                        <iframe src="/embed/map" height="500" loading="lazy"></iframe>
                    </div>
                </div>

                <div class="p-4">
                    <p class="text-xs text-gray-500 mb-2 font-medium">Embed-Code:</p>
                    <div class="code-block">
                        <button class="copy-btn" onclick="copyCode(this)">
                            <i class="fas fa-copy"></i>
                        </button>
                        <code>&lt;iframe
  src="{{ url('/embed/map') }}"
  width="100%"
  height="600"
  frameborder="0"&gt;
&lt;/iframe&gt;</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- Option 3: Dashboard (Full Width) -->
        <div class="mb-12">
            <div class="option-card">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <span class="badge bg-purple-100 text-purple-700">
                            <i class="fas fa-th-large mr-1"></i> Dashboard
                        </span>
                        <span class="text-xs text-gray-500">Volle Breite empfohlen</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Komplettansicht</h2>
                    <p class="text-gray-600 text-sm">
                        Komplettansicht mit Seitenleiste, Ereignisliste und interaktiver Karte.
                    </p>
                </div>

                <div class="p-4 bg-gray-50">
                    <div class="iframe-preview" style="height: 600px;">
                        <iframe src="/embed/dashboard" height="600" loading="lazy"></iframe>
                    </div>
                </div>

                <div class="p-4">
                    <p class="text-xs text-gray-500 mb-2 font-medium">Embed-Code:</p>
                    <div class="code-block">
                        <button class="copy-btn" onclick="copyCode(this)">
                            <i class="fas fa-copy"></i>
                        </button>
                        <code>&lt;iframe
  src="{{ url('/embed/dashboard') }}"
  width="100%"
  height="800"
  frameborder="0"&gt;
&lt;/iframe&gt;</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- Parameters Section -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                <i class="fas fa-cog text-gray-400"></i>
                URL-Parameter (Vorfilter)
            </h2>
            <p class="text-gray-600 mb-6">
                Sie können die Ereignisliste mit URL-Parametern vorfiltern. Die Filter werden beim Laden der Seite automatisch angewendet.
            </p>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="py-3 px-4 font-semibold text-gray-700">Parameter</th>
                            <th class="py-3 px-4 font-semibold text-gray-700">Werte</th>
                            <th class="py-3 px-4 font-semibold text-gray-700">Beschreibung</th>
                            <th class="py-3 px-4 font-semibold text-gray-700">Beispiel</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded text-blue-600">timePeriod</code></td>
                            <td class="py-3 px-4"><code>all</code>, <code>future</code>, <code>today</code>, <code>week</code>, <code>month</code></td>
                            <td class="py-3 px-4 text-gray-600">Zeitraum der Ereignisse</td>
                            <td class="py-3 px-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded">?timePeriod=future</code></td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded text-blue-600">priorities</code></td>
                            <td class="py-3 px-4"><code>critical</code>, <code>high</code>, <code>medium</code>, <code>low</code>, <code>info</code></td>
                            <td class="py-3 px-4 text-gray-600">Prioritäten (kommagetrennt)</td>
                            <td class="py-3 px-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded">?priorities=high,medium</code></td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded text-blue-600">continents</code></td>
                            <td class="py-3 px-4"><code>EU</code>, <code>AS</code>, <code>AF</code>, <code>NA</code>, <code>SA</code>, <code>OC</code></td>
                            <td class="py-3 px-4 text-gray-600">Kontinente (kommagetrennt)</td>
                            <td class="py-3 px-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded">?continents=EU,AS</code></td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded text-blue-600">eventTypes</code></td>
                            <td class="py-3 px-4">Event-Type-IDs</td>
                            <td class="py-3 px-4 text-gray-600">Ereignistypen nach ID (kommagetrennt)</td>
                            <td class="py-3 px-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded">?eventTypes=1,2,3</code></td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded text-blue-600">search</code></td>
                            <td class="py-3 px-4">Suchbegriff</td>
                            <td class="py-3 px-4 text-gray-600">Volltextsuche in Ereignissen</td>
                            <td class="py-3 px-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded">?search=Erdbeben</code></td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded text-blue-600">hide_badge</code></td>
                            <td class="py-3 px-4"><code>1</code> oder <code>true</code></td>
                            <td class="py-3 px-4 text-gray-600">Powered-by Badge ausblenden</td>
                            <td class="py-3 px-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded">?hide_badge=1</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 space-y-4">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Beispiel mit mehreren Parametern:</strong><br>
                        <code class="mt-2 inline-block bg-blue-100 px-2 py-1 rounded text-xs">{{ url('/embed/events') }}?timePeriod=future&priorities=high,medium&continents=EU</code>
                    </p>
                </div>

                <div class="p-4 bg-amber-50 rounded-lg">
                    <p class="text-sm text-amber-800">
                        <i class="fas fa-lightbulb mr-2"></i>
                        <strong>Kontinente-Kürzel:</strong>
                        EU = Europa, AS = Asien, AF = Afrika, NA = Nordamerika, SA = Südamerika, OC = Ozeanien
                    </p>
                </div>

                <div class="p-4 bg-green-50 rounded-lg">
                    <p class="text-sm text-green-800">
                        <i class="fas fa-tags mr-2"></i>
                        <strong>Event-Type-IDs:</strong>
                        9 = Reiseverkehr, 10 = Sicherheit, 11 = Umweltereignisse, 12 = Einreisebestimmungen, 13 = Allgemein, 14 = Gesundheit
                    </p>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-400 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <div class="flex items-center justify-center gap-3 mb-4">
                <img src="{{ asset('favicon-32x32.png') }}" alt="GTM" class="w-6 h-6">
                <span class="text-gray-800 font-semibold">Global Travel Monitor</span>
            </div>
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
