@props(['active' => 'dashboard'])

<nav class="navigation flex flex-col items-center justify-between py-4 h-full">
    <!-- Top Buttons -->
    <div class="flex flex-col items-center space-y-6">
        <!-- Menü Button -->
        <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Menü" onclick="toggleRightContainer()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <!-- Ereignisse Button (nur für Dashboard) -->
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

        <!-- Einreisebestimmungen -->
        @if(config('app.entry_conditions_enabled', true))
        <a href="{{ route('entry-conditions') }}" class="p-3 {{ $active === 'entry-conditions' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Einreisebestimmungen">
            <i class="fa-regular fa-passport text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Buchungsmöglichkeit -->
        @if(config('app.dashboard_booking_enabled', true))
        <a href="{{ route('booking') }}" class="p-3 {{ $active === 'booking' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Buchungsmöglichkeit">
            <i class="fa-regular fa-calendar-check text-2xl" aria-hidden="true"></i>
        </a>
        @endif

        <!-- Flughäfen -->
        @if(config('app.dashboard_airports_enabled', true))
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
        @if(auth('customer')->check() && auth('customer')->user()->branch_management_active)
        <a href="{{ route('branches') }}" class="p-3 {{ $active === 'branches' ? 'bg-white text-black' : 'text-white hover:bg-gray-800' }} rounded-lg transition-colors block" title="Filialen & Standorte">
            <i class="fa-regular fa-building text-2xl" aria-hidden="true"></i>
        </a>
        @endif
    </div>

    <!-- Bottom Buttons -->
    <div class="flex flex-col items-center space-y-3">
        <!-- Benachrichtigungen (nur für eingeloggte Kunden) -->
        @if(auth('customer')->check())
        <div x-data="notificationDropdown()" class="relative" style="z-index: 10001;">
            <button @click="toggleDropdown" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors relative" title="Benachrichtigungen">
                <i class="fa-regular fa-bell text-2xl" aria-hidden="true"></i>
                <span x-show="unreadCount > 0" x-text="unreadCount" class="absolute top-1 right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center" style="z-index: 10002;"></span>
            </button>

            <!-- Dropdown -->
            <div x-show="showDropdown" @click.away="showDropdown = false" x-cloak
                 class="absolute bottom-full right-0 mb-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden" style="z-index: 10000;">
                <div class="p-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-900">Benachrichtigungen</h3>
                    <button @click="markAllAsRead" class="text-xs text-blue-600 hover:text-blue-800">Alle als gelesen</button>
                </div>

                <div class="max-h-96 overflow-y-auto">
                    <template x-if="notifications.length === 0">
                        <div class="p-4 text-center text-gray-500 text-sm">
                            Keine Benachrichtigungen
                        </div>
                    </template>

                    <template x-for="notification in notifications" :key="notification.id">
                        <div @click="markAsRead(notification.id)"
                             :class="notification.read_at ? 'bg-white' : 'bg-blue-50'"
                             class="p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
                            <p class="text-sm text-gray-900" x-text="notification.data.message"></p>
                            <p class="text-xs text-gray-500 mt-1" x-text="formatDate(notification.created_at)"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        @endif

        @if($active === 'dashboard')
        <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Karte zentrieren" onclick="centerMap()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6" fill="currentColor" aria-hidden="true">
                <path d="M320 544C443.7 544 544 443.7 544 320C544 196.3 443.7 96 320 96C196.3 96 96 196.3 96 320C96 325.9 96.2 331.8 96.7 337.6L91.8 339.2C81.9 342.6 73.3 348.1 66.4 355.1C64.8 343.6 64 331.9 64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C308.1 576 296.4 575.2 284.9 573.6C291.9 566.7 297.4 558 300.7 548.2L302.3 543.3C308.1 543.8 314 544 319.9 544zM320 160C408.4 160 480 231.6 480 320C480 407.2 410.2 478.1 323.5 480L334.4 447.2C398.3 440 448 385.8 448 320C448 249.3 390.7 192 320 192C254.2 192 200 241.7 192.8 305.6L160 316.5C161.9 229.8 232.8 160 320 160zM315.3 324.7C319.6 329 321.1 335.3 319.2 341.1L255.2 533.1C253 539.6 246.9 544 240 544C233.1 544 227 539.6 224.8 533.1L201 461.6L107.3 555.3C101.1 561.5 90.9 561.5 84.7 555.3C78.5 549.1 78.5 538.9 84.7 532.7L178.4 439L107 415.2C100.4 413 96 406.9 96 400C96 393.1 100.4 387 106.9 384.8L298.9 320.8C304.6 318.9 311 320.4 315.3 324.7zM162.6 400L213.1 416.8C217.9 418.4 221.6 422.1 223.2 426.9L240 477.4L278.7 361.3L162.6 400z"></path>
            </svg>
        </button>
        @endif

    </div>
</nav>

@if(auth('customer')->check())
<script>
function notificationDropdown() {
    return {
        showDropdown: false,
        notifications: [],
        unreadCount: 0,

        init() {
            this.loadNotifications();
            // Poll alle 30 Sekunden
            setInterval(() => this.loadNotifications(), 30000);

            // Listen for manual reload trigger
            window.addEventListener('reload-notifications', () => {
                this.loadNotifications();
            });
        },

        toggleDropdown() {
            this.showDropdown = !this.showDropdown;
            if (this.showDropdown) {
                this.loadNotifications();
            }
        },

        async loadNotifications() {
            try {
                const response = await fetch('/customer/notifications');
                const data = await response.json();
                console.log('Notifications loaded:', data);
                if (data.success) {
                    this.notifications = data.notifications;
                    this.unreadCount = data.unread_count;
                    console.log('Unread count:', this.unreadCount);
                    console.log('Notifications:', this.notifications);
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        },

        async markAsRead(id) {
            try {
                await fetch(`/customer/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });
                this.loadNotifications();
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        },

        async markAllAsRead() {
            try {
                await fetch('/customer/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });
                this.loadNotifications();
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        },

        formatDate(dateString) {
            const date = new Date(dateString);
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
            return date.toLocaleDateString('de-DE');
        }
    };
}
</script>
@endif
