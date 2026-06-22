@php
    $switcherLocales = \App\Models\CustomEvent::translationLocales();
    $currentLocale = strtolower(app()->getLocale());
    if (! in_array($currentLocale, $switcherLocales, true)) {
        $currentLocale = \App\Models\CustomEvent::sourceLocale();
    }
@endphp

@if(config('app.event_language_switcher_enabled', false) && count($switcherLocales) > 1)
    <div class="border-t border-gray-200 my-2"></div>
    <div class="px-4 pt-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wide">Sprache</div>

    @foreach($switcherLocales as $loc)
        <a
            href="{{ request()->fullUrlWithQuery(['lang' => $loc]) }}"
            class="flex items-center gap-4 px-4 py-3 {{ $loc === $currentLocale ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-100' }}"
        >
            <span class="w-6 text-center text-lg leading-none">{{ \App\Models\CustomEvent::localeFlag($loc) }}</span>
            <span>{{ \App\Models\CustomEvent::localeLabel($loc, false) }}</span>
            @if($loc === $currentLocale)
                <i class="fa-solid fa-check ml-auto text-xs" aria-hidden="true"></i>
            @endif
        </a>
    @endforeach
@endif
