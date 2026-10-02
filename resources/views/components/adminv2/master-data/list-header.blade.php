{{-- Kopf einer Stammdaten-Liste: Titel, Beschreibung und "Neu"-Schaltflaeche. --}}
@props([
    'section',
    'createLabel',
])

@php
    $definition = \App\Support\AdminV2\MasterData::sections()[$section];
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <flux:heading size="xl" level="1">{{ $definition['label'] }}</flux:heading>
        <flux:subheading>Stammdaten · {{ $definition['description'] }}</flux:subheading>
    </div>

    <div class="flex items-center gap-2">
        {{ $slot }}
        <flux:button variant="primary" icon="plus" :href="route($definition['routes'].'.create')">{{ $createLabel }}</flux:button>
    </div>
</div>
