{{-- Kopf einer Liste der Kundenverwaltung: Titel, Beschreibung und – falls createLabel gesetzt – "Neu"-Schaltflaeche. --}}
@props([
    'section',
    'createLabel' => null,
])

@php
    $definition = \App\Support\AdminV2\CustomerManagement::sections()[$section];
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <flux:heading size="xl" level="1">{{ $definition['label'] }}</flux:heading>
        <flux:subheading>Kundenverwaltung · {{ $definition['description'] }}</flux:subheading>
    </div>

    <div class="flex items-center gap-2">
        {{ $slot }}
        @if ($createLabel)
            <flux:button variant="primary" icon="plus" :href="route($definition['routes'].'.create')">{{ $createLabel }}</flux:button>
        @endif
    </div>
</div>
