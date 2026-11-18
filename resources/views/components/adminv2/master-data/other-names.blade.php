{{--
    Namen in den weiteren Sprachen aus EVENT_LANGUAGES (außer Deutsch und Englisch)
    samt Knopf, der leere Sprachen – auch Englisch – per DeepL füllt.
    Erwartet den Trait EditsNameTranslations (otherNames, otherNameLocales, translateNames).
--}}
@php
    $locales = $this->otherNameLocales();
@endphp

<div class="flex flex-col gap-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-medium text-zinc-800 dark:text-white">Name in weiteren Sprachen</p>
            <p class="mt-0.5 text-sm text-zinc-500">Leer = englischer Name. Nach einer Änderung des deutschen Namens Felder leeren und neu übersetzen.</p>
        </div>
        <flux:button size="sm" variant="ghost" icon="language" wire:click="translateNames" wire:loading.attr="disabled" wire:target="translateNames">Fehlende per DeepL übersetzen</flux:button>
    </div>

    @if ($locales !== [])
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach ($locales as $locale)
                <div wire:key="other-name-{{ $locale }}">
                    <flux:input wire:model="otherNames.{{ $locale }}" :label="'Name ('.\App\Models\CustomEvent::localeLabel($locale, false).')'" maxlength="255" />
                    <flux:error name="otherNames.{{ $locale }}" />
                </div>
            @endforeach
        </div>
    @endif
</div>
