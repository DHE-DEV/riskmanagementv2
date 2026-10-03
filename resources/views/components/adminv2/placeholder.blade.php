{{--
    Platzhalter fuer einen Prompt, z. B. {name}: Ein Klick kopiert ihn in die
    Zwischenablage; der Tooltip nennt die Bedeutung.

    - name: Schluessel ohne Klammern
    - label: Bedeutung (Tooltip)
--}}
@props([
    'name',
    'label' => null,
])

@php
    $text = '{'.$name.'}';
    $tooltip = $label ? $label.' – klicken zum Kopieren' : 'Klicken zum Kopieren';
    $classes = 'relative cursor-pointer rounded bg-zinc-100 px-1 py-0.5 font-mono text-[0.7rem] text-zinc-700 transition hover:bg-[var(--color-accent)]/15 hover:text-[var(--color-accent)] dark:bg-zinc-800 dark:text-zinc-300';
@endphp

<span x-data="{ copied: false, copy() { navigator.clipboard?.writeText(this.$refs.text.textContent); this.copied = true; setTimeout(() => this.copied = false, 1200) } }" class="inline-block align-baseline [&_ui-tooltip]:inline-block">
    <flux:tooltip :content="$tooltip">
        <button type="button" x-on:click="copy()" {{ $attributes->class($classes) }} aria-label="{{ $text }} kopieren">
            <span x-ref="text" :class="copied && 'invisible'">{{ $text }}</span>
            <span x-show="copied" x-cloak class="absolute inset-0 flex items-center justify-center font-sans text-[0.65rem] font-medium text-[var(--color-accent)]">kopiert</span>
        </button>
    </flux:tooltip>
</span>
