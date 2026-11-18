@props([
    'icon' => 'fa-regular fa-circle-info',
    'iconColor' => 'text-orange-500',
    'title' => '',
    'countExpression' => '',
    'maximizeSection' => '',
    'maximizeVar' => 'maximizedSection',
    'toggleMethod' => 'toggleMaximize',
    'bgColor' => 'bg-gray-50',
    'borderColor' => 'border-gray-200',
    'hoverColor' => 'hover:bg-gray-200',
    'selectScope' => '',
])

<div class="px-4 py-3 {{ $bgColor }} border-b {{ $borderColor }} flex-shrink-0">
    <h3 class="text-sm font-semibold text-gray-900 flex items-center justify-between">
        <span class="flex items-center gap-2">
            <i class="{{ $icon }} {{ $iconColor }}"></i>
            {{ $title }}
            @if($countExpression)
                <span class="text-gray-500 font-normal"
                    x-text="'(' + {{ $countExpression }} + ')'"></span>
            @endif
            {{ $slot }}
        </span>
        <span class="flex items-center gap-1">
        @if($selectScope)
            {{-- Mehrfachauswahl der Ereignisse (Labels für mehrere auf einmal) --}}
            <button type="button"
                @click="selection.mode === 'events' && selection.scope === '{{ $selectScope }}' ? endSelection() : startSelection('events', '{{ $selectScope }}')"
                class="inline-flex items-center gap-1 rounded px-2 py-1 text-[11px] font-medium transition-colors"
                :class="selection.mode === 'events' && selection.scope === '{{ $selectScope }}' ? 'bg-blue-600 text-white' : 'text-gray-600 {{ $hoverColor }}'">
                <i class="fa-regular fa-square-check"></i>
                <span x-text="selection.mode === 'events' && selection.scope === '{{ $selectScope }}' ? 'Auswahl beenden' : 'Mehrere labeln'"></span>
            </button>
        @endif
        @if($maximizeSection)
            <button @click="{{ $toggleMethod }}('{{ $maximizeSection }}')"
                class="p-1.5 {{ $hoverColor }} rounded transition-colors"
                :title="{{ $maximizeVar }} === '{{ $maximizeSection }}' ? 'Ansicht wiederherstellen' : 'Maximieren'">
                <i class="fa-regular text-xs transition-all"
                    :class="{{ $maximizeVar }} === '{{ $maximizeSection }}' ? 'fa-compress' : 'fa-expand'"></i>
            </button>
        @endif
        </span>
    </h3>
</div>
