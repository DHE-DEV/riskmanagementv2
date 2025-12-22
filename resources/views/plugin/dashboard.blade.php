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
        <!-- API Key Section -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">API-Key</h2>

            @if($activeKey)
                <div class="bg-gray-50 rounded-md p-4 mb-4">
                    <code class="text-sm font-mono break-all text-gray-800">{{ $activeKey->public_key }}</code>
                </div>

                <form action="{{ route('plugin.regenerate-key') }}" method="POST" class="inline"
                      onsubmit="return confirm('Achtung: Der alte Key wird ungültig. Fortfahren?');">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Key neu generieren
                    </button>
                </form>
            @else
                <p class="text-gray-500">Kein aktiver Key vorhanden.</p>
            @endif
        </div>

        <!-- Domains Section -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Erlaubte Domains</h2>

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
            <div class="border border-gray-200 rounded-lg overflow-hidden">
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
            <h3 class="text-md font-medium text-gray-900 mt-6 mb-2">Tägliche Aufrufe</h3>
            <div class="overflow-x-auto">
                <div class="flex items-end space-x-1 h-32">
                    @php
                        $maxCount = max($stats['daily']) ?: 1;
                    @endphp
                    @foreach($stats['daily'] as $date => $count)
                        <div class="flex-1 min-w-[8px] bg-blue-500 rounded-t"
                             style="height: {{ ($count / $maxCount) * 100 }}%"
                             title="{{ $date }}: {{ $count }} Aufrufe"></div>
                    @endforeach
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
