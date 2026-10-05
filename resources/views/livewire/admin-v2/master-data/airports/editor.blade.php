@php
    use App\Models\Airport;

    $record = $this->record;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $cityOptions = $this->cityOptions->map(fn ($city) => ['value' => $city->id, 'label' => $city->getName('de'), 'code' => $city->is_capital ? 'Hauptstadt' : null])->all();
    $country = $this->countryOptions->firstWhere('id', (int) $countryId);
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <x-adminv2.master-data.editor-header
        section="airports"
        :title="$record ? $record->name : 'Neuer Flughafen'"
        :subtitle="$record ? trim(($record->iata_code ?: '').' · '.($record->icao_code ?: ''), ' ·') : null"
        :record="$record"
    />

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Flughafen">
                <x-slot:actions><x-adminv2.ai-check-button section="basics" /></x-slot:actions>
                <div class="flex flex-col gap-5">
                    <div>
                        <flux:input wire:model="name" label="Name" maxlength="255" placeholder="z. B. Flughafen München" />
                        <x-adminv2.ai-field-hint key="name" :review="$aiReview" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Land</flux:label>
                            <x-adminv2.search-select :options="$countryOptions" model="countryId" :selected="$countryId" placeholder="Land wählen …" search-placeholder="Land oder ISO-Code …" label="Land" live />
                            <x-adminv2.ai-field-hint key="country" :review="$aiReview" />
                            <flux:error name="countryId" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Stadt</flux:label>
                            <x-adminv2.search-select
                                :options="$cityOptions"
                                model="cityId"
                                :selected="$cityId"
                                :placeholder="$countryId === '' ? 'Zuerst ein Land wählen' : ($cityOptions ? 'Stadt wählen …' : 'Für dieses Land ist keine Stadt angelegt')"
                                search-placeholder="Stadt …"
                                label="Stadt"
                                :disabled="$cityOptions === []"
                                live
                                wire:key="city-select-{{ $countryId }}"
                            />
                            <x-adminv2.ai-field-hint key="city" :review="$aiReview" />
                            <flux:error name="cityId" />
                            @if ($countryId !== '')
                                <flux:description>Fehlt die Stadt? <a href="{{ route('adminv2.master-data.cities.create', ['country' => $countryId]) }}" target="_blank" class="underline underline-offset-2">Neue Stadt anlegen</a></flux:description>
                            @endif
                        </flux:field>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <flux:field>
                            <flux:label>IATA-Code</flux:label>
                            <flux:input wire:model="iataCode" maxlength="3" class="font-mono uppercase" placeholder="MUC" />
                            <x-adminv2.ai-field-hint key="iata_code" :review="$aiReview" />
                            <flux:error name="iataCode" />
                        </flux:field>
                        <flux:field>
                            <flux:label>ICAO-Code</flux:label>
                            <flux:input wire:model="icaoCode" maxlength="4" class="font-mono uppercase" placeholder="EDDM" />
                            <x-adminv2.ai-field-hint key="icao_code" :review="$aiReview" />
                            <flux:error name="icaoCode" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Typ</flux:label>
                            <flux:select wire:model="type">
                                @foreach (Airport::getTypeOptions() as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <x-adminv2.ai-field-hint key="type" :review="$aiReview" />
                            <flux:error name="type" />
                        </flux:field>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Website</flux:label>
                            <flux:description>Offizielle Website des Flughafens.</flux:description>
                            <x-adminv2.url-input wire:model="website" />
                            <x-adminv2.ai-field-hint key="website" :review="$aiReview" />
                            <flux:error name="website" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Zeitfenster-Reservierung für die Sicherheitskontrolle</flux:label>
                            <flux:description>Link zum Buchungssystem, falls der Flughafen eines anbietet.</flux:description>
                            <x-adminv2.url-input wire:model="securityTimeslotUrl" />
                            <x-adminv2.ai-field-hint key="security_timeslot_url" :review="$aiReview" />
                            <flux:error name="securityTimeslotUrl" />
                        </flux:field>
                    </div>

                    <div class="flex flex-wrap gap-x-8 gap-y-3">
                        <div>
                            <flux:switch wire:model="isActive" label="Aktiv" align="left" />
                            <x-adminv2.ai-field-hint key="is_active" :review="$aiReview" />
                        </div>
                        <div>
                            <flux:switch wire:model="operates24h" label="24-Stunden-Betrieb für Passagierflüge" align="left" />
                            <x-adminv2.ai-field-hint key="operates_24h" :review="$aiReview" />
                        </div>
                    </div>
                </div>
            </x-adminv2.card>

            <x-adminv2.master-data.coordinates :lat="$lat" :lng="$lng" :review="$aiReview" description="Lage des Flughafens – daran werden Reisen und Ereignisse in der Nähe erkannt." />

            <x-adminv2.card heading="Höhe und Zeitzone">
                <x-slot:actions><x-adminv2.ai-check-button section="altitude" /></x-slot:actions>
                <div class="grid gap-5 sm:grid-cols-3">
                    <flux:field>
                        <flux:label>Höhe (Meter)</flux:label>
                        <flux:input wire:model="altitude" inputmode="numeric" placeholder="z. B. 453" />
                        <x-adminv2.ai-field-hint key="altitude" :review="$aiReview" />
                        <flux:error name="altitude" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Zeitzone</flux:label>
                        <flux:input wire:model="timezone" placeholder="Europe/Berlin" maxlength="255" />
                        <x-adminv2.ai-field-hint key="timezone" :review="$aiReview" />
                        <flux:error name="timezone" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Sommerzeit-Zeitzone</flux:label>
                        <flux:input wire:model="dstTimezone" placeholder="z. B. CEST" maxlength="255" />
                        <x-adminv2.ai-field-hint key="dst_timezone" :review="$aiReview" />
                        <flux:error name="dstTimezone" />
                    </flux:field>
                </div>
            </x-adminv2.card>

            <x-adminv2.master-data.airport-extras :lounges="$lounges" :mobility="$mobility" :hotels="$hotels" :review="$aiReview" />
        </div>

        <div class="flex flex-col gap-6">
            @if ($record)
                <x-adminv2.master-data.record-meta :record="$record">
                    <dl class="mt-2 flex flex-col gap-2 text-sm">
                        @if ($record->source)
                            <div class="flex justify-between gap-3">
                                <dt class="text-zinc-500">Datenquelle</dt>
                                <dd class="text-zinc-900 dark:text-white">{{ $record->source }}</dd>
                            </div>
                        @endif
                    </dl>
                    <div class="mt-3 flex flex-col gap-1">
                        @if ($record->country_id)
                            <a href="{{ route('adminv2.master-data.countries.edit', $record->country_id) }}" class="inline-flex w-fit text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">Zum Land {{ $country?->getName('de') }}</a>
                        @endif
                        @if ($record->city_id)
                            <a href="{{ route('adminv2.master-data.cities.edit', $record->city_id) }}" class="inline-flex w-fit text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">Zur Stadt</a>
                        @endif
                    </div>
                </x-adminv2.master-data.record-meta>

                <x-adminv2.master-data.airline-links
                    heading="Airlines"
                    noun="Airline"
                    :links="$this->links"
                    :options="$this->availableLinkOptions"
                    :edit-url="fn ($airline) => route('adminv2.master-data.airlines.edit', $airline->id)"
                    :link-id="$linkId"
                    :link-direction="$linkDirection"
                    :link-terminal="$linkTerminal"
                    :editing-link-id="$editingLinkId"
                    :review="$aiReview"
                />

            @else
                <x-adminv2.card heading="Nach dem Speichern">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Sobald der Flughafen angelegt ist, lassen sich hier die Airlines verknüpfen, die ihn anfliegen, und der KI-Assistent nutzen.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />

    <x-adminv2.ai-check-modal area="airports" :section="$aiSection" :checks="$this->aiChecks" :data="$this->aiData" :check-id="$aiCheckId" :prompt-draft="$aiPromptDraft" :result="$aiResult" :error="$aiError" :models="$this->aiModelOptions" :save-as-check="$aiSaveAsCheck" :review="$aiReview" :title="$record?->name" />
</form>
