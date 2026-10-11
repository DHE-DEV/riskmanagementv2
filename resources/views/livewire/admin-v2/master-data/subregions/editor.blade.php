@php
    use App\Models\CustomEvent;
    use App\Support\AdminV2\RegionInfo;
    use App\Support\AdminV2\SubregionInfo;

    $record = $this->record;
    $locales = RegionInfo::locales();
    $sourceLocale = CustomEvent::sourceLocale();
    $infoStatus = $this->infoStatus();
    $countryTimezone = $this->countryTimezone;
    $bestMonths = RegionInfo::months((array) ($info['best_months'] ?? []));
    $timezoneOptions = $this->timezoneOptions;
    $region = $this->region;
    $regionOptions = $this->regionOptions->map(fn ($option) => ['value' => $option->id, 'label' => $option->getName('de'), 'code' => $option->country?->getName('de')])->all();
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <x-adminv2.master-data.editor-header
        section="subregions"
        :title="$record ? $record->getName('de') : 'Neues Teilgebiet'"
        :subtitle="$record && $region ? SubregionInfo::kindLabel($kind).' in '.$region->getName('de').($region->country ? ', '.$region->country->getName('de') : '') : null"
        :record="$record"
        :ai-check="false"
    />

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Teilgebiet" description="Eine Insel, Inselgruppe oder ein Reisegebiet innerhalb einer Region. Städte und Sehenswürdigkeiten lassen sich zuordnen, müssen aber nicht.">
                <div class="flex flex-col gap-5">
                    <div class="grid gap-5 sm:grid-cols-3">
                        @foreach ($locales as $locale)
                            <flux:field>
                                <flux:label>Name ({{ CustomEvent::localeLabel($locale, false) }})</flux:label>
                                <flux:input wire:model="names.{{ $locale }}" maxlength="255" />
                                <flux:error name="names.{{ $locale }}" />
                            </flux:field>
                        @endforeach
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Region</flux:label>
                            <x-adminv2.search-select :options="$regionOptions" model="regionId" :selected="$regionId" placeholder="Region wählen …" search-placeholder="Region oder Land …" label="Region" live />
                            <flux:error name="regionId" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Art</flux:label>
                            <flux:select wire:model.live="kind">
                                @foreach (SubregionInfo::KINDS as $key => [$label, $group])
                                    <flux:select.option value="{{ $key }}">{{ $label }} (unter „{{ $group }}“)</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:description>Bestimmt die Überschrift, unter der das Teilgebiet bei der Region steht.</flux:description>
                            <flux:error name="kind" />
                        </flux:field>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Code</flux:label>
                            <flux:input wire:model="code" maxlength="20" class="font-mono" placeholder="optional, z. B. TF für Teneriffa" />
                            <flux:error name="code" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Reihenfolge</flux:label>
                            <flux:input wire:model="sortOrder" type="number" min="0" max="9999" />
                            <flux:description>Kleinere Zahlen stehen bei der Region weiter oben.</flux:description>
                            <flux:error name="sortOrder" />
                        </flux:field>
                    </div>
                </div>
            </x-adminv2.card>

            @include('livewire.admin-v2.master-data.regions.partials.info-cards', ['labels' => [
                'description' => 'Beschreibung',
                'description_hint' => 'Das Teilgebiet allgemein – was es ausmacht. Was für die ganze Region oder das Land gilt, steht dort und wird hier nicht wiederholt.',
                'travel' => 'Reiseinfos',
                'travel_hint' => 'Nur, was in diesem Teilgebiet anders oder zusätzlich gilt – z. B. Anreise per Fähre, Busse auf der Insel.',
                'months_hint' => 'Nur, wenn sie hier anders sind als in der Region.',
                'facts' => 'Fakten',
                'facts_hint' => 'Einwohner und Fläche bei Inseln; bei Reisegebieten ohne feste Grenzen leer lassen. Die Zeitzone nur, wenn sie abweicht.',
                'ai_what' => 'die Infos des Teilgebiets',
                'key' => 'subregion',
            ]])

            <x-adminv2.master-data.coordinates :lat="$lat" :lng="$lng" description="Mittelpunkt der Insel bzw. des Gebiets für die Karte.">
                @if ($region?->country_id)
                    <x-slot:map>
                        <x-adminv2.master-data.boundary-map
                            :url="route('adminv2.master-data.boundaries.country', (int) $region->country_id)"
                            :lat="$lat"
                            :lng="$lng"
                            :focus-zoom="8"
                            empty-text="Für das Land liegen keine Grenzdaten vor – die Karte zeigt nur den Mittelpunkt."
                        />
                        <p class="text-xs text-zinc-500">Markierung: Mittelpunkt des Teilgebiets. Umriss: Grenzen von {{ $region->country?->getName('de') ?? 'dem Land' }}.</p>
                    </x-slot:map>
                @endif
            </x-adminv2.master-data.coordinates>
        </div>

        <div class="flex flex-col gap-6">
            @if ($record)
                @php $completeness = RegionInfo::completeness(RegionInfo::fromForm($info, null)); @endphp
                <x-adminv2.card heading="Infos">
                    <div class="flex flex-col gap-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <flux:badge size="sm" :color="match ($infoStatus) { 'ai' => 'amber', 'reviewed' => 'emerald', 'manual' => 'sky', default => 'zinc' }">{{ RegionInfo::STATUSES[$infoStatus] }}</flux:badge>
                            <span class="tabular-nums text-zinc-500">{{ $completeness['filled'] }} von {{ $completeness['total'] }} Texten</span>
                        </div>
                        @if (filled($infoMeta['ai_generated_at'] ?? null))
                            <p class="text-zinc-600 dark:text-zinc-400">KI-Vorschlag vom {{ \Illuminate\Support\Carbon::parse($infoMeta['ai_generated_at'])->timezone(config('app.timezone'))->format('d.m.Y, H:i') }} Uhr{{ filled($infoMeta['ai_model'] ?? null) ? ' ('.$infoMeta['ai_model'].')' : '' }}.</p>
                        @endif
                        @if (filled($infoMeta['reviewed_at'] ?? null))
                            <p class="text-zinc-600 dark:text-zinc-400">Geprüft am {{ \Illuminate\Support\Carbon::parse($infoMeta['reviewed_at'])->timezone(config('app.timezone'))->format('d.m.Y') }}{{ filled($infoMeta['reviewed_by'] ?? null) ? ' von '.$infoMeta['reviewed_by'] : '' }}.</p>
                        @elseif ($infoStatus === 'ai')
                            <flux:button size="sm" icon="check" wire:click="markReviewed">Als geprüft markieren</flux:button>
                        @endif
                    </div>
                </x-adminv2.card>

                <x-adminv2.master-data.record-meta :record="$record">
                    @if ($record->region_id)
                        <a href="{{ route('adminv2.master-data.regions.edit', $record->region_id) }}" class="mt-3 inline-flex items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">
                            Zur Region {{ $region?->getName('de') }}
                        </a>
                    @endif
                </x-adminv2.master-data.record-meta>

                @php $sights = $this->sights; @endphp
                <x-adminv2.master-data.related-list
                    heading="Sehenswürdigkeiten"
                    :count="$sights['count']"
                    :shown="$sights['items']->count()"
                    :all-url="route('adminv2.master-data.sights.index', ['country' => [$record->country_id], 'region' => $record->region_id, 'subregion' => $record->id])"
                    :create-url="route('adminv2.master-data.sights.create', ['subregion' => $record->id])"
                    create-label="Neue Sehenswürdigkeit"
                    empty-text="Diesem Teilgebiet ist noch keine Sehenswürdigkeit zugeordnet."
                >
                    @foreach ($sights['items'] as $sight)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <a href="{{ route('adminv2.master-data.sights.edit', $sight->id) }}" class="truncate text-zinc-900 hover:underline dark:text-white">{{ $sight->getName('de') }}</a>
                            @if ($sight->is_highlight)
                                <flux:badge size="sm" color="amber" inset="top bottom">Highlight</flux:badge>
                            @elseif ($sight->city)
                                <span class="shrink-0 truncate text-xs text-zinc-400">{{ $sight->city->getName('de') }}</span>
                            @endif
                        </li>
                    @endforeach
                </x-adminv2.master-data.related-list>

                @php $cities = $this->cities; @endphp
                <x-adminv2.master-data.related-list
                    heading="Städte"
                    :count="$cities['count']"
                    :shown="$cities['items']->count()"
                    :all-url="route('adminv2.master-data.cities.index', ['country' => [$record->country_id], 'region' => $record->region_id])"
                    :create-url="route('adminv2.master-data.cities.create', ['subregion' => $record->id])"
                    create-label="Neue Stadt"
                    empty-text="Diesem Teilgebiet ist noch keine Stadt zugeordnet – zuordnen lässt sie sich in der Bearbeitung der Stadt."
                >
                    @foreach ($cities['items'] as $city)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <a href="{{ route('adminv2.master-data.cities.edit', $city->id) }}" class="truncate text-zinc-900 hover:underline dark:text-white">{{ $city->getName('de') }}</a>
                            @if ($city->population)
                                <span class="shrink-0 text-xs tabular-nums text-zinc-400">{{ number_format($city->population, 0, ',', '.') }}</span>
                            @endif
                        </li>
                    @endforeach
                </x-adminv2.master-data.related-list>
            @else
                <x-adminv2.card heading="Nach dem Speichern">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Sobald das Teilgebiet angelegt ist, lassen sich die Infos per KI vorbefüllen und ihm Städte und Sehenswürdigkeiten zuordnen.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    <x-adminv2.master-data.translate-modal section="description" what="Kurzbeschreibung, Beschreibung und „Bekannt für“" />
    <x-adminv2.master-data.translate-modal section="travel" what="die Reiseinfos des Teilgebiets" />

    <flux:modal name="region-info-ai" class="md:w-[34rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Infos mit KI vorbefüllen</flux:heading>
                <flux:text class="mt-2">Die KI schreibt Beschreibung, Reiseinfos und Fakten in allen Sprachen – nur, was für dieses Teilgebiet eigen ist. Das dauert meist ein bis zwei Minuten. Das Ergebnis landet im Formular und wird erst mit „Speichern“ übernommen.</flux:text>
            </div>
            <flux:checkbox wire:model="suggestOverwrite" label="Vorhandene Texte überschreiben" description="Ohne Haken werden nur leere Felder gefüllt." />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" icon="sparkles" wire:click="startSuggestion">Vorbefüllen</flux:button>
            </div>
        </div>
    </flux:modal>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</form>
