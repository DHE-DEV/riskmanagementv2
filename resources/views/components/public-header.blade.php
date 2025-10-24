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
