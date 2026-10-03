{{-- Schaltflaeche "KI" an einem Abschnitt – oeffnet die KI-Pruefungen dieses Abschnitts (Trait RunsAiChecks). --}}
@props([
    'section',
    'label' => 'KI',
    'size' => 'sm',
])

<flux:button :size="$size" variant="ghost" icon="sparkles" wire:click="openAiCheck('{{ $section }}')" {{ $attributes }}>{{ $label }}</flux:button>
