<!DOCTYPE html>
<html lang="de">
<head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-T7R2SWKD');</script>
    <!-- End Google Tag Manager -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Global Travel Monitor - Ereignisse</title>

    {{-- SEO Meta Tags --}}
    <meta name="description" content="Global Travel Monitor - Aktuelle Reisesicherheitsinformationen und Ereignisse weltweit.">
    <meta name="robots" content="{{ env('ROBOTS_ALLOW_INDEXING', false) ? 'index, follow' : 'noindex, nofollow' }}">
    <link rel="canonical" href="{{ config('app.url') }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:title" content="Global Travel Monitor - Ereignisse">
    <meta property="og:description" content="Aktuelle Reisesicherheitsinformationen und Ereignisse weltweit.">
    <meta property="og:image" content="{{ asset('og-image.png') }}">
    <meta property="og:site_name" content="Global Travel Monitor">

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 0;
            overscroll-behavior-y: contain;
        }

        /* Priority Colors */
        .priority-low { background-color: #22c55e; }
        .priority-info { background-color: #3b82f6; }
        .priority-medium { background-color: #f97316; }
        .priority-high { background-color: #ef4444; }
        .priority-critical { background-color: #7c2d12; }

        .priority-border-low { border-left-color: #22c55e; }
        .priority-border-info { border-left-color: #3b82f6; }
        .priority-border-medium { border-left-color: #f97316; }
        .priority-border-high { border-left-color: #ef4444; }
        .priority-border-critical { border-left-color: #7c2d12; }

        /* Pull to Refresh */
        .ptr-element {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            color: #aaa;
            z-index: 10;
            text-align: center;
            height: 50px;
            transition: all 0.25s ease;
        }

        /* Hide scrollbar on filter chips */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Bottom nav safe area */
        .bottom-safe {
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        /* Drawer overlay */
        .drawer-overlay {
            background: rgba(0, 0, 0, 0.5);
            transition: opacity 0.3s ease;
        }

        /* Drawer slide */
        .drawer-content {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .drawer-content.open {
            transform: translateX(0);
        }

        /* Event card hover effect */
        .event-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .event-card:active {
            transform: scale(0.98);
        }

        /* Loading spinner */
        .spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Filter chip */
        .filter-chip {
            transition: all 0.2s ease;
        }
        .filter-chip.active {
            background-color: #1976D2;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" x-data="mobileApp()">
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T7R2SWKD"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

    <!-- Drawer Overlay -->
    <div x-show="drawerOpen"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="drawerOpen = false"
         class="fixed inset-0 z-40 drawer-overlay"
         style="display: none;">
    </div>

    <!-- Drawer Menu -->
    <div x-show="drawerOpen"
         :class="{ 'open': drawerOpen }"
         class="fixed top-0 left-0 h-full w-72 bg-white z-50 drawer-content shadow-xl"
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:leave="transition ease-in duration-200">

        <!-- Drawer Header -->
        <div class="bg-gray-100 border-b border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('android-chrome-192x192.png') }}" alt="GTM" class="h-10 w-10">
                <div>
                    <div class="font-semibold text-gray-900">Global Travel Monitor</div>
                    <div class="text-xs text-gray-500">Passolution GmbH</div>
                </div>
            </div>
        </div>

        <!-- Drawer Items -->
        <nav class="py-2">
            <a href="{{ route('home') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 bg-blue-50 border-r-4 border-blue-600">
                <i class="fa-solid fa-rss w-6 text-center text-blue-600"></i>
                <span class="font-medium text-blue-600">Nachrichten Feed</span>
            </a>

            <a href="{{ route('home') }}?view=map" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i class="fa-regular fa-map w-6 text-center"></i>
                <span>Karte</span>
            </a>

            @if(config('app.navigation_entry_conditions_enabled', true))
            <a href="{{ route('entry-conditions') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i class="fa-solid fa-earth-europe w-6 text-center"></i>
                <span>Einreisebestimmungen</span>
            </a>
            @endif

            @if(config('app.navigation_booking_enabled', true))
            <a href="{{ route('booking') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i class="fa-regular fa-calendar-check w-6 text-center"></i>
                <span>Buchungsmöglichkeit</span>
            </a>
            @endif

            @if(config('app.navigation_cruise_enabled', true))
            <a href="{{ route('cruise') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i class="fa-regular fa-ship w-6 text-center"></i>
                <span>Kreuzfahrt</span>
            </a>
            @endif

            <div class="border-t border-gray-200 my-2"></div>

            @auth('customer')
                @if(auth('customer')->user()->branch_management_active)
                <a href="{{ route('branches') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fa-regular fa-building w-6 text-center"></i>
                    <span>Filialen & Standorte</span>
                </a>
                @endif

                <a href="{{ route('my-travelers') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fa-regular fa-users w-6 text-center"></i>
                    <span>Meine Reisenden</span>
                </a>

                <div class="border-t border-gray-200 my-2"></div>

                <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fa-regular fa-user w-6 text-center"></i>
                    <span>{{ auth('customer')->user()->name }}</span>
                </a>

                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-4 px-4 py-3 text-red-600 hover:bg-red-50">
                        <i class="fa-regular fa-sign-out w-6 text-center"></i>
                        <span>Abmelden</span>
                    </button>
                </form>
            @else
                @if(config('app.customer_login_enabled', true))
                <a href="{{ route('customer.login') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fa-regular fa-sign-in w-6 text-center"></i>
                    <span>Anmelden</span>
                </a>
                @endif

                @if(config('app.customer_registration_enabled', true))
                <a href="{{ route('customer.register') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fa-regular fa-user-plus w-6 text-center"></i>
                    <span>Registrieren</span>
                </a>
                @endif
            @endauth
        </nav>

        <!-- Drawer Footer -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 text-xs text-gray-500">
            <div class="flex flex-wrap gap-x-4 gap-y-1 mb-2">
                <a href="https://www.passolution.de/datenschutz/" target="_blank" class="text-blue-600 hover:underline">Datenschutz</a>
                <a href="https://www.passolution.de/agb/" target="_blank" class="text-blue-600 hover:underline">AGB</a>
                <a href="https://www.passolution.de/impressum/" target="_blank" class="text-blue-600 hover:underline">Impressum</a>
                <a href="#" onclick="event.preventDefault(); document.getElementById('disclaimerModal')?.classList.remove('hidden');" class="text-blue-600 hover:underline">Haftungsausschluss</a>
            </div>
            <div class="flex justify-between">
                <span>© 2025 Passolution GmbH</span>
                <span>Version 1.0.2</span>
            </div>
        </div>
    </div>

    <!-- App Bar -->
    <header class="fixed top-0 left-0 right-0 bg-white shadow-sm z-30 h-14">
        <div class="flex items-center justify-between h-full px-4">
            <!-- Left: Hamburger -->
            <button @click="drawerOpen = true" class="p-2 -ml-2 text-gray-700">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <!-- Center: Title or Search -->
            <div class="flex-1 mx-4" x-show="!searchOpen">
                <div class="flex items-center justify-center gap-2">
                    <img src="{{ asset('android-chrome-192x192.png') }}" alt="GTM" class="h-6 w-6">
                    <span class="font-semibold text-gray-800">Nachrichten Feed</span>
                </div>
            </div>

            {{-- Search Input temporarily disabled
            <div class="flex-1 mx-4" x-show="searchOpen" x-cloak>
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.300ms="filterEvents()"
                       placeholder="Suchen..."
                       class="w-full px-3 py-2 bg-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       x-ref="searchInput">
            </div>
            --}}

            <!-- Right: Actions -->
            <div class="flex items-center gap-1">
                {{-- Search button temporarily disabled
                <button @click="toggleSearch()" class="p-2 text-gray-700">
                    <i :class="searchOpen ? 'fa-times' : 'fa-search'" class="fas text-lg"></i>
                </button>
                --}}
                <button @click="filterModalOpen = true" class="p-2 text-gray-700 relative">
                    <i class="fas fa-filter text-lg"></i>
                    <span x-show="activeFiltersCount > 0"
                          x-text="activeFiltersCount"
                          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Filter Chips (horizontal scroll) -->
    <div class="fixed top-14 left-0 right-0 bg-white border-b border-gray-200 z-20 px-4 py-2">
        <div class="flex gap-2 overflow-x-auto hide-scrollbar">
            <!-- Time Period Chips -->
            <button @click="setTimePeriod('all')"
                    :class="{ 'active': filters.timePeriod === 'all' }"
                    class="filter-chip whitespace-nowrap px-3 py-1.5 rounded-full text-sm bg-gray-200 text-gray-700">
                Aktuell
            </button>
            <button @click="setTimePeriod('future')"
                    :class="{ 'active': filters.timePeriod === 'future' }"
                    class="filter-chip whitespace-nowrap px-3 py-1.5 rounded-full text-sm bg-gray-200 text-gray-700">
                Zukünftig
            </button>
            <button @click="setTimePeriod('today')"
                    :class="{ 'active': filters.timePeriod === 'today' }"
                    class="filter-chip whitespace-nowrap px-3 py-1.5 rounded-full text-sm bg-gray-200 text-gray-700">
                Heute
            </button>
            <button @click="setTimePeriod('week')"
                    :class="{ 'active': filters.timePeriod === 'week' }"
                    class="filter-chip whitespace-nowrap px-3 py-1.5 rounded-full text-sm bg-gray-200 text-gray-700">
                Diese Woche
            </button>
            <button @click="setTimePeriod('month')"
                    :class="{ 'active': filters.timePeriod === 'month' }"
                    class="filter-chip whitespace-nowrap px-3 py-1.5 rounded-full text-sm bg-gray-200 text-gray-700">
                Dieser Monat
            </button>

            <!-- Priority Chips -->
            <div class="w-px bg-gray-300 mx-1"></div>
            <button @click="togglePriority('high')"
                    :class="{ 'active': filters.priorities.includes('high') }"
                    class="filter-chip whitespace-nowrap px-3 py-1.5 rounded-full text-sm bg-red-100 text-red-700">
                Hoch
            </button>
            <button @click="togglePriority('medium')"
                    :class="{ 'active': filters.priorities.includes('medium') }"
                    class="filter-chip whitespace-nowrap px-3 py-1.5 rounded-full text-sm bg-orange-100 text-orange-700">
                Mittel
            </button>
            <button @click="togglePriority('low')"
                    :class="{ 'active': filters.priorities.includes('low') }"
                    class="filter-chip whitespace-nowrap px-3 py-1.5 rounded-full text-sm bg-green-100 text-green-700">
                Niedrig
            </button>
            <button @click="togglePriority('info')"
                    :class="{ 'active': filters.priorities.includes('info') }"
                    class="filter-chip whitespace-nowrap px-3 py-1.5 rounded-full text-sm bg-blue-100 text-blue-700">
                Info
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <main class="pt-28 pb-20">
        <!-- SEO Static Content for Crawlers -->
        <noscript>
            <article class="px-4 py-6 bg-white mx-4 rounded-lg shadow-sm mb-4">
                <h1 class="text-xl font-bold text-gray-800 mb-3">Global Travel Monitor - Reisesicherheit weltweit</h1>
                <p class="text-gray-600 mb-3">
                    Willkommen beim Global Travel Monitor von Passolution. Hier finden Sie aktuelle Reisesicherheitsinformationen,
                    Ereignisse und Warnungen für über 200 Länder weltweit.
                </p>
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Aktuelle Sicherheitsereignisse</h2>
                <p class="text-gray-600 mb-3">
                    Unser Travel Risk Management System überwacht kontinuierlich weltweite Ereignisse wie Naturkatastrophen,
                    politische Unruhen, Gesundheitswarnungen und Sicherheitsrisiken.
                </p>
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Funktionen</h2>
                <ul class="list-disc list-inside text-gray-600 space-y-1">
                    <li>Echtzeit-Monitoring von Sicherheitsereignissen</li>
                    <li>Länderrisiko-Bewertungen</li>
                    <li>Filterung nach Regionen und Ereignistypen</li>
                    <li>Detaillierte Ereignisinformationen</li>
                </ul>
            </article>
        </noscript>

        <!-- Initial Static Content (hidden after JS loads) -->
        <div x-show="!initialized" class="px-4 py-6">
            <article class="bg-white rounded-lg shadow-sm p-4 mb-4">
                <h1 class="text-xl font-bold text-gray-800 mb-3">Global Travel Monitor - Reisesicherheit weltweit</h1>
                <p class="text-gray-600 mb-3">
                    Willkommen beim Global Travel Monitor von Passolution. Aktuelle Reisesicherheitsinformationen,
                    Ereignisse und Warnungen für über 200 Länder weltweit.
                </p>
                <p class="text-gray-600">
                    Unser Travel Risk Management System überwacht kontinuierlich weltweite Sicherheitsereignisse.
                </p>
            </article>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex justify-center py-12">
            <div class="text-center">
                <i class="fas fa-spinner spinner text-3xl text-blue-600 mb-3"></i>
                <p class="text-gray-500">Ereignisse werden geladen...</p>
            </div>
        </div>

        <!-- Error State -->
        <div x-show="error && !loading" class="px-4 py-12 text-center">
            <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-3"></i>
            <p class="text-gray-700 font-medium mb-2">Fehler beim Laden</p>
            <p class="text-gray-500 text-sm mb-4" x-text="error"></p>
            <button @click="loadEvents()" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                Erneut versuchen
            </button>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && !error && filteredEvents.length === 0" class="px-4 py-12 text-center">
            <i class="fas fa-inbox text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-700 font-medium mb-2">Keine Ereignisse gefunden</p>
            <p class="text-gray-500 text-sm">Versuchen Sie andere Filtereinstellungen</p>
        </div>

        <!-- Event List -->
        <div x-show="!loading && !error && filteredEvents.length > 0" class="px-4 space-y-3">
            <!-- Event Count -->
            <div class="text-sm text-gray-500 mb-2">
                <span x-text="filteredEvents.length"></span> Ereignisse
            </div>

            <!-- Event Cards -->
            <template x-for="event in filteredEvents" :key="event.id">
                <div @click="openEvent(event)"
                     class="event-card bg-white rounded-xl shadow-sm overflow-hidden border-l-4"
                     :class="'priority-border-' + (event.priority || 'info')">
                    <div class="flex p-3">
                        <!-- Image -->
                        <div class="w-20 h-20 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0 mr-3">
                            <template x-if="event.image">
                                <img :src="event.image" :alt="event.title" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!event.image && event.countries && event.countries.length > 0">
                                <img :src="'/images/countries/' + event.countries[0].iso_code.toLowerCase() + '.jpg'"
                                     :alt="event.countries[0].name"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'flex items-center justify-center h-full bg-gray-100\'><i class=\'fas fa-globe text-2xl text-gray-400\'></i></div>'">
                            </template>
                            <template x-if="!event.image && (!event.countries || event.countries.length === 0)">
                                <div class="flex items-center justify-center h-full bg-gray-100">
                                    <i class="fas fa-globe text-2xl text-gray-400"></i>
                                </div>
                            </template>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <!-- Title -->
                            <h3 class="font-semibold text-gray-900 text-sm leading-tight mb-1 line-clamp-2" x-text="event.title"></h3>

                            <!-- Countries -->
                            <div class="flex flex-wrap gap-1 mb-1" x-show="event.countries && event.countries.length > 0">
                                <template x-for="country in (event.countries || []).slice(0, 3)" :key="country.id">
                                    <span class="text-xs text-gray-600">
                                        <span x-text="getCountryFlag(country.iso_code)"></span>
                                        <span x-text="country.name"></span>
                                    </span>
                                </template>
                                <span x-show="event.countries && event.countries.length > 3"
                                      class="text-xs text-gray-500"
                                      x-text="'+' + (event.countries.length - 3)"></span>
                            </div>

                            <!-- Meta Row -->
                            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                <!-- Priority Badge -->
                                <span class="px-2 py-0.5 rounded-full text-white text-xs"
                                      :class="'priority-' + (event.priority || 'info')"
                                      x-text="getPriorityLabel(event.priority)"></span>

                                <!-- Event Type Tags -->
                                <template x-for="(eventType, index) in (event.event_types || [])" :key="event.id + '-type-' + index">
                                    <span class="px-2 py-0.5 rounded-full bg-gray-200 text-gray-700 text-xs"
                                          x-text="typeof eventType === 'string' ? eventType : eventType.name"></span>
                                </template>
                            </div>

                            <!-- Date -->
                            <div class="text-xs text-gray-400 mt-1" x-text="formatDate(event.start_date || event.created_at)"></div>
                        </div>

                        <!-- Arrow -->
                        <div class="flex items-center pl-2">
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Load More -->
            <div x-show="hasMore" class="py-4 text-center">
                <button @click="loadMore()"
                        :disabled="loadingMore"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg disabled:opacity-50">
                    <span x-show="!loadingMore">Mehr laden</span>
                    <span x-show="loadingMore"><i class="fas fa-spinner spinner mr-2"></i>Laden...</span>
                </button>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-30 bottom-safe">
        <div class="flex justify-around items-center h-14">
            <a href="{{ route('home') }}" class="flex flex-col items-center justify-center flex-1 py-2 text-blue-600">
                <i class="fa-solid fa-rss text-xl"></i>
                <span class="text-xs mt-1">Feed</span>
            </a>
            <a href="{{ route('home') }}?view=map" class="flex flex-col items-center justify-center flex-1 py-2 text-gray-500">
                <i class="fa-solid fa-map text-xl"></i>
                <span class="text-xs mt-1">Karte</span>
            </a>
            <button @click="drawerOpen = true" class="flex flex-col items-center justify-center flex-1 py-2 text-gray-500">
                <i class="fa-solid fa-ellipsis text-xl"></i>
                <span class="text-xs mt-1">Mehr</span>
            </button>
        </div>
    </nav>

    <!-- Filter Modal -->
    <div x-show="filterModalOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-end justify-center bg-black bg-opacity-50"
         style="display: none;"
         @click.self="filterModalOpen = false">

        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-y-full"
             x-transition:enter-end="transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-y-0"
             x-transition:leave-end="transform translate-y-full"
             class="bg-white rounded-t-2xl w-full max-h-[80vh] overflow-y-auto">

            <!-- Modal Header -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Filter</h2>
                <div class="flex gap-2">
                    <button @click="resetFilters()" class="text-sm text-blue-600">Zurücksetzen</button>
                    <button @click="filterModalOpen = false" class="p-1">
                        <i class="fas fa-times text-gray-500"></i>
                    </button>
                </div>
            </div>

            <div class="p-4 space-y-6">
                <!-- Continent Filter -->
                <div>
                    <h3 class="font-medium text-gray-900 mb-3">Kontinent</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="continent in continents" :key="continent.code">
                            <button @click="toggleContinent(continent.code)"
                                    :class="{ 'bg-blue-600 text-white': filters.continents.includes(continent.code), 'bg-gray-100 text-gray-700': !filters.continents.includes(continent.code) }"
                                    class="px-3 py-2 rounded-lg text-sm"
                                    x-text="continent.name"></button>
                        </template>
                    </div>
                </div>

                <!-- Event Type Filter -->
                <div>
                    <h3 class="font-medium text-gray-900 mb-3">Ereignistyp</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="eventType in eventTypes" :key="eventType.id">
                            <button @click="toggleEventType(eventType.id)"
                                    :class="{ 'bg-blue-600 text-white': filters.eventTypes.includes(eventType.id), 'bg-gray-100 text-gray-700': !filters.eventTypes.includes(eventType.id) }"
                                    class="px-3 py-2 rounded-lg text-sm flex items-center gap-2">
                                <i :class="eventType.icon" class="text-sm"></i>
                                <span x-text="eventType.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Priority Filter -->
                <div>
                    <h3 class="font-medium text-gray-900 mb-3">Priorität</h3>
                    <div class="flex flex-wrap gap-2">
                        <button @click="togglePriority('high')"
                                :class="{ 'bg-red-600 text-white': filters.priorities.includes('high'), 'bg-red-100 text-red-700': !filters.priorities.includes('high') }"
                                class="px-3 py-2 rounded-lg text-sm">Hoch</button>
                        <button @click="togglePriority('medium')"
                                :class="{ 'bg-orange-600 text-white': filters.priorities.includes('medium'), 'bg-orange-100 text-orange-700': !filters.priorities.includes('medium') }"
                                class="px-3 py-2 rounded-lg text-sm">Mittel</button>
                        <button @click="togglePriority('low')"
                                :class="{ 'bg-green-600 text-white': filters.priorities.includes('low'), 'bg-green-100 text-green-700': !filters.priorities.includes('low') }"
                                class="px-3 py-2 rounded-lg text-sm">Niedrig</button>
                        <button @click="togglePriority('info')"
                                :class="{ 'bg-blue-600 text-white': filters.priorities.includes('info'), 'bg-blue-100 text-blue-700': !filters.priorities.includes('info') }"
                                class="px-3 py-2 rounded-lg text-sm">Information</button>
                    </div>
                </div>

                <!-- Country Search -->
                <div>
                    <h3 class="font-medium text-gray-900 mb-3">Land</h3>
                    <input type="text"
                           x-model="countrySearch"
                           @input="filterCountries()"
                           placeholder="Land suchen..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <div x-show="filteredCountries.length > 0" class="mt-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg">
                        <template x-for="country in filteredCountries" :key="country.id">
                            <button @click="toggleCountry(country.id)"
                                    class="w-full flex items-center justify-between px-3 py-2 hover:bg-gray-50 text-left">
                                <span class="flex items-center gap-2">
                                    <span x-text="getCountryFlag(country.iso_code)"></span>
                                    <span x-text="country.name" class="text-sm"></span>
                                </span>
                                <i x-show="filters.countries.includes(country.id)" class="fas fa-check text-blue-600"></i>
                            </button>
                        </template>
                    </div>
                    <!-- Selected Countries -->
                    <div x-show="filters.countries.length > 0" class="mt-2 flex flex-wrap gap-2">
                        <template x-for="countryId in filters.countries" :key="countryId">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm flex items-center gap-1">
                                <span x-text="getCountryName(countryId)"></span>
                                <button @click="toggleCountry(countryId)" class="hover:text-blue-600">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Apply Button -->
            <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4">
                <button @click="filterModalOpen = false; filterEvents()"
                        class="w-full py-3 bg-blue-600 text-white rounded-lg font-medium">
                    Filter anwenden
                </button>
            </div>
        </div>
    </div>

    <script>
        function mobileApp() {
            return {
                // State
                initialized: false,
                drawerOpen: false,
                searchOpen: false,
                searchQuery: '',
                filterModalOpen: false,
                loading: true,
                loadingMore: false,
                error: null,
                events: [],
                filteredEvents: [],
                hasMore: false,
                page: 1,

                // Filter State
                filters: {
                    timePeriod: 'all',
                    priorities: [],
                    continents: [],
                    eventTypes: [],
                    countries: []
                },

                // Reference Data
                // Country ISO code to continent mapping
                countryToContinentMap: {
                    // Europa
                    'DE': 'EU', 'AT': 'EU', 'CH': 'EU', 'FR': 'EU', 'IT': 'EU', 'ES': 'EU', 'PT': 'EU', 'GB': 'EU', 'IE': 'EU',
                    'NL': 'EU', 'BE': 'EU', 'LU': 'EU', 'PL': 'EU', 'CZ': 'EU', 'SK': 'EU', 'HU': 'EU', 'RO': 'EU', 'BG': 'EU',
                    'GR': 'EU', 'HR': 'EU', 'SI': 'EU', 'RS': 'EU', 'BA': 'EU', 'ME': 'EU', 'MK': 'EU', 'AL': 'EU', 'XK': 'EU',
                    'SE': 'EU', 'NO': 'EU', 'FI': 'EU', 'DK': 'EU', 'IS': 'EU', 'EE': 'EU', 'LV': 'EU', 'LT': 'EU',
                    'UA': 'EU', 'BY': 'EU', 'MD': 'EU', 'RU': 'EU', 'MT': 'EU', 'CY': 'EU', 'TR': 'EU',
                    // Asien
                    'CN': 'AS', 'JP': 'AS', 'KR': 'AS', 'KP': 'AS', 'MN': 'AS', 'TW': 'AS', 'HK': 'AS', 'MO': 'AS',
                    'IN': 'AS', 'PK': 'AS', 'BD': 'AS', 'LK': 'AS', 'NP': 'AS', 'BT': 'AS', 'MV': 'AS', 'AF': 'AS',
                    'TH': 'AS', 'VN': 'AS', 'MY': 'AS', 'SG': 'AS', 'ID': 'AS', 'PH': 'AS', 'MM': 'AS', 'KH': 'AS', 'LA': 'AS', 'BN': 'AS', 'TL': 'AS',
                    'SA': 'AS', 'AE': 'AS', 'QA': 'AS', 'KW': 'AS', 'BH': 'AS', 'OM': 'AS', 'YE': 'AS', 'IR': 'AS', 'IQ': 'AS', 'SY': 'AS', 'JO': 'AS', 'LB': 'AS', 'IL': 'AS', 'PS': 'AS',
                    'KZ': 'AS', 'UZ': 'AS', 'TM': 'AS', 'TJ': 'AS', 'KG': 'AS', 'AZ': 'AS', 'GE': 'AS', 'AM': 'AS',
                    // Afrika
                    'EG': 'AF', 'LY': 'AF', 'TN': 'AF', 'DZ': 'AF', 'MA': 'AF', 'SD': 'AF', 'SS': 'AF', 'ET': 'AF', 'ER': 'AF', 'DJ': 'AF', 'SO': 'AF',
                    'KE': 'AF', 'UG': 'AF', 'TZ': 'AF', 'RW': 'AF', 'BI': 'AF', 'CD': 'AF', 'CG': 'AF', 'GA': 'AF', 'GQ': 'AF', 'CM': 'AF', 'CF': 'AF', 'TD': 'AF',
                    'NG': 'AF', 'GH': 'AF', 'CI': 'AF', 'SN': 'AF', 'ML': 'AF', 'BF': 'AF', 'NE': 'AF', 'MR': 'AF', 'GM': 'AF', 'GW': 'AF', 'GN': 'AF', 'SL': 'AF', 'LR': 'AF', 'TG': 'AF', 'BJ': 'AF',
                    'ZA': 'AF', 'NA': 'AF', 'BW': 'AF', 'ZW': 'AF', 'ZM': 'AF', 'MW': 'AF', 'MZ': 'AF', 'AO': 'AF', 'SZ': 'AF', 'LS': 'AF', 'MG': 'AF', 'MU': 'AF', 'SC': 'AF', 'KM': 'AF', 'RE': 'AF',
                    // Nordamerika
                    'US': 'NA', 'CA': 'NA', 'MX': 'NA', 'GT': 'NA', 'BZ': 'NA', 'HN': 'NA', 'SV': 'NA', 'NI': 'NA', 'CR': 'NA', 'PA': 'NA',
                    'CU': 'NA', 'JM': 'NA', 'HT': 'NA', 'DO': 'NA', 'PR': 'NA', 'BS': 'NA', 'TT': 'NA', 'BB': 'NA', 'LC': 'NA', 'VC': 'NA', 'GD': 'NA', 'AG': 'NA', 'DM': 'NA', 'KN': 'NA',
                    // Südamerika
                    'BR': 'SA', 'AR': 'SA', 'CL': 'SA', 'PE': 'SA', 'CO': 'SA', 'VE': 'SA', 'EC': 'SA', 'BO': 'SA', 'PY': 'SA', 'UY': 'SA', 'GY': 'SA', 'SR': 'SA', 'GF': 'SA',
                    // Ozeanien
                    'AU': 'OC', 'NZ': 'OC', 'PG': 'OC', 'FJ': 'OC', 'SB': 'OC', 'VU': 'OC', 'NC': 'OC', 'PF': 'OC', 'WS': 'OC', 'TO': 'OC', 'KI': 'OC', 'MH': 'OC', 'FM': 'OC', 'PW': 'OC', 'NR': 'OC', 'TV': 'OC'
                },
                continents: [
                    { code: 'EU', name: 'Europa' },
                    { code: 'AS', name: 'Asien' },
                    { code: 'AF', name: 'Afrika' },
                    { code: 'NA', name: 'Nordamerika' },
                    { code: 'SA', name: 'Südamerika' },
                    { code: 'OC', name: 'Ozeanien' }
                ],
                eventTypes: [],
                countries: [],
                countrySearch: '',
                filteredCountries: [],

                // Computed
                get activeFiltersCount() {
                    let count = 0;
                    if (this.filters.timePeriod !== 'all') count++;
                    count += this.filters.priorities.length;
                    count += this.filters.continents.length;
                    count += this.filters.eventTypes.length;
                    count += this.filters.countries.length;
                    return count;
                },

                // Initialize
                init() {
                    this.loadEvents();
                    this.loadEventTypes();
                    this.loadCountries();
                },

                // Methods
                async loadEvents() {
                    this.loading = true;
                    this.error = null;

                    try {
                        // Fetch custom events
                        const customResponse = await fetch('/api/custom-events/dashboard-events');
                        const customData = await customResponse.json();

                        // Process events - API returns { data: { events: [...] } }
                        const eventsArray = customData.data?.events || customData.events || [];
                        this.events = eventsArray.map(event => ({
                            id: event.id,
                            title: event.title,
                            description: event.description || event.popup_content,
                            priority: event.priority || 'info',
                            start_date: event.start_date,
                            end_date: event.end_date,
                            created_at: event.created_at,
                            countries: event.countries || [],
                            event_types: event.event_types || [],
                            image: event.image,
                            latitude: event.latitude,
                            longitude: event.longitude
                        }));

                        this.filterEvents();
                    } catch (err) {
                        console.error('Error loading events:', err);
                        this.error = 'Ereignisse konnten nicht geladen werden.';
                    } finally {
                        this.loading = false;
                        this.initialized = true;
                    }
                },

                async loadEventTypes() {
                    try {
                        const response = await fetch('/api/custom-events/event-types');
                        const data = await response.json();
                        // API returns { success: true, data: [...] }
                        this.eventTypes = data.data || data.event_types || [];
                    } catch (err) {
                        console.error('Error loading event types:', err);
                    }
                },

                async loadCountries() {
                    try {
                        const response = await fetch('/api/countries');
                        const data = await response.json();
                        this.countries = data.countries || data || [];
                    } catch (err) {
                        console.error('Error loading countries:', err);
                    }
                },

                filterEvents() {
                    let filtered = [...this.events];

                    // Search
                    if (this.searchQuery) {
                        const query = this.searchQuery.toLowerCase();
                        filtered = filtered.filter(e =>
                            e.title.toLowerCase().includes(query) ||
                            (e.countries || []).some(c => c.name.toLowerCase().includes(query))
                        );
                    }

                    // Time Period
                    if (this.filters.timePeriod !== 'all') {
                        const now = new Date();
                        now.setHours(0, 0, 0, 0); // Set to midnight for date comparison
                        filtered = filtered.filter(e => {
                            const eventDate = new Date(e.start_date || e.created_at);
                            switch (this.filters.timePeriod) {
                                case 'future':
                                    const eventDateOnly = new Date(eventDate);
                                    eventDateOnly.setHours(0, 0, 0, 0);
                                    return eventDateOnly > now;
                                case 'today':
                                    return eventDate.toDateString() === now.toDateString();
                                case 'week':
                                    const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                                    return eventDate >= weekAgo;
                                case 'month':
                                    const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                                    return eventDate >= monthAgo;
                                default:
                                    return true;
                            }
                        });

                        // Sort future events by date (soonest first)
                        if (this.filters.timePeriod === 'future') {
                            filtered.sort((a, b) => {
                                const dateA = new Date(a.start_date || a.created_at);
                                const dateB = new Date(b.start_date || b.created_at);
                                return dateA - dateB;
                            });
                        }
                    }

                    // Priority
                    if (this.filters.priorities.length > 0) {
                        filtered = filtered.filter(e => this.filters.priorities.includes(e.priority));
                    }

                    // Continent - use countryToContinentMap to determine continent from ISO code
                    if (this.filters.continents.length > 0) {
                        filtered = filtered.filter(e =>
                            (e.countries || []).some(c => {
                                const continentCode = this.countryToContinentMap[c.iso_code];
                                return this.filters.continents.includes(continentCode);
                            })
                        );
                    }

                    // Event Types (API returns array of strings or objects)
                    if (this.filters.eventTypes.length > 0) {
                        filtered = filtered.filter(e => {
                            const eventTypes = e.event_types || [];
                            return eventTypes.some(t => {
                                // Handle both string and object formats
                                const typeName = typeof t === 'string' ? t : t.name;
                                const typeId = typeof t === 'object' ? t.id : null;
                                // Match by ID or by name
                                return this.filters.eventTypes.includes(typeId) ||
                                       this.eventTypes.some(et =>
                                           this.filters.eventTypes.includes(et.id) && et.name === typeName
                                       );
                            });
                        });
                    }

                    // Countries
                    if (this.filters.countries.length > 0) {
                        filtered = filtered.filter(e =>
                            (e.countries || []).some(c =>
                                this.filters.countries.includes(c.id)
                            )
                        );
                    }

                    // Keep original API order (same as desktop) - no additional sorting
                    this.filteredEvents = filtered;
                },

                filterCountries() {
                    if (!this.countrySearch) {
                        this.filteredCountries = this.countries.slice(0, 10);
                    } else {
                        const query = this.countrySearch.toLowerCase();
                        this.filteredCountries = this.countries
                            .filter(c => c.name.toLowerCase().includes(query))
                            .slice(0, 10);
                    }
                },

                // Actions
                toggleSearch() {
                    this.searchOpen = !this.searchOpen;
                    if (this.searchOpen) {
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    } else {
                        this.searchQuery = '';
                        this.filterEvents();
                    }
                },

                setTimePeriod(period) {
                    this.filters.timePeriod = period;
                    this.filterEvents();
                },

                togglePriority(priority) {
                    const idx = this.filters.priorities.indexOf(priority);
                    if (idx > -1) {
                        this.filters.priorities.splice(idx, 1);
                    } else {
                        this.filters.priorities.push(priority);
                    }
                    this.filterEvents();
                },

                toggleContinent(code) {
                    const idx = this.filters.continents.indexOf(code);
                    if (idx > -1) {
                        this.filters.continents.splice(idx, 1);
                    } else {
                        this.filters.continents.push(code);
                    }
                },

                toggleEventType(id) {
                    const idx = this.filters.eventTypes.indexOf(id);
                    if (idx > -1) {
                        this.filters.eventTypes.splice(idx, 1);
                    } else {
                        this.filters.eventTypes.push(id);
                    }
                },

                toggleCountry(id) {
                    const idx = this.filters.countries.indexOf(id);
                    if (idx > -1) {
                        this.filters.countries.splice(idx, 1);
                    } else {
                        this.filters.countries.push(id);
                    }
                },

                resetFilters() {
                    this.filters = {
                        timePeriod: 'all',
                        priorities: [],
                        continents: [],
                        eventTypes: [],
                        countries: []
                    };
                    this.filterEvents();
                },

                openEvent(event) {
                    // Track click for custom events
                    this.trackEventClick(event.id, 'mobile_list');
                    window.location.href = '/?event=' + event.id;
                },

                trackEventClick(eventId, clickType) {
                    const numericEventId = typeof eventId === 'string' ? parseInt(eventId, 10) : eventId;
                    if (!numericEventId || isNaN(numericEventId)) return;

                    // Use fetch with keepalive for reliable tracking during page navigation
                    fetch('/api/custom-events/track-click', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            event_id: numericEventId,
                            click_type: clickType
                        }),
                        keepalive: true  // Ensures request completes even when page navigates away
                    }).catch(() => {});  // Ignore errors silently
                },

                loadMore() {
                    // Implement pagination if needed
                },

                // Helpers
                getCountryFlag(isoCode) {
                    if (!isoCode) return '🌍';
                    const codePoints = isoCode
                        .toUpperCase()
                        .split('')
                        .map(char => 127397 + char.charCodeAt());
                    return String.fromCodePoint(...codePoints);
                },

                getCountryName(id) {
                    const country = this.countries.find(c => c.id === id);
                    return country ? country.name : '';
                },

                getPriorityLabel(priority) {
                    const labels = {
                        low: 'Niedrig',
                        info: 'Info',
                        medium: 'Mittel',
                        high: 'Hoch',
                        critical: 'Kritisch'
                    };
                    return labels[priority] || 'Info';
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const date = new Date(dateStr);
                    const now = new Date();
                    const diff = now - date;
                    const minutes = Math.floor(diff / 60000);
                    const hours = Math.floor(diff / 3600000);
                    const days = Math.floor(diff / 86400000);

                    if (minutes < 1) return 'Gerade eben';
                    if (minutes < 60) return `vor ${minutes} Min.`;
                    if (hours < 24) return `vor ${hours} Std.`;
                    if (days === 1) return 'Gestern';
                    if (days < 7) return `vor ${days} Tagen`;

                    return date.toLocaleDateString('de-DE', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                }
            };
        }
    </script>

<!-- SEO Content Section -->
<section class="bg-gray-100 border-t border-gray-200 py-6 px-4">
    <div class="max-w-lg mx-auto">
        <h1 class="text-xl font-bold text-gray-800 mb-3">Global Travel Monitor - Weltweites Reiserisiko-Monitoring</h1>
        <div class="text-sm text-gray-600 space-y-3">
            <p>
                Der <strong>Global Travel Monitor (GTM)</strong> von Passolution ist Ihre zentrale Plattform für weltweite Reisesicherheitsinformationen.
                Aktuelle Ereignisse, Reisewarnungen und Sicherheitshinweise für über 200 Länder weltweit.
            </p>
            <p>
                Als führendes <strong>Travel Risk Management Tool</strong> bietet der Global Travel Monitor: Länderrisikobewertungen,
                Sicherheitsereignisse, Naturkatastrophen-Warnungen und gesundheitliche Reisehinweise.
            </p>
            <div class="grid grid-cols-1 gap-3 mt-4">
                <div class="bg-white p-3 rounded-lg shadow-sm">
                    <h3 class="font-semibold text-gray-800 mb-1">Echtzeit-Monitoring</h3>
                    <p class="text-xs">Aktuelle Ereignisse und Warnungen aus aller Welt.</p>
                </div>
                <div class="bg-white p-3 rounded-lg shadow-sm">
                    <h3 class="font-semibold text-gray-800 mb-1">Länderrisiko-Analyse</h3>
                    <p class="text-xs">Detaillierte Risikobewertungen für jedes Land.</p>
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-500">
                © {{ date('Y') }} Passolution GmbH - Global Travel Monitor | Reisesicherheit | Travel Risk Management
            </p>
        </div>
    </div>
</section>

</body>
</html>
