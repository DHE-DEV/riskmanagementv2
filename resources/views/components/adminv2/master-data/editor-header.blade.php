{{--
    Kopf einer Stammdaten-Bearbeitungsseite: Rueckweg, Titel, Speichern.

    - section: Schluessel des Bereichs (continents, countries, …)
    - title: Ueberschrift
    - record: der geoeffnete Eintrag oder null (neuer Eintrag)
    - subtitle: optionale Zeile unter dem Titel
--}}
@props([
    'section',
    'title',
    'record' => null,
    'subtitle' => null,
])

@php
    $definition = \App\Support\AdminV2\MasterData::sections()[$section];
    $index = route($definition['routes'].'.index');
@endphp

<div class="flex flex-col gap-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ $index }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> {{ $definition['label'] }}
            </a>
            <flux:heading size="xl" level="1" class="mt-1">{{ $title }}</flux:heading>
            @if ($subtitle)
                <flux:subheading>{{ $subtitle }}</flux:subheading>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="ghost" :href="$index">{{ $record ? 'Zur Liste' : 'Abbrechen' }}</flux:button>
            @unless ($record)
                <flux:button wire:click="save(true)" wire:loading.attr="disabled" wire:target="save">Speichern &amp; weitere anlegen</flux:button>
            @endunless
            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save">Speichern</flux:button>
        </div>
    </div>

    @if ($record?->trashed())
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            <span class="inline-flex items-center gap-2">
                <flux:icon.trash variant="mini" />
                Dieser Eintrag liegt seit dem {{ $record->deleted_at->format('d.m.Y H:i') }} im Papierkorb und wird nirgends mehr angeboten.
            </span>
            <flux:button size="sm" icon="arrow-uturn-left" wire:click="restore({{ $record->getKey() }})">Wiederherstellen</flux:button>
        </div>
    @endif
</div>
