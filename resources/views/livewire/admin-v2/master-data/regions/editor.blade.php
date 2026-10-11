@php
    use App\Models\CustomEvent;
    use App\Support\AdminV2\RegionInfo;

    $record = $this->record;
    $locales = RegionInfo::locales();
    $sourceLocale = CustomEvent::sourceLocale();
    $infoStatus = $this->infoStatus();
    $countryTimezone = $this->countryTimezone;
    $bestMonths = RegionInfo::months((array) ($info['best_months'] ?? []));
    $timezoneOptions = $this->timezoneOptions;
    $cities = $this->cities;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $country = $this->countryOptions->firstWhere('id', (int) $countryId);
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <x-adminv2.master-data.editor-header
        section="regions"
        :title="$record ? $record->getName('de') : 'Neue Region'"
        :subtitle="$record && $country ? 'Region in '.$country->getName('de') : null"
        :record="$record"
    />

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Region">
                <x-slot:actions><x-adminv2.ai-check-button section="basics" /></x-slot:actions>
                <div class="flex flex-col gap-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <flux:input wire:model="nameDe" label="Name (Deutsch)" maxlength="255" />
                            <x-adminv2.ai-field-hint key="name" :review="$aiReview" />
                        </div>
                        <div>
                            <flux:input wire:model="nameEn" label="Name (Englisch)" maxlength="255" />
                            <x-adminv2.ai-field-hint key="name_en" :review="$aiReview" />
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Land</flux:label>
                            <x-adminv2.search-select :options="$countryOptions" model="countryId" :selected="$countryId" placeholder="Land wählen …" search-placeholder="Land oder ISO-Code …" label="Land" live />
                            <x-adminv2.ai-field-hint key="country" :review="$aiReview" />
                            <flux:error name="countryId" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Code</flux:label>
                            <flux:input wire:model="code" maxlength="10" class="font-mono" placeholder="z. B. BY für Bayern" />
                            <x-adminv2.ai-field-hint key="code" :review="$aiReview" />
                            <flux:error name="code" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Schlagwörter</flux:label>
                        <flux:description>Weitere Bezeichnungen, unter denen die Region gefunden wird – mit Komma getrennt.</flux:description>
                        <flux:input wire:model="keywords" placeholder="z. B. Bayern, Bavaria, Freistaat Bayern" maxlength="1000" />
                        <x-adminv2.ai-field-hint key="keywords" :review="$aiReview" />
                        <flux:error name="keywords" />
                    </flux:field>
                </div>
            </x-adminv2.card>

            @include('livewire.admin-v2.master-data.regions.partials.info-cards', ['labels' => [
                'description' => 'Regionsbeschreibung',
                'description_hint' => 'Die Region allgemein – was sie ausmacht. Was fürs ganze Land gilt, steht am Land und wird hier nicht wiederholt.',
                'travel' => 'Reiseinfos der Region',
                'travel_hint' => 'Nur, was in dieser Region anders oder zusätzlich gilt. Leere Felder zeigen die Angaben des Landes.',
                'months_hint' => 'Nur, wenn sie für die Region anders sind als fürs ganze Land.',
                'facts' => 'Fakten zur Region',
                'facts_hint' => 'Zahlen der Region. Die Zeitzone nur, wenn sie von der des Landes abweicht.',
                'ai_what' => 'die Regionsinfos',
                'key' => 'region',
            ]])

            <x-adminv2.master-data.coordinates :lat="$lat" :lng="$lng" :review="$aiReview" description="Mittelpunkt der Region für die Darstellung auf der Karte.">
                @if ($countryId !== '')
                    <x-slot:map>
                        <x-adminv2.master-data.boundary-map
                            :url="route('adminv2.master-data.boundaries.country', (int) $countryId)"
                            :lat="$lat"
                            :lng="$lng"
                            :focus-zoom="6"
                            empty-text="Für das Land liegen keine Grenzdaten vor – die Karte zeigt nur den Mittelpunkt der Region."
                        />
                        <p class="text-xs text-zinc-500">Markierung: Mittelpunkt der Region. Umriss: Grenzen von {{ $country?->getName('de') ?? 'dem Land' }}.@if (! is_numeric($lat) || ! is_numeric($lng)) Ohne Koordinaten zeigt die Karte nur das Land.@endif</p>
                    </x-slot:map>
                @endif
            </x-adminv2.master-data.coordinates>
        </div>

        <div class="flex flex-col gap-6">
            @if ($record)
                @php $completeness = RegionInfo::completeness(RegionInfo::fromForm($info, null)); @endphp
                <x-adminv2.card heading="Regionsinfos">
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
                    @if ($record->country_id)
                        <a href="{{ route('adminv2.master-data.countries.edit', $record->country_id) }}" class="mt-3 inline-flex items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">
                            Zum Land {{ $country?->getName('de') }}
                        </a>
                    @endif
                </x-adminv2.master-data.record-meta>

                @php $subregions = $this->subregions; @endphp
                <x-adminv2.master-data.related-list
                    heading="Teilgebiete"
                    :count="$subregions->count()"
                    :shown="$subregions->count()"
                    :all-url="route('adminv2.master-data.subregions.index', ['country' => [$record->country_id], 'region' => $record->id])"
                    :create-url="route('adminv2.master-data.subregions.create', ['region' => $record->id])"
                    create-label="Neues Teilgebiet"
                    empty-text="Keine Teilgebiete – Inseln oder Reisegebiete lassen sich hier anlegen, wo es sich lohnt."
                >
                    @foreach ($subregions as $subregion)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <a href="{{ route('adminv2.master-data.subregions.edit', $subregion->id) }}" class="truncate text-zinc-900 hover:underline dark:text-white">{{ $subregion->getName('de') }}</a>
                            <span class="shrink-0 text-xs text-zinc-400">{{ $subregion->kindLabel() }} · {{ $subregion->sights_count }}</span>
                        </li>
                    @endforeach
                </x-adminv2.master-data.related-list>

                <x-adminv2.master-data.related-list
                    heading="Städte"
                    ai-section="cities"
                    :review="$aiReview"
                    :count="$cities['count']"
                    :shown="$cities['items']->count()"
                    :all-url="route('adminv2.master-data.cities.index', ['country' => [$record->country_id], 'region' => $record->id])"
                    :create-url="route('adminv2.master-data.cities.create', ['region' => $record->id])"
                    create-label="Neue Stadt"
                    empty-text="Dieser Region ist noch keine Stadt zugeordnet."
                >
                    @foreach ($cities['items'] as $city)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <a href="{{ route('adminv2.master-data.cities.edit', $city->id) }}" class="truncate text-zinc-900 hover:underline dark:text-white">{{ $city->getName('de') }}</a>
                            @if ($city->is_regional_capital)
                                <flux:badge size="sm" color="sky" inset="top bottom">Regionshauptstadt</flux:badge>
                            @elseif ($city->population)
                                <span class="shrink-0 text-xs tabular-nums text-zinc-400">{{ number_format($city->population, 0, ',', '.') }}</span>
                            @endif
                        </li>
                    @endforeach
                </x-adminv2.master-data.related-list>


                @php $sights = $this->sights; @endphp
                <x-adminv2.master-data.related-list
                    heading="Sehenswürdigkeiten"
                    :count="$sights['count']"
                    :shown="$sights['items']->count()"
                    :all-url="route('adminv2.master-data.sights.index', ['country' => [$record->country_id], 'region' => $record->id])"
                    :create-url="route('adminv2.master-data.sights.create', ['region' => $record->id])"
                    create-label="Neue Sehenswürdigkeit"
                    empty-text="Für diese Region sind noch keine Sehenswürdigkeiten erfasst."
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
                <div class="-mt-3">
                    @if ($sightsRunId)
                        <div wire:poll.4s="checkSightsSuggestion" class="flex items-center gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
                            <flux:icon.loading class="size-4 shrink-0" />
                            <span>Die KI sucht Sehenswürdigkeiten und gleicht die Lage mit OpenStreetMap ab – das dauert zwei bis drei Minuten.</span>
                        </div>
                    @else
                        <flux:button size="sm" icon="sparkles" class="w-full" wire:click="startSightsSuggestion">Sehenswürdigkeiten mit KI vorschlagen</flux:button>
                    @endif
                </div>
            @else
                <x-adminv2.card heading="Nach dem Speichern">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Sobald die Region angelegt ist, lassen sich ihr Städte zuordnen – hier oder in der Bearbeitung einer Stadt.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    <x-adminv2.master-data.translate-modal section="description" what="Kurzbeschreibung, Beschreibung und „Bekannt für“" />
    <x-adminv2.master-data.translate-modal section="travel" what="die Reiseinfos der Region" />

    <flux:modal name="region-info-ai" class="md:w-[34rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Regionsinfos mit KI vorbefüllen</flux:heading>
                <flux:text class="mt-2">Die KI schreibt Beschreibung, Reiseinfos und Fakten in allen Sprachen – nur, was für diese Region eigen ist. Das dauert meist ein bis zwei Minuten. Das Ergebnis landet im Formular und wird erst mit „Speichern“ übernommen.</flux:text>
            </div>
            <flux:checkbox wire:model="suggestOverwrite" label="Vorhandene Texte überschreiben" description="Ohne Haken werden nur leere Felder gefüllt." />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" icon="sparkles" wire:click="startSuggestion">Vorbefüllen</flux:button>
            </div>
        </div>
    </flux:modal>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />

    <x-adminv2.ai-check-modal area="regions" :section="$aiSection" :checks="$this->aiChecks" :data="$this->aiData" :check-id="$aiCheckId" :prompt-draft="$aiPromptDraft" :result="$aiResult" :error="$aiError" :models="$this->aiModelOptions" :save-as-check="$aiSaveAsCheck" :review="$aiReview" :title="$record?->getName('de')" />
</form>
