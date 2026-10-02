@php
    $record = $this->record;
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
                            <flux:label>Code</flux:label>
                            <flux:input wire:model="code" maxlength="10" class="font-mono" placeholder="z. B. BY für Bayern" />
                            <flux:error name="code" />
                        </flux:field>
                    </div>

                    <flux:textarea wire:model="description" label="Beschreibung" rows="3" maxlength="1000" />

                    <flux:field>
                        <flux:label>Schlagwörter</flux:label>
                        <flux:description>Weitere Bezeichnungen, unter denen die Region gefunden wird – mit Komma getrennt.</flux:description>
                        <flux:input wire:model="keywords" placeholder="z. B. Bayern, Bavaria, Freistaat Bayern" maxlength="1000" />
                        <flux:error name="keywords" />
                    </flux:field>
                </div>
            </x-adminv2.card>

            <x-adminv2.master-data.coordinates :lat="$lat" :lng="$lng" description="Mittelpunkt der Region für die Darstellung auf der Karte." />
        </div>

        <div class="flex flex-col gap-6">
            @if ($record)
                <x-adminv2.master-data.record-meta :record="$record">
                    @if ($record->country_id)
                        <a href="{{ route('adminv2.master-data.countries.edit', $record->country_id) }}" class="mt-3 inline-flex items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">
                            Zum Land {{ $country?->getName('de') }}
                        </a>
                    @endif
                </x-adminv2.master-data.record-meta>

                <x-adminv2.master-data.related-list
                    heading="Städte"
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

                <x-adminv2.master-data.ai-assistant :prompts="$this->aiPrompts" :prompt-id="$aiPromptId" :result="$aiResult" :error="$aiError" noun="Regionen" />
            @else
                <x-adminv2.card heading="Nach dem Speichern">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Sobald die Region angelegt ist, lassen sich ihr Städte zuordnen – hier oder in der Bearbeitung einer Stadt.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</form>
