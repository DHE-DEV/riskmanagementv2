@php
    use App\Livewire\AdminV2\Events\Infosystem;
    use App\Support\AdminV2\Infosystem as InfosystemSupport;

    $rows = $this->rows;
    $hasCredentials = $this->hasCredentials;
    $langColors = ['de' => 'amber', 'en' => 'green', 'fr' => 'sky', 'it' => 'red'];
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Passolution Infosystem</flux:heading>
            <flux:subheading>Einträge aus dem Passolution Infosystem – abgerufen über die API und hier gespeichert. Aus jedem Eintrag lässt sich ein Ereignis anlegen.</flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:modal.trigger name="infosystem-sync-100">
                <flux:button icon="arrow-down-tray">Letzte {{ Infosystem::SYNC_LIMIT }} Einträge abrufen</flux:button>
            </flux:modal.trigger>
            <flux:modal.trigger name="infosystem-sync">
                <flux:button variant="primary" icon="arrow-path">Daten synchronisieren</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    @unless ($hasCredentials)
        <flux:callout variant="warning" icon="exclamation-triangle" heading="API-Konfiguration fehlt">
            <flux:callout.text>Für den Abruf aus dem Infosystem muss PASSOLUTION_API_KEY in der .env-Datei gesetzt sein.</flux:callout.text>
        </flux:callout>
    @endunless

    {{-- Waehrend eines Abrufs: Die Seite bleibt stehen, bis die API geantwortet hat. --}}
    <div wire:loading.flex wire:target="sync, syncLast100" class="items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-5 py-4 text-sm text-zinc-700 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300">
        <flux:icon.loading class="size-5 shrink-0" />
        <span>Abruf läuft … Das kann bei mehreren Seiten einige Minuten dauern.</span>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Titel, Inhalt, Land oder API-ID suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-48">
            <flux:select wire:model.live="published" aria-label="Veröffentlichung">
                <flux:select.option value="">Veröffentlicht: alle</flux:select.option>
                <flux:select.option value="yes">Veröffentlicht</flux:select.option>
                <flux:select.option value="no">Nicht veröffentlicht</flux:select.option>
            </flux:select>
        </div>
        <div class="w-40">
            <flux:select wire:model.live="lang" aria-label="Sprache">
                <flux:select.option value="">Alle Sprachen</flux:select.option>
                @foreach (InfosystemSupport::LANGUAGES as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-36">
            <flux:select wire:model.live="active" aria-label="Aktiv">
                <flux:select.option value="">Aktiv: alle</flux:select.option>
                <flux:select.option value="yes">Aktiv</flux:select.option>
                <flux:select.option value="no">Inaktiv</flux:select.option>
            </flux:select>
        </div>
        <div class="w-40">
            <flux:select wire:model.live="archive" aria-label="Archiv">
                <flux:select.option value="">Archiv: alle</flux:select.option>
                <flux:select.option value="yes">Archiviert</flux:select.option>
                <flux:select.option value="no">Nicht archiviert</flux:select.option>
            </flux:select>
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Eintrag', 'Einträge']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Einträge" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 xl:grid-cols-2" wire:loading.class="opacity-60" wire:target="search, published, lang, active, archive, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $entry)
                @php
                    $countryName = $entry->getCountryName('de');
                    $createUrl = $entry->is_published ? null : $this->createEventUrl($entry);
                    $eventUrl = $entry->publishedEvent ? route('adminv2.events.edit', $entry->publishedEvent) : null;
                @endphp
                <article
                    wire:key="infosystem-entry-{{ $entry->id }}"
                    @class([
                        'flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950',
                        'opacity-70' => ! $entry->active || $entry->archive,
                    ])
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold leading-snug text-zinc-900 dark:text-white">{{ $entry->header }}</h2>
                            <p class="mt-0.5 text-sm text-zinc-500 tabular-nums">
                                {{ $entry->tagdate?->format('d.m.Y') ?? '–' }}
                                @if ($countryName || $entry->country_code)
                                    · {{ $countryName ?: $entry->country_code }}@if ($countryName && $entry->country_code) ({{ $entry->country_code }})@endif
                                @endif
                                · API-ID {{ $entry->api_id }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            @if ($createUrl)
                                <flux:button size="sm" variant="primary" icon="plus-circle" :href="$createUrl" target="_blank">Event anlegen</flux:button>
                            @elseif ($eventUrl)
                                <flux:button size="sm" icon="arrow-top-right-on-square" :href="$eventUrl" target="_blank">Ereignis öffnen</flux:button>
                            @endif
                        </div>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                        <flux:badge size="sm" :color="$langColors[$entry->lang] ?? 'zinc'" inset="top bottom">{{ InfosystemSupport::LANGUAGES[$entry->lang] ?? strtoupper((string) $entry->lang) }}</flux:badge>
                        @if ($entry->is_published)
                            <flux:badge size="sm" color="green" inset="top bottom">Veröffentlicht {{ $entry->published_at?->format('d.m.Y H:i') }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc" inset="top bottom">Nicht veröffentlicht</flux:badge>
                        @endif
                        @foreach (collect($entry->categories ?? [])->map(fn ($category) => is_array($category) ? ($category['name'] ?? null) : $category)->filter() as $category)
                            <flux:badge size="sm" color="sky" inset="top bottom">{{ $category }}</flux:badge>
                        @endforeach
                        @if ($entry->tagtext)
                            <flux:badge size="sm" color="zinc" inset="top bottom">{{ $entry->tagtext }}</flux:badge>
                        @endif
                        @unless ($entry->active)
                            <flux:badge size="sm" color="red" inset="top bottom">Inaktiv</flux:badge>
                        @endunless
                        @if ($entry->archive)
                            <flux:badge size="sm" color="amber" inset="top bottom">Archiviert</flux:badge>
                        @endif
                    </div>

                    <p class="mt-3 line-clamp-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $entry->content)), 400) }}</p>

                    <div class="mt-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pt-4 text-xs text-zinc-500">
                        <span class="tabular-nums">abgerufen {{ $entry->created_at?->format('d.m.Y H:i') ?? '–' }}</span>
                        @if ($entry->api_created_at)
                            <span class="tabular-nums">im Infosystem seit {{ $entry->api_created_at->format('d.m.Y H:i') }}</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    {{-- Rueckfrage: aktuelle Daten abrufen --}}
    <flux:modal name="infosystem-sync" class="md:w-[30rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Daten synchronisieren</flux:heading>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Möchten Sie die aktuellen Daten aus dem externen Infosystem abrufen? Abgerufen wird die erste Seite mit den neuesten Einträgen; bereits vorhandene Einträge werden aktualisiert.
                </p>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" icon="arrow-path" wire:click="sync">Ja, synchronisieren</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Rueckfrage: die letzten 100 Eintraege abrufen --}}
    <flux:modal name="infosystem-sync-100" class="md:w-[30rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">{{ Infosystem::SYNC_LIMIT }} Einträge abrufen</flux:heading>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Möchten Sie die letzten {{ Infosystem::SYNC_LIMIT }} Einträge aus dem externen Infosystem abrufen? Dies kann einige Minuten dauern.
                </p>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" icon="arrow-down-tray" wire:click="syncLast100">Ja, {{ Infosystem::SYNC_LIMIT }} Einträge abrufen</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
