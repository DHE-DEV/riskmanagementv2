@props(['active' => 'dashboard'])

<nav class="navigation flex flex-col items-center justify-between py-4 h-full">
    <!-- Top Buttons -->
    <div class="flex flex-col items-center space-y-6">
        <!-- Menü Button -->
        @if(config('app.navigation_menu_enabled', true))
        <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Menü" onclick="toggleRightContainer()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        @endif

        <!-- Ereignisse Button (nur für Dashboard) -->
        @if(config('app.navigation_events_enabled', true))
        @php
            $isAirportsParam = request()->has('airports') && request()->get('airports') == '1';
            $isEventsActive = $active === 'dashboard' && !$isAirportsParam;
        @endphp
        @if($active === 'dashboard')
        <button class="p-3 {{ $isEventsActive ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors" title="Ereignisse" onclick="showSidebarLiveStatistics()">
            <i class="fa-regular fa-brake-warning text-2xl" aria-hidden="true"></i>
        </button>
        @else
        <a href="{{ route('home') }}" class="p-3 {{ $isEventsActive ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Ereignisse">
            <i class="fa-regular fa-brake-warning text-2xl" aria-hidden="true"></i>
        </a>
        @endif
        @endif

        <!-- Einreisebestimmungen -->
        @if(config('app.navigation_entry_conditions_enabled', true))
        <a href="{{ route('entry-conditions') }}" class="p-3 {{ $active === 'entry-conditions' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Einreisebestimmungen">
            <i class="fa-regular fa-passport text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Buchungsmöglichkeit -->
        @if(config('app.navigation_booking_enabled', true))
        <a href="{{ route('booking') }}" class="p-3 {{ $active === 'booking' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Buchungsmöglichkeit">
            <i class="fa-regular fa-calendar-check text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Flughäfen -->
        @if(config('app.navigation_airports_enabled', true))
            @php
                $isAirportsActive = request()->has('airports') && request()->get('airports') == '1';
            @endphp
            @if($active === 'dashboard')
                <button class="p-3 {{ $isAirportsActive ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors" title="Flughäfen" onclick="createAirportSidebar()">
                    <i class="fa-regular fa-plane text-2xl" aria-hidden="true"></i>
                </button>
            @else
                <a href="/?airports=1" class="p-3 {{ $isAirportsActive ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Flughäfen">
                    <i class="fa-regular fa-plane text-2xl" aria-hidden="true"></i>
                </a>
            @endif
        @endif

        <!-- Filialen & Standorte -->
        @if(config('app.navigation_branches_enabled', true) && auth('customer')->check() && auth('customer')->user()->branch_management_active)
        <a href="{{ route('branches') }}" class="p-3 {{ $active === 'branches' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Filialen & Standorte">
            <i class="fa-regular fa-building text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Meine Reisenden -->
        @if(config('app.navigation_my_travelers_enabled', true) && auth('customer')->check())
        <a href="{{ route('my-travelers') }}" class="p-3 {{ $active === 'my-travelers' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Meine Reisenden">
            <i class="fa-regular fa-users text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Kreuzfahrt -->
        @if(config('app.navigation_cruise_enabled', true))
        <a href="{{ route('cruise') }}" class="p-3 {{ $active === 'cruise' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Kreuzfahrt">
            <i class="fa-regular fa-ship text-2xl" aria-hidden="true"></i>
        </a>
        @endif
    </div>

    <!-- Bottom Buttons -->
    <div class="flex flex-col items-center space-y-3">
        @if($active === 'dashboard' && config('app.navigation_center_map_enabled', true))
        <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Karte zentrieren" onclick="centerMap()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6" fill="currentColor" aria-hidden="true">
                <path d="M320 544C443.7 544 544 443.7 544 320C544 196.3 443.7 96 320 96C196.3 96 96 196.3 96 320C96 325.9 96.2 331.8 96.7 337.6L91.8 339.2C81.9 342.6 73.3 348.1 66.4 355.1C64.8 343.6 64 331.9 64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C308.1 576 296.4 575.2 284.9 573.6C291.9 566.7 297.4 558 300.7 548.2L302.3 543.3C308.1 543.8 314 544 319.9 544zM320 160C408.4 160 480 231.6 480 320C480 407.2 410.2 478.1 323.5 480L334.4 447.2C398.3 440 448 385.8 448 320C448 249.3 390.7 192 320 192C254.2 192 200 241.7 192.8 305.6L160 316.5C161.9 229.8 232.8 160 320 160zM315.3 324.7C319.6 329 321.1 335.3 319.2 341.1L255.2 533.1C253 539.6 246.9 544 240 544C233.1 544 227 539.6 224.8 533.1L201 461.6L107.3 555.3C101.1 561.5 90.9 561.5 84.7 555.3C78.5 549.1 78.5 538.9 84.7 532.7L178.4 439L107 415.2C100.4 413 96 406.9 96 400C96 393.1 100.4 387 106.9 384.8L298.9 320.8C304.6 318.9 311 320.4 315.3 324.7zM162.6 400L213.1 416.8C217.9 418.4 221.6 422.1 223.2 426.9L240 477.4L278.7 361.3L162.6 400z"></path>
            </svg>
        </button>
        @endif

    </div>
</nav>
