{{--
    Eingabefeld fuer eine Adresse (Website, Info-URL, Buchungslink, …): vorn
    steht ein Link-Symbol, das die eingetragene Seite in einem neuen Browser-Tab
    oeffnet. Verwendung wie flux:input – wire:model, label, maxlength usw.
    werden durchgereicht.
--}}
@props([
    'placeholder' => 'https://…',
])

@php
    // Hoehe des Eingabefelds je Groesse – daran richtet sich das Symbol aus.
    $height = match ($attributes->get('size')) { 'sm' => 'h-8', 'xs' => 'h-6', default => 'h-10' };
@endphp

<flux:input :placeholder="$placeholder" {{ $attributes }}>
    {{--
        Das Symbol sitzt auf der Hoehe des Eingabefelds, nicht auf der des Rahmens darum:
        in einem Raster neben einem hoeheren Feld wird der Rahmen gestreckt, und das
        Symbol stuende sonst unterhalb der Mitte.
    --}}
    <x-slot name="iconLeading" class="!bottom-auto {{ $height }}">
        {{-- Nur http(s) wird geoeffnet; eine Adresse ohne Protokoll bekommt https:// vorangestellt. --}}
        <button
            type="button"
            x-data
            x-on:click="
                const url = $el.closest('[data-flux-input]').querySelector('input').value.trim();
                const target = /^https?:\/\//i.test(url) ? url : (url === '' || /^[a-z][a-z0-9+.-]*:/i.test(url) ? null : 'https://' + url);
                if (target) window.open(target, '_blank', 'noopener');
            "
            title="Seite in neuem Tab öffnen"
            aria-label="Seite in neuem Tab öffnen"
            class="flex size-6 items-center justify-center rounded-md text-zinc-400 transition hover:bg-zinc-100 hover:text-[var(--color-accent)] dark:hover:bg-white/10"
        >
            <flux:icon.link variant="mini" />
        </button>
    </x-slot>
</flux:input>
