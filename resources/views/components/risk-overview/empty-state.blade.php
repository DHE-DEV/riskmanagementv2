@props([
    'icon' => 'fa-regular fa-circle-info',
    'title' => '',
    'message' => '',
    'variant' => 'card',
])

@if($variant === 'centered')
<div {{ $attributes->merge(['class' => 'flex-1 flex items-center justify-center bg-gray-50']) }}>
    <div class="text-center">
        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            <i class="{{ $icon }} text-4xl text-gray-400 mb-3"></i>
            <h3 class="font-semibold text-gray-700">{{ $title }}</h3>
            @if($message)
                <p class="text-sm text-gray-500 mt-1">{{ $message }}</p>
            @endif
        @endif
    </div>
</div>
@else
<div {{ $attributes->merge(['class' => 'bg-gray-50 p-6 rounded-lg border border-dashed border-gray-300 text-center']) }}>
    @if($slot->isNotEmpty())
        {{ $slot }}
    @else
        <i class="{{ $icon }} text-4xl text-gray-400 mb-3"></i>
        <h3 class="font-semibold text-gray-700">{{ $title }}</h3>
        @if($message)
            <p class="text-sm text-gray-500 mt-1">{{ $message }}</p>
        @endif
    @endif
</div>
@endif
