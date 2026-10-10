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

            <x-adminv2.card heading="Regionsbeschreibung" description="Die Region allgemein – was sie ausmacht. Was fürs ganze Land gilt, steht am Land und wird hier nicht wiederholt." collapsible collapse-key="region-description">
                <x-slot:actions>
                    <flux:modal.trigger name="translate-description">
                        <flux:button size="sm" variant="ghost" icon="language">Übersetzen</flux:button>
                    </flux:modal.trigger>
                    @if ($record)
                        <flux:modal.trigger name="region-info-ai">
                            <flux:button size="sm" variant="ghost" icon="sparkles" :disabled="(bool) $suggestRunId">Mit KI vorbefüllen</flux:button>
                        </flux:modal.trigger>
                    @endif
                </x-slot:actions>
                <div x-data="{ locale: @js($sourceLocale) }" class="flex flex-col gap-5">
                    @if ($suggestRunId)
                        <div wire:poll.3s="checkSuggestion" class="flex items-center gap-3 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
                            <flux:icon.loading class="size-4 shrink-0" />
                            <span class="flex-1">Die KI schreibt die Regionsinfos – das dauert meist ein bis zwei Minuten. Die Texte erscheinen hier automatisch und werden erst mit „Speichern“ übernommen.</span>
                            <flux:button size="xs" variant="ghost" wire:click="cancelSuggestion">Abbrechen</flux:button>
                        </div>
                    @endif
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Sprache der Texte</span>
                        <div class="inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                            @foreach ($locales as $locale)
                                <button type="button" x-on:click="locale = @js($locale)" class="rounded-md px-3 py-1 text-sm font-medium transition" :class="locale === @js($locale) ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'">
                                    {{ CustomEvent::localeLabel($locale) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    @foreach ($locales as $locale)
                        <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif wire:key="region-description-{{ $locale }}" class="flex flex-col gap-5">
                            @foreach (RegionInfo::DESCRIPTION_TEXTS as $field => [$label, $kind, $hint, $rows])
                                <flux:field>
                                    <flux:label>{{ $label }} ({{ strtoupper($locale) }})</flux:label>
                                    <flux:description>{{ $hint }}</flux:description>
                                    @if ($kind === 'tags')
                                        <flux:input wire:model="info.texts.{{ $field }}.{{ $locale }}" />
                                    @else
                                        <flux:textarea wire:model="info.texts.{{ $field }}.{{ $locale }}" rows="{{ $rows }}" />
                                    @endif
                                    <flux:error name="info.texts.{{ $field }}.{{ $locale }}" />
                                </flux:field>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Reiseinfos der Region" description="Nur, was in dieser Region anders oder zusätzlich gilt. Leere Felder zeigen die Angaben des Landes." collapsible collapse-key="region-travel">
                <x-slot:actions>
                    <flux:modal.trigger name="translate-travel">
                        <flux:button size="sm" variant="ghost" icon="language">Übersetzen</flux:button>
                    </flux:modal.trigger>
                </x-slot:actions>
                <div x-data="{ locale: @js($sourceLocale) }" class="flex flex-col gap-5">
                    <flux:field>
                        <flux:label>Beste Reisemonate</flux:label>
                        <flux:description>Nur, wenn sie für die Region anders sind als fürs ganze Land.</flux:description>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            @foreach (RegionInfo::MONTHS as $month => $monthLabel)
                                @php $on = in_array($month, $bestMonths, true); @endphp
                                <button type="button" wire:click="toggleBestMonth({{ $month }})" aria-pressed="{{ $on ? 'true' : 'false' }}" class="w-12 rounded-md border px-2 py-1 text-sm font-medium transition {{ $on ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-zinc-200 text-zinc-600 hover:border-zinc-400 dark:border-zinc-700 dark:text-zinc-300' }}">{{ $monthLabel }}</button>
                            @endforeach
                        </div>
                    </flux:field>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Sprache der Texte</span>
                        <div class="inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                            @foreach ($locales as $locale)
                                <button type="button" x-on:click="locale = @js($locale)" class="rounded-md px-3 py-1 text-sm font-medium transition" :class="locale === @js($locale) ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'">
                                    {{ CustomEvent::localeLabel($locale) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    @foreach ($locales as $locale)
                        <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif wire:key="region-travel-{{ $locale }}" class="flex flex-col gap-5">
                            @foreach (RegionInfo::TRAVEL_TEXTS as $field => [$label, $kind, $hint, $rows])
                                <flux:field>
                                    <flux:label>{{ $label }} ({{ strtoupper($locale) }})</flux:label>
                                    <flux:description>{{ $hint }}</flux:description>
                                    @if ($kind === 'tags')
                                        <flux:input wire:model="info.texts.{{ $field }}.{{ $locale }}" />
                                    @else
                                        <flux:textarea wire:model="info.texts.{{ $field }}.{{ $locale }}" rows="{{ $rows }}" />
                                    @endif
                                    <flux:error name="info.texts.{{ $field }}.{{ $locale }}" />
                                </flux:field>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Fakten zur Region" description="Zahlen der Region. Die Zeitzone nur, wenn sie von der des Landes abweicht." collapsible collapse-key="region-facts">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Einwohner</flux:label>
                        <flux:input wire:model="info.population" inputmode="numeric" placeholder="z. B. 4851851" />
                        <flux:error name="info.population" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Fläche (km²)</flux:label>
                        <flux:input wire:model="info.area_km2" inputmode="numeric" placeholder="z. B. 18345" />
                        <flux:error name="info.area_km2" />
                    </flux:field>
                    <flux:field class="min-w-0 sm:col-span-2">
                        <flux:label>Zeitzone</flux:label>
                        <x-adminv2.search-select :options="$timezoneOptions" model="info.timezone" :selected="(string) ($info['timezone'] ?? '')" :placeholder="$countryTimezone ? 'Wie das Land ('.$countryTimezone.')' : 'Wie das Land'" search-placeholder="Zeitzone suchen …" label="Zeitzone" clearable />
                        <flux:description>Leer = wie das Land{{ $countryTimezone ? ' ('.$countryTimezone.')' : '' }}.</flux:description>
                        <flux:error name="info.timezone" />
                    </flux:field>
                </div>
            </x-adminv2.card>

            <x-adminv2.master-data.coordinates :lat="$lat" :lng="$lng" :review="$aiReview" description="Mittelpunkt der Region für die Darstellung auf der Karte." />
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
