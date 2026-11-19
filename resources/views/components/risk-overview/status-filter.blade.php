{{--
    Status-Filter (Travel Alert): Alle | Offen | Erledigt.

    - scope:   "trips" (Reiter Reisen, mit Anzahl) oder "events" (Reiter Ereignisse)
    - compact: ohne Überschrift, z. B. direkt über der Liste
--}}
@props(['scope' => 'trips', 'compact' => false])

@php
    $model = $scope === 'events' ? 'filters.eventStatus' : 'filters.tripStatus';
    $options = [
        ['value' => 'all', 'label' => 'Alle'],
        ['value' => 'open', 'label' => 'Offen'],
        ['value' => 'done', 'label' => 'Erledigt'],
    ];
@endphp

<div {{ $attributes->merge(['class' => $compact ? '' : 'mb-4']) }}>
    @unless ($compact)
        <span class="mb-2 block text-xs font-medium text-gray-700">Status</span>
    @endunless
    <div class="grid grid-cols-3 rounded-lg border border-gray-200 bg-gray-50 p-0.5" role="group" aria-label="Status">
        @foreach ($options as $option)
            <button type="button" @click="{{ $model }} = '{{ $option['value'] }}'"
                class="inline-flex items-center justify-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium transition-colors"
                :class="{{ $model }} === '{{ $option['value'] }}' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'"
                :aria-pressed="({{ $model }} === '{{ $option['value'] }}').toString()">
                @if ($option['value'] === 'done')
                    <i class="fa-regular fa-circle-check text-[10px]"></i>
                @elseif ($option['value'] === 'open')
                    <i class="fa-regular fa-circle text-[10px]"></i>
                @endif
                <span>{{ $option['label'] }}</span>
                @if ($scope === 'trips')
                    <span class="tabular-nums opacity-70" x-text="tripStatusCounts.{{ $option['value'] }}"></span>
                @endif
            </button>
        @endforeach
    </div>
</div>
