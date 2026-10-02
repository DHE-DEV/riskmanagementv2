{{--
    Mehrfachauswahl als Aufklappliste.

    - options: [['value' => …, 'label' => …, 'code' => … (optional)], …]
    - model: Name der Livewire-Eigenschaft (Array)
    - selected: aktuell gewaehlte Werte
    - searchable: Suchfeld mit Autovervollstaendigung. Haben die Optionen einen Code,
      sucht eine Eingabe bis zwei Zeichen nur im Code, ab drei Zeichen nur im Namen.
      Enter waehlt den ersten Treffer.
--}}
@props([
    'options' => [],
    'model',
    'selected' => [],
    'allLabel' => 'Alle',
    'noun' => 'ausgewählt',
    'searchable' => false,
    'searchPlaceholder' => 'Suchen …',
    'label' => null,
    // Kante, an der die Liste ausgerichtet wird – "end" fuer Felder am rechten Rand.
    'align' => 'start',
])

@php
    $selected = array_map('strval', $selected);
    $chosen = collect($options)->filter(fn ($option) => in_array((string) $option['value'], $selected, true));

    $summary = match (true) {
        $chosen->isEmpty() => $allLabel,
        $chosen->count() <= 2 => $chosen->pluck('label')->implode(', '),
        default => $chosen->count().' '.$noun,
    };
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
            // Bis zwei Zeichen wird nur der Code durchsucht, ab drei nur der Name.
            if (this.hasCodes && q.length <= 2) {
                if (el.dataset.code === q) return 0;
                return el.dataset.code.startsWith(q) ? 1 : -1;
            }
            if (el.dataset.name.startsWith(q)) return 0;
            return el.dataset.name.includes(q) ? 1 : -1;
        },
        hasMatches() {
            return [...this.$refs.list.querySelectorAll('label')].some((el) => this.rank(el) >= 0);
        },
        pickFirst() {
            const hits = [...this.$refs.list.querySelectorAll('label')]
                .map((el) => ({ el, rank: this.rank(el) }))
                .filter((hit) => hit.rank >= 0)
                .sort((a, b) => a.rank - b.rank);
            if (hits.length && this.query.trim() !== '') {
                hits[0].el.querySelector('input').click();
                this.query = '';
            }
        },
    }"
    {{-- Das Suchfeld bekommt den Fokus, sobald die Liste sichtbar ist. --}}
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
        @class([
            'flex h-10 w-full items-center justify-between gap-2 rounded-lg border bg-white px-3 text-start text-sm shadow-xs transition dark:bg-white/10',
            'border-zinc-200 border-b-zinc-300/80 text-zinc-700 dark:border-white/10 dark:text-zinc-300' => $chosen->isEmpty(),
            'border-[var(--color-accent)] text-zinc-900 dark:text-white' => $chosen->isNotEmpty(),
        ])
    >
        <span class="truncate">{{ $summary }}</span>
        <flux:icon.chevron-up-down variant="mini" class="shrink-0 text-zinc-400" />
    </button>

    <div
        x-show="open"
        x-cloak
        @class([
            'absolute z-30 mt-1 w-72 max-w-[calc(100vw-2rem)] rounded-xl border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900',
            'start-0' => $align !== 'end',
            'end-0' => $align === 'end',
        ])
    >
        @if ($searchable)
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

        <div x-ref="list" role="listbox" aria-multiselectable="true" class="flex max-h-72 flex-col overflow-y-auto">
            @foreach ($options as $option)
                <label
                    wire:key="{{ $model }}-{{ $option['value'] }}"
                    data-name="{{ mb_strtolower($option['label']) }}"
                    data-code="{{ mb_strtolower((string) ($option['code'] ?? '')) }}"
                    x-show="rank($el) >= 0"
                    {{-- Ohne Suchbegriff stehen die gewaehlten Eintraege oben (vom Server gesetzt),
                         mit Suchbegriff bestimmt die Trefferguete die Reihenfolge. --}}
                    {{-- Objekt-Schreibweise: sie setzt nur "order" und laesst das display von x-show stehen. --}}
                    :style="{ order: query.trim() === '' ? '' : rank($el) }"
                    @class([
                        'flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm text-zinc-800 hover:bg-zinc-100 has-[:checked]:font-medium dark:text-zinc-200 dark:hover:bg-zinc-800',
                        'order-0' => in_array((string) $option['value'], $selected, true),
                        'order-1' => ! in_array((string) $option['value'], $selected, true),
                    ])
                >
                    <input type="checkbox" wire:model.live="{{ $model }}" value="{{ $option['value'] }}" class="size-4 shrink-0 rounded border-zinc-300 accent-[var(--color-accent)]" />
                    <span class="min-w-0 flex-1 truncate">{{ $option['label'] }}</span>
                    @if (filled($option['code'] ?? null))
                        <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $option['code'] }}</span>
                    @endif
                </label>
            @endforeach
        </div>

        @if ($searchable)
            <p x-show="query.trim() !== '' && ! hasMatches()" x-cloak class="px-2.5 py-2 text-sm text-zinc-500">
                Kein Treffer für „<span x-text="query.trim()"></span>“.
            </p>
        @endif

        @if ($chosen->isNotEmpty())
            <div class="mt-1 border-t border-zinc-100 p-1 dark:border-zinc-800">
                <button type="button" wire:click="$set('{{ $model }}', [])" class="w-full rounded-lg px-2.5 py-1.5 text-start text-sm text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                    Auswahl aufheben ({{ $chosen->count() }})
                </button>
            </div>
        @endif
    </div>
</div>
