@php
    use App\Models\Country;
    use App\Support\AdminV2\CountryRiskProfile;

    $record = $this->record;
    $related = $this->related;
    $boundary = $this->boundary;
    $overallRisk = $this->overallRisk;
    $categories = CountryRiskProfile::categories();
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <x-adminv2.master-data.editor-header
        section="countries"
        :title="$record ? $record->getName('de') : 'Neues Land'"
        :subtitle="$record ? trim($record->iso_code.' · '.$record->iso3_code, ' ·') : null"
        :record="$record"
    />

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            {{-- Grunddaten --}}
            <x-adminv2.card heading="Grunddaten">
                <div class="flex flex-col gap-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:input wire:model="nameDe" label="Name (Deutsch)" maxlength="255" />
                        <flux:input wire:model="nameEn" label="Name (Englisch)" maxlength="255" />
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
                            <flux:error name="isoCode" />
                        </flux:field>
                        <flux:field>
                            <flux:label>ISO-Code (3 Buchstaben)</flux:label>
                            <flux:input wire:model="iso3Code" maxlength="3" class="font-mono uppercase" placeholder="DEU" />
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
                            <flux:error name="continentId" />
                        </flux:field>
                    </div>

                    <div class="flex flex-wrap gap-x-8 gap-y-3">
                        <flux:switch wire:model="isEuMember" label="EU-Mitglied" align="left" />
                        <flux:switch wire:model="isSchengenMember" label="Schengen-Mitglied" align="left" />
                    </div>
                </div>
            </x-adminv2.card>

            {{-- Weitere Informationen --}}
            <x-adminv2.card heading="Weitere Informationen">
                <div class="grid gap-5 sm:grid-cols-3">
                    <flux:field>
                        <flux:label>Währungscode</flux:label>
                        <flux:input wire:model="currencyCode" maxlength="3" class="font-mono uppercase" placeholder="EUR" />
                        <flux:error name="currencyCode" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Währungsname</flux:label>
                        <flux:input wire:model="currencyName" maxlength="255" placeholder="Euro" />
                        <flux:error name="currencyName" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Währungssymbol</flux:label>
                        <flux:input wire:model="currencySymbol" maxlength="5" placeholder="€" />
                        <flux:error name="currencySymbol" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Telefonvorwahl</flux:label>
                        <flux:input wire:model="phonePrefix" maxlength="10" placeholder="+49" />
                        <flux:error name="phonePrefix" />
                    </flux:field>
                    <flux:field class="sm:col-span-2">
                        <flux:label>Zeitzone</flux:label>
                        <flux:input wire:model="timezone" maxlength="255" placeholder="Europe/Berlin" />
                        <flux:error name="timezone" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Bevölkerung</flux:label>
                        <flux:input wire:model="population" inputmode="numeric" placeholder="z. B. 83200000" />
                        <flux:error name="population" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Fläche (km²)</flux:label>
                        <flux:input wire:model="areaKm2" inputmode="decimal" placeholder="z. B. 357588" />
                        <flux:error name="areaKm2" />
                    </flux:field>
                </div>
            </x-adminv2.card>

            <x-adminv2.master-data.coordinates :lat="$lat" :lng="$lng" description="Mittelpunkt des Landes – dorthin setzt die Karte landesweite Ereignisse." />

            {{-- Risikoprofil --}}
            <x-adminv2.card
                heading="Risikoprofil"
                :description="$overallRisk ? 'Gesamt-Risiko: '.$overallRisk.' – '.Country::getRiskLevelLabel($overallRisk).' (höchste Stufe aus Sicherheit, Gesundheit und Naturgefahren)' : 'Noch nicht bewertet.'"
                collapsible
                collapsed
                collapse-key="country-risk-profile"
            >
                <div x-data="{ tab: 'security' }" class="flex flex-col gap-5">
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

                    @foreach ($categories as $key => $category)
                        <div x-show="tab === '{{ $key }}'" @if (! $loop->first) x-cloak @endif role="tabpanel" class="grid gap-5 sm:grid-cols-2">
                            @foreach ($category['fields'] as $field => $meta)
                                @php $model = 'riskProfile.'.$key.'.'.$field; @endphp

                                @switch($meta['type'])
                                    @case('level')
                                        <flux:field :class="($meta['wide'] ?? false) ? 'sm:col-span-2' : ''">
                                            <flux:label>{{ $meta['label'] }}</flux:label>
                                            <flux:select wire:model="{{ $model }}">
                                                <flux:select.option value="">Nicht bewertet</flux:select.option>
                                                @foreach (CountryRiskProfile::LEVELS as $level => $label)
                                                    <flux:select.option value="{{ $level }}">{{ $level }} – {{ $label }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </flux:field>
                                        @break

                                    @case('bool')
                                        <div class="flex items-center">
                                            <flux:switch wire:model="{{ $model }}" :label="$meta['label']" align="left" />
                                        </div>
                                        @break

                                    @case('textarea')
                                        <flux:field
                                            class="sm:col-span-2"
                                            {{-- Die Malaria-Beschreibung gehoert zum Schalter "Malaria-Risiko". --}}
                                            :x-show="$key === 'health' && $field === 'malaria_description' ? '$wire.riskProfile.health.malaria_risk' : null"
                                        >
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
                                            <flux:input wire:model="{{ $model }}" type="number" min="0" step="1" />
                                            <flux:error name="{{ $model }}" />
                                        </flux:field>
                                        @break

                                    @default
                                        <flux:field>
                                            <flux:label>{{ $meta['label'] }}</flux:label>
                                            <flux:input wire:model="{{ $model }}" />
                                        </flux:field>
                                @endswitch
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
                    :shown="$related['regions']['items']->count()"
                    :all-url="route('adminv2.master-data.regions.index', ['country' => [$record->id]])"
                    :create-url="route('adminv2.master-data.regions.create', ['country' => $record->id])"
                    create-label="Neue Region"
                    empty-text="Zu diesem Land ist noch keine Region angelegt."
                >
                    @foreach ($related['regions']['items'] as $region)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <a href="{{ route('adminv2.master-data.regions.edit', $region->id) }}" class="truncate text-zinc-900 hover:underline dark:text-white">{{ $region->getName('de') }}</a>
                            <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $region->code }}</span>
                        </li>
                    @endforeach
                </x-adminv2.master-data.related-list>

                <x-adminv2.master-data.related-list
                    heading="Städte"
                    :count="$related['cities']['count']"
                    :shown="$related['cities']['items']->count()"
                    :all-url="route('adminv2.master-data.cities.index', ['country' => [$record->id]])"
                    :create-url="route('adminv2.master-data.cities.create', ['country' => $record->id])"
                    create-label="Neue Stadt"
                    empty-text="Zu diesem Land ist noch keine Stadt angelegt."
                >
                    @foreach ($related['cities']['items'] as $city)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <a href="{{ route('adminv2.master-data.cities.edit', $city->id) }}" class="truncate text-zinc-900 hover:underline dark:text-white">{{ $city->getName('de') }}</a>
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
                    :shown="$related['airports']['items']->count()"
                    :all-url="route('adminv2.master-data.airports.index', ['country' => [$record->id]])"
                    :create-url="route('adminv2.master-data.airports.create', ['country' => $record->id])"
                    create-label="Neuer Flughafen"
                    empty-text="Zu diesem Land ist kein Flughafen angelegt."
                >
                    @foreach ($related['airports']['items'] as $airport)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <a href="{{ route('adminv2.master-data.airports.edit', $airport->id) }}" class="truncate text-zinc-900 hover:underline dark:text-white">{{ $airport->name }}</a>
                            <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $airport->iata_code ?: $airport->icao_code }}</span>
                        </li>
                    @endforeach
                </x-adminv2.master-data.related-list>

                <x-adminv2.master-data.ai-assistant :prompts="$this->aiPrompts" :prompt-id="$aiPromptId" :result="$aiResult" :error="$aiError" noun="Länder" />
            @else
                <x-adminv2.card heading="Nach dem Speichern">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Sobald das Land angelegt ist, lassen sich hier Regionen und Städte dazu anlegen und der KI-Assistent nutzen.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</form>
