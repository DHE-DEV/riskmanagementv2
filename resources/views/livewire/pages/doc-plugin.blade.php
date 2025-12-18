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
    <header class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex items-center gap-4 mb-4">
                <img src="{{ asset('android-chrome-192x192.png') }}" alt="GTM Logo" class="w-12 h-12 rounded-lg">
                <div>
                    <h1 class="text-3xl font-bold">Plugin Dokumentation</h1>
                    <p class="text-gray-400">Global Travel Monitor - Embed Integration</p>
                </div>
            </div>
            <p class="text-gray-300 max-w-2xl">
                Integrieren Sie aktuelle Reiseereignisse und Sicherheitsinformationen direkt in Ihre Website.
                Wählen Sie aus drei verschiedenen Darstellungsoptionen.
            </p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-12">
        <!-- Option 1: Events List (1/3 width) -->
        <div class="mb-12">
            <div class="option-card max-w-md">
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
                    <div class="iframe-preview" style="height: 400px;">
                        <iframe src="/embed/events" height="400" loading="lazy"></iframe>
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
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Weltkarte</h2>
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
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Dashboard</h2>
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
                Optionale Parameter
            </h2>

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
                            <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded text-blue-600">filter</code></td>
                            <td class="py-3 px-4"><code>critical</code>, <code>high</code>, <code>medium</code></td>
                            <td class="py-3 px-4 text-gray-600">Vorfilterung nach Priorität</td>
                            <td class="py-3 px-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded">?filter=high</code></td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded text-blue-600">lang</code></td>
                            <td class="py-3 px-4"><code>de</code>, <code>en</code></td>
                            <td class="py-3 px-4 text-gray-600">Sprache (Standard: de)</td>
                            <td class="py-3 px-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded">?lang=en</code></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4"><code class="bg-gray-100 px-2 py-1 rounded text-blue-600">hide_badge</code></td>
                            <td class="py-3 px-4"><code>1</code></td>
                            <td class="py-3 px-4 text-gray-600">"Powered by" Badge ausblenden</td>
                            <td class="py-3 px-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded">?hide_badge=1</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Beispiel mit mehreren Parametern:</strong>
                    <code class="ml-2 bg-blue-100 px-2 py-1 rounded">{{ url('/embed/events') }}?filter=high&lang=en&hide_badge=1</code>
                </p>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <div class="flex items-center justify-center gap-3 mb-4">
                <img src="{{ asset('favicon-32x32.png') }}" alt="GTM" class="w-6 h-6">
                <span class="font-semibold">Global Travel Monitor</span>
            </div>
            <p class="text-gray-400 text-sm">
                &copy; {{ date('Y') }} Passolution GmbH. Alle Rechte vorbehalten.
            </p>
            <div class="flex items-center justify-center gap-4 mt-4 text-sm">
                <a href="https://www.passolution.de/impressum/" target="_blank" class="text-gray-400 hover:text-white transition-colors">Impressum</a>
                <a href="https://www.passolution.de/datenschutz/" target="_blank" class="text-gray-400 hover:text-white transition-colors">Datenschutz</a>
                <a href="https://global-travel-monitor.eu" target="_blank" class="text-gray-400 hover:text-white transition-colors">Global Travel Monitor</a>
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
