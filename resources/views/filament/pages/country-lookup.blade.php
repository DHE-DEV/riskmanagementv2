@php
    $colors = [
        'success' => ['bg' => '#dcfce7', 'border' => '#86efac', 'text' => '#166534'],
        'info' => ['bg' => '#dbeafe', 'border' => '#93c5fd', 'text' => '#1e40af'],
        'warning' => ['bg' => '#fef3c7', 'border' => '#fcd34d', 'text' => '#92400e'],
        'danger' => ['bg' => '#fee2e2', 'border' => '#fca5a5', 'text' => '#991b1b'],
        'gray' => ['bg' => '#f3f4f6', 'border' => '#d1d5db', 'text' => '#374151'],
    ];
@endphp

<x-filament-panels::page>
    <form wire:submit.prevent="lookup">
        <div style="display: flex; gap: 0.75rem; align-items: flex-start; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 18rem;">
                <input
                    type="text"
                    wire:model="query"
                    placeholder="z.B. 52.5200, 13.4050 – oder FRA – oder ein Google-Maps-Link"
                    autofocus
                    style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.9375rem;"
                />
            </div>
            <x-filament::button type="submit" color="primary">
                Land bestimmen
            </x-filament::button>
        </div>
    </form>

    @if ($error)
        <div style="margin-top: 1.25rem; padding: 0.875rem 1rem; border-radius: 0.5rem; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b;">
            {{ $error }}
        </div>
    @endif

    @if ($result)
        @php
            $info = \App\Filament\Pages\CountryLookup::methodInfo($result['method']);
            $c = $colors[$info['color']] ?? $colors['gray'];
            $country = $result['country'];
        @endphp

        <div style="margin-top: 1.5rem;">
            {{-- Ergebnis --}}
            <div style="padding: 1.25rem 1.5rem; border-radius: 0.75rem; background: {{ $c['bg'] }}; border: 1px solid {{ $c['border'] }};">
                @if ($country)
                    <div style="font-size: 1.5rem; font-weight: 700; color: {{ $c['text'] }};">
                        {{ $country->getName('de') }}
                    </div>
                    <div style="margin-top: 0.25rem; font-size: 0.875rem; color: {{ $c['text'] }};">
                        {{ $country->iso_code }}@if ($country->iso3_code) / {{ $country->iso3_code }}@endif
                        @if ($country->continent) &middot; {{ $country->continent->getName('de') }} @endif
                    </div>
                @else
                    <div style="font-size: 1.5rem; font-weight: 700; color: {{ $c['text'] }};">
                        Keinem Land zugeordnet
                    </div>
                @endif

                <div style="margin-top: 0.875rem;">
                    <span style="display: inline-block; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: rgba(255,255,255,0.7); color: {{ $c['text'] }};">
                        {{ $info['label'] }}
                    </span>
                    @if ($result['distance_km'] !== null && $result['distance_km'] > 0)
                        <span style="margin-left: 0.5rem; font-size: 0.8125rem; color: {{ $c['text'] }};">
                            Abstand zur Grenze: {{ number_format($result['distance_km'], 2, ',', '.') }} km
                        </span>
                    @endif
                </div>

                <div style="margin-top: 0.625rem; font-size: 0.8125rem; line-height: 1.5; color: {{ $c['text'] }};">
                    {{ $info['explanation'] }}
                </div>

                @if ($result['rejected_country'])
                    <div style="margin-top: 0.625rem; font-size: 0.8125rem; color: {{ $c['text'] }};">
                        Nächstgelegenes Land wäre <strong>{{ $result['rejected_country']->getName('de') }}</strong>
                        ({{ $result['rejected_country']->iso_code }}) gewesen – verworfen, weil dessen Grenze den Punkt nicht enthält.
                    </div>
                @endif
            </div>

            {{-- Details zur Abfrage --}}
            <div style="margin-top: 1.25rem; padding: 1rem 1.25rem; border-radius: 0.75rem; border: 1px solid #e5e7eb;">
                <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 0.75rem;">
                    Abfrage
                </div>

                @if ($result['input_type'] === 'airport_code')
                    <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; border-bottom: 1px solid #f3f4f6;">
                        <span style="color: #6b7280; font-size: 0.8125rem;">3-Letter-Code</span>
                        <span style="font-size: 0.8125rem; font-weight: 500;">{{ $result['code'] }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; border-bottom: 1px solid #f3f4f6;">
                        <span style="color: #6b7280; font-size: 0.8125rem;">Flughafen</span>
                        <span style="font-size: 0.8125rem; font-weight: 500;">
                            {{ $result['airport_name'] }}@if ($result['airport_municipality']), {{ $result['airport_municipality'] }}@endif
                        </span>
                    </div>
                @endif

                <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; border-bottom: 1px solid #f3f4f6;">
                    <span style="color: #6b7280; font-size: 0.8125rem;">Koordinaten</span>
                    <span style="font-size: 0.8125rem; font-weight: 500;">
                        {{ number_format($result['latitude'], 6, ',', '.') }}, {{ number_format($result['longitude'], 6, ',', '.') }}
                    </span>
                </div>

                @if ($country)
                    <div style="display: flex; justify-content: space-between; padding: 0.375rem 0; border-bottom: 1px solid #f3f4f6;">
                        <span style="color: #6b7280; font-size: 0.8125rem;">Grenzdaten für dieses Land</span>
                        <span style="font-size: 0.8125rem; font-weight: 500;">
                            {{ $result['has_boundary'] ? 'vorhanden' : 'nicht vorhanden (Näherung)' }}
                        </span>
                    </div>
                @endif

                <div style="display: flex; gap: 0.75rem; margin-top: 1rem; flex-wrap: wrap;">
                    @if ($country)
                        <x-filament::button
                            tag="a"
                            color="gray"
                            size="sm"
                            :href="\App\Filament\Resources\Countries\CountryResource::getUrl('view', ['record' => $country])"
                        >
                            Land öffnen
                        </x-filament::button>
                    @endif
                    <x-filament::button
                        tag="a"
                        color="gray"
                        size="sm"
                        target="_blank"
                        href="https://www.google.com/maps?q={{ $result['latitude'] }},{{ $result['longitude'] }}"
                    >
                        Auf Google Maps ansehen
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
