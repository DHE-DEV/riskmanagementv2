<div class="space-y-4">
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-sm text-gray-600">Gesamt-Klicks</div>
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] ?? 0 }}</div>
        </div>

        <div class="bg-blue-50 p-3 rounded-lg">
            <div class="text-sm text-gray-600">Event-Liste</div>
            <div class="text-2xl font-bold text-blue-600">{{ $stats['list'] ?? 0 }}</div>
        </div>

        <div class="bg-green-50 p-3 rounded-lg">
            <div class="text-sm text-gray-600">Karten-Symbol</div>
            <div class="text-2xl font-bold text-green-600">{{ $stats['map_marker'] ?? 0 }}</div>
        </div>

        <div class="bg-purple-50 p-3 rounded-lg">
            <div class="text-sm text-gray-600">Details-Button</div>
            <div class="text-2xl font-bold text-purple-600">{{ $stats['details_button'] ?? 0 }}</div>
        </div>

        <div class="bg-yellow-50 p-3 rounded-lg">
            <div class="text-sm text-gray-600">Heute</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['today'] ?? 0 }}</div>
        </div>

        <div class="bg-orange-50 p-3 rounded-lg">
            <div class="text-sm text-gray-600">Diese Woche</div>
            <div class="text-2xl font-bold text-orange-600">{{ $stats['this_week'] ?? 0 }}</div>
        </div>
    </div>

    @if($recentClicks && count($recentClicks) > 0)
        <div class="mt-6">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Letzte Klicks</h4>
            <div class="bg-white border rounded-lg">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-2 text-left text-gray-600">Typ</th>
                            <th class="px-3 py-2 text-left text-gray-600">Benutzer</th>
                            <th class="px-3 py-2 text-left text-gray-600">Zeitpunkt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($recentClicks as $click)
                            <tr>
                                <td class="px-3 py-2">
                                    @switch($click->click_type)
                                        @case('list')
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700">
                                                Event-Liste
                                            </span>
                                            @break
                                        @case('map_marker')
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700">
                                                Karten-Symbol
                                            </span>
                                            @break
                                        @case('details_button')
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700">
                                                Details-Button
                                            </span>
                                            @break
                                        @default
                                            {{ $click->click_type }}
                                    @endswitch
                                </td>
                                <td class="px-3 py-2 text-gray-900">
                                    {{ $click->user?->name ?? 'Anonym' }}
                                </td>
                                <td class="px-3 py-2 text-gray-600">
                                    {{ $click->clicked_at->format('d.m.Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="mt-4 text-sm text-gray-500 italic">
            Noch keine Klicks aufgezeichnet
        </div>
    @endif
</div>