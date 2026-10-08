@php
    use App\Models\Country;
    use App\Models\CustomEvent;
    use App\Support\AdminV2\CountryRiskProfile;
    use App\Support\AdminV2\CountryTravelInfo;

    $record = $this->record;
    $related = $this->related;
    $boundary = $this->boundary;
    $overallRisk = $this->overallRisk;
    $categories = CountryRiskProfile::categories();
    $noteLocales = CountryRiskProfile::noteLocales();
    $sourceLocale = CustomEvent::sourceLocale();

    // Auswahl als Pillen in einer Reihe (Gebietstyp, Fahrseite).
    $chip = 'inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-700 transition select-none hover:border-zinc-300 '
        .'has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)] has-[:checked]:text-[var(--color-accent-foreground)] '
        .'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[var(--color-accent)]/40 '
        .'dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-600';
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <x-adminv2.master-data.editor-header
        section="countries"
        :title="$record ? $record->getName('de') : 'Neues Land'"
        :subtitle="$record ? trim($record->iso_code.' · '.$record->iso3_code, ' ·') : null"
        :record="$record"
    >
        <x-adminv2.cards-toggle />
    </x-adminv2.master-data.editor-header>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            {{-- Grunddaten --}}
            <x-adminv2.card heading="Grunddaten" collapsible collapse-key="country-basics">
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

                    {{-- Weitere Sprachen --}}
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-zinc-800 dark:text-white">Namen in weiteren Sprachen</p>
                                <p class="mt-0.5 text-sm text-zinc-500">Sprachkürzel und Name, z. B. fr – Allemagne.</p>
                            </div>
                            <flux:button size="sm" icon="plus" wire:click="addName">Sprache</flux:button>
                        </div>

                        <x-adminv2.ai-field-hint key="names" :review="$aiReview" :applyable="false" class="!mt-3 rounded-xl border border-dashed border-zinc-300 p-3 dark:border-zinc-700" />

                        @if ($extraNames !== [])
                            <div class="mt-3 flex flex-col gap-2">
                                @foreach ($extraNames as $index => $row)
                                    <div wire:key="extra-name-{{ $index }}" class="flex items-start gap-2">
                                        <div class="w-24 shrink-0">
                                            <flux:input wire:model="extraNames.{{ $index }}.code" placeholder="fr" maxlength="10" class="font-mono" aria-label="Sprachkürzel" />
                                            <flux:error name="extraNames.{{ $index }}.code" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <flux:input wire:model="extraNames.{{ $index }}.name" placeholder="Name in dieser Sprache" maxlength="255" aria-label="Name" />
                                            <flux:error name="extraNames.{{ $index }}.name" />
                                        </div>
                                        <flux:button variant="ghost" icon="x-mark" wire:click="removeName({{ $index }})" aria-label="Sprache entfernen" />
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <flux:field>
                            <flux:label>ISO-Code (2 Buchstaben)</flux:label>
                            <flux:input wire:model="isoCode" maxlength="2" class="font-mono uppercase" placeholder="DE" />
                            <x-adminv2.ai-field-hint key="iso_code" :review="$aiReview" />
                            <flux:error name="isoCode" />
                        </flux:field>
                        <flux:field>
                            <flux:label>ISO-Code (3 Buchstaben)</flux:label>
                            <flux:input wire:model="iso3Code" maxlength="3" class="font-mono uppercase" placeholder="DEU" />
                            <x-adminv2.ai-field-hint key="iso3_code" :review="$aiReview" />
                            <flux:error name="iso3Code" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Kontinent</flux:label>
                            <flux:select wire:model="continentId">
                                <flux:select.option value="">Bitte wählen …</flux:select.option>
                                @foreach ($this->continents as $continent)
                                    <flux:select.option value="{{ $continent->id }}">{{ $continent->getName('de') }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <x-adminv2.ai-field-hint key="continent" :review="$aiReview" />
                            <flux:error name="continentId" />
                        </flux:field>
                    </div>

                    <div class="flex flex-wrap gap-x-8 gap-y-3">
                        <div>
                            <flux:switch wire:model="isEuMember" label="EU-Mitglied" align="left" />
                            <x-adminv2.ai-field-hint key="is_eu_member" :review="$aiReview" />
                        </div>
                        <div>
                            <flux:switch wire:model="isSchengenMember" label="Schengen-Mitglied" align="left" />
                            <x-adminv2.ai-field-hint key="is_schengen_member" :review="$aiReview" />
                        </div>
                    </div>
                </div>
            </x-adminv2.card>

            {{-- Weitere Informationen: Waehrung, Vorwahl, Zeitzone, Groesse – und was Reisende vor Ort brauchen --}}
            <x-adminv2.card heading="Weitere Informationen" description="Währung, Vorwahl, Zeitzone, Größe – und was Reisende vor Ort brauchen." collapsible collapse-key="country-details">
                <x-slot:actions>
                    <flux:modal.trigger name="translate-details">
                        <flux:button size="sm" variant="ghost" icon="language">Übersetzen</flux:button>
                    </flux:modal.trigger>
                    <x-adminv2.ai-check-button section="details" />
                </x-slot:actions>
                <div x-data="{ locale: @js($sourceLocale) }" class="flex flex-col gap-6">
                <div class="grid gap-5 sm:grid-cols-3">
                    <flux:field>
                        <flux:label>Währungscode</flux:label>
                        <flux:input wire:model="currencyCode" maxlength="3" class="font-mono uppercase" placeholder="EUR" />
                        <x-adminv2.ai-field-hint key="currency_code" :review="$aiReview" />
                        <flux:error name="currencyCode" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Währungsname</flux:label>
                        <flux:input wire:model="currencyName" maxlength="255" placeholder="Euro" />
                        <x-adminv2.ai-field-hint key="currency_name" :review="$aiReview" />
                        <flux:error name="currencyName" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Währungssymbol</flux:label>
                        <flux:input wire:model="currencySymbol" maxlength="5" placeholder="€" />
                        <x-adminv2.ai-field-hint key="currency_symbol" :review="$aiReview" />
                        <flux:error name="currencySymbol" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Telefonvorwahl</flux:label>
                        <flux:description>Beispiel: +49</flux:description>
                        <flux:input wire:model="phonePrefix" maxlength="10" placeholder="+49" />
                        <x-adminv2.ai-field-hint key="phone_prefix" :review="$aiReview" />
                        <flux:error name="phonePrefix" />
                    </flux:field>
                    <flux:field class="sm:col-span-2">
                        <flux:label>Zeitzone</flux:label>
                        <flux:description>IANA-Name, bei mehreren Zeitzonen mit Komma – die erste ist die wichtigste.</flux:description>
                        <flux:input wire:model="timezone" maxlength="255" placeholder="Europe/Berlin" class="font-mono" />
                        <x-adminv2.ai-field-hint key="timezone" :review="$aiReview" />
                        <flux:error name="timezone" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Bevölkerung</flux:label>
                        <flux:input wire:model="population" inputmode="numeric" placeholder="z. B. 83200000" />
                        <x-adminv2.ai-field-hint key="population" :review="$aiReview" />
                        <flux:error name="population" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Fläche (km²)</flux:label>
                        <flux:input wire:model="areaKm2" inputmode="decimal" placeholder="z. B. 357588" />
                        <x-adminv2.ai-field-hint key="area_km2" :review="$aiReview" />
                        <flux:error name="areaKm2" />
                    </flux:field>
                </div>

                <div class="flex flex-col gap-6 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                    {{-- Gebiet --}}
                    <div class="flex flex-col gap-5">
                        <flux:field>
                            <flux:label>Gebietstyp</flux:label>
                            <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Gebietstyp">
                                @foreach (CountryTravelInfo::TERRITORY_TYPES as $value => $label)
                                    <label wire:key="territory-{{ $value }}" class="{{ $chip }}">
                                        <input type="radio" wire:model.live="territoryType" value="{{ $value }}" class="sr-only" />
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <x-adminv2.ai-field-hint key="territory_type" :review="$aiReview" />
                            <flux:error name="territoryType" />
                        </flux:field>
                        <div x-show="$wire.territoryType !== 'sovereign'" x-cloak class="sm:max-w-md">
                            <x-adminv2.search-select
                                label="Mutterland"
                                model="parentCountryId"
                                :selected="$parentCountryId"
                                :options="$this->parentOptions"
                                placeholder="Kein Mutterland"
                                search-placeholder="Land suchen …"
                                clearable
                            />
                            <x-adminv2.ai-field-hint key="parent_country" :review="$aiReview" />
                            <flux:error name="parentCountryId" />
                        </div>
                    </div>

                    {{-- Fahrseite --}}
                    <flux:field>
                        <flux:label>Fahrseite</flux:label>
                        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Fahrseite">
                            @foreach (['' => 'Unbekannt'] + CountryTravelInfo::DRIVING_SIDES as $value => $label)
                                <label wire:key="driving-{{ $value ?: 'none' }}" class="{{ $chip }}">
                                    <input type="radio" wire:model="drivingSide" value="{{ $value }}" class="sr-only" />
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <x-adminv2.ai-field-hint key="driving_side" :review="$aiReview" />
                        <flux:error name="drivingSide" />
                    </flux:field>

                    {{-- Notrufnummern --}}
                    <div>
                        <p class="text-sm font-medium text-zinc-800 dark:text-white">Notrufnummern</p>
                        <div class="mt-2 grid gap-4 sm:grid-cols-4">
                            @foreach (CountryTravelInfo::EMERGENCY as $key => $label)
                                <flux:field wire:key="emergency-{{ $key }}">
                                    <flux:label>{{ $label }}</flux:label>
                                    <flux:input wire:model="travelInfo.emergency.{{ $key }}" inputmode="tel" placeholder="{{ $key === 'general' ? '112' : '' }}" class="font-mono" />
                                    <x-adminv2.ai-field-hint key="emergency_{{ $key }}" :review="$aiReview" />
                                    <flux:error name="travelInfo.emergency.{{ $key }}" />
                                </flux:field>
                            @endforeach
                        </div>
                    </div>

                    {{-- Religionen: die Reihenfolge des Anklickens ist die Reihenfolge fuer Kunden --}}
                    @php $religionOrder = array_flip(CountryTravelInfo::religionKeys((array) ($travelInfo['religions'] ?? []))); @endphp
                    <flux:field>
                        <flux:label>Religionen</flux:label>
                        <flux:description>Die im Land verbreiteten, mehrere möglich. Die Reihenfolge des Anklickens ist die Reihenfolge, in der Kunden sie sehen – die Nummer zeigt sie an.</flux:description>
                        <div class="flex flex-wrap gap-2">
                            @foreach (CountryTravelInfo::RELIGIONS as $key => [$religionLabel])
                                <label wire:key="religion-{{ $key }}" class="{{ $chip }}">
                                    <input type="checkbox" wire:model.live="travelInfo.religions" value="{{ $key }}" class="sr-only" />
                                    @isset ($religionOrder[$key])
                                        <span class="flex size-5 items-center justify-center rounded-full bg-white/25 text-xs font-semibold tabular-nums">{{ $religionOrder[$key] + 1 }}</span>
                                    @endisset
                                    {{ $religionLabel }}
                                </label>
                            @endforeach
                        </div>
                        <x-adminv2.ai-field-hint key="religions" :review="$aiReview" />
                        <flux:error name="travelInfo.religions" />
                        <flux:error name="travelInfo.religions.*" />
                    </flux:field>

                    @if ($religionOrder !== [])
                        <div class="sm:max-w-md">
                            <p class="text-sm font-medium text-zinc-800 dark:text-white">Reihenfolge für Kunden</p>
                            <ol class="mt-2 flex flex-col divide-y divide-zinc-100 rounded-xl border border-zinc-200 text-sm dark:divide-zinc-800 dark:border-zinc-700">
                                @foreach (array_keys($religionOrder) as $position => $key)
                                    <li wire:key="religion-order-{{ $key }}" class="flex items-center gap-3 px-3 py-1.5">
                                        <span class="w-5 text-right text-xs tabular-nums text-zinc-400">{{ $position + 1 }}.</span>
                                        <span class="min-w-0 flex-1 truncate text-zinc-900 dark:text-white">{{ CountryTravelInfo::RELIGIONS[$key][0] }}</span>
                                        <flux:button size="xs" variant="ghost" icon="chevron-up" wire:click="moveReligion('{{ $key }}', 'up')" :disabled="$loop->first" aria-label="Nach vorn" />
                                        <flux:button size="xs" variant="ghost" icon="chevron-down" wire:click="moveReligion('{{ $key }}', 'down')" :disabled="$loop->last" aria-label="Nach hinten" />
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

                    {{-- Texte je Sprache --}}
                    <div class="flex flex-col gap-4 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Sprache der Texte</span>
                            <div class="inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                                @foreach ($noteLocales as $locale)
                                    <button
                                        type="button"
                                        x-on:click="locale = @js($locale)"
                                        class="rounded-md px-3 py-1 text-sm font-medium transition"
                                        :class="locale === @js($locale)
                                            ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-950 dark:text-white'
                                            : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'"
                                    >
                                        {{ CustomEvent::localeLabel($locale) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Nationaltag: Datum und Bezeichnung je Sprache --}}
                        <div class="grid items-start gap-4 sm:grid-cols-[11rem_minmax(0,1fr)]">
                            <flux:field>
                                <flux:label>Nationaltag</flux:label>
                                <flux:input wire:model="travelInfo.national_day.date" type="date" />
                                <flux:description>Jahr = Ursprung, falls bekannt.</flux:description>
                                <x-adminv2.ai-field-hint key="national_day_date" :review="$aiReview" />
                                <flux:error name="travelInfo.national_day.date" />
                            </flux:field>
                            <div>
                                @foreach ($noteLocales as $locale)
                                    <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif wire:key="national-day-name-{{ $locale }}">
                                        <flux:field>
                                            <flux:label>Bezeichnung des Nationaltags ({{ strtoupper($locale) }})</flux:label>
                                            <flux:input wire:model="travelInfo.national_day.name.{{ $locale }}" maxlength="255" placeholder="{{ $locale === 'de' ? 'z. B. Tag der Deutschen Einheit' : ($locale === 'en' ? 'e.g. German Unity Day' : '') }}" />
                                            <flux:error name="travelInfo.national_day.name.{{ $locale }}" />
                                        </flux:field>
                                        <x-adminv2.ai-field-hint key="national_day_name_{{ $locale }}" :review="$aiReview" />
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @foreach (CountryTravelInfo::TEXTS as $field => [$label, $type])
                            <div wire:key="travel-text-{{ $field }}">
                                @foreach ($noteLocales as $locale)
                                    <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif wire:key="travel-text-{{ $field }}-{{ $locale }}">
                                        <flux:field>
                                            <flux:label>{{ $label }} ({{ strtoupper($locale) }})</flux:label>
                                            @if ($type === 'tags')
                                                <flux:description>3–6 Stichworte, mit Komma getrennt – z. B. Strände, Küche, Geschichte.</flux:description>
                                                <flux:input wire:model="travelInfo.texts.{{ $field }}.{{ $locale }}" />
                                            @else
                                                <flux:textarea wire:model="travelInfo.texts.{{ $field }}.{{ $locale }}" rows="{{ $field === 'intro' ? 4 : 2 }}" />
                                            @endif
                                            <flux:error name="travelInfo.texts.{{ $field }}.{{ $locale }}" />
                                        </flux:field>
                                        <x-adminv2.ai-field-hint key="{{ $field }}_{{ $locale }}" :review="$aiReview" />
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
                </div>
            </x-adminv2.card>

            {{-- Trinkgeld: je Bereich ein Rahmen von–bis und eine Beschreibung je Sprache --}}
            <x-adminv2.card
                heading="Trinkgeld"
                description="Üblicher Rahmen oder fester Wert für Hotels, Guides, Restaurants und Taxi – als Prozent oder Betrag mit Währung."
                collapsible
                collapse-key="country-tipping"
            >
                <x-slot:actions>
                    <flux:modal.trigger name="translate-tipping">
                        <flux:button size="sm" variant="ghost" icon="language">Übersetzen</flux:button>
                    </flux:modal.trigger>
                    <x-adminv2.ai-check-button section="tipping" />
                </x-slot:actions>
                <div x-data="{ locale: @js($sourceLocale) }" class="flex flex-col gap-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Sprache der Beschreibungen</span>
                        <div class="inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                            @foreach ($noteLocales as $locale)
                                <button type="button" x-on:click="locale = @js($locale)" class="rounded-md px-3 py-1 text-sm font-medium transition" :class="locale === @js($locale) ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'">
                                    {{ CustomEvent::localeLabel($locale) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach (CountryTravelInfo::TIPPING_CATEGORIES as $category => $label)
                            @php $base = 'travelInfo.tipping.'.$category; @endphp
                            <div wire:key="tipping-{{ $category }}" class="grid items-start gap-4 py-4 first:pt-0 last:pb-0 md:grid-cols-[9rem_minmax(0,1fr)]">
                                <p class="pt-2 text-sm font-medium text-zinc-900 dark:text-white">{{ $label }}</p>
                                <div class="flex flex-col gap-3">
                                    {{-- Art: Spanne oder fester Wert --}}
                                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Art der Angabe {{ $label }}">
                                        @foreach (CountryTravelInfo::TIPPING_MODES as $modeValue => $modeLabel)
                                            <label wire:key="tipping-mode-{{ $category }}-{{ $modeValue }}" class="{{ $chip }}">
                                                <input type="radio" wire:model.live="{{ $base }}.mode" value="{{ $modeValue }}" class="sr-only" />
                                                {{ $modeLabel }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @php $isFixed = ($travelInfo['tipping'][$category]['mode'] ?? 'range') === 'fixed'; @endphp
                                    <div class="grid gap-3 {{ $isFixed ? 'sm:grid-cols-[6rem_minmax(0,1fr)_minmax(10rem,1.3fr)]' : 'sm:grid-cols-[5rem_5rem_minmax(0,1fr)_minmax(10rem,1.3fr)]' }}">
                                        <flux:field>
                                            <flux:label>{{ $isFixed ? 'Wert' : 'Von' }}</flux:label>
                                            <flux:input wire:model="{{ $base }}.from" inputmode="decimal" placeholder="{{ $isFixed ? '10' : '5' }}" />
                                            <flux:error name="{{ $base }}.from" />
                                        </flux:field>
                                        @unless ($isFixed)
                                            <flux:field>
                                                <flux:label>Bis</flux:label>
                                                <flux:input wire:model="{{ $base }}.to" inputmode="decimal" placeholder="10" />
                                                <flux:error name="{{ $base }}.to" />
                                            </flux:field>
                                        @endunless
                                        <flux:field>
                                            <flux:label>Einheit</flux:label>
                                            <flux:select wire:model="{{ $base }}.unit">
                                                @foreach (CountryTravelInfo::TIPPING_UNITS as $value => $unitLabel)
                                                    <flux:select.option value="{{ $value }}">{{ $unitLabel }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:error name="{{ $base }}.unit" />
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>Währung</flux:label>
                                            <x-adminv2.search-select
                                                label="Währung {{ $label }}"
                                                model="{{ $base }}.currency"
                                                :selected="$travelInfo['tipping'][$category]['currency'] ?? ''"
                                                :options="$this->currencyOptions"
                                                :placeholder="$currencyCode ? 'Wie das Land ('.$currencyCode.')' : 'Währung wählen …'"
                                                search-placeholder="Name oder Code …"
                                                clearable
                                            />
                                            <flux:error name="{{ $base }}.currency" />
                                        </flux:field>
                                    </div>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                                        <x-adminv2.ai-field-hint key="tipping_{{ $category }}_mode" :review="$aiReview" class="!mt-0" />
                                        <x-adminv2.ai-field-hint key="tipping_{{ $category }}_from" :review="$aiReview" class="!mt-0" />
                                        <x-adminv2.ai-field-hint key="tipping_{{ $category }}_to" :review="$aiReview" class="!mt-0" />
                                        <x-adminv2.ai-field-hint key="tipping_{{ $category }}_unit" :review="$aiReview" class="!mt-0" />
                                        <x-adminv2.ai-field-hint key="tipping_{{ $category }}_currency" :review="$aiReview" class="!mt-0" />
                                    </div>
                                    @foreach ($noteLocales as $locale)
                                        <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif wire:key="tipping-{{ $category }}-{{ $locale }}">
                                            <flux:textarea wire:model="{{ $base }}.description.{{ $locale }}" rows="2" aria-label="Beschreibung {{ $label }} ({{ strtoupper($locale) }})" placeholder="Beschreibung{{ $locale === $sourceLocale ? '' : ' ('.strtoupper($locale).')' }} – z. B. Aufrunden üblich, Service oft enthalten" />
                                            <flux:error name="{{ $base }}.description.{{ $locale }}" />
                                            <x-adminv2.ai-field-hint key="tipping_{{ $category }}_description_{{ $locale }}" :review="$aiReview" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-adminv2.card>

            {{-- Strom: Steckertypen mit Bild, Spannung und Frequenz --}}
            <x-adminv2.card
                heading="Strom"
                description="Steckertypen nach IEC sowie Netzspannung und -frequenz. Die Bilder zeigen die Steckdose von vorn."
                collapsible
                collapse-key="country-power"
            >
                <x-slot:actions>
                    <flux:modal.trigger name="translate-power">
                        <flux:button size="sm" variant="ghost" icon="language">Übersetzen</flux:button>
                    </flux:modal.trigger>
                    <x-adminv2.ai-check-button section="power" />
                </x-slot:actions>
                <div x-data="{ locale: @js($sourceLocale) }" class="flex flex-col gap-6">
                    <div class="grid gap-5 sm:max-w-md sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Netzspannung (V)</flux:label>
                            <flux:input wire:model="travelInfo.voltage" inputmode="numeric" placeholder="230" />
                            <x-adminv2.ai-field-hint key="voltage" :review="$aiReview" />
                            <flux:error name="travelInfo.voltage" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Netzfrequenz (Hz)</flux:label>
                            <flux:select wire:model="travelInfo.frequency">
                                <flux:select.option value="">Unbekannt</flux:select.option>
                                <flux:select.option value="50">50 Hz</flux:select.option>
                                <flux:select.option value="60">60 Hz</flux:select.option>
                            </flux:select>
                            <x-adminv2.ai-field-hint key="frequency" :review="$aiReview" />
                            <flux:error name="travelInfo.frequency" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Steckertypen</flux:label>
                        <flux:description>Mehrere möglich – anklicken, was im Land verbreitet ist.</flux:description>
                        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(7.5rem, 1fr));">
                            @foreach (CountryTravelInfo::PLUG_TYPES as $type => $meaning)
                                <label
                                    wire:key="plug-{{ $type }}"
                                    class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border border-zinc-200 bg-white p-3 text-center transition select-none hover:border-zinc-300 has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)]/10 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[var(--color-accent)]/40 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600"
                                    title="{{ $meaning }}"
                                >
                                    <input type="checkbox" wire:model="travelInfo.plug_types" value="{{ $type }}" class="sr-only" />
                                    <img src="{{ CountryTravelInfo::plugImage($type) }}" alt="" class="size-16" loading="lazy" />
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">Typ {{ $type }}</span>
                                    <span class="text-xs leading-snug text-zinc-500">{{ $meaning }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-adminv2.ai-field-hint key="plug_types" :review="$aiReview" />
                        <flux:error name="travelInfo.plug_types" />
                        <flux:error name="travelInfo.plug_types.*" />
                    </flux:field>

                    {{-- Bemerkung je Sprache: Besonderheiten wie mehrere Spannungen oder Frequenzen --}}
                    <div class="flex flex-col gap-3 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Sprache der Bemerkung</span>
                            <div class="inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                                @foreach ($noteLocales as $locale)
                                    <button type="button" x-on:click="locale = @js($locale)" class="rounded-md px-3 py-1 text-sm font-medium transition" :class="locale === @js($locale) ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'">
                                        {{ CustomEvent::localeLabel($locale) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        @foreach ($noteLocales as $locale)
                            <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif wire:key="power-notes-{{ $locale }}">
                                <flux:field>
                                    <flux:label>Bemerkung ({{ strtoupper($locale) }})</flux:label>
                                    <flux:description>Besonderheiten, z. B. mehrere Spannungen oder Frequenzen je Region, Adapterhinweise.</flux:description>
                                    <flux:textarea wire:model="travelInfo.texts.power_notes.{{ $locale }}" rows="3" />
                                    <flux:error name="travelInfo.texts.power_notes.{{ $locale }}" />
                                </flux:field>
                                <x-adminv2.ai-field-hint key="power_notes_{{ $locale }}" :review="$aiReview" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-adminv2.card>

            {{-- Taxi-Apps: verbreitete Anbieter im Land --}}
            <x-adminv2.card
                heading="Taxi-Apps"
                description="Welche Taxi- und Mobilitäts-Apps im Land verbreitet sind. Die Anbieter werden unter System › Taxi Apps gepflegt."
                collapsible
                collapse-key="country-taxi-apps"
            >
                <x-slot:actions>
                    <x-adminv2.ai-check-button section="taxi_apps" />
                    <flux:button size="sm" variant="ghost" icon="cog-6-tooth" :href="route('adminv2.system.taxi-apps.index')">Anbieter verwalten</flux:button>
                </x-slot:actions>
                <div class="flex flex-col gap-4">
                    @if ($this->taxiAppOptions->isEmpty())
                        <p class="text-sm text-zinc-500">Noch keine Anbieter angelegt – zuerst unter System › Taxi Apps anlegen.</p>
                    @else
                        <p class="text-sm text-zinc-500">Anklicken, was im Land verbreitet ist – mehrere möglich.</p>
                        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr));">
                            @foreach ($this->taxiAppOptions as $app)
                                <label
                                    wire:key="taxi-app-{{ $app->id }}"
                                    class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 bg-white p-3 transition select-none hover:border-zinc-300 has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)]/10 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[var(--color-accent)]/40 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600"
                                >
                                    <input type="checkbox" wire:model.live="taxiAppIds" value="{{ $app->id }}" class="sr-only" />
                                    <x-adminv2.taxi-app-logo :taxi-app="$app" class="size-11" />
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-center gap-2 text-sm font-medium text-zinc-900 dark:text-white">
                                            <span class="truncate">{{ $app->name }}</span>
                                            @unless ($app->is_active) <flux:badge size="sm" color="zinc" inset="top bottom">Inaktiv</flux:badge> @endunless
                                        </span>
                                        @if ($app->website_url)
                                            <span class="block truncate text-xs text-zinc-500">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($app->website_url, '/')) }}</span>
                                        @endif
                                    </span>
                                    <flux:icon.check-circle variant="mini" class="hidden shrink-0 text-[var(--color-accent)] has-[:checked]:block" />
                                </label>
                            @endforeach
                        </div>
                        <flux:error name="taxiAppIds" />
                        <flux:error name="taxiAppIds.*" />
                    @endif

                    <x-adminv2.ai-field-hint key="taxi_apps" :review="$aiReview" :applyable="false" />
                </div>
            </x-adminv2.card>

            {{-- Mobilfunkanbieter: Suche mit Autovervollstaendigung, Auswahl als Kacheln --}}
            <x-adminv2.card
                heading="Mobilfunkanbieter"
                description="Welche Mobilfunkanbieter im Land verbreitet sind. Die Anbieter werden unter System › Mobilfunkanbieter gepflegt."
                collapsible
                collapse-key="country-mobile-operators"
            >
                <x-slot:actions>
                    <x-adminv2.ai-check-button section="mobile_operators" />
                    <flux:button size="sm" variant="ghost" icon="cog-6-tooth" :href="route('adminv2.system.mobile-operators.index')">Anbieter verwalten</flux:button>
                </x-slot:actions>
                @php
                    $selectedOperators = $this->selectedMobileOperators;
                    $operatorMatches = $this->mobileOperatorMatches;
                @endphp
                <div class="flex flex-col gap-5">
                    {{-- Ausgewaehlt --}}
                    <div>
                        <p class="text-sm font-medium text-zinc-800 dark:text-white">Zugeordnet{{ $selectedOperators->isNotEmpty() ? ' ('.$selectedOperators->count().')' : '' }}</p>
                        @if ($selectedOperators->isEmpty())
                            <p class="mt-1 text-sm text-zinc-500">Noch kein Anbieter zugeordnet – unten suchen und anklicken.</p>
                        @else
                            <ul class="mt-2 grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr));">
                                @foreach ($selectedOperators as $operator)
                                    <li wire:key="mobile-operator-selected-{{ $operator->id }}" class="flex items-start gap-3 rounded-xl border border-[var(--color-accent)]/40 bg-[var(--color-accent)]/5 p-3">
                                        <x-adminv2.provider-logo :url="$operator->logo_url" :name="$operator->name" class="size-11" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-zinc-900 dark:text-white" title="{{ $operator->name }}">{{ $operator->name }}</span>
                                            @if ($operator->website_url)
                                                <a href="{{ $operator->website_url }}" target="_blank" rel="noopener" class="block truncate text-xs text-zinc-600 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-400">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($operator->website_url, '/')) }}</a>
                                            @endif
                                            @if ($operator->offers_esim || ! $operator->is_active)
                                                <span class="mt-1 flex flex-wrap gap-1">
                                                    @if ($operator->offers_esim) <flux:badge size="sm" color="sky" inset="top bottom">eSIM</flux:badge> @endif
                                                    @unless ($operator->is_active) <flux:badge size="sm" color="zinc" inset="top bottom">Inaktiv</flux:badge> @endunless
                                                </span>
                                            @endif
                                        </span>
                                        <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="removeMobileOperator({{ $operator->id }})" aria-label="{{ $operator->name }} entfernen" />
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Suche und verfuegbare Anbieter --}}
                    @if ($this->mobileOperatorsTotal === 0)
                        <p class="text-sm text-zinc-500">Noch keine Anbieter angelegt – zuerst unter System › Mobilfunkanbieter anlegen.</p>
                    @else
                        <div class="flex flex-col gap-3 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                            <div class="sm:max-w-md">
                                <flux:input
                                    wire:model.live.debounce.300ms="mobileOperatorSearch"
                                    icon="magnifying-glass"
                                    placeholder="Anbieter suchen und anklicken …"
                                    aria-label="Mobilfunkanbieter suchen"
                                    list="mobile-operator-suggestions"
                                    autocomplete="off"
                                    clearable
                                />
                                <datalist id="mobile-operator-suggestions">
                                    @foreach ($operatorMatches as $operator)
                                        <option value="{{ $operator->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>

                            @if ($operatorMatches->isEmpty())
                                <p class="text-sm text-zinc-500">{{ trim($mobileOperatorSearch) !== '' ? 'Kein Anbieter passt zur Suche.' : 'Alle Anbieter sind bereits zugeordnet.' }}</p>
                            @else
                                <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr));">
                                    @foreach ($operatorMatches as $operator)
                                        <button
                                            type="button"
                                            wire:key="mobile-operator-match-{{ $operator->id }}"
                                            wire:click="addMobileOperator({{ $operator->id }})"
                                            class="flex items-center gap-2.5 rounded-xl border border-zinc-200 bg-white p-2 text-start transition hover:border-[var(--color-accent)] hover:bg-[var(--color-accent)]/5 dark:border-zinc-700 dark:bg-zinc-900"
                                        >
                                            <x-adminv2.provider-logo :url="$operator->logo_url" :name="$operator->name" class="size-9" />
                                            <span class="min-w-0 flex-1 truncate text-sm text-zinc-900 dark:text-white">{{ $operator->name }}</span>
                                            <flux:icon.plus variant="micro" class="shrink-0 text-zinc-400" />
                                        </button>
                                    @endforeach
                                </div>
                                @if ($this->mobileOperatorsTotal - $selectedOperators->count() > $operatorMatches->count())
                                    <p class="text-xs text-zinc-500">Es werden die ersten {{ $operatorMatches->count() }} Treffer gezeigt – zum Eingrenzen suchen.</p>
                                @endif
                            @endif
                        </div>
                    @endif

                    <x-adminv2.ai-field-hint key="mobile_operators" :review="$aiReview" :applyable="false" />
                </div>
            </x-adminv2.card>

            {{-- Feiertage: je Jahr mit Datum, Name und Kommentar je Sprache --}}
            <x-adminv2.card
                heading="Feiertage"
                description="Gesetzliche Feiertage je Jahr, landesweit oder nur in einer Region – bewegliche Feiertage stehen mit ihrem Datum. Änderungen werden je Zeile gespeichert."
                collapsible
                collapse-key="country-holidays"
            >
                <x-slot:actions>
                    @if ($record && $this->holidays->isNotEmpty())
                        <flux:modal.trigger name="translate-holidays">
                            <flux:button size="sm" variant="ghost" icon="language">Übersetzen</flux:button>
                        </flux:modal.trigger>
                    @endif
                    <x-adminv2.ai-check-button section="holidays" />
                </x-slot:actions>
                @if ($record)
                    @php
                        $holidays = $this->holidays;
                        $holidayInput = 'w-full rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-sm text-zinc-900 outline-none focus:border-[var(--color-accent)] dark:border-zinc-700 dark:bg-zinc-900 dark:text-white';
                    @endphp
                    <div x-data="{ locale: @js($sourceLocale) }" class="flex flex-col gap-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Jahr</span>
                                <div class="w-28">
                                    <flux:select wire:model.live="holidayYear" size="sm" aria-label="Jahr">
                                        @foreach ($this->holidayYears as $year)
                                            <flux:select.option value="{{ $year }}">{{ $year }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                @php $regional = $holidays->filter(fn ($holiday) => $holiday->regions->isNotEmpty())->count(); @endphp
                                <span class="text-sm text-zinc-500 tabular-nums">{{ $holidays->count() }} {{ $holidays->count() === 1 ? 'Feiertag' : 'Feiertage' }}@if ($regional > 0), davon {{ $regional }} regional @endif</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Sprache</span>
                                <div class="inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                                    @foreach ($noteLocales as $locale)
                                        <button type="button" x-on:click="locale = @js($locale)" class="rounded-md px-3 py-1 text-sm font-medium transition" :class="locale === @js($locale) ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'">
                                            {{ CustomEvent::localeLabel($locale) }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <ul class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($holidays as $holiday)
                                <li wire:key="holiday-{{ $holiday->id }}" class="flex flex-col gap-2 py-3">
                                    <div class="grid items-start gap-2 sm:grid-cols-[9rem_minmax(0,1fr)_auto]">
                                        <div>
                                            <input type="date" wire:model="holidayRows.{{ $holiday->id }}.date" class="{{ $holidayInput }} tabular-nums" aria-label="Datum" />
                                            <span class="mt-1 block text-xs text-zinc-500">{{ CountryTravelInfo::weekday($holiday->date) }}, {{ $holiday->date->format('d.m.Y') }}</span>
                                            <flux:error name="holidayRows.{{ $holiday->id }}.date" />
                                        </div>
                                        <div>
                                            @foreach ($noteLocales as $locale)
                                                <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif>
                                                    <input type="text" wire:model="holidayRows.{{ $holiday->id }}.name.{{ $locale }}" maxlength="255" class="{{ $holidayInput }} font-medium" placeholder="Name ({{ strtoupper($locale) }})" aria-label="Name ({{ strtoupper($locale) }})" />
                                                    <flux:error name="holidayRows.{{ $holiday->id }}.name.{{ $locale }}" />
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex items-center gap-1 whitespace-nowrap">
                                            <flux:button size="xs" variant="ghost" icon="check" wire:click="saveHoliday({{ $holiday->id }})" aria-label="Feiertag speichern" />
                                            <flux:button size="xs" variant="ghost" icon="trash" class="!text-red-600" wire:click="deleteHoliday({{ $holiday->id }})" wire:confirm="Feiertag „{{ $holiday->getName($sourceLocale) }}“ löschen?" aria-label="Feiertag löschen" />
                                        </div>
                                    </div>
                                    @foreach ($noteLocales as $locale)
                                        <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif>
                                            <input type="text" wire:model="holidayRows.{{ $holiday->id }}.comment.{{ $locale }}" maxlength="2000" class="{{ $holidayInput }} text-zinc-600" placeholder="Kommentar ({{ strtoupper($locale) }}) – z. B. Geschäfte geschlossen, Brückentag" aria-label="Kommentar ({{ strtoupper($locale) }})" />
                                        </div>
                                    @endforeach
                                    @php $assigned = (array) ($holidayRows[$holiday->id]['region_ids'] ?? []); @endphp
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if ($assigned === [])
                                            <flux:badge size="sm" color="green" inset="top bottom">Landesweit</flux:badge>
                                        @else
                                            <span class="text-xs text-zinc-500">Gilt in:</span>
                                            @foreach ($assigned as $regionId)
                                                @php $regionLabel = collect($this->holidayRegionOptions)->firstWhere('value', (int) $regionId)['label'] ?? $regionId; @endphp
                                                <span wire:key="holiday-{ $holiday->id }-region-{ $regionId }" class="inline-flex items-center gap-1 rounded-full bg-zinc-100 py-0.5 ps-2.5 pe-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                    {{ $regionLabel }}
                                                    <button type="button" wire:click="removeHolidayRegion('{ $holiday->id }', { $regionId })" class="rounded-full p-0.5 text-zinc-400 hover:text-red-600" aria-label="{{ $regionLabel }} entfernen"><flux:icon.x-mark variant="micro" /></button>
                                                </span>
                                            @endforeach
                                        @endif
                                        @if ($this->holidayRegionOptions !== [])
                                            <select wire:model="holidayRows.{ $holiday->id }.add_region" wire:change="addHolidayRegion('{ $holiday->id }')" class="rounded-full border border-dashed border-zinc-300 bg-transparent py-0.5 ps-2 pe-6 text-xs text-zinc-600 dark:border-zinc-600 dark:text-zinc-400" aria-label="Region hinzufügen">
                                                <option value="">+ Region</option>
                                                @foreach ($this->holidayRegionOptions as $option)
                                                    @if (! in_array((string) $option['value'], $assigned, true))
                                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        @endif
                                        <flux:error name="holidayRows.{ $holiday->id }.region_ids.*" />
                                    </div>
                                </li>
                            @endforeach

                            {{-- Neue Zeile --}}
                            <li wire:key="holiday-new" class="-mx-5 flex flex-col gap-2 bg-zinc-50 px-5 py-3 dark:bg-zinc-900/40">
                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Neuer Feiertag</p>
                                <div class="grid items-start gap-2 sm:grid-cols-[9rem_minmax(0,1fr)_auto]">
                                    <div>
                                        <input type="date" wire:model="holidayRows.new.date" class="{{ $holidayInput }} tabular-nums" aria-label="Datum des neuen Feiertags" />
                                        <flux:error name="holidayRows.new.date" />
                                    </div>
                                    <div>
                                        @foreach ($noteLocales as $locale)
                                            <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif>
                                                <input type="text" wire:model="holidayRows.new.name.{{ $locale }}" maxlength="255" class="{{ $holidayInput }}" placeholder="Name ({{ strtoupper($locale) }})" aria-label="Name des neuen Feiertags ({{ strtoupper($locale) }})" />
                                                <flux:error name="holidayRows.new.name.{{ $locale }}" />
                                            </div>
                                        @endforeach
                                    </div>
                                    <flux:button size="sm" icon="plus" wire:click="addHoliday">Anlegen</flux:button>
                                </div>
                                @foreach ($noteLocales as $locale)
                                    <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif>
                                        <input type="text" wire:model="holidayRows.new.comment.{{ $locale }}" maxlength="2000" class="{{ $holidayInput }}" placeholder="Kommentar ({{ strtoupper($locale) }})" aria-label="Kommentar des neuen Feiertags ({{ strtoupper($locale) }})" />
                                    </div>
                                @endforeach
                                    @php $assigned = (array) ($holidayRows['new']['region_ids'] ?? []); @endphp
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if ($assigned === [])
                                            <flux:badge size="sm" color="green" inset="top bottom">Landesweit</flux:badge>
                                        @else
                                            <span class="text-xs text-zinc-500">Gilt in:</span>
                                            @foreach ($assigned as $regionId)
                                                @php $regionLabel = collect($this->holidayRegionOptions)->firstWhere('value', (int) $regionId)['label'] ?? $regionId; @endphp
                                                <span wire:key="holiday-{ 'new' }-region-{ $regionId }" class="inline-flex items-center gap-1 rounded-full bg-zinc-100 py-0.5 ps-2.5 pe-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                    {{ $regionLabel }}
                                                    <button type="button" wire:click="removeHolidayRegion('{ 'new' }', { $regionId })" class="rounded-full p-0.5 text-zinc-400 hover:text-red-600" aria-label="{{ $regionLabel }} entfernen"><flux:icon.x-mark variant="micro" /></button>
                                                </span>
                                            @endforeach
                                        @endif
                                        @if ($this->holidayRegionOptions !== [])
                                            <select wire:model="holidayRows.{ 'new' }.add_region" wire:change="addHolidayRegion('{ 'new' }')" class="rounded-full border border-dashed border-zinc-300 bg-transparent py-0.5 ps-2 pe-6 text-xs text-zinc-600 dark:border-zinc-600 dark:text-zinc-400" aria-label="Region hinzufügen">
                                                <option value="">+ Region</option>
                                                @foreach ($this->holidayRegionOptions as $option)
                                                    @if (! in_array((string) $option['value'], $assigned, true))
                                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        @endif
                                        <flux:error name="holidayRows.{ 'new' }.region_ids.*" />
                                    </div>
                            </li>
                        </ul>

                        <x-adminv2.ai-field-hint key="holidays" :review="$aiReview" :applyable="false" />
                    </div>
                @else
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Feiertage lassen sich erfassen, sobald das Land gespeichert ist.</p>
                @endif
            </x-adminv2.card>

            {{-- Bilder: Flagge, Titelbild und Galerie --}}
            <x-adminv2.card
                heading="Bilder"
                description="Flagge, Titelbild und Galerie für Apps und Feeds. Bilder werden sofort abgelegt; Texte je Bild mit „Angaben speichern“."
                collapsible
                collapse-key="country-images"
            >
                <x-slot:actions>
                    @if ($record && $this->images->isNotEmpty())
                        <flux:modal.trigger name="translate-images">
                            <flux:button size="sm" variant="ghost" icon="language">Übersetzen</flux:button>
                        </flux:modal.trigger>
                    @endif
                    <x-adminv2.ai-check-button section="images" />
                </x-slot:actions>
                @if ($record)
                    <div x-data="{ locale: @js($sourceLocale) }" class="flex flex-col gap-6">
                        {{-- Flagge --}}
                        <div class="flex flex-wrap items-center gap-4">
                            <img src="{{ $record->flag_url }}" alt="Flagge {{ $record->getName('de') }}" class="h-10 w-auto rounded border border-zinc-200 dark:border-zinc-700" loading="lazy" />
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                <p class="font-medium text-zinc-900 dark:text-white">Flagge {{ $record->flag_emoji }}</p>
                                <p>Kommt über den ISO-Code von flagcdn.com, wird nicht hochgeladen.</p>
                            </div>
                            <x-adminv2.ai-field-hint key="flag" :review="$aiReview" :applyable="false" class="!mt-0 w-full" />
                        </div>

                        {{-- Hochladen --}}
                        <flux:field>
                            <flux:label>Bilder hochladen</flux:label>
                            <flux:description>JPEG, PNG, WebP oder SVG, bis 10 MB je Bild. Das erste Bild eines Landes wird sein Titelbild.</flux:description>
                            <flux:input type="file" wire:model="newImages" multiple accept="image/png,image/jpeg,image/webp,image/svg+xml" />
                            <p class="text-xs text-zinc-500" wire:loading wire:target="newImages">Dateien werden hochgeladen …</p>
                            <flux:error name="newImages" />
                            <flux:error name="newImages.*" />
                        </flux:field>

                        @if ($this->images->isEmpty())
                            <div class="rounded-xl border border-dashed border-zinc-300 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-400">
                                Noch kein Bild hochgeladen.
                                @if ($record->hero_image_url)
                                    Apps und Feeds nutzen so lange das bisherige Standardbild:
                                    <img src="{{ $record->hero_image_url }}" alt="" class="mt-3 h-28 w-auto rounded-lg object-cover" loading="lazy" />
                                @endif
                            </div>
                        @else
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Sprache der Bildtexte</span>
                                <div class="inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                                    @foreach ($noteLocales as $locale)
                                        <button type="button" x-on:click="locale = @js($locale)" class="rounded-md px-3 py-1 text-sm font-medium transition" :class="locale === @js($locale) ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'">
                                            {{ CustomEvent::localeLabel($locale) }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex flex-col gap-4">
                                @foreach ($this->images as $image)
                                    <div wire:key="image-{{ $image->id }}" @class([
                                        'grid gap-4 rounded-2xl border p-4 md:grid-cols-[14rem_minmax(0,1fr)]',
                                        'border-[var(--color-accent)]/40 bg-[var(--color-accent)]/5' => $image->isHero(),
                                        'border-zinc-200 dark:border-zinc-800' => ! $image->isHero(),
                                    ])>
                                        <div class="flex flex-col gap-2">
                                            <a href="{{ $image->url() }}" target="_blank" class="block overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                                <img src="{{ $image->thumbUrl() }}" alt="{{ $image->alt($sourceLocale) }}" class="aspect-video w-full object-cover" loading="lazy" />
                                            </a>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @if ($image->isHero())
                                                    <flux:badge size="sm" color="green" inset="top bottom">Titelbild</flux:badge>
                                                @else
                                                    <flux:badge size="sm" color="zinc" inset="top bottom">Galerie</flux:badge>
                                                @endif
                                                @unless ($image->is_published)
                                                    <flux:badge size="sm" color="amber" inset="top bottom">Nicht freigegeben</flux:badge>
                                                @endunless
                                            </div>
                                            <p class="text-xs text-zinc-500 tabular-nums">
                                                {{ $image->width && $image->height ? $image->width.' × '.$image->height.' px · ' : '' }}{{ $image->size ? number_format($image->size / 1024, 0, ',', '.').' KB' : '' }}
                                                @if ($image->original_name)<span class="block truncate" title="{{ $image->original_name }}">{{ $image->original_name }}</span>@endif
                                            </p>
                                            <div class="flex flex-wrap gap-1">
                                                @unless ($image->isHero())
                                                    <flux:button size="xs" icon="star" wire:click="setHeroImage({{ $image->id }})">Als Titelbild</flux:button>
                                                    <flux:button size="xs" variant="ghost" icon="chevron-up" wire:click="moveImage({{ $image->id }}, 'up')" aria-label="Nach oben" />
                                                    <flux:button size="xs" variant="ghost" icon="chevron-down" wire:click="moveImage({{ $image->id }}, 'down')" aria-label="Nach unten" />
                                                @endunless
                                                <flux:button size="xs" variant="ghost" icon="trash" class="!text-red-600" wire:click="deleteImage({{ $image->id }})" wire:confirm="Dieses Bild endgültig löschen?">Löschen</flux:button>
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-3">
                                            @foreach ($noteLocales as $locale)
                                                <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif class="grid gap-3 sm:grid-cols-2" wire:key="image-{{ $image->id }}-{{ $locale }}">
                                                    <flux:field>
                                                        <flux:label>Alt-Text ({{ strtoupper($locale) }})</flux:label>
                                                        <flux:input wire:model="imageMeta.{{ $image->id }}.alt.{{ $locale }}" maxlength="255" placeholder="Was ist zu sehen?" />
                                                        <flux:error name="imageMeta.{{ $image->id }}.alt.{{ $locale }}" />
                                                    </flux:field>
                                                    <flux:field>
                                                        <flux:label>Bildunterschrift ({{ strtoupper($locale) }})</flux:label>
                                                        <flux:input wire:model="imageMeta.{{ $image->id }}.caption.{{ $locale }}" maxlength="1000" />
                                                        <flux:error name="imageMeta.{{ $image->id }}.caption.{{ $locale }}" />
                                                    </flux:field>
                                                </div>
                                            @endforeach
                                            <div class="grid gap-3 sm:grid-cols-3">
                                                <flux:field>
                                                    <flux:label>Urheber</flux:label>
                                                    <flux:input wire:model="imageMeta.{{ $image->id }}.credit" maxlength="255" placeholder="Name oder Agentur" />
                                                    <flux:error name="imageMeta.{{ $image->id }}.credit" />
                                                </flux:field>
                                                <flux:field>
                                                    <flux:label>Lizenz</flux:label>
                                                    <flux:input wire:model="imageMeta.{{ $image->id }}.license" maxlength="100" placeholder="z. B. CC BY 4.0, eigene Aufnahme" />
                                                    <flux:error name="imageMeta.{{ $image->id }}.license" />
                                                </flux:field>
                                                <flux:field>
                                                    <flux:label>Quelle (URL)</flux:label>
                                                    <flux:input wire:model="imageMeta.{{ $image->id }}.source_url" placeholder="https://…" />
                                                    <flux:error name="imageMeta.{{ $image->id }}.source_url" />
                                                </flux:field>
                                            </div>
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <flux:switch wire:model="imageMeta.{{ $image->id }}.is_published" label="Freigegeben für Apps und Feeds" align="left" />
                                                <flux:button size="sm" icon="check" wire:click="saveImage({{ $image->id }})">Angaben speichern</flux:button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <x-adminv2.ai-field-hint key="images" :review="$aiReview" :applyable="false" />
                    </div>
                @else
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Bilder lassen sich hochladen, sobald das Land gespeichert ist.</p>
                @endif
            </x-adminv2.card>

            <x-adminv2.master-data.coordinates :lat="$lat" :lng="$lng" :review="$aiReview" description="Mittelpunkt des Landes – dorthin setzt die Karte landesweite Ereignisse." collapsible collapse-key="country-coordinates">
                @if ($record)
                    <x-slot:map>
                        <x-adminv2.master-data.boundary-map :url="route('adminv2.master-data.boundaries.country', $record->id)" :lat="$lat" :lng="$lng" />
                    </x-slot:map>
                @endif
            </x-adminv2.master-data.coordinates>

            {{-- Risikoprofil --}}
            <x-adminv2.card
                heading="Risikoprofil"
                :description="$overallRisk ? 'Gesamt-Risiko: '.$overallRisk.' – '.Country::getRiskLevelLabel($overallRisk).' (höchste Stufe aus Sicherheit, Gesundheit und Naturgefahren)' : 'Noch nicht bewertet.'"
                collapsible
                collapsed
                collapse-key="country-risk-profile"
            >
                <x-slot:actions>
                    <flux:modal.trigger name="translate-risk-notes">
                        <flux:button size="sm" variant="ghost" icon="language">Übersetzen</flux:button>
                    </flux:modal.trigger>
                    <x-adminv2.ai-check-button section="risk_profile" />
                </x-slot:actions>
                <div x-data="{ tab: 'security', locale: @js($sourceLocale) }" class="flex flex-col gap-5">
                    <div class="flex flex-wrap gap-1.5" role="tablist">
                        @foreach ($categories as $key => $category)
                            <button
                                type="button"
                                role="tab"
                                x-on:click="tab = '{{ $key }}'"
                                :aria-selected="tab === '{{ $key }}'"
                                :class="tab === '{{ $key }}'
                                    ? 'border-[var(--color-accent)] bg-[var(--color-accent)] text-[var(--color-accent-foreground)]'
                                    : 'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm transition"
                            >
                                <flux:icon :name="$category['icon']" variant="micro" />
                                {{ $category['label'] }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Sprache der Notizen – Ausgangssprache ist die erste. --}}
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Sprache der Notizen</span>
                        <div class="inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                            @foreach ($noteLocales as $locale)
                                <button
                                    type="button"
                                    x-on:click="locale = @js($locale)"
                                    class="rounded-md px-3 py-1 text-sm font-medium transition"
                                    :class="locale === @js($locale)
                                        ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-950 dark:text-white'
                                        : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'"
                                >
                                    {{ CustomEvent::localeLabel($locale) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @foreach ($categories as $key => $category)
                        {{-- Je Punkt eine Zeile, darunter die Notiz. --}}
                        <div x-show="tab === '{{ $key }}'" @if (! $loop->first) x-cloak @endif role="tabpanel" class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($category['fields'] as $field => $meta)
                                @php
                                    $model = 'riskProfile.'.$key.'.'.$field;
                                    $hintKey = CountryRiskProfile::placeholderKey($key, $field);
                                @endphp

                                <div
                                    wire:key="risk-{{ $key }}-{{ $field }}"
                                    class="flex flex-col gap-2 py-4 first:pt-0 last:pb-0"
                                    {{-- Die Malaria-Beschreibung gehoert zum Schalter "Malaria-Risiko". --}}
                                    @if ($key === 'health' && $field === 'malaria_description') x-show="$wire.riskProfile.health.malaria_risk" @endif
                                >
                                    @switch($meta['type'])
                                        @case('level')
                                            <flux:field>
                                                <flux:label>{{ $meta['label'] }}</flux:label>
                                                <flux:select wire:model="{{ $model }}" class="sm:max-w-xs">
                                                    <flux:select.option value="">Nicht bewertet</flux:select.option>
                                                    @foreach (CountryRiskProfile::LEVELS as $level => $label)
                                                        <flux:select.option value="{{ $level }}">{{ $level }} – {{ $label }}</flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                            </flux:field>
                                            @break

                                        @case('bool')
                                            <flux:switch wire:model="{{ $model }}" :label="$meta['label']" align="left" />
                                            @break

                                        @case('textarea')
                                            <flux:field>
                                                <flux:label>{{ $meta['label'] }}</flux:label>
                                                <flux:textarea wire:model="{{ $model }}" rows="{{ $field === 'description' ? 3 : 2 }}" />
                                            </flux:field>
                                            @break

                                        @case('tags')
                                            <flux:field>
                                                <flux:label>{{ $meta['label'] }}</flux:label>
                                                <flux:description>Mehrere mit Komma trennen.</flux:description>
                                                <flux:input wire:model="{{ $model }}" placeholder="{{ $meta['placeholder'] ?? '' }}" />
                                            </flux:field>
                                            @break

                                        @case('number')
                                            <flux:field>
                                                <flux:label>{{ $meta['label'] }}</flux:label>
                                                <flux:input wire:model="{{ $model }}" type="number" min="0" step="1" class="sm:max-w-xs" />
                                                <flux:error name="{{ $model }}" />
                                            </flux:field>
                                            @break

                                        @default
                                            <flux:field>
                                                <flux:label>{{ $meta['label'] }}</flux:label>
                                                <flux:input wire:model="{{ $model }}" />
                                            </flux:field>
                                    @endswitch

                                    <x-adminv2.ai-field-hint :key="$hintKey" :review="$aiReview" :noteable="CountryRiskProfile::hasNote($meta)" class="!mt-0" />

                                    @if (CountryRiskProfile::hasNote($meta))
                                        @foreach ($noteLocales as $locale)
                                            <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif wire:key="risk-note-{{ $key }}-{{ $field }}-{{ $locale }}">
                                                <flux:textarea
                                                    wire:model="riskProfile.{{ $key }}.notes.{{ $field }}.{{ $locale }}"
                                                    rows="2"
                                                    aria-label="Notiz zu {{ $meta['label'] }} ({{ strtoupper($locale) }})"
                                                    placeholder="Notiz{{ $locale === $sourceLocale ? '' : ' ('.strtoupper($locale).')' }} – Fließtext zu diesem Punkt"
                                                />
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </x-adminv2.card>

            {{-- Laendergrenze: reine Anzeige --}}
            @if ($record)
                <x-adminv2.card heading="Ländergrenze" description="Nur Anzeige – gepflegt über den Import aus Natural Earth." collapsible collapsed>
                    @if ($boundary)
                        <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[14rem_minmax(0,1fr)]">
                            @foreach ($boundary as $label => $value)
                                <dt class="text-zinc-500">{{ $label }}</dt>
                                <dd @class(['text-zinc-900 dark:text-white', 'font-medium text-red-700 dark:text-red-400' => $value === 'ungültig'])>{{ $value }}</dd>
                            @endforeach
                        </dl>
                    @else
                        <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Keine Grenzdaten hinterlegt. Der Import läuft über
                            <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">php artisan countries:import-boundaries</code>.
                            Solange keine Grenze vorliegt, wird das Land bei Koordinaten-Abfragen über den nächstgelegenen Flughafen bzw. die nächste Stadt genähert.
                        </p>
                    @endif
                </x-adminv2.card>
            @endif
        </div>

        <div class="flex flex-col gap-6">
            @if ($record)
                <x-adminv2.master-data.record-meta :record="$record">
                    @if ($related['events'] > 0)
                        <a href="{{ route('adminv2.events.index', ['country' => [$record->id]]) }}" class="mt-3 inline-flex items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">
                            {{ number_format($related['events'], 0, ',', '.') }} {{ $related['events'] === 1 ? 'Ereignis' : 'Ereignisse' }} zu diesem Land
                        </a>
                    @endif
                </x-adminv2.master-data.record-meta>

                <x-adminv2.master-data.related-list
                    heading="Regionen"
                    :count="$related['regions']['count']"
                    :shown="$related['regions']['to']"
                    :found="$related['regions']['found']"
                    :all-url="route('adminv2.master-data.regions.index', ['country' => [$record->id]])"
                    :create-url="route('adminv2.master-data.regions.create', ['country' => $record->id])"
                    create-label="Neue Region"
                    empty-text="Zu diesem Land ist noch keine Region angelegt."
                >
                    <x-slot:toolbar><x-adminv2.master-data.related-toolbar list="regions" :data="$related['regions']" placeholder="Region oder Code suchen …" :limits="\App\Livewire\AdminV2\MasterData\Countries\Editor::RELATED_LIMITS" /></x-slot:toolbar>
                    <x-slot:footer><x-adminv2.master-data.related-pager list="regions" :data="$related['regions']" /></x-slot:footer>
                    @foreach ($related['regions']['items'] as $region)
                        <li wire:key="related-region-{{ $region->id }}" class="flex items-center justify-between gap-2 py-1">
                            <x-adminv2.master-data.major-toggle :active="$region->is_major" :label="$region->getName('de')" noun="Major Region" wire:click="toggleMajorRegion({{ $region->id }})" />
                            <a href="{{ route('adminv2.master-data.regions.edit', $region->id) }}" @class(['min-w-0 flex-1 truncate text-zinc-900 hover:underline dark:text-white', 'font-medium' => $region->is_major])>{{ $region->getName('de') }}</a>
                            <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $region->code }}</span>
                        </li>
                    @endforeach
                </x-adminv2.master-data.related-list>

                <x-adminv2.master-data.related-list
                    heading="Städte"
                    :count="$related['cities']['count']"
                    :shown="$related['cities']['to']"
                    :found="$related['cities']['found']"
                    :all-url="route('adminv2.master-data.cities.index', ['country' => [$record->id]])"
                    :create-url="route('adminv2.master-data.cities.create', ['country' => $record->id])"
                    create-label="Neue Stadt"
                    empty-text="Zu diesem Land ist noch keine Stadt angelegt."
                >
                    <x-slot:toolbar><x-adminv2.master-data.related-toolbar list="cities" :data="$related['cities']" placeholder="Stadt suchen …" :limits="\App\Livewire\AdminV2\MasterData\Countries\Editor::RELATED_LIMITS" /></x-slot:toolbar>
                    <x-slot:footer><x-adminv2.master-data.related-pager list="cities" :data="$related['cities']" /></x-slot:footer>
                    @foreach ($related['cities']['items'] as $city)
                        <li wire:key="related-city-{{ $city->id }}" class="flex items-center justify-between gap-2 py-1">
                            <x-adminv2.master-data.major-toggle :active="$city->is_major" :label="$city->getName('de')" noun="Major City" wire:click="toggleMajorCity({{ $city->id }})" />
                            <a href="{{ route('adminv2.master-data.cities.edit', $city->id) }}" @class(['min-w-0 flex-1 truncate text-zinc-900 hover:underline dark:text-white', 'font-medium' => $city->is_major])>{{ $city->getName('de') }}</a>
                            @if ($city->is_capital)
                                <flux:badge size="sm" color="blue" inset="top bottom">Hauptstadt</flux:badge>
                            @elseif ($city->population)
                                <span class="shrink-0 text-xs tabular-nums text-zinc-400">{{ number_format($city->population, 0, ',', '.') }}</span>
                            @endif
                        </li>
                    @endforeach
                </x-adminv2.master-data.related-list>

                <x-adminv2.master-data.related-list
                    heading="Flughäfen"
                    :count="$related['airports']['count']"
                    :shown="$related['airports']['to']"
                    :found="$related['airports']['found']"
                    :all-url="route('adminv2.master-data.airports.index', ['country' => [$record->id]])"
                    :create-url="route('adminv2.master-data.airports.create', ['country' => $record->id])"
                    create-label="Neuer Flughafen"
                    empty-text="Zu diesem Land ist kein Flughafen angelegt."
                >
                    <x-slot:toolbar><x-adminv2.master-data.related-toolbar list="airports" :data="$related['airports']" placeholder="Flughafen, IATA- oder ICAO-Code suchen …" :limits="\App\Livewire\AdminV2\MasterData\Countries\Editor::RELATED_LIMITS" /></x-slot:toolbar>
                    <x-slot:footer><x-adminv2.master-data.related-pager list="airports" :data="$related['airports']" /></x-slot:footer>
                    @foreach ($related['airports']['items'] as $airport)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <a href="{{ route('adminv2.master-data.airports.edit', $airport->id) }}" class="truncate text-zinc-900 hover:underline dark:text-white">{{ $airport->name }}</a>
                            <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $airport->iata_code ?: $airport->icao_code }}</span>
                        </li>
                    @endforeach
                </x-adminv2.master-data.related-list>

            @else
                <x-adminv2.card heading="Nach dem Speichern">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Sobald das Land angelegt ist, lassen sich hier Regionen und Städte dazu anlegen und der KI-Assistent nutzen.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />

    <x-adminv2.master-data.translate-modal section="details" what="Einleitung, „Bekannt für“ und die Bezeichnung des Nationaltags" />
    <x-adminv2.master-data.translate-modal section="tipping" what="die Trinkgeld-Beschreibungen" />
    <x-adminv2.master-data.translate-modal section="power" what="die Bemerkung zum Strom" />
    <x-adminv2.master-data.translate-modal section="images" what="Alt-Texte und Bildunterschriften aller Bilder" saved />
    <x-adminv2.master-data.translate-modal section="holidays" what="Namen und Kommentare der Feiertage des gewählten Jahres" saved />

    <flux:modal name="translate-risk-notes" class="md:w-[32rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Notizen per DeepL übersetzen</flux:heading>
                <flux:text class="mt-2">
                    Übersetzt die Notizen des Risikoprofils aus {{ CustomEvent::localeLabel($sourceLocale, false) }} in die übrigen Sprachen. Gespeichert wird erst mit „Speichern“.
                </flux:text>
            </div>
            <flux:checkbox wire:model="overwriteNoteTranslations" label="Bereits ausgefüllte Übersetzungen überschreiben" description="Ohne Haken werden nur leere Notizen gefüllt." />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="translateRiskNotes" icon="language">Übersetzen</flux:button>
            </div>
        </div>
    </flux:modal>

    <x-adminv2.ai-check-modal area="countries" :section="$aiSection" :checks="$this->aiChecks" :data="$this->aiData" :check-id="$aiCheckId" :prompt-draft="$aiPromptDraft" :result="$aiResult" :error="$aiError" :models="$this->aiModelOptions" :save-as-check="$aiSaveAsCheck" :review="$aiReview" :note-keys="CountryRiskProfile::noteKeys()" :title="$record?->getName('de')" />
</form>
