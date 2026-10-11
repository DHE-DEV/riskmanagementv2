{{-- Beschreibung, Reiseinfos und Fakten (RegionInfo) – fuer Regionen und Teilgebiete. Erwartet die Variablen des Editors und $labels. --}}
@php
    use App\Models\CustomEvent;
    use App\Support\AdminV2\RegionInfo;
@endphp
<x-adminv2.card :heading="$labels['description']" :description="$labels['description_hint']" collapsible :collapse-key="$labels['key'].'-description'">
    <x-slot:actions>
        <flux:modal.trigger name="translate-description">
            <flux:button size="sm" variant="ghost" icon="language">Übersetzen</flux:button>
        </flux:modal.trigger>
        @if ($record)
            <flux:modal.trigger name="region-info-ai">
                <flux:button size="sm" variant="ghost" icon="sparkles" :disabled="(bool) $suggestRunId">Mit KI vorbefüllen</flux:button>
            </flux:modal.trigger>
        @endif
    </x-slot:actions>
    <div x-data="{ locale: @js($sourceLocale) }" class="flex flex-col gap-5">
        @if ($suggestRunId)
            <div wire:poll.3s="checkSuggestion" class="flex items-center gap-3 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
                <flux:icon.loading class="size-4 shrink-0" />
                <span class="flex-1">Die KI schreibt {{ $labels['ai_what'] }} – das dauert meist ein bis zwei Minuten. Die Texte erscheinen hier automatisch und werden erst mit „Speichern“ übernommen.</span>
                <flux:button size="xs" variant="ghost" wire:click="cancelSuggestion">Abbrechen</flux:button>
            </div>
        @endif
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
            <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif wire:key="region-description-{{ $locale }}" class="flex flex-col gap-5">
                @foreach (RegionInfo::DESCRIPTION_TEXTS as $field => [$label, $kind, $hint, $rows])
                    <flux:field>
                        <flux:label>{{ $label }} ({{ strtoupper($locale) }})</flux:label>
                        <flux:description>{{ $hint }}</flux:description>
                        @if ($kind === 'tags')
                            <flux:input wire:model="info.texts.{{ $field }}.{{ $locale }}" />
                        @else
                            <flux:textarea wire:model="info.texts.{{ $field }}.{{ $locale }}" rows="{{ $rows }}" />
                        @endif
                        <flux:error name="info.texts.{{ $field }}.{{ $locale }}" />
                    </flux:field>
                @endforeach
            </div>
        @endforeach
    </div>
</x-adminv2.card>

<x-adminv2.card :heading="$labels['travel']" :description="$labels['travel_hint']" collapsible :collapse-key="$labels['key'].'-travel'">
    <x-slot:actions>
        <flux:modal.trigger name="translate-travel">
            <flux:button size="sm" variant="ghost" icon="language">Übersetzen</flux:button>
        </flux:modal.trigger>
    </x-slot:actions>
    <div x-data="{ locale: @js($sourceLocale) }" class="flex flex-col gap-5">
        <flux:field>
            <flux:label>Beste Reisemonate</flux:label>
            <flux:description>{{ $labels['months_hint'] }}</flux:description>
            <div class="mt-1 flex flex-wrap gap-1.5">
                @foreach (RegionInfo::MONTHS as $month => $monthLabel)
                    @php $on = in_array($month, $bestMonths, true); @endphp
                    <button type="button" wire:click="toggleBestMonth({{ $month }})" aria-pressed="{{ $on ? 'true' : 'false' }}" class="w-12 rounded-md border px-2 py-1 text-sm font-medium transition {{ $on ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-zinc-200 text-zinc-600 hover:border-zinc-400 dark:border-zinc-700 dark:text-zinc-300' }}">{{ $monthLabel }}</button>
                @endforeach
            </div>
        </flux:field>
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
            <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif wire:key="region-travel-{{ $locale }}" class="flex flex-col gap-5">
                @foreach (RegionInfo::TRAVEL_TEXTS as $field => [$label, $kind, $hint, $rows])
                    <flux:field>
                        <flux:label>{{ $label }} ({{ strtoupper($locale) }})</flux:label>
                        <flux:description>{{ $hint }}</flux:description>
                        @if ($kind === 'tags')
                            <flux:input wire:model="info.texts.{{ $field }}.{{ $locale }}" />
                        @else
                            <flux:textarea wire:model="info.texts.{{ $field }}.{{ $locale }}" rows="{{ $rows }}" />
                        @endif
                        <flux:error name="info.texts.{{ $field }}.{{ $locale }}" />
                    </flux:field>
                @endforeach
            </div>
        @endforeach
    </div>
</x-adminv2.card>

<x-adminv2.card :heading="$labels['facts']" :description="$labels['facts_hint']" collapsible :collapse-key="$labels['key'].'-facts'">
    <div class="grid gap-5 sm:grid-cols-2">
        <flux:field>
            <flux:label>Einwohner</flux:label>
            <flux:input wire:model="info.population" inputmode="numeric" placeholder="z. B. 4851851" />
            <flux:error name="info.population" />
        </flux:field>
        <flux:field>
            <flux:label>Fläche (km²)</flux:label>
            <flux:input wire:model="info.area_km2" inputmode="numeric" placeholder="z. B. 18345" />
            <flux:error name="info.area_km2" />
        </flux:field>
        <flux:field class="min-w-0 sm:col-span-2">
            <flux:label>Zeitzone</flux:label>
            <x-adminv2.search-select :options="$timezoneOptions" model="info.timezone" :selected="(string) ($info['timezone'] ?? '')" :placeholder="$countryTimezone ? 'Wie das Land ('.$countryTimezone.')' : 'Wie das Land'" search-placeholder="Zeitzone suchen …" label="Zeitzone" clearable />
            <flux:description>Leer = wie das Land{{ $countryTimezone ? ' ('.$countryTimezone.')' : '' }}.</flux:description>
            <flux:error name="info.timezone" />
        </flux:field>
    </div>
</x-adminv2.card>

