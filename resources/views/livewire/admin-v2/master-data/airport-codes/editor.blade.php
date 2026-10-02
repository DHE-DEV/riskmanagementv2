@php
    use App\Livewire\AdminV2\MasterData\AirportCodes\Editor;
    use App\Models\AirportCode;

    $record = $this->record;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $cityOptions = $this->cityOptions->map(fn ($city) => ['value' => $city->id, 'label' => $city->getName('de'), 'code' => $city->is_capital ? 'Hauptstadt' : null])->all();
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <x-adminv2.master-data.editor-header
        section="airport-codes"
        :title="$record ? $record->name : 'Neuer Flughafen-Code'"
        :subtitle="$record ? trim(($record->ident ?: '').' · '.($record->iata_code ?: '–').' · '.($record->icao_code ?: '–'), ' ·') : null"
        :record="$record"
    />

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Flugplatz">
                <div class="flex flex-col gap-5">
                    <flux:input wire:model="name" label="Name" maxlength="255" />

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Land (verknüpft)</flux:label>
                            <x-adminv2.search-select :options="$countryOptions" model="countryId" :selected="$countryId" placeholder="Kein Land verknüpft" search-placeholder="Land oder ISO-Code …" label="Land" live clearable />
                            <flux:error name="countryId" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Stadt (verknüpft)</flux:label>
                            <x-adminv2.search-select
                                :options="$cityOptions"
                                model="cityId"
                                :selected="$cityId"
                                :placeholder="$countryId === '' ? 'Zuerst ein Land wählen' : ($cityOptions ? 'Keine Stadt verknüpft' : 'Für dieses Land ist keine Stadt angelegt')"
                                search-placeholder="Stadt …"
                                label="Stadt"
                                :disabled="$cityOptions === []"
                                live
                                clearable
                                wire:key="city-select-{{ $countryId }}"
                            />
                            <flux:error name="cityId" />
                        </flux:field>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <flux:field>
                            <flux:label>Land (ISO)</flux:label>
                            <flux:input wire:model="isoCountry" maxlength="5" class="font-mono uppercase" placeholder="DE" />
                            <flux:error name="isoCountry" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Region (ISO)</flux:label>
                            <flux:input wire:model="isoRegion" maxlength="10" class="font-mono uppercase" placeholder="DE-BY" />
                            <flux:error name="isoRegion" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Stadt/Gemeinde</flux:label>
                            <flux:input wire:model="municipality" maxlength="100" />
                            <flux:error name="municipality" />
                        </flux:field>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Website</flux:label>
                            <flux:input wire:model="website" placeholder="https://…" />
                            <flux:error name="website" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Zeitfenster-Reservierung für die Sicherheitskontrolle</flux:label>
                            <flux:input wire:model="securityTimeslotUrl" placeholder="https://…" />
                            <flux:error name="securityTimeslotUrl" />
                        </flux:field>
                    </div>

                    <div class="flex flex-wrap gap-x-8 gap-y-3">
                        <flux:switch wire:model="isActive" label="Aktiv" align="left" />
                        <flux:switch wire:model="operates24h" label="24-Stunden-Betrieb für Passagierflüge" align="left" />
                    </div>
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Codes und Einstufung">
                <div class="flex flex-col gap-5">
                    <div class="grid gap-5 sm:grid-cols-5">
                        <flux:field>
                            <flux:label>Ident</flux:label>
                            <flux:input wire:model="ident" maxlength="10" class="font-mono uppercase" />
                            <flux:error name="ident" />
                        </flux:field>
                        <flux:field>
                            <flux:label>IATA</flux:label>
                            <flux:input wire:model="iataCode" maxlength="10" class="font-mono uppercase" />
                            <flux:error name="iataCode" />
                        </flux:field>
                        <flux:field>
                            <flux:label>ICAO</flux:label>
                            <flux:input wire:model="icaoCode" maxlength="10" class="font-mono uppercase" />
                            <flux:error name="icaoCode" />
                        </flux:field>
                        <flux:field>
                            <flux:label>GPS-Code</flux:label>
                            <flux:input wire:model="gpsCode" maxlength="10" class="font-mono uppercase" />
                            <flux:error name="gpsCode" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Lokaler Code</flux:label>
                            <flux:input wire:model="localCode" maxlength="20" class="font-mono uppercase" />
                            <flux:error name="localCode" />
                        </flux:field>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <flux:field>
                            <flux:label>Typ</flux:label>
                            <flux:select wire:model="type">
                                @foreach (AirportCode::getTypeOptions() as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="type" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Kontinent</flux:label>
                            <flux:select wire:model="continent">
                                <flux:select.option value="">Nicht angegeben</flux:select.option>
                                @foreach (AirportCode::getContinentOptions() as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="continent" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Linienflugverkehr</flux:label>
                            <flux:select wire:model="scheduledService">
                                @foreach (Editor::SCHEDULED as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="scheduledService" />
                        </flux:field>
                    </div>
                </div>
            </x-adminv2.card>

            <x-adminv2.master-data.coordinates :lat="$lat" :lng="$lng" description="Lage des Flugplatzes." />

            <x-adminv2.card heading="Höhe und Zeitzone">
                <div class="grid gap-5 sm:grid-cols-3">
                    <flux:field>
                        <flux:label>Höhe (Fuß)</flux:label>
                        <flux:input wire:model="elevationFt" inputmode="numeric" placeholder="z. B. 1487" />
                        <flux:error name="elevationFt" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Zeitzone</flux:label>
                        <flux:input wire:model="timezone" placeholder="Europe/Berlin" maxlength="255" />
                        <flux:error name="timezone" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Sommerzeit-Zeitzone</flux:label>
                        <flux:input wire:model="dstTimezone" maxlength="255" />
                        <flux:error name="dstTimezone" />
                    </flux:field>
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Links und Suchbegriffe" collapsible :collapsed="$homeLink === '' && $wikipediaLink === '' && $keywords === ''">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Home-Link</flux:label>
                        <flux:input wire:model="homeLink" placeholder="https://…" />
                        <flux:error name="homeLink" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Wikipedia-Link</flux:label>
                        <flux:input wire:model="wikipediaLink" placeholder="https://…" />
                        <flux:error name="wikipediaLink" />
                    </flux:field>
                    <flux:field class="sm:col-span-2">
                        <flux:label>Suchbegriffe</flux:label>
                        <flux:description>Weitere Namen und Schreibweisen, mit Komma getrennt.</flux:description>
                        <flux:textarea wire:model="keywords" rows="2" />
                        <flux:error name="keywords" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Datenquelle</flux:label>
                        <flux:input wire:model="source" maxlength="50" placeholder="z. B. ourairports" />
                        <flux:error name="source" />
                    </flux:field>
                </div>
            </x-adminv2.card>

            <x-adminv2.master-data.airport-extras :lounges="$lounges" :mobility="$mobility" :hotels="$hotels" />
        </div>

        <div class="flex flex-col gap-6">
            @if ($record)
                <x-adminv2.master-data.record-meta :record="$record">
                    <div class="mt-3 flex flex-col gap-1">
                        @if ($record->country_id)
                            <a href="{{ route('adminv2.master-data.countries.edit', $record->country_id) }}" class="inline-flex w-fit text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">Zum Land</a>
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
                />
            @else
                <x-adminv2.card heading="Hinweis">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Das Verzeichnis stammt überwiegend aus dem Import (OurAirports). Ein von Hand angelegter Eintrag bekommt die Datenquelle „manual“.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</form>
