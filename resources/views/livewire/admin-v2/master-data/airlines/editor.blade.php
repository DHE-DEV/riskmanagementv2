@php
    use App\Livewire\AdminV2\MasterData\Airlines\Editor;
    use App\Models\Airline;

    $record = $this->record;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $country = $this->countryOptions->firstWhere('id', (int) $homeCountryId);
    $classes = Airline::getCabinClassOptions();
    $hasBaggage = collect($checkedBaggage)->contains(fn ($value) => $value !== '') || collect($handBaggage)->contains(fn ($value) => $value !== '') || $handBaggageNotes !== '' || $handBaggageInfoUrl !== '';
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <x-adminv2.master-data.editor-header
        section="airlines"
        :title="$record ? $record->name : 'Neue Airline'"
        :subtitle="$record ? trim(($record->iata_code ?: '–').' · '.($record->icao_code ?: '–'), ' ·') : null"
        :record="$record"
    />

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Airline">
                <x-slot:actions><x-adminv2.ai-check-button section="basics" /></x-slot:actions>
                <div class="flex flex-col gap-5">
                    <div>
                        <flux:input wire:model="name" label="Name der Airline" maxlength="255" />
                        <x-adminv2.ai-field-hint key="name" :review="$aiReview" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-4">
                        <flux:field>
                            <flux:label>IATA-Code</flux:label>
                            <flux:input wire:model="iataCode" maxlength="2" class="font-mono uppercase" placeholder="LH" />
                            <x-adminv2.ai-field-hint key="iata_code" :review="$aiReview" />
                            <flux:error name="iataCode" />
                        </flux:field>
                        <flux:field>
                            <flux:label>ICAO-Code</flux:label>
                            <flux:input wire:model="icaoCode" maxlength="3" class="font-mono uppercase" placeholder="DLH" />
                            <x-adminv2.ai-field-hint key="icao_code" :review="$aiReview" />
                            <flux:error name="icaoCode" />
                        </flux:field>
                        <flux:field class="sm:col-span-2">
                            <flux:label>Heimatland</flux:label>
                            <x-adminv2.search-select :options="$countryOptions" model="homeCountryId" :selected="$homeCountryId" placeholder="Kein Heimatland" search-placeholder="Land oder ISO-Code …" label="Heimatland" live clearable />
                            <x-adminv2.ai-field-hint key="home_country" :review="$aiReview" />
                            <flux:error name="homeCountryId" />
                        </flux:field>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Hauptsitz</flux:label>
                            <flux:input wire:model="headquarters" maxlength="255" placeholder="z. B. Köln" />
                            <x-adminv2.ai-field-hint key="headquarters" :review="$aiReview" />
                            <flux:error name="headquarters" />
                        </flux:field>
                        <div class="flex items-end pb-2">
                            <div>
                                <flux:switch wire:model="isActive" label="Aktiv" align="left" />
                                <x-adminv2.ai-field-hint key="is_active" :review="$aiReview" />
                            </div>
                        </div>
                        <flux:field>
                            <flux:label>Website</flux:label>
                            <x-adminv2.url-input wire:model="website" maxlength="255" />
                            <x-adminv2.ai-field-hint key="website" :review="$aiReview" />
                            <flux:error name="website" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Buchungslink</flux:label>
                            <x-adminv2.url-input wire:model="bookingUrl" maxlength="255" />
                            <x-adminv2.ai-field-hint key="booking_url" :review="$aiReview" />
                            <flux:error name="bookingUrl" />
                        </flux:field>
                    </div>

                    <div>
                        <p class="mb-3 text-sm font-medium text-zinc-800 dark:text-white">Kontaktmöglichkeiten</p>
                        <x-adminv2.ai-field-hint key="contact" :review="$aiReview" :applyable="false" class="!mt-0 mb-3 rounded-xl border border-dashed border-zinc-300 p-3 dark:border-zinc-700" />
                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach (Editor::CONTACT_FIELDS as $field => $label)
                                <flux:field>
                                    <flux:label>{{ $label }}</flux:label>
                                    @if (str_ends_with($field, '_url'))
                                        <x-adminv2.url-input wire:model="contact.{{ $field }}" />
                                    @else
                                        <flux:input wire:model="contact.{{ $field }}" />
                                    @endif
                                    <flux:error name="contact.{{ $field }}" />
                                </flux:field>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Tarifarten / Kabinenklassen" :description="$cabinClasses ? implode(', ', array_intersect_key($classes, array_flip($cabinClasses))) : 'Noch keine Klasse markiert.'">
                <x-slot:actions><x-adminv2.ai-check-button section="cabin_classes" /></x-slot:actions>
                <x-adminv2.ai-field-hint key="cabin_classes" :review="$aiReview" :applyable="false" class="!mt-0 mb-3 rounded-xl border border-dashed border-zinc-300 p-3 dark:border-zinc-700" />
                <div class="flex flex-wrap gap-x-8 gap-y-3">
                    @foreach ($classes as $value => $label)
                        <flux:checkbox wire:model.live="cabinClasses" value="{{ $value }}" :label="$label" />
                    @endforeach
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Freigepäck & Handgepäck" collapsible :collapsed="! $hasBaggage" collapse-key="airline-baggage">
                <x-slot:actions><x-adminv2.ai-check-button section="baggage" /></x-slot:actions>
                <div class="flex flex-col gap-6">
                    <div>
                        <p class="mb-3 text-sm font-medium text-zinc-800 dark:text-white">Freigepäck (Aufgabegepäck)</p>
                        <div class="grid items-start gap-4 sm:grid-cols-4">
                            @foreach ($classes as $value => $label)
                                <div wire:key="checked-{{ $value }}">
                                    <flux:input wire:model="checkedBaggage.{{ $value }}" :label="$label" placeholder="z. B. 23 kg" maxlength="100" />
                                    <x-adminv2.ai-field-hint :key="'baggage_checked_'.$value" :review="$aiReview" />
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 text-sm font-medium text-zinc-800 dark:text-white">Handgepäck</p>
                        <div class="flex flex-col gap-4">
                            @foreach ($classes as $value => $label)
                                <div wire:key="hand-{{ $value }}" class="grid items-start gap-3 rounded-xl border border-zinc-200 p-3 sm:grid-cols-[minmax(0,1.6fr)_repeat(3,minmax(0,1fr))] dark:border-zinc-800">
                                    <div><flux:input wire:model="handBaggage.{{ $value }}" :label="$label.' – Gewicht'" placeholder="z. B. 8 kg" maxlength="100" /><x-adminv2.ai-field-hint :key="'baggage_hand_'.$value" :review="$aiReview" /></div>
                                    <div><flux:input wire:model="handDimensions.{{ $value }}.length" label="Länge (cm)" inputmode="decimal" /><x-adminv2.ai-field-hint :key="'baggage_hand_'.$value.'_length'" :review="$aiReview" /></div>
                                    <div><flux:input wire:model="handDimensions.{{ $value }}.width" label="Breite (cm)" inputmode="decimal" /><x-adminv2.ai-field-hint :key="'baggage_hand_'.$value.'_width'" :review="$aiReview" /></div>
                                    <div><flux:input wire:model="handDimensions.{{ $value }}.height" label="Höhe (cm)" inputmode="decimal" /><x-adminv2.ai-field-hint :key="'baggage_hand_'.$value.'_height'" :review="$aiReview" /></div>
                                    <flux:error name="handDimensions.{{ $value }}.length" />
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div><flux:textarea wire:model="handBaggageNotes" label="Allgemeine Hinweise" rows="3" /><x-adminv2.ai-field-hint key="baggage_notes" :review="$aiReview" /></div>
                    <flux:field>
                        <flux:label>Info-URL</flux:label>
                        <x-adminv2.url-input wire:model="handBaggageInfoUrl" />
                        <flux:error name="handBaggageInfoUrl" />
                        <x-adminv2.ai-field-hint key="baggage_info_url" :review="$aiReview" />
                    </flux:field>
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Haustiermitnahme" :description="$petsAllowed ? 'Haustiere dürfen mitreisen.' : 'Keine Haustiermitnahme hinterlegt.'" collapsible :collapsed="! $petsAllowed" collapse-key="airline-pets">
                <x-slot:actions><x-adminv2.ai-check-button section="pets" /></x-slot:actions>
                <div class="flex flex-col gap-5">
                    <div>
                        <flux:switch wire:model.live="petsAllowed" label="Haustiermitnahme erlaubt" align="left" />
                        <x-adminv2.ai-field-hint key="pets_allowed" :review="$aiReview" />
                    </div>

                    @if ($petsAllowed)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <p class="mb-3 text-sm font-medium text-zinc-800 dark:text-white">In der Kabine</p>
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-wrap gap-x-8 gap-y-3">
                                    <div><flux:switch wire:model="petCabin.allowed" label="Erlaubt" align="left" /><x-adminv2.ai-field-hint key="pets_cabin_allowed" :review="$aiReview" /></div>
                                    <div><flux:switch wire:model="petCabin.weight_includes_bag" label="Gewicht inklusive Tasche" align="left" /><x-adminv2.ai-field-hint key="pets_cabin_weight_includes_bag" :review="$aiReview" /></div>
                                    <div><flux:switch wire:model="petCabin.advance_notice_required" label="Voranmeldung erforderlich" align="left" /><x-adminv2.ai-field-hint key="pets_cabin_advance_notice_required" :review="$aiReview" /></div>
                                </div>
                                <div class="grid items-start gap-4 sm:grid-cols-4">
                                    <div><flux:input wire:model="petCabin.max_weight" label="Maximales Gewicht" placeholder="z. B. 8 kg" maxlength="50" /><x-adminv2.ai-field-hint key="pets_cabin_max_weight" :review="$aiReview" /></div>
                                    <div><flux:input wire:model="petCabin.carrier_length" label="Transportbox-Länge (cm)" inputmode="decimal" /><x-adminv2.ai-field-hint key="pets_cabin_carrier_length" :review="$aiReview" /></div>
                                    <div><flux:input wire:model="petCabin.carrier_width" label="Transportbox-Breite (cm)" inputmode="decimal" /><x-adminv2.ai-field-hint key="pets_cabin_carrier_width" :review="$aiReview" /></div>
                                    <div><flux:input wire:model="petCabin.carrier_height" label="Transportbox-Höhe (cm)" inputmode="decimal" /><x-adminv2.ai-field-hint key="pets_cabin_carrier_height" :review="$aiReview" /></div>
                                </div>
                                <flux:error name="petCabin.carrier_length" />
                                <div><flux:textarea wire:model="petCabin.notes" label="Zusätzliche Hinweise" rows="3" /><x-adminv2.ai-field-hint key="pets_cabin_notes" :review="$aiReview" /></div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <p class="mb-3 text-sm font-medium text-zinc-800 dark:text-white">Im Frachtraum</p>
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-wrap gap-x-8 gap-y-3">
                                    <div><flux:switch wire:model="petHold.allowed" label="Erlaubt" align="left" /><x-adminv2.ai-field-hint key="pets_hold_allowed" :review="$aiReview" /></div>
                                    <div><flux:switch wire:model="petHold.advance_notice_required" label="Voranmeldung erforderlich" align="left" /><x-adminv2.ai-field-hint key="pets_hold_advance_notice_required" :review="$aiReview" /></div>
                                </div>
                                <div class="grid items-start gap-4 sm:grid-cols-4">
                                    <div><flux:input wire:model="petHold.max_weight" label="Maximales Gewicht" placeholder="z. B. 32 kg" maxlength="50" /><x-adminv2.ai-field-hint key="pets_hold_max_weight" :review="$aiReview" /></div>
                                </div>
                                <div><flux:textarea wire:model="petHold.notes" label="Zusätzliche Hinweise" rows="3" /><x-adminv2.ai-field-hint key="pets_hold_notes" :review="$aiReview" /></div>
                            </div>
                        </div>

                        <div>
                            <p class="mb-3 text-sm font-medium text-zinc-800 dark:text-white">Allgemeine Einschränkungen</p>
                            <div class="flex flex-wrap gap-x-8 gap-y-3">
                                @foreach (Editor::PET_RESTRICTIONS as $value => $label)
                                    <flux:checkbox wire:model="petRestrictions" value="{{ $value }}" :label="$label" />
                                @endforeach
                            </div>
                            <x-adminv2.ai-field-hint key="pets_restrictions" :review="$aiReview" />
                        </div>

                        <flux:field>
                            <flux:label>Info-URL</flux:label>
                            <x-adminv2.url-input wire:model="petInfoUrl" />
                            <flux:error name="petInfoUrl" />
                            <x-adminv2.ai-field-hint key="pets_info_url" :review="$aiReview" />
                        </flux:field>
                        <div><flux:textarea wire:model="petNotes" label="Allgemeine Hinweise" rows="3" /><x-adminv2.ai-field-hint key="pets_notes" :review="$aiReview" /></div>
                    @endif
                </div>
            </x-adminv2.card>
        </div>

        <div class="flex flex-col gap-6">
            @if ($record)
                <x-adminv2.master-data.record-meta :record="$record">
                    @if ($record->home_country_id)
                        <a href="{{ route('adminv2.master-data.countries.edit', $record->home_country_id) }}" class="mt-3 inline-flex w-fit text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">Zum Heimatland {{ $country?->getName('de') }}</a>
                    @endif
                </x-adminv2.master-data.record-meta>

                <x-adminv2.master-data.airline-links
                    heading="Flughäfen"
                    noun="Flughafen"
                    :links="$this->links"
                    :options="$this->availableLinkOptions"
                    :edit-url="fn ($airport) => route('adminv2.master-data.airports.edit', $airport->id)"
                    :link-id="$linkId"
                    :link-direction="$linkDirection"
                    :link-terminal="$linkTerminal"
                    :editing-link-id="$editingLinkId"
                    :review="$aiReview"
                />
            @else
                <x-adminv2.card heading="Nach dem Speichern">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Sobald die Airline angelegt ist, lassen sich hier die Flughäfen verknüpfen, die sie anfliegt (Direktverbindungen).</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />

    <x-adminv2.ai-check-modal area="airlines" :section="$aiSection" :checks="$this->aiChecks" :data="$this->aiData" :check-id="$aiCheckId" :prompt-draft="$aiPromptDraft" :result="$aiResult" :error="$aiError" :models="$this->aiModelOptions" :save-as-check="$aiSaveAsCheck" :review="$aiReview" :title="$record?->name" />
</form>
