{{--
    Einfachauswahl als Aufklappliste mit Suchfeld – das Gegenstueck zu
    x-adminv2.multi-select fuer genau einen Wert.

    - options: [['value' => …, 'label' => …, 'code' => … (optional)], …]
    - model: Name der Livewire-Eigenschaft (Zeichenkette, '' = nichts gewaehlt)
    - selected: aktueller Wert
    - live: Aenderung sofort an den Server melden
    - clearable: Auswahl laesst sich aufheben
    Haben die Optionen einen Code, sucht eine Eingabe bis zwei Zeichen nur im
    Code, ab drei Zeichen nur im Namen. Enter waehlt den ersten Treffer.
--}}
@props([
    'options' => [],
    'model',
    'selected' => '',
    'placeholder' => 'Bitte wählen …',
    'searchPlaceholder' => 'Suchen …',
    'label' => null,
    'live' => false,
    'clearable' => false,
    'disabled' => false,
    'emptyText' => 'Keine Einträge vorhanden.',
])

@php
    $selected = (string) $selected;
    $current = collect($options)->first(fn ($option) => (string) $option['value'] === $selected);
    $wireModel = $live ? 'wire:model.live' : 'wire:model';
@endphp

<div
    {{ $attributes->class('relative') }}
    x-data="{
        open: false,
        query: '',
        hasCodes: @js(collect($options)->contains(fn ($option) => filled($option['code'] ?? null))),
        rank(el) {
            const q = this.query.trim().toLowerCase();
            if (q === '') return 1;
            if (this.hasCodes && q.length <= 2) {
                if (el.dataset.code === q) return 0;
                return el.dataset.code.startsWith(q) ? 1 : -1;
            }
            if (el.dataset.name.startsWith(q)) return 0;
            return el.dataset.name.includes(q) ? 1 : -1;
        },
        hasMatches() {
            return [...this.$refs.list.querySelectorAll('label[data-name]')].some((el) => this.rank(el) >= 0);
        },
        pickFirst() {
            const hits = [...this.$refs.list.querySelectorAll('label[data-name]')]
                .map((el) => ({ el, rank: this.rank(el) }))
                .filter((hit) => hit.rank >= 0)
                .sort((a, b) => a.rank - b.rank);
            if (hits.length && this.query.trim() !== '') {
                hits[0].el.querySelector('input').click();
            }
        },
    }"
    x-init="$watch('open', (value) => { if (value) { setTimeout(() => $refs.search?.focus(), 30) } else { query = '' } })"
    x-on:click.outside="open = false"
    x-on:keydown.escape="open = false"
>
    <button
        type="button"
        x-on:click="open = ! open"
        :aria-expanded="open"
        aria-haspopup="listbox"
        @if ($label) aria-label="{{ $label }}" @endif
        @disabled($disabled)
        @class([
            'flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-3 text-start text-sm shadow-xs transition disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-white/10',
            'text-zinc-400' => ! $current,
            'text-zinc-900 dark:text-white' => $current,
        ])
    >
        <span class="truncate">{{ $current['label'] ?? $placeholder }}</span>
        <flux:icon.chevron-up-down variant="mini" class="shrink-0 text-zinc-400" />
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute start-0 z-30 mt-1 w-full min-w-64 max-w-[calc(100vw-2rem)] rounded-xl border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
    >
        @if (count($options) > 8)
            <div class="p-1">
                <input
                    type="search"
                    x-ref="search"
                    x-model="query"
                    x-on:keydown.enter.prevent="pickFirst()"
                    placeholder="{{ $searchPlaceholder }}"
                    autocomplete="off"
                    class="h-9 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none placeholder:text-zinc-400 focus:border-[var(--color-accent)] dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                />
            </div>
        @endif

        <div x-ref="list" role="listbox" class="flex max-h-72 flex-col overflow-y-auto">
            @if ($clearable && $current)
                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" x-show="query.trim() === ''">
                    <input type="radio" {{ $wireModel }}="{{ $model }}" value="" class="sr-only" x-on:change="open = false" />
                    <flux:icon.x-mark variant="micro" class="shrink-0" />
                    <span>Auswahl aufheben</span>
                </label>
            @endif

            @forelse ($options as $option)
                <label
                    wire:key="{{ $model }}-{{ $option['value'] }}"
                    data-name="{{ mb_strtolower($option['label']) }}"
                    data-code="{{ mb_strtolower((string) ($option['code'] ?? '')) }}"
                    x-show="rank($el) >= 0"
                    :style="{ order: query.trim() === '' ? '' : rank($el) }"
                    class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm text-zinc-800 hover:bg-zinc-100 has-[:checked]:bg-zinc-100 has-[:checked]:font-medium dark:text-zinc-200 dark:hover:bg-zinc-800 dark:has-[:checked]:bg-zinc-800"
                >
                    <input type="radio" {{ $wireModel }}="{{ $model }}" value="{{ $option['value'] }}" class="sr-only" x-on:change="open = false" />
                    <span class="min-w-0 flex-1 truncate">{{ $option['label'] }}</span>
                    @if (filled($option['code'] ?? null))
                        <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $option['code'] }}</span>
                    @endif
                </label>
            @empty
                <p class="px-2.5 py-2 text-sm text-zinc-500">{{ $emptyText }}</p>
            @endforelse
        </div>

        <p x-show="query.trim() !== '' && ! hasMatches()" x-cloak class="px-2.5 py-2 text-sm text-zinc-500">
            Kein Treffer für „<span x-text="query.trim()"></span>“.
        </p>
    </div>
</div>
