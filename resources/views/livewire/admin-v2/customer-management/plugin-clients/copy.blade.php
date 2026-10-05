{{--
    Kleine Schaltflaeche "in die Zwischenablage kopieren".

    - text: der zu kopierende Text
    - label: Bezeichnung fuer Vorleser und Tooltip, z. B. "E-Mail kopieren"
--}}
<button
    type="button"
    x-data="{ copied: false }"
    x-on:click.stop.prevent="navigator.clipboard?.writeText(@js($text)); copied = true; setTimeout(() => copied = false, 1500)"
    class="relative z-10 inline-flex shrink-0 items-center rounded-md p-0.5 align-middle text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
    title="{{ $label }}"
    aria-label="{{ $label }}"
>
    <flux:icon.clipboard-document variant="micro" x-show="! copied" />
    <flux:icon.check variant="micro" class="text-green-600" x-show="copied" x-cloak />
</button>
