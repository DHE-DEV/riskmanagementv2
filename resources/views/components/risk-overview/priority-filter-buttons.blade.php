@props(['callback' => 'applyFilters()'])

<div class="mb-4">
    <label class="text-xs font-medium text-gray-700 mb-2 block">Risikostufe</label>
    <div class="grid grid-cols-2 gap-2">
        @php
            $buttons = [
                ['value' => 'null', 'label' => 'Alle', 'color' => 'blue', 'dot' => false, 'span' => false],
                ['value' => "'high'", 'label' => 'Hoch', 'color' => 'red', 'dot' => true, 'span' => false],
                ['value' => "'medium'", 'label' => 'Mittel', 'color' => 'orange', 'dot' => true, 'span' => false],
                ['value' => "'low'", 'label' => 'Niedrig', 'color' => 'green', 'dot' => true, 'span' => false],
                ['value' => "'info'", 'label' => 'Information', 'color' => 'blue', 'dot' => true, 'span' => true],
            ];
        @endphp
        @foreach($buttons as $btn)
            <button @click="filters.priority = {{ $btn['value'] }}; {{ $callback }}"
                class="px-3 py-2 text-xs rounded-lg border transition-colors {{ $btn['dot'] ? 'flex items-center justify-center gap-1' : '' }} {{ $btn['span'] ? 'col-span-2' : '' }}"
                :class="filters.priority === {{ $btn['value'] }} ? 'bg-{{ $btn['color'] }}-50 border-{{ $btn['color'] }}-500 text-{{ $btn['color'] }}-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                @if($btn['dot'])
                    <span class="w-2 h-2 rounded-full bg-{{ $btn['color'] }}-500"></span>
                @endif
                {{ $btn['label'] }}
            </button>
        @endforeach
    </div>
</div>
