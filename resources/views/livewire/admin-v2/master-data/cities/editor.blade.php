@php
    $record = $this->record;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $regionOptions = $this->regionOptions->map(fn ($region) => ['value' => $region->id, 'label' => $region->getName('de'), 'code' => $region->code])->all();
    $country = $this->countryOptions->firstWhere('id', (int) $countryId);
    $otherCapitals = $this->otherCapitals;
    $airports = $this->airports;
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <x-adminv2.master-data.editor-header
        section="cities"
        :title="$record ? $record->getName('de') : 'Neue Stadt'"
        :subtitle="$record && $country ? 'Stadt in '.$country->getName('de') : null"
        :record="$record"
    />

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Stadt">
                <div class="flex flex-col gap-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:input wire:model="nameDe" label="Name (Deutsch)" maxlength="255" />
                        <flux:input wire:model="nameEn" label="Name (Englisch)" maxlength="255" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Land</flux:label>
                            <x-adminv2.search-select :options="$countryOptions" model="countryId" :selected="$countryId" placeholder="Land wählen …" search-placeholder="Land oder ISO-Code …" label="Land" live />
                            <flux:error name="countryId" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Region</flux:label>
                            <x-adminv2.search-select
                                :options="$regionOptions"
                                model="regionId"
                                :selected="$regionId"
                                :placeholder="$countryId === '' ? 'Zuerst ein Land wählen' : ($regionOptions ? 'Keine Region' : 'Für dieses Land gibt es keine Regionen')"
                                search-placeholder="Region oder Code …"
                                label="Region"
                                :disabled="$regionOptions === []"
                                live
                                clearable
                                wire:key="region-select-{{ $countryId }}"
                            />
                            <flux:error name="regionId" />
                        </flux:field>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Bevölkerung</flux:label>
                            <flux:input wire:model="population" inputmode="numeric" placeholder="z. B. 1500000" />
                            <flux:error name="population" />
                        </flux:field>
                    </div>

                    <div class="flex flex-col gap-3">
                        <flux:switch wire:model.live="isCapital" label="Hauptstadt des Landes" align="left" />
                        @if ($isCapital && $otherCapitals->isNotEmpty())
                            <p class="rounded-xl bg-amber-50 px-3 py-2.5 text-sm leading-relaxed text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                                {{ $country?->getName('de') ?? 'Das Land' }} hat bereits {{ $otherCapitals->count() === 1 ? 'eine Hauptstadt' : $otherCapitals->count().' Hauptstädte' }}:
                                @foreach ($otherCapitals as $capital)
                                    <a href="{{ route('adminv2.master-data.cities.edit', $capital->id) }}" target="_blank" class="underline underline-offset-2">{{ $capital->getName('de') }}</a>@if (! $loop->last), @endif
                                @endforeach.
                                Beide bleiben als Hauptstadt markiert, bis eine der Markierungen entfernt wird.
                            </p>
                        @endif
                        <flux:switch wire:model="isRegionalCapital" label="Hauptstadt der Region" align="left" />
                    </div>
                </div>
            </x-adminv2.card>

            <x-adminv2.master-data.coordinates :lat="$lat" :lng="$lng" description="Lage der Stadt – daran werden Ereignisse und Reisen in der Nähe erkannt." />
        </div>

        <div class="flex flex-col gap-6">
            @if ($record)
                <x-adminv2.master-data.record-meta :record="$record">
                    <div class="mt-3 flex flex-col gap-1">
                        @if ($record->country_id)
                            <a href="{{ route('adminv2.master-data.countries.edit', $record->country_id) }}" class="inline-flex w-fit items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">Zum Land {{ $country?->getName('de') }}</a>
                        @endif
                        @if ($record->region_id)
                            <a href="{{ route('adminv2.master-data.regions.edit', $record->region_id) }}" class="inline-flex w-fit items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">Zur Region</a>
                        @endif
                    </div>
                </x-adminv2.master-data.record-meta>

                <x-adminv2.master-data.related-list
                    heading="Flughäfen"
                    :count="$airports->count()"
                    :shown="$airports->count()"
                    :all-url="route('adminv2.master-data.airports.index', ['country' => [$record->country_id]])"
                    :create-url="route('adminv2.master-data.airports.create', ['city' => $record->id])"
                    create-label="Neuer Flughafen"
                    empty-text="Dieser Stadt ist kein Flughafen zugeordnet."
                >
                    @foreach ($airports as $airport)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <a href="{{ route('adminv2.master-data.airports.edit', $airport->id) }}" class="truncate text-zinc-900 hover:underline dark:text-white">{{ $airport->name }}</a>
                            <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $airport->iata_code ?: $airport->icao_code }}</span>
                        </li>
                    @endforeach
                </x-adminv2.master-data.related-list>

                <x-adminv2.master-data.ai-assistant :prompts="$this->aiPrompts" :prompt-id="$aiPromptId" :result="$aiResult" :error="$aiError" noun="Städte" />
            @else
                <x-adminv2.card heading="Hinweis">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Mit „Speichern &amp; weitere anlegen“ bleiben Land und Region für die nächste Stadt vorbelegt.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</form>
