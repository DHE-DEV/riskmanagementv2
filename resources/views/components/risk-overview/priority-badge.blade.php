@props([
    'priority' => 'event.priority',
    'variant' => 'pill',
    'showLabel' => true,
    'lowColor' => 'green',
])

@php
    $p = $priority;
    $lowBg = $lowColor === 'yellow' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700';
    $lowDot = $lowColor === 'yellow' ? 'bg-yellow-500' : 'bg-green-500';
@endphp

@if($variant === 'dot')
<span {{ $attributes->merge(['class' => 'rounded-full']) }}
    :class="{
        'bg-red-500': {{ $p }} === 'high',
        'bg-orange-500': {{ $p }} === 'medium',
        '{{ $lowDot }}': {{ $p }} === 'low',
        'bg-blue-500': {{ $p }} === 'info'
    }"
></span>
@else
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded text-xs font-medium px-2 py-0.5']) }}
    :class="{
        'bg-red-100 text-red-700': {{ $p }} === 'high',
        'bg-orange-100 text-orange-700': {{ $p }} === 'medium',
        '{{ $lowBg }}': {{ $p }} === 'low',
        'bg-blue-100 text-blue-700': {{ $p }} === 'info'
    }"
    @if($showLabel)
    x-text="{{ $p }} === 'high' ? 'Hoch' : {{ $p }} === 'medium' ? 'Mittel' : {{ $p }} === 'low' ? 'Niedrig' : 'Information'"
    @endif
>{{ $slot }}</span>
@endif
