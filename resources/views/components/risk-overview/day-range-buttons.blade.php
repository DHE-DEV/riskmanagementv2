@props([
    'callback' => 'applyFilters()',
    'label' => 'Zeitraum',
    'showExtendedRange' => true,
    'tooltipText' => '',
])

@php
    $row1 = [
        ['days' => 0, 'label' => 'Heute', 'content' => '<i class="fa-regular fa-calendar-day"></i>'],
        ['days' => 7, 'label' => 'Tage', 'content' => '7'],
        ['days' => 14, 'label' => 'Tage', 'content' => '14'],
        ['days' => 30, 'label' => 'Tage', 'content' => '30'],
    ];
    $row2 = [
        ['days' => 90, 'label' => 'Tage', 'content' => '90'],
        ['days' => 180, 'label' => 'Tage', 'content' => '180'],
        ['days' => 360, 'label' => 'Tage', 'content' => '360'],
        ['days' => -1, 'label' => 'Alle', 'content' => '<i class="fa-regular fa-infinity"></i>'],
    ];
@endphp

<div class="mb-4">
    <label class="text-xs font-medium text-gray-700 mb-2 flex items-center gap-1">
        {{ $label }}
        @if($tooltipText)
            <span x-data="{ showTooltip: false }" class="relative inline-flex">
                <button type="button" @click.stop="showTooltip = !showTooltip"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-regular fa-circle-info text-xs"></i>
                </button>
                <div x-show="showTooltip" x-cloak @click.outside="showTooltip = false"
                    class="absolute left-0 top-full mt-1 z-[9999] w-56 p-2 text-[11px] text-gray-600 bg-white border border-gray-200 rounded-lg shadow-lg">
                    {{ $tooltipText }}
                </div>
            </span>
        @endif
    </label>
    <div class="grid grid-cols-4 gap-2 mb-{{ $showExtendedRange ? '1' : '2' }}">
        @foreach($row1 as $btn)
            <button @click="filters.days = {{ $btn['days'] }}; filters.customDateRange = false; {{ $callback }}"
                class="px-2 py-2 text-xs rounded-lg border transition-colors flex flex-col items-center"
                :class="filters.days === {{ $btn['days'] }} && !filters.customDateRange ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                <span class="text-sm font-bold">{!! $btn['content'] !!}</span>
                <span>{{ $btn['label'] }}</span>
            </button>
        @endforeach
    </div>
    @if($showExtendedRange)
        <div class="grid grid-cols-4 gap-2 mb-2">
            @foreach($row2 as $btn)
                <button @click="filters.days = {{ $btn['days'] }}; filters.customDateRange = false; {{ $callback }}"
                    class="px-2 py-2 text-xs rounded-lg border transition-colors flex flex-col items-center"
                    :class="filters.days === {{ $btn['days'] }} && !filters.customDateRange ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                    <span class="text-sm font-bold">{!! $btn['content'] !!}</span>
                    <span>{{ $btn['label'] }}</span>
                </button>
            @endforeach
        </div>
    @endif
    <button @click="filters.customDateRange = !filters.customDateRange"
        class="w-full px-3 py-2 text-xs rounded-lg border transition-colors flex items-center justify-center gap-1"
        :class="filters.customDateRange ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
        <i class="fa-regular fa-calendar-range mr-1"></i>
        Eigener Zeitraum
    </button>
    <div x-show="filters.customDateRange" x-collapse class="mt-2 space-y-2">
        <div>
            <label class="text-xs text-gray-500 block mb-1">Von</label>
            <input type="date" x-model="filters.dateFrom" @change="{{ $callback }}"
                class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Bis</label>
            <input type="date" x-model="filters.dateTo" @change="{{ $callback }}"
                class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>
</div>
