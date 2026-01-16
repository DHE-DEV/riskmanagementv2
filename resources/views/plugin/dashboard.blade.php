@extends('layouts.dashboard-minimal')

@section('title', 'Plugin Dashboard - Global Travel Monitor')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Plugin Dashboard</h1>
        <p class="mt-1 text-sm text-gray-600">Verwalten Sie Ihren Global Travel Monitor Plugin-Zugang</p>
    </div>

    @if (session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if (session('info'))
        <div class="mb-6 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
            {{ session('info') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Domains Section -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Erlaubte Domains</h2>
            <p class="text-xs text-gray-500 mb-4">Ohne https:// oder http:// angeben (z.B. meine-website.de)</p>

            <ul class="divide-y divide-gray-200 mb-4">
                @forelse($domains as $domain)
                    <li class="py-3 flex justify-between items-center">
                        <span class="text-sm text-gray-900">{{ $domain->domain }}</span>
                        @if($domains->count() > 1)
                            <form action="{{ route('plugin.remove-domain', $domain->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Domain wirklich entfernen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                    Entfernen
                                </button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="py-3 text-gray-500 text-sm">Keine Domains konfiguriert.</li>
                @endforelse
            </ul>

            <form action="{{ route('plugin.add-domain') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="domain" placeholder="neue-domain.de" required
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Hinzufügen
                </button>
            </form>
            @error('domain')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- App-Integration Section -->
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">App-Integration</h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Ermöglicht die Nutzung in Desktop- und Mobile-Apps (WebView)
                    </p>
                </div>
                <form action="{{ route('plugin.toggle-app-access') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $pluginClient->allow_app_access ? 'border-red-300 text-red-700 bg-red-50 hover:bg-red-100 focus:ring-red-500' : 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100 focus:ring-green-500' }}">
                        @if($pluginClient->allow_app_access)
                            <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Deaktivieren
                        @else
                            <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Aktivieren
                        @endif
                    </button>
                </form>
            </div>

            @if($pluginClient->allow_app_access)
                <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-blue-500 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h3 class="font-medium text-blue-900">Integration in Ihre App</h3>
                            <p class="text-sm text-blue-700 mt-1">Laden Sie folgende URL in einem WebView:</p>
                            <div class="mt-2 p-2 bg-white rounded border border-blue-200 overflow-x-auto">
                                <code class="text-xs text-gray-800 font-mono break-all">{{ config('app.url') }}/embed/dashboard?key={{ $activeKey->public_key }}</code>
                            </div>
                            <p class="text-xs text-blue-600 mt-2">
                                Funktioniert mit: Android WebView, iOS WKWebView, Electron, Qt WebEngine, etc.
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-sm text-gray-600">
                        Aktivieren Sie den App-Zugang, um das Plugin ohne Domain-Validierung in Desktop- oder Mobile-Apps nutzen zu können.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Embed Options Section -->
    <div class="mt-6 bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Einbindung</h2>
        <p class="text-sm text-gray-600 mb-6">
            Wählen Sie eine der drei Optionen und kopieren Sie den Code in Ihre Website.
        </p>

        @php
            $apiKey = $activeKey?->public_key ?? 'YOUR_API_KEY';
            $baseUrl = config('app.url');
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Option 1: Events List -->
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-blue-50 px-4 py-3 border-b border-blue-100">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                            Option 1
                        </span>
                        <h3 class="font-medium text-gray-900">Ereignisliste</h3>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Für schmale Spalten (300-400px)</p>
                </div>
                <div class="p-4">
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto mb-3">
                        <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap" id="code-events">&lt;iframe
  src="{{ $baseUrl }}/embed/events?key={{ $apiKey }}"
  width="400"
  height="600"
  frameborder="0"&gt;
&lt;/iframe&gt;</pre>
                    </div>
                    <button onclick="copyCode('events')"
                            class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Code kopieren
                    </button>
                </div>
            </div>

            <!-- Option 2: Map View -->
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-green-50 px-4 py-3 border-b border-green-100">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            Option 2
                        </span>
                        <h3 class="font-medium text-gray-900">Kartenansicht</h3>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Interaktive Weltkarte</p>
                </div>
                <div class="p-4">
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto mb-3">
                        <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap" id="code-map">&lt;iframe
  src="{{ $baseUrl }}/embed/map?key={{ $apiKey }}"
  width="100%"
  height="600"
  frameborder="0"&gt;
&lt;/iframe&gt;</pre>
                    </div>
                    <button onclick="copyCode('map')"
                            class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Code kopieren
                    </button>
                </div>
            </div>

            <!-- Option 3: Full Dashboard -->
            <div class="border border-gray-200 rounded-lg overflow-hidden relative">
                <div class="absolute top-2 right-2 z-10">
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-semibold bg-green-500 text-white shadow-sm">
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" />
                        </svg>
                        Empfohlen
                    </span>
                </div>
                <div class="bg-purple-50 px-4 py-3 border-b border-purple-100">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                            Option 3
                        </span>
                        <h3 class="font-medium text-gray-900">Komplettansicht</h3>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Liste + Karte kombiniert</p>
                </div>
                <div class="p-4">
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto mb-3">
                        <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap" id="code-dashboard">&lt;iframe
  src="{{ $baseUrl }}/embed/dashboard?key={{ $apiKey }}"
  width="100%"
  height="800"
  frameborder="0"&gt;
&lt;/iframe&gt;</pre>
                    </div>
                    <button onclick="copyCode('dashboard')"
                            class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Code kopieren
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-500">
                <strong>Tipp:</strong> Weitere Parameter wie <code class="bg-gray-200 px-1 rounded">timePeriod</code>, <code class="bg-gray-200 px-1 rounded">priorities</code> oder <code class="bg-gray-200 px-1 rounded">continents</code> finden Sie in der <a href="{{ url('/doc-plugin') }}" class="text-blue-600 hover:underline" target="_blank">Dokumentation</a>.
            </p>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="mt-6 bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Nutzungsstatistik (letzte 30 Tage)</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-blue-600 font-medium">Gesamt-Aufrufe</p>
                <p class="text-2xl font-bold text-blue-900">{{ number_format($stats['total']) }}</p>
            </div>

            @foreach($stats['by_type'] as $type => $count)
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 font-medium">{{ ucfirst(str_replace('_', ' ', $type)) }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($count) }}</p>
                </div>
            @endforeach
        </div>

        @if(count($stats['top_domains']) > 0)
            <h3 class="text-md font-medium text-gray-900 mb-2">Top Domains</h3>
            <ul class="space-y-2">
                @foreach($stats['top_domains'] as $domain => $count)
                    <li class="flex justify-between items-center text-sm">
                        <span class="text-gray-700">{{ $domain }}</span>
                        <span class="text-gray-500">{{ number_format($count) }} Aufrufe</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if(count($stats['daily']) > 0)
            <h3 class="text-md font-medium text-gray-900 mt-6 mb-4">Tägliche Aufrufe der Seiten mit Plugin</h3>
            @php
                $maxCount = max($stats['daily']) ?: 1;
                $dailyData = $stats['daily'];
                $totalDays = count($dailyData);
            @endphp

            <div class="relative">
                <!-- Y-Axis Labels -->
                <div class="absolute left-0 top-0 bottom-6 w-10 flex flex-col justify-between text-xs text-gray-400">
                    <span>{{ number_format($maxCount) }}</span>
                    <span>{{ number_format($maxCount / 2) }}</span>
                    <span>0</span>
                </div>

                <!-- Chart Area -->
                <div class="ml-12">
                    <!-- Grid Lines -->
                    <div class="relative h-40 border-b border-l border-gray-200">
                        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                            <div class="border-t border-gray-100 border-dashed"></div>
                            <div class="border-t border-gray-100 border-dashed"></div>
                            <div></div>
                        </div>

                        <!-- Bars -->
                        <div class="absolute inset-0 flex items-end gap-px px-1">
                            @foreach($dailyData as $date => $count)
                                @php
                                    $height = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                                    $formattedDate = \Carbon\Carbon::parse($date)->format('d.m.');
                                @endphp
                                <div class="flex-1 group relative flex flex-col items-center justify-end h-full">
                                    <div class="w-full bg-blue-500 hover:bg-blue-600 rounded-t transition-colors cursor-pointer"
                                         style="height: {{ max($height, 2) }}%; min-height: 2px;">
                                    </div>
                                    <!-- Tooltip -->
                                    <div class="absolute bottom-full mb-2 hidden group-hover:block z-10">
                                        <div class="bg-gray-900 text-white text-xs rounded py-1 px-2 whitespace-nowrap">
                                            {{ $formattedDate }}: {{ number_format($count) }} Aufrufe
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- X-Axis Labels -->
                    <div class="flex justify-between mt-2 text-xs text-gray-400">
                        @php
                            $dates = array_keys($dailyData);
                            $dateCount = count($dates);
                            $firstDate = \Carbon\Carbon::parse(reset($dates))->format('d.m.');
                            $lastDate = \Carbon\Carbon::parse(end($dates))->format('d.m.');
                        @endphp
                        @if($dateCount === 1)
                            <span class="w-full text-center">{{ $firstDate }}</span>
                        @elseif($dateCount <= 3)
                            <span>{{ $firstDate }}</span>
                            <span>{{ $lastDate }}</span>
                        @else
                            @php
                                $midIndex = intval($dateCount / 2);
                                $midDate = \Carbon\Carbon::parse($dates[$midIndex])->format('d.m.');
                            @endphp
                            <span>{{ $firstDate }}</span>
                            <span>{{ $midDate }}</span>
                            <span>{{ $lastDate }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function copyCode(type) {
        const codeElement = document.getElementById('code-' + type);
        if (!codeElement) return;

        // Get text and decode HTML entities
        let code = codeElement.innerText;

        navigator.clipboard.writeText(code).then(() => {
            // Find the button and show success state
            const button = event.target.closest('button');
            const originalHtml = button.innerHTML;
            button.innerHTML = '<svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Kopiert!';
            button.classList.remove('text-gray-700', 'bg-white');
            button.classList.add('text-green-700', 'bg-green-50');

            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('text-green-700', 'bg-green-50');
                button.classList.add('text-gray-700', 'bg-white');
            }, 2000);
        }).catch(err => {
            console.error('Fehler beim Kopieren:', err);
        });
    }
</script>
@endpush
@endsection
