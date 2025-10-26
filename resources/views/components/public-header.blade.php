<header class="header">
    <div class="flex items-center justify-between h-full px-4">
        <!-- Logo -->
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <img src="/logo.png" alt="Logo" class="h-8 w-auto" style="margin-left:-5px"/>
                <span class="text-xl font-semibold text-gray-800" style="margin-left: 30px;">Global Travel Monitor</span>
            </div>
        </div>

        <!-- User Actions -->
        <div class="flex items-center space-x-4">
            @auth('customer')
                <!-- Benachrichtigungen -->
                <div x-data="notificationDropdown()" class="relative" style="margin-right: 50px;">
                    <button @click="toggleDropdown" class="relative p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Benachrichtigungen">
                        <i class="fa-regular fa-bell text-xl"></i>
                        <span x-show="unreadCount > 0" x-text="unreadCount"
                              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"
                              style="z-index: 10002;"></span>
                    </button>

                    <!-- Dropdown -->
                    <div x-show="showDropdown" @click.away="showDropdown = false" x-cloak
                         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden" style="z-index: 10000;">
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

                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button
                        @click="open = !open"
                        @click.away="open = false"
                        class="flex items-center space-x-2 p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                            {{ strtoupper(substr(auth('customer')->user()->name, 0, 1)) }}
                        </div>
                        <span class="text-sm font-medium">{{ auth('customer')->user()->name }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition
                        class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-[10000]"
                    >
                        <a href="{{ route('customer.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2"></i>Dashboard
                        </a>
                        <div class="border-t border-gray-100"></div>
                        <form method="POST" action="{{ route('customer.logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i>Abmelden
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <!-- Login & Register Buttons -->
                <a
                    href="{{ route('customer.login') }}"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>Anmelden
                </a>
                <a
                    href="{{ route('customer.register') }}"
                    class="px-4 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                >
                    <i class="fas fa-user-plus mr-2"></i>Registrieren
                </a>
            @endauth
        </div>
    </div>
</header>

@auth('customer')
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
@endauth
