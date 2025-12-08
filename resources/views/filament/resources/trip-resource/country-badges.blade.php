@php
    $countries = $getRecord()->countries_visited ?? [];
@endphp

<div>
    <p class="fi-in-entry-wrp-label text-sm font-medium leading-6 text-gray-950 dark:text-white" style="margin-bottom: 4px;">
        Besuchte Länder
    </p>
    @if(empty($countries))
        <span class="text-gray-500">-</span>
    @else
        <div class="flex flex-wrap gap-1">
            @foreach($countries as $code)
                @php
                    $country = \App\Models\Country::where('iso_code', $code)->first();
                    $name = $country ? $country->getName('de') : $code;
                @endphp
                <button
                    type="button"
                    wire:click="mountAction('showEntryConditions', @js(['country' => $code]))"
                    class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 cursor-pointer transition-colors"
                    style="background-color: rgb(255 247 237); color: rgb(234 88 12); border-color: rgba(234, 88, 12, 0.1);"
                    title="{{ $name }} - Einreisebestimmungen anzeigen"
                >
                    {{ $code }}
                </button>
            @endforeach
        </div>
    @endif
</div>
