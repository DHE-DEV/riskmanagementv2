@php
    $lat = $getViewData()['lat'] ?? null;
    $lng = $getViewData()['lng'] ?? null;
    $dashboardUrl = $lat && $lng ? "/dashboard?lat={$lat}&lng={$lng}&zoom=12&marker=true" : '#';
@endphp

@if($lat && $lng)
    <div class="fi-fo-field-wrp mt-2">
        <a href="{{ $dashboardUrl }}"
           target="_blank"
           class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-primary-600 text-white hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400 focus-visible:ring-primary-500/50">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
            </svg>
            <span class="fi-btn-label">Auf Karte anzeigen</span>
        </a>
    </div>
@endif