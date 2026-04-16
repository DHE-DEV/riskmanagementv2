@props([])

<header class="header">
    <div class="flex items-center justify-between h-full px-4">
        <!-- Logo -->
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <img src="/logo.png" alt="Global Travel Monitor - Weltweites Reiserisiko-Monitoring und Sicherheitsinformationen" class="h-8 w-auto" style="margin-left:-5px"/>
                <span class="text-xl font-light tracking-wide text-gray-800" style="margin-left: 30px;">Passolution Travel Information Platform</span>
            </div>
        </div>

        <!-- User Actions -->
        <div class="flex items-center space-x-4">
            @auth('customer')
                <!-- Benachrichtigungen -->
                @if(config('app.customer_notifications_enabled', true))
                <div x-data="notificationDropdown()" class="relative" :style="'margin-right: 30px; margin-top: ' + (unreadCount > 0 ? '15px' : '8px')">
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
                                <div :class="notification.read_at ? 'bg-white' : 'bg-blue-50'"
                                     class="p-3 border-b border-gray-100 transition-colors group">
                                    <div class="flex items-start justify-between">
                                        <div @click="markAsRead(notification.id)" class="flex-1 cursor-pointer hover:opacity-80">
                                            <p class="text-sm text-gray-900" x-text="notification.data.message"></p>
                                            <p class="text-xs text-gray-500 mt-1" x-text="formatDate(notification.created_at)"></p>
                                        </div>
                                        <button @click.stop="deleteNotification(notification.id)"
                                                class="ml-2 p-1 text-gray-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity"
                                                title="Löschen">
                                            <i class="fa-regular fa-trash-can text-sm"></i>
                                        </button>
                                    </div>
                                    <!-- Download-Button für Export-Benachrichtigungen -->
                                    <div x-show="notification.data.type === 'branch_export' && notification.data.filename" class="mt-2">
                                        <a :href="'/customer/branches/download/' + notification.data.filename"
                                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors">
                                            <i class="fa-regular fa-download"></i>
                                            CSV herunterladen
                                        </a>
                                        <p x-show="notification.data.expires_formatted" class="text-xs text-orange-600 mt-2">
                                            <i class="fa-regular fa-clock"></i>
                                            Verfügbar bis: <span x-text="notification.data.expires_formatted"></span> Uhr
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                @endif

                @php
                    $originalCustomerId = session('original_customer_id');
                    $currentCustomer = auth('customer')->user();
                    $originalCustomer = $originalCustomerId ? \App\Models\Customer::find($originalCustomerId) : $currentCustomer;
                    $hasAccessibleAccounts = in_array($originalCustomer->email, config('app.agentur_super_admin_emails', []))
                        || $originalCustomer->accessibleAccounts()->exists();
                @endphp

                <!-- Account Switcher -->
                @if($hasAccessibleAccounts)
                <div class="relative" x-data="accountSwitcher()" @click.away="open = false">
                    <button @click="toggle()"
                            class="flex items-center space-x-2 px-3 py-1.5 text-sm rounded-lg transition-colors {{ $originalCustomerId ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-regular fa-arrows-repeat"></i>
                        <span class="text-xs font-medium">{{ $currentCustomer->company_name }}</span>
                        <span class="font-mono text-xs font-bold">({{ $currentCustomer->app_code }})</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="open" x-transition
                         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-[10000]">

                        {{-- Header with total count --}}
                        <div class="px-3 py-2 border-b border-gray-100 flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500 uppercase">Agentur wechseln</span>
                            <span class="text-xs text-gray-400" x-text="total + ' Agenturen'"></span>
                        </div>

                        {{-- Search --}}
                        <div class="p-2 border-b border-gray-100">
                            <input type="text" x-model="search" @input.debounce.300ms="searchAccounts()"
                                   placeholder="Suche: Code, PLZ, Ort, Firma..."
                                   class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- Own account (always visible) --}}
                        <form method="POST" action="{{ route('customer.account.switch', $originalCustomer) }}" class="border-b border-gray-100">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center justify-between {{ !$originalCustomerId ? 'bg-blue-50' : '' }}">
                                <div>
                                    <span class="font-medium">{{ $originalCustomer->company_name }}</span>
                                    <span class="text-xs text-gray-500 block">{{ $originalCustomer->email }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-400">Eigener</span>
                                    <span class="font-mono text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">{{ $originalCustomer->app_code }}</span>
                                </div>
                            </button>
                        </form>

                        {{-- Results --}}
                        <div class="max-h-[400px] overflow-y-auto">
                            <template x-if="loading">
                                <div class="p-4 text-center text-gray-500 text-sm">
                                    <i class="fa-regular fa-spinner-third fa-spin mr-1"></i> Laden...
                                </div>
                            </template>
                            <template x-if="!loading && accounts.length === 0">
                                <div class="p-4 text-center text-gray-500 text-sm">Keine Ergebnisse</div>
                            </template>
                            <template x-for="account in accounts" :key="account.id">
                                <form method="POST" :action="'/customer/account/switch/' + account.id">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <button type="submit" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center justify-between border-b border-gray-50"
                                            :class="account.id === {{ $currentCustomer->id }} ? 'bg-blue-50' : ''">
                                        <div class="min-w-0 flex-1">
                                            <span class="font-semibold block truncate" x-text="account.company_name"></span>
                                            <span class="text-xs text-gray-500 block" x-text="account.company_postal_code + ' ' + account.company_city"></span>
                                        </div>
                                        <span class="font-mono text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded flex-shrink-0 ml-2" x-text="account.app_code"></span>
                                    </button>
                                </form>
                            </template>
                        </div>

                        {{-- Pagination --}}
                        <div class="px-3 py-2 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-500" x-text="'Seite ' + page + ' von ' + lastPage"></span>
                            <div class="flex gap-1">
                                <button @click="prevPage()" :disabled="page <= 1"
                                        class="px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">
                                    <i class="fa-regular fa-chevron-left"></i>
                                </button>
                                <button @click="nextPage()" :disabled="page >= lastPage"
                                        class="px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">
                                    <i class="fa-regular fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- User Dropdown -->
                @php
                    $headerEmployee = session('logged_in_employee_id') ? \App\Models\Employee::find(session('logged_in_employee_id')) : null;
                    $headerDisplayName = $headerEmployee ? $headerEmployee->first_name . ' ' . $headerEmployee->last_name : $originalCustomer->name;
                @endphp
                <div id="user-menu" class="relative" x-data="{ open: false }">
                    <button
                        @click="open = !open"
                        @click.away="open = false"
                        class="flex items-center space-x-2 p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                            {{ strtoupper(substr($headerDisplayName, 0, 1)) }}
                        </div>
                        <span class="text-sm font-medium">{{ $headerDisplayName }}</span>
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
                        <a href="{{ route('customer.settings') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-cog mr-2"></i>Einstellungen
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
            @endauth

            @guest('customer')
                @if(config('app.navigation_guest_auth_enabled', true))
                <!-- Login & Register Buttons -->
                <a
                    href="{{ route('customer.login') }}"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                    style="background-color: #CEE741; color: #002742;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>Anmelden
                </a>
                @if(config('app.customer_registration_enabled', true))
                    <a
                        href="{{ route('customer.register') }}"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                        style="border: 1px solid #CEE741; color: #CEE741; background: transparent;"
                        onmouseover="this.style.backgroundColor='rgba(206,231,65,0.1)'" onmouseout="this.style.backgroundColor='transparent'"
                    >
                        <i class="fas fa-user-plus mr-2"></i>Registrieren
                    </a>
                @endif
                @endif
            @endguest
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

        async deleteNotification(id) {
            try {
                await fetch(`/customer/notifications/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });
                this.loadNotifications();
            } catch (error) {
                console.error('Error deleting notification:', error);
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
function accountSwitcher() {
    return {
        open: false,
        search: '',
        accounts: [],
        page: 1,
        lastPage: 1,
        total: 0,
        loading: false,

        toggle() {
            this.open = !this.open;
            if (this.open && this.accounts.length === 0) {
                this.loadAccounts();
            }
        },

        async loadAccounts() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.page,
                    search: this.search
                });
                const response = await fetch(`/customer/account/accessible?${params}`);
                const data = await response.json();
                this.accounts = data.data;
                this.lastPage = data.last_page;
                this.total = data.total;
            } catch (error) {
                console.error('Error loading accounts:', error);
            }
            this.loading = false;
        },

        searchAccounts() {
            this.page = 1;
            this.loadAccounts();
        },

        nextPage() {
            if (this.page < this.lastPage) {
                this.page++;
                this.loadAccounts();
            }
        },

        prevPage() {
            if (this.page > 1) {
                this.page--;
                this.loadAccounts();
            }
        }
    };
}
</script>
@endauth
