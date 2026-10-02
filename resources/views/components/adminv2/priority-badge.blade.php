@props(['priority'])

@php
    $color = ['high' => 'red', 'medium' => 'amber', 'low' => 'sky', 'info' => 'zinc'][$priority] ?? 'zinc';
    $label = \App\Models\CustomEvent::getPriorityOptions()[$priority] ?? $priority;
@endphp

<span class="inline-flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-300">
    <span @class([
        'size-2 rounded-full',
        'bg-red-500' => $color === 'red',
        'bg-amber-500' => $color === 'amber',
        'bg-sky-500' => $color === 'sky',
        'bg-zinc-400' => $color === 'zinc',
    ])></span>
    {{ $label }}
</span>
