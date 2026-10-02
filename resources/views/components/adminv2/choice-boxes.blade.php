{{--
    Einfachauswahl als Boxen – gleiche Darstellung wie die Event-Typen im
    Ereignis-Formular: die gewaehlte Box ist markiert und traegt einen Haken.

    - model: Name der Livewire-Eigenschaft
    - options: [['value' => …, 'label' => …, 'dot' => 'bg-red-500' (optional)], …]
    - live: Aenderung sofort an den Server melden
--}}
@props([
    'label' => null,
    'model',
    'options' => [],
    'live' => false,
])

@php
    // Jede Box ist so breit, dass die laengste Bezeichnung samt Haken in eine Zeile passt.
    $longest = max(6, (int) collect($options)->max(fn ($option) => mb_strlen((string) $option['label'])));
    $hasDots = collect($options)->contains(fn ($option) => filled($option['dot'] ?? null));
    $minWidth = 'calc('.$longest.'ch + '.($hasDots ? '5rem' : '4rem').')';
@endphp

<flux:field {{ $attributes }}>
    @if ($label)
        <flux:label>{{ $label }}</flux:label>
    @endif

    <div class="grid gap-2 text-sm" role="radiogroup" @if ($label) aria-label="{{ $label }}" @endif style="grid-template-columns: repeat(auto-fill, minmax(min({{ $minWidth }}, 100%), 1fr));">
        @foreach ($options as $option)
            {{-- Das Radio bleibt fuer Tastatur und Screenreader erhalten,
                 sichtbar ist nur die Markierung der gewaehlten Box. --}}
            <label
                wire:key="{{ $model }}-{{ $option['value'] }}"
                class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm text-zinc-700 transition hover:border-zinc-300 has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)]/10 has-[:checked]:text-[var(--color-accent)] has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[var(--color-accent)]/40 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600"
            >
                <input
                    type="radio"
                    name="{{ $model }}"
                    @if ($live) wire:model.live="{{ $model }}" @else wire:model="{{ $model }}" @endif
                    value="{{ $option['value'] }}"
                    class="peer sr-only"
                />
                @if (filled($option['dot'] ?? null))
                    <span class="size-2 shrink-0 rounded-full {{ $option['dot'] }}" aria-hidden="true"></span>
                @endif
                <span class="min-w-0 flex-1 font-medium break-words hyphens-auto" lang="de">{{ $option['label'] }}</span>
                <flux:icon.check variant="mini" class="invisible shrink-0 peer-checked:visible" />
            </label>
        @endforeach
    </div>

    <flux:error name="{{ $model }}" />
</flux:field>
