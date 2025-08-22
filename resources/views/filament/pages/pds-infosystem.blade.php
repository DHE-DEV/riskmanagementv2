<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-blue-600 rounded-lg p-6">
            <h1 class="text-2xl font-bold text-white">Passolution Infosystem</h1>
            <p class="text-blue-100">Reise- und Sicherheitsinformationen verwalten</p>
        </div>

        <!-- API Controls -->
        <div class="bg-white rounded-lg p-6 shadow">
            <button 
                wire:click="fetchApiData" 
                wire:loading.attr="disabled"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Daten synchronisieren
            </button>
            
            @if(session()->has('api_message'))
                <div class="mt-4 p-3 rounded {{ session('api_success') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ session('api_message') }}
                </div>
            @endif
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg p-4 shadow">
                <h3 class="text-sm text-gray-600">Gesamt Einträge</h3>
                <p class="text-2xl font-bold text-blue-600">{{ $this->statistics['total_entries'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow">
                <h3 class="text-sm text-gray-600">Aktive Einträge</h3>
                <p class="text-2xl font-bold text-green-600">{{ $this->statistics['active_entries'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow">
                <h3 class="text-sm text-gray-600">Diese Woche</h3>
                <p class="text-2xl font-bold text-yellow-600">{{ $this->statistics['entries_this_week'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow">
                <h3 class="text-sm text-gray-600">Länder</h3>
                <p class="text-2xl font-bold text-purple-600">{{ $this->statistics['countries_count'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Entries Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold">Aktuelle Einträge</h2>
            </div>
            
            @if($this->latestEntries->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Datum</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Land</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Titel</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($this->latestEntries as $entry)
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $entry->getFormattedTagDate() }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $entry->country_code }}</span>
                                        {{ $entry->getCountryName() }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium">{{ $entry->header }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded-full text-xs {{ $entry->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $entry->active ? 'Aktiv' : 'Inaktiv' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center">
                    <h3 class="text-lg font-medium text-gray-900">Keine Daten verfügbar</h3>
                    <p class="text-gray-500 mt-2">Klicken Sie auf "Daten synchronisieren" um Daten zu laden.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>