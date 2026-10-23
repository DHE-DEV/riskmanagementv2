@php
    use App\Support\AdminV2\SightInfo;

    $rows = $this->rows;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $regionOptions = $this->regionOptions;
    $aiCountry = $this->aiCountry;
    $aiRun = $this->aiRun;
    $aiTargets = $this->aiTargets;
    $aiTotal = count($aiTargets['region']) + count($aiTargets['subregion']) + count($aiTargets['city']);
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.master-data.list-header section="sights" create-label="Neue Sehenswürdigkeit">
        {{-- KI-Funktionen gelten einem Land – sie erscheinen, sobald genau eines gefiltert ist. --}}
        @if ($aiCountry)
            {{-- Oeffnen ueber den Server statt flux:modal.trigger: die Knoepfe kommen erst mit der Filterauswahl dazu,
                 ein so eingefuegter Trigger oeffnete den falschen bzw. keinen Dialog. --}}
            <flux:button wire:key="ai-find-button" icon="magnifying-glass" wire:click="openAiFind">Per KI suchen</flux:button>
            <flux:button wire:key="ai-fill-button" icon="sparkles" wire:click="openAiFill">Mit KI ergänzen</flux:button>
        @endif
    </x-adminv2.master-data.list-header>

    @if ($aiRun)
        @php
            $runRunning = $aiRun->isRunning();
            $failed = (array) $aiRun->failed;
            $processed = $aiRun->done + count($failed);
            $percent = $aiRun->total > 0 ? (int) floor($processed / $aiRun->total * 100) : 0;
            $runCountry = $this->aiRunCountry();
        @endphp
        <x-adminv2.card :heading="'KI-Sehenswürdigkeiten'.($runCountry ? ' für '.$runCountry->getName('de') : '').($runRunning ? ' läuft' : ($aiRun->status === 'cancelled' ? ' angehalten' : ' abgeschlossen'))">
            <x-slot:actions>
                @if ($runRunning)
                    <flux:button size="sm" variant="ghost" icon="stop" wire:click="cancelAiFill">Anhalten</flux:button>
                @else
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="dismissAiFill">Ausblenden</flux:button>
                @endif
            </x-slot:actions>
            <div class="flex flex-col gap-3 text-sm" @if ($runRunning) wire:poll.5s="refreshAiFill" @endif>
                <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div class="h-full rounded-full bg-emerald-600 transition-all" style="width: {{ $percent }}%"></div>
                </div>
                <p class="text-zinc-600 dark:text-zinc-400">
                    <span class="tabular-nums font-medium text-zinc-900 dark:text-white">{{ number_format($aiRun->done, 0, ',', '.') }}</span> von {{ number_format($aiRun->total, 0, ',', '.') }} Gebieten bearbeitet, {{ number_format((int) ($aiRun->result['created'] ?? 0), 0, ',', '.') }} Sehenswürdigkeiten angelegt{{ count($failed) ? ', '.count($failed).' fehlgeschlagen' : '' }}.
                    @if ($runRunning)
                        Regionen, Teilgebiete und Städte werden im Hintergrund nacheinander bearbeitet, je zwei bis drei Minuten – die Seite muss dafür nicht offen bleiben.
                    @endif
                    Neue Einträge sind als „KI-Entwurf, ungeprüft“ gekennzeichnet; Dubletten im Land werden übersprungen.
                </p>
                @if (count($failed))
                    <details class="text-xs text-zinc-500">
                        <summary class="cursor-pointer">Fehlgeschlagen</summary>
                        <ul class="mt-1 list-disc ps-5">
                            @foreach (array_slice($failed, 0, 20, true) as $key => $message)
                                <li>{{ $key }}: {{ $message }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        </x-adminv2.card>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name oder Adresse suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-60">
            <x-adminv2.multi-select :options="$countryOptions" model="countryIds" :selected="$countryIds" all-label="Alle Länder" noun="Länder" searchable search-placeholder="Land oder ISO-Code …" label="Land" />
        </div>
        {{-- Regionen gehoeren zu einem Land – der Filter erscheint, sobald genau eines gewaehlt ist. --}}
        @if ($regionOptions->isNotEmpty())
            <div class="w-56">
                <flux:select wire:model.live="region" variant="listbox" searchable placeholder="Alle Regionen" aria-label="Region">
                    <flux:select.option value="">Alle Regionen</flux:select.option>
                    <flux:select.option value="none">Ohne Region</flux:select.option>
                    @foreach ($regionOptions as $option)
                        <flux:select.option value="{{ $option->id }}">{{ $option->getName('de') }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif
        {{-- Teilgebiete und Staedte: sobald genau ein Land gewaehlt ist (eingegrenzt durch Region bzw. Teilgebiet).
             Region, Teilgebiet, Stadt und Kategorie sind durchsuchbar (Flux Pro: listbox mit Suche). --}}
        @if ($this->subregionOptions->isNotEmpty())
            <div class="w-64">
                <flux:select wire:model.live="subregion" variant="listbox" searchable placeholder="Alle Teilgebiete" aria-label="Teilgebiet">
                    <flux:select.option value="">Alle Teilgebiete</flux:select.option>
                    <flux:select.option value="none">Ohne Teilgebiet</flux:select.option>
                    @foreach ($this->subregionOptions as $option)
                        <flux:select.option value="{{ $option['id'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif
        @if ($this->cityOptions->isNotEmpty())
            <div class="w-56">
                <flux:select wire:model.live="city" variant="listbox" searchable placeholder="Alle Städte" aria-label="Stadt">
                    <flux:select.option value="">Alle Städte</flux:select.option>
                    <flux:select.option value="none">Ohne Stadt</flux:select.option>
                    @foreach ($this->cityOptions as $option)
                        <flux:select.option value="{{ $option['id'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif
        <div class="w-60">
            <flux:select wire:model.live="category" variant="listbox" searchable placeholder="Alle Kategorien" aria-label="Kategorie">
                <flux:select.option value="">Alle Kategorien</flux:select.option>
                @foreach (SightInfo::CATEGORIES as $key => [$label])
                    <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-44">
            <flux:select wire:model.live="highlight" aria-label="Highlights">
                <flux:select.option value="">Alle Einträge</flux:select.option>
                <flux:select.option value="yes">Nur Highlights</flux:select.option>
            </flux:select>
        </div>
        <div class="w-52">
            <flux:select wire:model.live="status" aria-label="Stand">
                <flux:select.option value="">Stand: alle</flux:select.option>
                @foreach (SightInfo::STATUSES as $key => $label)
                    <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="coordinates" aria-label="Koordinaten">
                <flux:select.option value="">Koordinaten: alle</flux:select.option>
                <flux:select.option value="missing">Ohne Koordinaten</flux:select.option>
            </flux:select>
        </div>
        <div class="w-48">
            <x-adminv2.master-data.trashed-filter />
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Sehenswürdigkeit', 'Sehenswürdigkeiten']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Sehenswürdigkeiten" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, countryIds, region, subregion, city, category, highlight, status, coordinates, trashed, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $sight)
                @php $status = SightInfo::status($sight->info); @endphp
                <x-adminv2.master-data.record-card
                    wire:key="sight-{{ $sight->id }}"
                    :id="$sight->id"
                    :edit-url="route('adminv2.master-data.sights.edit', $sight->id)"
                    :title="$sight->getName('de')"
                    :tags="array_filter([$sight->is_highlight ? 'Highlight' : null])"
                    :trashed="$sight->trashed()"
                >
                    <x-slot:aside><x-adminv2.master-data.coordinates-mark :lat="$sight->lat" :lng="$sight->lng" /></x-slot:aside>
                    <x-adminv2.master-data.card-row :icon="SightInfo::categoryIcon($sight->category)" label="Kategorie">{{ SightInfo::categoryLabel($sight->category) }}</x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="flag" label="Ort">
                        {{ collect([$sight->city?->getName('de'), $sight->subregion?->getName('de'), $sight->region?->getName('de'), $sight->country?->getName('de')])->filter()->implode(' · ') ?: '–' }}
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="document-text" label="Stand">
                        <flux:badge size="sm" inset="top bottom" :color="match ($status) { 'ai' => 'amber', 'reviewed' => 'emerald', default => 'sky' }">{{ SightInfo::STATUSES[$status] }}</flux:badge>
                    </x-adminv2.master-data.card-row>
                    <x-slot:footer>
                        <span class="tabular-nums">geändert {{ $sight->updated_at?->format('d.m.Y') ?? '–' }}</span>
                    </x-slot:footer>
                </x-adminv2.master-data.record-card>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />

    {{-- Die Dialoge stehen immer in der Seite (nur ihr Inhalt haengt am Land): erscheinen sie erst, nachdem im Filter
         ein Land gewaehlt wurde, werden sie nicht eingebunden und die Knoepfe oeffnen nichts. --}}
    {{-- Mit KI ergaenzen: Regionen, Teilgebiete und wichtige Staedte des Landes --}}
    <flux:modal wire:key="modal-sights-ai-fill" name="sights-ai-fill" class="md:w-[32rem]">
        @if ($aiCountry)
            <div class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">Sehenswürdigkeiten für {{ $this->aiScopeLabel }} ergänzen</flux:heading>
                    @if ($this->aiScopeLabel !== $aiCountry->getName('de'))
                        <flux:text class="mt-1 text-xs">Nur die Auswahl in den Filtern – für das ganze Land Region, Teilgebiet und Stadt zurücksetzen.</flux:text>
                    @endif
                    <flux:text class="mt-2">Die KI schlägt je Gebiet die wichtigsten Sehenswürdigkeiten und Unternehmungen vor – mit Kategorie, Highlights, Adresse, Besuchsdauer, Kurzbeschreibung und Beschreibung in allen Sprachen. Koordinaten werden mit OpenStreetMap abgeglichen. Alles wird als ungeprüfter KI-Entwurf angelegt, Dubletten im Land übersprungen.</flux:text>
                </div>
                <div class="flex flex-col gap-3">
                    <flux:checkbox wire:model.live="aiSubregions" label="Teilgebiete (Inseln, Reisegebiete) einbeziehen" />
                    <flux:checkbox wire:model.live="aiCities" label="Wichtige Städte einbeziehen (Hauptstadt, Regionshauptstädte, große Städte)" />
                    <flux:checkbox wire:model.live="aiAll" label="Auch Gebiete, die schon Sehenswürdigkeiten haben (ergänzt nur Neue)" />
                </div>
                <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800/60">
                    <span class="font-medium tabular-nums">{{ count($aiTargets['region']) }}</span> Regionen ·
                    <span class="font-medium tabular-nums">{{ count($aiTargets['subregion']) }}</span> Teilgebiete ·
                    <span class="font-medium tabular-nums">{{ count($aiTargets['city']) }}</span> Städte
                    <span class="block text-xs text-zinc-500">Dauer etwa {{ max(1, (int) ceil($aiTotal * 1.2)) }} Minuten im Hintergrund.</span>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                    <flux:button variant="primary" icon="sparkles" wire:click="startAiFill" :disabled="$aiTotal === 0 || ($aiRun && $aiRun->isRunning())">Starten</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Per KI suchen: eine bestimmte Sehenswuerdigkeit finden und nach Vorschau anlegen --}}
    <flux:modal wire:key="modal-sights-ai-find" name="sights-ai-find" class="md:w-[34rem]">
        @if ($aiCountry)
            <form wire:submit="aiFind" class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">Sehenswürdigkeit per KI suchen</flux:heading>
                    <flux:text class="mt-2">Fehlt etwas in {{ $aiCountry->getName('de') }}? Die KI sucht genau diese Sehenswürdigkeit und füllt alle Details aus. Angelegt wird erst nach deiner Bestätigung – als ungeprüfter KI-Entwurf.</flux:text>
                </div>
                <flux:input wire:model="aiQuery" label="Name" placeholder="z. B. Loro Parque, Masca-Schlucht, Mercado de la Boqueria" autocomplete="off" data-1p-ignore autofocus />
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" icon="sparkles">
                        <span wire:loading.remove wire:target="aiFind">Suchen</span>
                        <span wire:loading wire:target="aiFind">KI sucht … (bis zu einer Minute)</span>
                    </flux:button>
                </div>
            </form>

            @if ($aiError)
                <flux:callout variant="danger" icon="exclamation-triangle" class="mt-4">{{ $aiError }}</flux:callout>
            @endif

            @if ($aiFound)
                <div class="mt-5 flex flex-col gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-start justify-between gap-3">
                        <flux:heading>{{ $aiFound['name'] }}</flux:heading>
                        @if ($aiFound['suggestion']['is_highlight'] ?? false)
                            <flux:badge size="sm" color="amber">Highlight</flux:badge>
                        @endif
                    </div>
                    <dl class="grid grid-cols-[7rem_1fr] gap-x-3 gap-y-1 text-sm">
                        <dt class="text-zinc-500">Kategorie</dt><dd>{{ $aiFound['category'] }}</dd>
                        <dt class="text-zinc-500">Ort</dt><dd>{{ $aiFound['place'] ?: '–' }}</dd>
                        <dt class="text-zinc-500">Adresse</dt><dd>{{ $aiFound['suggestion']['address'] ?? '–' }}</dd>
                        <dt class="text-zinc-500">Koordinaten</dt><dd class="tabular-nums">{{ $aiFound['coordinates'] ?? '–' }}</dd>
                    </dl>
                    @if ($aiFound['short'])
                        <flux:text>{{ $aiFound['short'] }}</flux:text>
                    @endif
                    @if ($aiFound['existing_id'])
                        <flux:callout variant="warning" icon="information-circle">
                            Gibt es schon:
                            <a class="underline" href="{{ route('adminv2.master-data.sights.edit', $aiFound['existing_id']) }}">zum Eintrag</a>
                        </flux:callout>
                    @else
                        <div class="flex justify-end">
                            <flux:button variant="primary" icon="plus" wire:click="aiCreate">
                                <span wire:loading.remove wire:target="aiCreate">Anlegen und prüfen</span>
                                <span wire:loading wire:target="aiCreate">Wird angelegt …</span>
                            </flux:button>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </flux:modal>
</div>
