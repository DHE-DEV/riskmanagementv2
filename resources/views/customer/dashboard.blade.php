<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <title>Customer Dashboard - {{ config('app.name') }}</title>
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-gradient-to-b dark:from-neutral-950 dark:to-neutral-900">
        <!-- Header -->
        <header class="bg-white dark:bg-stone-950 border-b dark:border-stone-800 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <a href="{{ route('home') }}" class="flex items-center">
                        <img src="/logo.png" alt="Passolution" class="h-8 w-auto" />
                    </a>

                    <!-- Navigation -->
                    <nav class="hidden md:flex items-center space-x-8">
                        <a href="{{ route('home') }}" class="text-sm text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white transition-colors">
                            Dashboard
                        </a>
                        <a href="{{ route('entry-conditions') }}" class="text-sm text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white transition-colors">
                            Einreisebestimmungen
                        </a>
                    </nav>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false"
                            class="flex items-center space-x-2 p-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-stone-800 rounded-lg transition-colors">
                            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                                {{ strtoupper(substr(auth('customer')->user()->name, 0, 1)) }}
                            </div>
                            <span class="text-sm font-medium">{{ auth('customer')->user()->name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-stone-900 rounded-lg shadow-lg py-1 z-50">
                            <a href="{{ route('customer.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-stone-800">
                                <i class="fas fa-user mr-2"></i>Dashboard
                            </a>
                            <div class="border-t border-gray-100 dark:border-stone-800"></div>
                            <form method="POST" action="{{ route('customer.logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-stone-800">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Abmelden
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white dark:bg-stone-950 shadow-sm rounded-lg p-6 border dark:border-stone-800">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Willkommen, {{ auth('customer')->user()->name }}!
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Dies ist Ihr Customer Dashboard
                    </p>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg border dark:border-blue-800">
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                            Profil
                        </h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            {{ auth('customer')->user()->email }}
                        </p>
                        @if(auth('customer')->user()->isSocialLogin())
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                                Angemeldet mit: {{ ucfirst(auth('customer')->user()->provider) }}
                            </p>
                        @endif
                    </div>

                    <div class="bg-green-50 dark:bg-green-900/20 p-6 rounded-lg border dark:border-green-800">
                        <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-2">
                            E-Mail Status
                        </h3>
                        <p class="text-sm text-green-700 dark:text-green-300">
                            @if(auth('customer')->user()->hasVerifiedEmail())
                                <span class="inline-flex items-center">
                                    <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Verifiziert
                                </span>
                            @else
                                <span class="text-yellow-600">Nicht verifiziert</span>
                            @endif
                        </p>
                    </div>

                    <div class="bg-purple-50 dark:bg-purple-900/20 p-6 rounded-lg border dark:border-purple-800">
                        <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-2">
                            Mitglied seit
                        </h3>
                        <p class="text-sm text-purple-700 dark:text-purple-300">
                            {{ auth('customer')->user()->created_at->format('d.m.Y') }}
                        </p>
                    </div>
                </div>

                <div class="mt-8 p-6 bg-gray-50 dark:bg-stone-900 rounded-lg border dark:border-stone-800">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Schnellzugriff
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="{{ route('home') }}" class="block p-4 bg-white dark:bg-stone-950 rounded-lg hover:shadow-md transition border dark:border-stone-800">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Reisewarnungen</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Aktuelle Warnungen ansehen</p>
                        </a>
                        <a href="{{ route('entry-conditions') }}" class="block p-4 bg-white dark:bg-stone-950 rounded-lg hover:shadow-md transition border dark:border-stone-800">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Einreisebestimmungen</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Bestimmungen prüfen</p>
                        </a>
                    </div>
                </div>
            </div>
        </main>

        @fluxScripts
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </body>
</html>
