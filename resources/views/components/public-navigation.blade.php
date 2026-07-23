@props(['active' => 'dashboard'])

@php
    $featureService = app(\App\Services\CustomerFeatureService::class);
    $customer = auth('customer')->user();
@endphp

<style>
    .nav-scrollable {
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE/Edge */
    }
    .nav-scrollable::-webkit-scrollbar {
        display: none; /* Chrome/Safari/Opera */
    }
</style>

<nav id="main-navigation" class="navigation flex flex-col items-center py-4 h-full">
    <!-- Top Buttons (scrollable) -->
    <div class="flex-1 overflow-y-auto nav-scrollable w-full">
        <div class="flex flex-col items-center space-y-3">
        <!-- Menü Button -->
        @if(config('app.navigation_hamburger_enabled', true))
        <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Menü" onclick="toggleRightContainer()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        @endif

        <!-- Travel Requirements Service -->
        @php($trsExternalUrl = config('app.navigation_trs_external_url'))
        <a href="{{ $trsExternalUrl ?: route('travel-requirements-service') }}"
           class="p-3 {{ $active === 'travel-requirements-service' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Travel Requirements Service">
            <i class="fa-regular fa-browser text-2xl" aria-hidden="true"></i>
        </a>

        <!-- Einreisebestimmungen -->
        @if($featureService->isFeatureEnabled('navigation_entry_conditions_enabled', $customer))
        <a href="{{ route('entry-conditions') }}" class="p-3 {{ $active === 'entry-conditions' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Einreisebestimmungen">
            <i class="fa-regular fa-passport text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Global Travel Monitor (Dashboard) -->
        @if($featureService->isFeatureEnabled('navigation_events_enabled', $customer))
        <a href="{{ route('global-travel-monitor') }}?v=1" class="p-3 {{ $active === 'dashboard' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Global Travel Monitor">
            <i class="fas fa-globe text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Travel Alert -->
        <a href="{{ route('risk-overview') }}" class="p-3 {{ $active === 'travel-alert' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Travel Alert">
            <i class="fas fa-triangle-exclamation text-2xl" aria-hidden="true"></i>
        </a>

        <!-- Travel Data -->
        @if(config('app.navigation_travel_data_enabled', true) && auth('customer')->check())
        <a href="{{ route('customer.travel-data') }}" class="p-3 {{ $active === 'travel-data' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Travel Data">
            <i class="fas fa-route text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Travel Links -->
        @if(config('app.navigation_travel_links_enabled', true) && auth('customer')->check())
        <a href="{{ route('customer.travel-links') }}" class="p-3 {{ $active === 'customer-travel-links' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Travel Links">
            <i class="fa-regular fa-link text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Buchungsmöglichkeit -->
        @if($featureService->isFeatureEnabled('navigation_booking_enabled', $customer))
        <a href="{{ route('booking') }}" class="p-3 {{ $active === 'booking' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Buchungsmöglichkeit">
            <i class="fa-regular fa-calendar-check text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Flughäfen -->
        @if($featureService->isFeatureEnabled('navigation_airports_enabled', $customer))
        <a href="{{ route('airports') }}" class="p-3 {{ $active === 'airports' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Flughäfen">
            <i class="fa-regular fa-plane text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Filialen & Standorte -->
        @if($featureService->isFeatureEnabled('navigation_branches_enabled', $customer) && auth('customer')->check() && auth('customer')->user()->branch_management_active)
        <a href="{{ route('branches') }}" class="p-3 {{ $active === 'branches' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Filialen & Standorte">
            <i class="fa-regular fa-building text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Meine Reisenden -->
        @if($featureService->isFeatureEnabled('navigation_my_travelers_enabled', $customer) && auth('customer')->check())
        <a href="{{ route('my-travelers') }}" class="p-3 {{ $active === 'my-travelers' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Meine Reisenden">
            <i class="fa-regular fa-users text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Meine Ereignisse -->
        @if(config('app.navigation_customer_events_enabled', true) && $featureService->isFeatureEnabled('navigation_customer_events_enabled', $customer) && auth('customer')->check())
        <a href="{{ route('customer.events') }}" class="p-3 {{ $active === 'customer-events' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Meine Ereignisse">
            <i class="fa-regular fa-calendar-alt text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Kreuzfahrt -->
        @if($featureService->isFeatureEnabled('navigation_cruise_enabled', $customer))
        <a href="{{ route('cruise') }}" class="p-3 {{ $active === 'cruise' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Kreuzfahrt">
            <i class="fa-regular fa-ship text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Business Visum -->
        @if($featureService->isFeatureEnabled('navigation_business_visa_enabled', $customer))
        <a href="{{ route('business-visa') }}" class="p-3 {{ $active === 'business-visa' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Business Visum">
            <i class="fa-regular fa-id-card text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- VisumPoint Check -->
        @if($featureService->isFeatureEnabled('navigation_visumpoint_enabled', $customer))
        <a href="{{ route('visumpoint') }}" class="p-3 {{ $active === 'visumpoint-check' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Visum Check (VisumPoint)">
            <i class="fa-regular fa-stamp text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Plugin Dashboard -->
        @if(auth('customer')->check() && auth('customer')->user()->pluginClient)
        <a href="{{ route('plugin.dashboard') }}" class="p-3 {{ $active === 'plugin-dashboard' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Plugin Dashboard">
            <i class="fa-regular fa-puzzle-piece text-2xl" aria-hidden="true"></i>
        </a>
        @endif
        </div>
    </div>

    <!-- Bottom Buttons (fixed) -->
    @if(config('app.navigation_bottom_buttons_enabled', true))
    <div class="flex-shrink-0 flex flex-col items-center space-y-3 pt-4">
        <!-- Sprachumschalter (per .env aktivierbar) -->
        <x-language-switcher />

        @if($active === 'dashboard' && $featureService->isFeatureEnabled('navigation_center_map_enabled', $customer))
        <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Karte zentrieren" onclick="centerMap()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6" fill="currentColor" aria-hidden="true">
                <path d="M320 544C443.7 544 544 443.7 544 320C544 196.3 443.7 96 320 96C196.3 96 96 196.3 96 320C96 325.9 96.2 331.8 96.7 337.6L91.8 339.2C81.9 342.6 73.3 348.1 66.4 355.1C64.8 343.6 64 331.9 64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C308.1 576 296.4 575.2 284.9 573.6C291.9 566.7 297.4 558 300.7 548.2L302.3 543.3C308.1 543.8 314 544 319.9 544zM320 160C408.4 160 480 231.6 480 320C480 407.2 410.2 478.1 323.5 480L334.4 447.2C398.3 440 448 385.8 448 320C448 249.3 390.7 192 320 192C254.2 192 200 241.7 192.8 305.6L160 316.5C161.9 229.8 232.8 160 320 160zM315.3 324.7C319.6 329 321.1 335.3 319.2 341.1L255.2 533.1C253 539.6 246.9 544 240 544C233.1 544 227 539.6 224.8 533.1L201 461.6L107.3 555.3C101.1 561.5 90.9 561.5 84.7 555.3C78.5 549.1 78.5 538.9 84.7 532.7L178.4 439L107 415.2C100.4 413 96 406.9 96 400C96 393.1 100.4 387 106.9 384.8L298.9 320.8C304.6 318.9 311 320.4 315.3 324.7zM162.6 400L213.1 416.8C217.9 418.4 221.6 422.1 223.2 426.9L240 477.4L278.7 361.3L162.6 400z"></path>
            </svg>
        </button>
        @endif

        <!-- Hilfecenter (externes Support-Portal, oeffnet in neuem Tab) -->
        <a href="https://support.passolution.de/portal/de/home" target="_blank" rel="noopener noreferrer" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors block" title="Hilfecenter">
            <i class="fa-regular fa-circle-question text-2xl" aria-hidden="true"></i>
        </a>

        @guest('customer')
        <!-- Login: oeffnet das SSO-Login im Modal-iframe, ohne die Plattform zu verlassen -->
        <button type="button" onclick="openSsoLoginModal()" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors block" title="Anmelden">
            <i class="fa-regular fa-right-to-bracket text-2xl" aria-hidden="true"></i>
        </button>
        <!-- Registrieren -->
        @if(config('app.customer_registration_enabled', true))
        <a href="{{ route('customer.register') }}" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors block" title="Registrieren">
            <i class="fa-regular fa-user-plus text-2xl" aria-hidden="true"></i>
        </a>
        @endif
        @endguest
    </div>
    @endif
</nav>

@guest('customer')
{{-- SSO-Login im Modal-iframe: laedt den Login der Auth-Domain ein, ohne die
     Plattform zu verlassen. Nach erfolgreichem Login meldet die Done-Seite
     (auth.keycloak.iframe-done) per postMessage zurueck -> Seite laedt neu. --}}
<div id="sso-login-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 p-4">
    <div class="relative w-full max-w-md h-[640px] max-h-[92vh] bg-white rounded-xl shadow-2xl overflow-hidden">
        <button type="button" onclick="closeSsoLoginModal()" aria-label="Schließen"
                class="absolute top-2 right-2 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-gray-600 shadow hover:bg-white hover:text-gray-900">
            <i class="fa-regular fa-xmark text-lg" aria-hidden="true"></i>
        </button>
        <iframe id="sso-login-iframe" src="" title="Anmelden" loading="lazy"
                class="h-full w-full border-0"></iframe>
    </div>
</div>
<script>
    (function () {
        var ssoUrl = @json(route('auth.keycloak.redirect', ['from' => 'iframe']));
        var expectedOrigin = window.location.origin;

        window.openSsoLoginModal = function () {
            var modal = document.getElementById('sso-login-modal');
            var frame = document.getElementById('sso-login-iframe');
            if (!modal || !frame) { return; }
            // src erst beim Oeffnen setzen (keine SSO-Session im Hintergrund pro Seitenaufruf).
            if (frame.getAttribute('src') !== ssoUrl) { frame.setAttribute('src', ssoUrl); }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closeSsoLoginModal = function () {
            var modal = document.getElementById('sso-login-modal');
            if (!modal) { return; }
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        // Esc schliesst das Modal.
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { window.closeSsoLoginModal(); }
        });

        // Erfolgsmeldung der Done-Seite (same-origin) -> Seite neu laden.
        window.addEventListener('message', function (e) {
            if (e.origin !== expectedOrigin) { return; }
            if (e.data && e.data.type === 'pds-platform-login-success') {
                window.location.reload();
            }
        });
    })();
</script>
@endguest
