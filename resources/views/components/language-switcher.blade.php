@php
    $switcherLocales = \App\Models\CustomEvent::translationLocales();
    $currentLocale = strtolower(app()->getLocale());
    if (! in_array($currentLocale, $switcherLocales, true)) {
        $currentLocale = \App\Models\CustomEvent::sourceLocale();
    }
@endphp

@if(config('app.event_language_switcher_enabled', false) && count($switcherLocales) > 1)
<style>[x-cloak]{display:none !important;}</style>
<div x-data="{ open: false }" class="relative w-full flex justify-center">
    <button
        type="button"
        @click="open = !open"
        @keydown.escape.window="open = false"
        class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors flex items-center gap-1"
        :aria-expanded="open"
        aria-haspopup="true"
        title="Sprache / Language"
    >
        <i class="fa-regular fa-globe text-2xl" aria-hidden="true"></i>
        <span class="text-xs font-semibold uppercase">{{ $currentLocale }}</span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        @click.outside="open = false"
        class="absolute left-full bottom-0 ml-2 z-50 min-w-[12rem] rounded-lg bg-gray-900 border border-gray-700 shadow-xl py-1"
    >
        @foreach($switcherLocales as $loc)
            <a
                href="{{ request()->fullUrlWithQuery(['lang' => $loc]) }}"
                class="flex items-center gap-2 px-4 py-2 text-sm transition-colors
                    {{ $loc === $currentLocale ? 'bg-white text-black font-semibold' : 'text-white hover:bg-gray-800' }}"
            >
                <span>{{ \App\Models\CustomEvent::localeLabel($loc) }}</span>
                @if($loc === $currentLocale)
                    <i class="fa-solid fa-check ml-auto text-xs" aria-hidden="true"></i>
                @endif
            </a>
        @endforeach
    </div>
</div>
@endif
