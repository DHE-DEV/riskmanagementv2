@php
    use App\Models\CustomEvent;
    use App\Support\AdminV2\SightInfo;

    $record = $this->record;
    $locales = SightInfo::locales();
    $sourceLocale = CustomEvent::sourceLocale();
    $status = $this->status();
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $regionOptions = $this->regionOptions->map(fn ($region) => ['value' => $region->id, 'label' => $region->getName('de'), 'code' => $region->code])->all();
    $cityOptions = $this->cityOptions->map(fn ($city) => ['value' => $city->id, 'label' => $city->getName('de')])->all();
    $country = $this->countryOptions->firstWhere('id', (int) $countryId);
    $title = $record ? $record->getName('de') : 'Neue Sehenswürdigkeit';
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <x-adminv2.master-data.editor-header
        section="sights"
        :title="$title"
        :subtitle="$record ? SightInfo::categoryLabel($record->category).($country ? ' in '.$country->getName('de') : '') : null"
        :record="$record"
    />

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Sehenswürdigkeit">
                <x-slot:actions><x-adminv2.ai-check-button section="basics" /></x-slot:actions>
                <div class="flex flex-col gap-5">
                    <div class="grid gap-5 sm:grid-cols-3">
                        @foreach ($locales as $locale)
                            <div>
                                <flux:input wire:model="names.{{ $locale }}" :label="'Name ('.CustomEvent::localeLabel($locale, false).')'" maxlength="255" />
                                @if ($locale === $sourceLocale)
                                    <x-adminv2.ai-field-hint key="name" :review="$aiReview" />
                                @elseif ($locale === 'en')
                                    <x-adminv2.ai-field-hint key="name_en" :review="$aiReview" />
                                @endif
                                <flux:error name="names.{{ $locale }}" />
                            </div>
                        @endforeach
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Kategorie</flux:label>
                            <flux:select wire:model="category">
                                @foreach (SightInfo::CATEGORIES as $key => [$label])
                                    <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <x-adminv2.ai-field-hint key="category" :review="$aiReview" />
                            <flux:error name="category" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Besuchsdauer (Minuten)</flux:label>
                            <flux:input wire:model="info.visit_minutes" inputmode="numeric" placeholder="z. B. 90" />
                            <flux:error name="info.visit_minutes" />
                        </flux:field>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <flux:field>
                            <flux:label>Land</flux:label>
                            <x-adminv2.search-select :options="$countryOptions" model="countryId" :selected="$countryId" placeholder="Land wählen …" search-placeholder="Land oder ISO-Code …" label="Land" live />
                            <flux:error name="countryId" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Region</flux:label>
                            <x-adminv2.search-select :options="$regionOptions" model="regionId" :selected="$regionId" :placeholder="$countryId === '' ? 'Zuerst ein Land wählen' : 'Keine Region'" search-placeholder="Region oder Code …" label="Region" :disabled="$regionOptions === []" live clearable wire:key="sight-region-{{ $countryId }}" />
                            <flux:error name="regionId" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Stadt</flux:label>
                            <x-adminv2.search-select :options="$cityOptions" model="cityId" :selected="$cityId" :placeholder="$countryId === '' ? 'Zuerst ein Land wählen' : 'Keine Stadt'" search-placeholder="Stadt …" label="Stadt" :disabled="$cityOptions === []" live clearable wire:key="sight-city-{{ $countryId }}-{{ $regionId }}" />
                            <flux:error name="cityId" />
                        </flux:field>
                    </div>
                    <p class="-mt-2 text-sm text-zinc-500">Nationalparks und Naturgebiete hängen ohne Stadt direkt an der Region.</p>

                    <div>
                        <flux:input wire:model="address" label="Adresse oder Lage" maxlength="500" placeholder="z. B. Piazza San Marco, 30124 Venedig" />
                        <x-adminv2.ai-field-hint key="address" :review="$aiReview" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <flux:input wire:model="websiteUrl" label="Offizielle Website" type="url" maxlength="500" placeholder="https://…" />
                            <x-adminv2.ai-field-hint key="website" :review="$aiReview" />
                        </div>
                        <flux:input wire:model="ticketUrl" label="Tickets" type="url" maxlength="500" placeholder="https://…" />
                    </div>

                    <flux:switch wire:model="isHighlight" label="Highlight der Region – wird zuerst gezeigt" align="left" />
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Texte" description="Je Sprache – Öffnungszeiten und Eintritt allgemein und ohne Gewähr." collapsible collapse-key="sight-texts">
                <x-slot:actions>
                    <flux:modal.trigger name="translate-texts">
                        <flux:button size="sm" variant="ghost" icon="language">Übersetzen</flux:button>
                    </flux:modal.trigger>
                </x-slot:actions>
                <div x-data="{ locale: @js($sourceLocale) }" class="flex flex-col gap-5">
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
                        <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif wire:key="sight-texts-{{ $locale }}" class="flex flex-col gap-5">
                            @foreach (SightInfo::TEXTS as $field => [$label, $hint, $rows])
                                <flux:field>
                                    <flux:label>{{ $label }} ({{ strtoupper($locale) }})</flux:label>
                                    <flux:description>{{ $hint }}</flux:description>
                                    <flux:textarea wire:model="info.texts.{{ $field }}.{{ $locale }}" rows="{{ $rows }}" />
                                    @if ($locale === $sourceLocale && in_array($field, ['short_description', 'description', 'opening_hours', 'admission'], true))
                                        <x-adminv2.ai-field-hint :key="$field" :review="$aiReview" />
                                    @endif
                                    <flux:error name="info.texts.{{ $field }}.{{ $locale }}" />
                                </flux:field>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </x-adminv2.card>

            <x-adminv2.master-data.coordinates :lat="$lat" :lng="$lng" :review="$aiReview" description="Lage für die Karte in der App.">
                @if ($countryId !== '')
                    <x-slot:map>
                        <x-adminv2.master-data.boundary-map :url="route('adminv2.master-data.boundaries.country', (int) $countryId)" :lat="$lat" :lng="$lng" :focus-zoom="12" empty-text="Für das Land liegen keine Grenzdaten vor – die Karte zeigt nur die Lage." />
                    </x-slot:map>
                @endif
            </x-adminv2.master-data.coordinates>
        </div>

        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Stand">
                <div class="flex flex-col gap-3 text-sm">
                    <flux:badge size="sm" class="w-fit" :color="match ($status) { 'ai' => 'amber', 'reviewed' => 'emerald', default => 'sky' }">{{ SightInfo::STATUSES[$status] }}</flux:badge>
                    @if (filled($infoMeta['ai_generated_at'] ?? null))
                        <p class="text-zinc-600 dark:text-zinc-400">KI-Vorschlag vom {{ \Illuminate\Support\Carbon::parse($infoMeta['ai_generated_at'])->timezone(config('app.timezone'))->format('d.m.Y, H:i') }} Uhr{{ filled($infoMeta['ai_model'] ?? null) ? ' ('.$infoMeta['ai_model'].')' : '' }}.</p>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            Koordinaten: {{ match ($infoMeta['geocoded'] ?? null) { 'osm' => 'mit OpenStreetMap abgeglichen', 'ai' => 'nur von der KI – bitte prüfen', default => 'nicht ermittelt' } }}.
                        </p>
                    @endif
                    @if (filled($infoMeta['reviewed_at'] ?? null))
                        <p class="text-zinc-600 dark:text-zinc-400">Geprüft am {{ \Illuminate\Support\Carbon::parse($infoMeta['reviewed_at'])->timezone(config('app.timezone'))->format('d.m.Y') }}{{ filled($infoMeta['reviewed_by'] ?? null) ? ' von '.$infoMeta['reviewed_by'] : '' }}.</p>
                    @elseif ($status === 'ai')
                        <flux:button size="sm" icon="check" wire:click="markReviewed">Als geprüft markieren</flux:button>
                    @endif
                </div>
            </x-adminv2.card>

            @if ($record)
                <x-adminv2.master-data.record-meta :record="$record">
                    <div class="mt-3 flex flex-col gap-1">
                        @if ($record->country_id)
                            <a href="{{ route('adminv2.master-data.countries.edit', $record->country_id) }}" class="inline-flex w-fit items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">Zum Land {{ $country?->getName('de') }}</a>
                        @endif
                        @if ($record->region_id)
                            <a href="{{ route('adminv2.master-data.regions.edit', $record->region_id) }}" class="inline-flex w-fit items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">Zur Region</a>
                        @endif
                        @if ($record->city_id)
                            <a href="{{ route('adminv2.master-data.cities.edit', $record->city_id) }}" class="inline-flex w-fit items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">Zur Stadt</a>
                        @endif
                    </div>
                </x-adminv2.master-data.record-meta>
            @else
                <x-adminv2.card heading="Hinweis">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Mit „Speichern &amp; weitere anlegen“ bleiben Land und Region für den nächsten Eintrag vorbelegt. Viele Einträge auf einmal legt die KI in der Bearbeitung einer Region an.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    <x-adminv2.master-data.translate-modal section="texts" what="Name und Texte" />
    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />

    <x-adminv2.ai-check-modal area="sights" :section="$aiSection" :checks="$this->aiChecks" :data="$this->aiData" :check-id="$aiCheckId" :prompt-draft="$aiPromptDraft" :result="$aiResult" :error="$aiError" :models="$this->aiModelOptions" :save-as-check="$aiSaveAsCheck" :review="$aiReview" :title="$record?->getName('de')" />
</form>
