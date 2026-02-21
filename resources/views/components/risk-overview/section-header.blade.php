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
        @if($maximizeSection)
            <button @click="{{ $toggleMethod }}('{{ $maximizeSection }}')"
                class="p-1.5 {{ $hoverColor }} rounded transition-colors"
                :title="{{ $maximizeVar }} === '{{ $maximizeSection }}' ? 'Ansicht wiederherstellen' : 'Maximieren'">
                <i class="fa-regular text-xs transition-all"
                    :class="{{ $maximizeVar }} === '{{ $maximizeSection }}' ? 'fa-compress' : 'fa-expand'"></i>
            </button>
        @endif
    </h3>
</div>
