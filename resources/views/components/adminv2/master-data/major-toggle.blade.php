{{--
    Stern zum Markieren eines Eintrags als "Major Region" bzw. "Major City"
    des Landes – ein Klick schaltet um.

    - active: ist markiert
    - label: Name des Eintrags (fuer Vorleser)
    - noun: "Major Region" oder "Major City"
--}}
@props([
    'active' => false,
    'label',
    'noun' => 'Major City',
])

<flux:tooltip :content="$active ? $noun.' – Klick hebt die Markierung auf' : 'Als '.$noun.' markieren'">
    <button
        type="button"
        {{ $attributes->class([
            'flex size-7 shrink-0 items-center justify-center rounded-md transition',
            'text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-500/10' => $active,
            'text-zinc-300 hover:bg-zinc-100 hover:text-amber-500 dark:text-zinc-600 dark:hover:bg-zinc-800' => ! $active,
        ]) }}
        aria-pressed="{{ $active ? 'true' : 'false' }}"
        aria-label="{{ $label }}: {{ $active ? $noun.' – Markierung aufheben' : 'als '.$noun.' markieren' }}"
    >
        @if ($active)
            <flux:icon.star variant="solid" class="size-4" />
        @else
            <flux:icon.star variant="outline" class="size-4" />
        @endif
    </button>
</flux:tooltip>
