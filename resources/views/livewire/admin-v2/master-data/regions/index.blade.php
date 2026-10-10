@php
    use App\Support\AdminV2\Coordinates;
    use App\Support\AdminV2\RegionInfo;

    $rows = $this->rows;
    $fillRuns = $this->fillRuns;
    $fillRunning = $fillRuns->contains(fn ($run) => $run->isRunning());
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.master-data.list-header section="regions" create-label="Neue Region">
        <flux:modal.trigger name="region-info-fill">
            <flux:button icon="sparkles">Mit KI vorbefüllen</flux:button>
        </flux:modal.trigger>
    </x-adminv2.master-data.list-header>

    @foreach ($fillRuns as $fillRun)
        @php
            $isSights = $fillRun->isSights();
            $label = $isSights ? 'KI-Sehenswürdigkeiten' : 'KI-Vorbefüllung';
            $runRunning = $fillRun->isRunning();
            $failed = (array) $fillRun->failed;
            $processed = $fillRun->done + count($failed);
            $percent = $fillRun->total > 0 ? (int) floor($processed / $fillRun->total * 100) : 0;
        @endphp
        <x-adminv2.card :heading="$label.($runRunning ? ' läuft' : ($fillRun->status === 'cancelled' ? ' angehalten' : ' abgeschlossen'))">
            <x-slot:actions>
                @if ($runRunning)
                    <flux:button size="sm" variant="ghost" icon="stop" wire:click="cancelFill('{{ $fillRun->kind }}')">Anhalten</flux:button>
                @else
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="dismissFill('{{ $fillRun->kind }}')">Ausblenden</flux:button>
                @endif
            </x-slot:actions>
            <div class="flex flex-col gap-3 text-sm" @if ($runRunning) wire:poll.5s="refreshFill" @endif>
                <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div class="h-full rounded-full bg-emerald-600 transition-all" style="width: {{ $percent }}%"></div>
                </div>
                <p class="text-zinc-600 dark:text-zinc-400">
                    <span class="tabular-nums font-medium text-zinc-900 dark:text-white">{{ number_format($fillRun->done, 0, ',', '.') }}</span> von {{ number_format($fillRun->total, 0, ',', '.') }} Regionen {{ $isSights ? 'bearbeitet, '.number_format((int) ($fillRun->result['created'] ?? 0), 0, ',', '.').' Sehenswürdigkeiten angelegt' : 'vorbefüllt' }}{{ count($failed) ? ', '.count($failed).' fehlgeschlagen' : '' }}.
                    @if ($runRunning)
                        Die Regionen werden im Hintergrund nacheinander bearbeitet, je Region {{ $isSights ? 'zwei bis drei Minuten' : 'etwa eine Minute' }} – die Seite muss dafür nicht offen bleiben.
                    @endif
                    Ergebnisse sind als „KI-Entwurf, ungeprüft“ gekennzeichnet.
                </p>
                @if (count($failed))
                    <details class="text-zinc-600 dark:text-zinc-400">
                        <summary class="cursor-pointer">Fehlgeschlagene Regionen</summary>
                        <ul class="mt-2 flex flex-col gap-1">
                            @foreach (array_slice($failed, 0, 20, true) as $failedId => $message)
                                <li><a href="{{ route('adminv2.master-data.regions.edit', $failedId) }}" class="underline">Region {{ $failedId }}</a>: {{ $message }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        </x-adminv2.card>
    @endforeach

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name oder Code suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-60">
            <x-adminv2.multi-select :options="$countryOptions" model="countryIds" :selected="$countryIds" all-label="Alle Länder" noun="Länder" searchable search-placeholder="Land oder ISO-Code …" label="Land" />
        </div>
        <div class="w-48">
            <flux:select wire:model.live="coordinates" aria-label="Koordinaten">
                <flux:select.option value="">Koordinaten: alle</flux:select.option>
                <flux:select.option value="missing">Ohne Koordinaten</flux:select.option>
            </flux:select>
        </div>
        <div class="w-56">
            <flux:select wire:model.live="info" aria-label="Regionsinfos">
                <flux:select.option value="">Regionsinfos: alle</flux:select.option>
                @foreach (RegionInfo::STATUSES as $statusKey => $statusLabel)
                    <flux:select.option value="{{ $statusKey }}">{{ $statusLabel }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-48">
            <x-adminv2.master-data.trashed-filter />
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Region', 'Regionen']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Regionen" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, countryIds, coordinates, info, trashed, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $region)
                <x-adminv2.master-data.record-card
                    wire:key="region-{{ $region->id }}"
                    :id="$region->id"
                    :edit-url="route('adminv2.master-data.regions.edit', $region->id)"
                    :title="$region->getName('de')"
                    :tags="array_filter([$region->code])"
                    :trashed="$region->trashed()"
                >
                    <x-slot:aside><x-adminv2.master-data.coordinates-mark :lat="$region->lat" :lng="$region->lng" /></x-slot:aside>
                    @if (($region->name_translations['en'] ?? '') !== '' && $region->name_translations['en'] !== $region->getName('de'))
                        <x-adminv2.master-data.card-row icon="language" label="Englisch">{{ $region->name_translations['en'] }}</x-adminv2.master-data.card-row>
                    @endif
                    <x-adminv2.master-data.card-row icon="flag" label="Land">
                        @if ($region->country)
                            <a href="{{ route('adminv2.master-data.countries.edit', $region->country->id) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $region->country->getName('de') }}</a>
                            @if ($region->country->trashed()) <span class="text-xs text-zinc-400">(Papierkorb)</span> @endif
                        @else
                            –
                        @endif
                    </x-adminv2.master-data.card-row>
                    @php $status = RegionInfo::status($region->info); @endphp
                    <x-adminv2.master-data.card-row icon="document-text" label="Infos">
                        <flux:badge size="sm" inset="top bottom" :color="match ($status) { 'ai' => 'amber', 'reviewed' => 'emerald', 'manual' => 'sky', default => 'zinc' }">{{ RegionInfo::STATUSES[$status] }}</flux:badge>
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="building-office-2" label="Städte">
                        @if ($region->cities_count > 0)
                            <a href="{{ route('adminv2.master-data.cities.index', ['country' => [$region->country_id], 'region' => $region->id]) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ number_format($region->cities_count, 0, ',', '.') }} {{ $region->cities_count === 1 ? 'Stadt' : 'Städte' }}</a>
                        @else
                            Keine Städte
                        @endif
                    </x-adminv2.master-data.card-row>
                    <x-slot:footer>
                        <span class="tabular-nums">geändert {{ $region->updated_at?->format('d.m.Y') ?? '–' }}</span>
                    </x-slot:footer>
                </x-adminv2.master-data.record-card>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    <flux:modal name="region-info-fill" class="md:w-[36rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Regionen mit KI vorbefüllen</flux:heading>
                <flux:text class="mt-2">Es gelten die Filter der Liste (Suche, Land, Infos …). Die Arbeit läuft im Hintergrund; die Ergebnisse werden als ungeprüfter KI-Entwurf gespeichert.</flux:text>
            </div>
            <flux:radio.group wire:model.live="fillKind" label="Was soll die KI anlegen?">
                <flux:radio value="fill" label="Regionsinfos" description="Beschreibung, Reiseinfos und Fakten in allen Sprachen – nur, was für die Region eigen ist. Etwa eine Minute je Region." />
                <flux:radio value="sights" label="Sehenswürdigkeiten" description="3 bis 15 Sehenswürdigkeiten und Unternehmungen je Region mit Texten, Lage und Highlights; Dubletten werden übersprungen. Zwei bis drei Minuten je Region." />
            </flux:radio.group>
            @if ($fillKind === 'sights')
                <flux:checkbox wire:model.live="fillAll" label="Auch Regionen, die schon Sehenswürdigkeiten haben" description="Ergänzt dort nur neue Einträge." />
            @else
                <flux:checkbox wire:model.live="fillAll" label="Auch Regionen, die schon Infos haben" description="Ohne Haken nur Regionen ohne Infos. Vorhandene Texte bleiben, nur leere Felder werden ergänzt." />
                <flux:checkbox wire:model.live="fillOverwrite" label="Vorhandene Texte überschreiben" description="Ersetzt auch geprüfte oder von Hand gepflegte Texte – nur mit Bedacht." />
            @endif
            <p class="rounded-lg bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                <span class="font-semibold tabular-nums">{{ number_format($this->fillCount, 0, ',', '.') }}</span> {{ $this->fillCount === 1 ? 'Region wird' : 'Regionen werden' }} bearbeitet.
            </p>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" icon="sparkles" wire:click="startFill" :disabled="$this->fillCount === 0">Starten</flux:button>
            </div>
        </div>
    </flux:modal>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</div>
