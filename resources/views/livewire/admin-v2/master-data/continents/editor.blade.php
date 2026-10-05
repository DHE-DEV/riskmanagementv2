@php
    $record = $this->record;
    $countries = $this->countries;
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <x-adminv2.master-data.editor-header
        section="continents"
        :title="$record ? $record->getName('de') : 'Neuer Kontinent'"
        :record="$record"
    />

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Kontinent">
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
                            <flux:label>Code</flux:label>
                            <flux:description>Eindeutiges Kürzel mit höchstens 5 Zeichen, z. B. EU für Europa.</flux:description>
                            <flux:input wire:model="code" maxlength="5" class="font-mono" />
                            <x-adminv2.ai-field-hint key="code" :review="$aiReview" />
                            <flux:error name="code" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Sortierung</flux:label>
                            <flux:description>Niedrigere Werte stehen in Auswahllisten weiter oben.</flux:description>
                            <flux:input wire:model="sortOrder" type="number" min="0" step="1" />
                            <x-adminv2.ai-field-hint key="sort_order" :review="$aiReview" />
                            <flux:error name="sortOrder" />
                        </flux:field>
                    </div>

                    <div>
                        <flux:textarea wire:model="description" label="Beschreibung" rows="3" maxlength="1000" />
                        <x-adminv2.ai-field-hint key="description" :review="$aiReview" />
                    </div>

                    <flux:field>
                        <flux:label>Schlagwörter</flux:label>
                        <flux:description>Weitere Bezeichnungen, unter denen der Kontinent gefunden wird – mit Komma getrennt.</flux:description>
                        <flux:input wire:model="keywords" placeholder="z. B. Europa, Europe, EU" maxlength="1000" />
                        <x-adminv2.ai-field-hint key="keywords" :review="$aiReview" />
                        <flux:error name="keywords" />
                    </flux:field>
                </div>
            </x-adminv2.card>

            <x-adminv2.master-data.coordinates :lat="$lat" :lng="$lng" :review="$aiReview" description="Mittelpunkt des Kontinents für die Darstellung auf der Karte.">
                @if ($record)
                    <x-slot:map>
                        <x-adminv2.master-data.boundary-map
                            :url="route('adminv2.master-data.boundaries.continent', $record->id)"
                            :lat="$lat"
                            :lng="$lng"
                            height="h-[28rem]"
                            empty-text="Für die Länder dieses Kontinents liegen keine Grenzdaten vor – die Karte zeigt nur den Mittelpunkt."
                        />
                    </x-slot:map>
                @endif
            </x-adminv2.master-data.coordinates>
        </div>

        <div class="flex flex-col gap-6">
            @if ($record)
                <x-adminv2.master-data.record-meta :record="$record" />

                <x-adminv2.master-data.related-list
                    heading="Länder"
                    ai-section="countries"
                    :review="$aiReview"
                    :count="$countries->count()"
                    :shown="$countries->count()"
                    :all-url="route('adminv2.master-data.countries.index', ['continent' => $record->id])"
                    :create-url="route('adminv2.master-data.countries.create', ['continent' => $record->id])"
                    create-label="Neues Land"
                    empty-text="Diesem Kontinent ist noch kein Land zugeordnet."
                >
                    @foreach ($countries as $country)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <a href="{{ route('adminv2.master-data.countries.edit', $country->id) }}" class="truncate text-zinc-900 hover:underline dark:text-white">{{ $country->getName('de') }}</a>
                            <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $country->iso_code }}</span>
                        </li>
                    @endforeach
                </x-adminv2.master-data.related-list>

            @else
                <x-adminv2.card heading="Nach dem Speichern">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Sobald der Kontinent angelegt ist, lassen sich ihm Länder zuordnen – hier oder in der Bearbeitung eines Landes.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />

    <x-adminv2.ai-check-modal area="continents" :section="$aiSection" :checks="$this->aiChecks" :data="$this->aiData" :check-id="$aiCheckId" :prompt-draft="$aiPromptDraft" :result="$aiResult" :error="$aiError" :models="$this->aiModelOptions" :save-as-check="$aiSaveAsCheck" :review="$aiReview" :title="$record?->getName('de')" />
</form>
