@php
    use App\Livewire\AdminV2\MasterData\Airports\Index as AirportsIndex;
    use App\Models\Airport;
    use App\Support\AdminV2\Coordinates;

    $rows = $this->rows;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $types = Airport::getTypeOptions();
    $stats = $this->stats;
    $growth = $this->monthlyGrowth;
    $completeness = $this->completeness;
    $growthMax = max(1, ...array_values($growth));
    $daysMax = max(1, ...array_values($stats['lastDays']));
    $number = fn ($value) => number_format($value, 0, ',', '.');
    $barColor = fn (float $percent) => $percent >= 75 ? 'bg-green-500' : ($percent >= 50 ? 'bg-amber-500' : 'bg-red-500');

    $tile = 'rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-950';
    $tileLabel = 'text-sm font-medium text-zinc-600 dark:text-zinc-400';
    $tileValue = 'mt-3 text-4xl font-semibold tracking-tight text-zinc-900 tabular-nums dark:text-white';
    $tileHint = 'mt-1 text-sm text-zinc-500 dark:text-zinc-400';
@endphp

<div class="flex flex-col gap-6" x-data="{ showStats: $persist(false).as('adminv2-airports-stats') }">
    <x-adminv2.master-data.list-header section="airports" create-label="Neuer Flughafen">
        <flux:button x-on:click="showStats = ! showStats" icon="chart-bar" x-bind:aria-pressed="showStats">
            <span x-text="showStats ? 'Statistiken ausblenden' : 'Statistiken einblenden'">Statistiken einblenden</span>
        </flux:button>
    </x-adminv2.master-data.list-header>

    {{-- Statistiken – lassen sich ueber den Schalter oben komplett ausblenden --}}
    <div x-show="showStats" x-collapse x-cloak class="flex flex-col gap-6">

    {{-- Kennzahlen – wie im bisherigen Admin --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="{{ $tile }}">
            <div class="{{ $tileLabel }}">Gesamt</div>
            <div class="{{ $tileValue }}">{{ $number($stats['total']) }}</div>
            <div class="{{ $tileHint }}">davon {{ $number($stats['deleted']) }} im Papierkorb, {{ $number($stats['inactive']) }} inaktiv</div>
        </div>
        <div class="{{ $tile }}">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="{{ $tileLabel }}">Diesen Monat angelegt</div>
                    <div class="{{ $tileValue }}">{{ $number($stats['thisMonth']) }}</div>
                    <div @class([$tileHint, '!text-green-700 dark:!text-green-400' => ($stats['trend'] ?? 0) > 0, '!text-amber-700 dark:!text-amber-400' => ($stats['trend'] ?? 0) < 0])>
                        @if ($stats['trend'] === null)
                            {{ $stats['thisMonth'] > 0 ? 'Letzten Monat keine' : 'Wie letzter Monat' }}
                        @elseif ($stats['trend'] > 0)
                            +{{ number_format($stats['trend'], 1, ',', '.') }} % gegenüber letztem Monat
                        @elseif ($stats['trend'] < 0)
                            {{ number_format($stats['trend'], 1, ',', '.') }} % gegenüber letztem Monat
                        @else
                            Wie letzter Monat
                        @endif
                    </div>
                </div>
                {{-- Die letzten sieben Tage als kleine Balken --}}
                <div class="mt-1 flex h-12 shrink-0 items-end gap-1" aria-label="Neuanlagen der letzten sieben Tage">
                    @foreach ($stats['lastDays'] as $day => $count)
                        <flux:tooltip :content="$day.': '.$count">
                            <span class="block w-2 rounded-sm bg-[var(--color-accent)]/70" style="height: {{ max(8, round($count / $daysMax * 100)) }}%"></span>
                        </flux:tooltip>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="{{ $tile }}">
            <div class="{{ $tileLabel }}">Datenqualität</div>
            <div @class([$tileValue, '!text-green-700 dark:!text-green-400' => $stats['quality'] >= 75, '!text-amber-700 dark:!text-amber-400' => $stats['quality'] >= 50 && $stats['quality'] < 75, '!text-red-700 dark:!text-red-400' => $stats['quality'] < 50])>{{ $stats['quality'] }} %</div>
            <div class="{{ $tileHint }}">{{ $number($stats['withWebsite']) }} von {{ $number($stats['total']) }} mit Website</div>
        </div>
        <div class="{{ $tile }}">
            <div class="{{ $tileLabel }}">Airlines verknüpft</div>
            <div class="{{ $tileValue }}">{{ $number($stats['airlineLinks']) }}</div>
            <div class="{{ $tileHint }}">an {{ $number($stats['airportsWithAirlines']) }} Flughäfen</div>
        </div>
    </div>

    {{-- Pruefen & Ergaenzen: jede Kachel fuehrt in die passend gefilterte Liste --}}
    @php
        $checkTiles = [
            ['label' => 'Große Flughäfen, die fehlen', 'value' => $stats['unmanaged'], 'hint' => 'Mit Linienverkehr im Verzeichnis, noch nicht gepflegt', 'href' => route('adminv2.master-data.airport-codes.index', ['managed' => 'unmanaged']), 'warn' => $stats['unmanaged'] > 0],
            ['label' => 'IATA-Code nicht im Verzeichnis', 'value' => $stats['checks']['unknown-iata'], 'hint' => 'Vermutlich Tippfehler im Code', 'href' => route('adminv2.master-data.airports.index', ['check' => 'unknown-iata']), 'warn' => $stats['checks']['unknown-iata'] > 0],
            ['label' => 'Koordinaten weichen ab', 'value' => $stats['checks']['coordinates-off'], 'hint' => 'Mehr als '.AirportsIndex::COORDINATE_DEVIATION_KM.' km neben dem Verzeichnis', 'href' => route('adminv2.master-data.airports.index', ['check' => 'coordinates-off']), 'warn' => $stats['checks']['coordinates-off'] > 0],
            ['label' => 'Ohne verknüpfte Airline', 'value' => $stats['checks']['no-airlines'], 'hint' => 'Keine Direktverbindung hinterlegt', 'href' => route('adminv2.master-data.airports.index', ['check' => 'no-airlines']), 'warn' => false],
            ['label' => 'Länder ohne Flughafen', 'value' => $stats['countries'] - $stats['countriesWithAirport'], 'hint' => $number($stats['countriesWithAirport']).' von '.$number($stats['countries']).' Ländern abgedeckt', 'href' => route('adminv2.master-data.countries.index', ['airports' => 'none']), 'warn' => false],
            ['label' => 'Lange nicht geändert', 'value' => $stats['checks']['stale'], 'hint' => 'Seit über einem Jahr – Preise und Hotels prüfen', 'href' => route('adminv2.master-data.airports.index', ['check' => 'stale']), 'warn' => false],
        ];
    @endphp
    <div>
        <h2 class="mb-3 text-xs font-medium uppercase tracking-wide text-zinc-500">Prüfen &amp; ergänzen</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 min-[1700px]:grid-cols-6">
            @foreach ($checkTiles as $item)
                <a href="{{ $item['href'] }}" target="_blank" @class([$tile, 'group transition hover:border-zinc-300 hover:shadow-sm dark:hover:border-zinc-700'])>
                    <div class="{{ $tileLabel }}">{{ $item['label'] }}</div>
                    <div @class(['mt-3 text-3xl font-semibold tracking-tight tabular-nums', 'text-amber-700 dark:text-amber-400' => $item['warn'], 'text-zinc-900 dark:text-white' => ! $item['warn']])>{{ $number($item['value']) }}</div>
                    <div class="{{ $tileHint }}">{{ $item['hint'] }}</div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Diagramme: Zugang je Monat, Vollstaendigkeit und Verteilung --}}
    <div class="grid gap-6 xl:grid-cols-3">
        <x-adminv2.card heading="Flughäfen angelegt pro Monat" description="Die letzten sechs Monate." collapsible collapse-key="airports-growth">
            <div class="flex h-48 items-end gap-3">
                @foreach ($growth as $month => $count)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-1">
                        <span class="text-xs font-medium tabular-nums text-zinc-700 dark:text-zinc-300">{{ $count }}</span>
                        <div class="flex w-full flex-1 items-end">
                            <div class="w-full rounded-t-md bg-[var(--color-accent)]/70" style="height: {{ $count > 0 ? max(3, round($count / $growthMax * 100)) : 0 }}%"></div>
                        </div>
                        <span class="text-xs text-zinc-500">{{ $month }}</span>
                    </div>
                @endforeach
            </div>
        </x-adminv2.card>

        <x-adminv2.card heading="Datenvollständigkeit" description="Anteil der Flughäfen mit Angabe je Feld." collapsible collapse-key="airports-completeness">
            <dl class="flex flex-col gap-2.5">
                @foreach ($completeness as $label => $value)
                    <div class="grid grid-cols-[7rem_minmax(0,1fr)_5rem] items-center gap-3 text-sm">
                        <dt class="text-zinc-600 dark:text-zinc-400">{{ $label }}</dt>
                        <dd class="h-2.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full {{ $barColor($value['percent']) }}" style="width: {{ $value['percent'] }}%"></div>
                        </dd>
                        <dd class="text-end tabular-nums text-zinc-900 dark:text-white">{{ number_format($value['percent'], 1, ',', '.') }} %</dd>
                    </div>
                @endforeach
            </dl>
        </x-adminv2.card>

        @php
            $byContinent = $this->byContinent;
            $continentMax = max(1, ...array_column($byContinent ?: [['count' => 0]], 'count'));
        @endphp
        <x-adminv2.card heading="Nach Kontinent" description="Über das Land des Flughafens – Klick öffnet die Liste in einem neuen Tab." collapsible collapse-key="airports-continents">
            @if ($byContinent === [])
                <p class="text-sm text-zinc-500">Noch keine Flughäfen.</p>
            @else
                <dl class="flex flex-col gap-2.5">
                    @foreach ($byContinent as $item)
                        <a href="{{ route('adminv2.master-data.airports.index', ['continent' => $item['id']]) }}" target="_blank" class="grid grid-cols-[8rem_minmax(0,1fr)_3rem] items-center gap-3 text-start text-sm hover:opacity-80">
                            <dt class="truncate text-zinc-600 dark:text-zinc-400">{{ $item['label'] }}</dt>
                            <dd class="h-2.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-[var(--color-accent)]/70" style="width: {{ round($item['count'] / $continentMax * 100) }}%"></div>
                            </dd>
                            <dd class="text-end tabular-nums text-zinc-900 dark:text-white">{{ $number($item['count']) }}</dd>
                        </a>
                    @endforeach
                </dl>
            @endif
        </x-adminv2.card>
    </div>

    {{-- Verteilung: Laender, Typen, Besonderheiten – jeweils klickbar --}}
    <div class="grid gap-6 xl:grid-cols-3">
        <x-adminv2.card heading="Länder mit den meisten Flughäfen" collapsible collapse-key="airports-countries">
            <ul class="flex flex-col divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                @forelse ($this->topCountries as $item)
                    <li>
                        <a href="{{ route('adminv2.master-data.airports.index', ['country' => [$item['id']]]) }}" target="_blank" class="flex w-full items-center justify-between gap-3 py-2 text-start hover:text-[var(--color-accent)]">
                            <span class="truncate text-zinc-900 dark:text-white">{{ $item['label'] }}</span>
                            <span class="shrink-0 tabular-nums text-zinc-600 dark:text-zinc-400">{{ $number($item['count']) }}</span>
                        </a>
                    </li>
                @empty
                    <li class="py-2 text-zinc-500">Noch keine Flughäfen.</li>
                @endforelse
            </ul>
        </x-adminv2.card>

        <x-adminv2.card heading="Nach Typ" collapsible collapse-key="airports-types">
            <ul class="flex flex-col divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                @forelse ($this->byType as $value => $count)
                    <li>
                        <a href="{{ route('adminv2.master-data.airports.index', ['type' => $value]) }}" target="_blank" class="flex w-full items-center justify-between gap-3 py-2 text-start hover:text-[var(--color-accent)]">
                            <span class="text-zinc-900 dark:text-white">{{ $types[$value] ?? $value }}</span>
                            <span class="shrink-0 tabular-nums text-zinc-600 dark:text-zinc-400">{{ $number($count) }}</span>
                        </a>
                    </li>
                @empty
                    <li class="py-2 text-zinc-500">Noch keine Flughäfen.</li>
                @endforelse
            </ul>
        </x-adminv2.card>

        <x-adminv2.card heading="Besonderheiten und Nutzung" collapsible collapse-key="airports-features">
            <ul class="flex flex-col divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                @foreach (AirportsIndex::FEATURES as $value => $label)
                    <li>
                        <a href="{{ route('adminv2.master-data.airports.index', ['feature' => $value]) }}" target="_blank" class="flex w-full items-center justify-between gap-3 py-2 text-start hover:text-[var(--color-accent)]">
                            <span class="text-zinc-900 dark:text-white">{{ $label }}</span>
                            <span class="shrink-0 tabular-nums text-zinc-600 dark:text-zinc-400">{{ $number($stats['features'][$value]) }}</span>
                        </a>
                    </li>
                @endforeach
                <li class="flex items-center justify-between gap-3 py-2">
                    <span class="text-zinc-900 dark:text-white">In Reisen genutzt</span>
                    <span class="shrink-0 text-end tabular-nums text-zinc-600 dark:text-zinc-400">
                        @if ($stats['usageSegments'] > 0)
                            {{ $number($stats['usageAirports']) }} Flughäfen in {{ $number($stats['usageSegments']) }} Flugsegmenten
                        @else
                            noch keine Flugsegmente
                        @endif
                    </span>
                </li>
            </ul>
        </x-adminv2.card>
    </div>

    </div>{{-- /Statistiken --}}

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name, IATA/ICAO-Code oder Stadt suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-60">
            <x-adminv2.multi-select :options="$countryOptions" model="countryIds" :selected="$countryIds" all-label="Alle Länder" noun="Länder" searchable search-placeholder="Land oder ISO-Code …" label="Land" />
        </div>
        <div class="w-52">
            <flux:select wire:model.live="type" aria-label="Typ">
                <flux:select.option value="">Alle Typen</flux:select.option>
                @foreach ($types as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-40">
            <flux:select wire:model.live="active" aria-label="Status">
                <flux:select.option value="">Aktiv und inaktiv</flux:select.option>
                <flux:select.option value="active">Nur aktive</flux:select.option>
                <flux:select.option value="inactive">Nur inaktive</flux:select.option>
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="coordinates" aria-label="Koordinaten">
                <flux:select.option value="">Koordinaten: alle</flux:select.option>
                <flux:select.option value="missing">Ohne Koordinaten</flux:select.option>
            </flux:select>
        </div>
        <div class="w-56">
            <flux:select wire:model.live="check" aria-label="Prüfliste">
                <flux:select.option value="">Prüfliste: alle</flux:select.option>
                @foreach (AirportsIndex::CHECKS as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="feature" aria-label="Besonderheit">
                <flux:select.option value="">Besonderheit: alle</flux:select.option>
                @foreach (AirportsIndex::FEATURES as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if ($continent !== '' && ($continentLabel = collect($this->byContinent)->firstWhere('id', (int) $continent)['label'] ?? null))
            <flux:badge color="zinc" class="gap-1">
                Kontinent: {{ $continentLabel }}
                <button type="button" wire:click="$set('continent', '')" class="ms-1" aria-label="Kontinent-Filter entfernen"><flux:icon.x-mark variant="micro" /></button>
            </flux:badge>
        @endif
        <div class="w-48">
            <x-adminv2.master-data.trashed-filter />
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Flughafen', 'Flughäfen']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Flughäfen" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, countryIds, type, active, coordinates, continent, check, feature, trashed, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $airport)
                <x-adminv2.master-data.record-card
                    wire:key="airport-{{ $airport->id }}"
                    :id="$airport->id"
                    :edit-url="route('adminv2.master-data.airports.edit', $airport->id)"
                    :title="$airport->name"
                    :tags="array_filter([$airport->iata_code, $airport->icao_code])"
                    :trashed="$airport->trashed()"
                    :inactive="! $airport->is_active"
                >
                    <x-slot:aside>
                        <span class="inline-flex items-center gap-1.5">
                            @if ($airport->operates_24h) <flux:badge size="sm" color="green" inset="top bottom">24 h</flux:badge> @endif
                            <x-adminv2.master-data.coordinates-mark :lat="$airport->lat" :lng="$airport->lng" />
                        </span>
                    </x-slot:aside>
                    <x-adminv2.master-data.card-row icon="map-pin" label="Stadt und Land">
                        @if ($airport->city)
                            <a href="{{ route('adminv2.master-data.cities.edit', $airport->city->id) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $airport->city->getName('de') }}</a>
                        @else
                            –
                        @endif
                        <span class="text-zinc-400">·</span>
                        @if ($airport->country)
                            <a href="{{ route('adminv2.master-data.countries.edit', $airport->country->id) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $airport->country->getName('de') }}</a>
                        @else
                            –
                        @endif
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="tag" label="Typ">{{ $types[$airport->type] ?? ($airport->type ?: '–') }}</x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="paper-airplane" label="Airlines">{{ $airport->airlines_count > 0 ? $airport->airlines_count.' '.($airport->airlines_count === 1 ? 'Airline' : 'Airlines') : 'Keine Airline verknüpft' }}</x-adminv2.master-data.card-row>
                    <x-slot:footer>
                        <span class="tabular-nums">geändert {{ $airport->updated_at?->format('d.m.Y') ?? '–' }}</span>
                    </x-slot:footer>
                </x-adminv2.master-data.record-card>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</div>
