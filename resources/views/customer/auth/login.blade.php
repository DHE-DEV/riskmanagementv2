<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-gradient-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="flex min-h-screen flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium">
                    <img src="/logo.png" alt="Passolution" class="h-12 w-auto" />
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <!-- Card -->
                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-lg">
                        <div class="px-8 py-8 sm:px-10">
                            <!-- Header -->
                            <div class="flex w-full flex-col text-center mb-8">
                                <h1 class="text-2xl font-semibold text-stone-900 dark:text-white mb-2">Passolution Travel Information Platform</h1>
                                <p class="text-sm text-stone-600 dark:text-stone-400">Melden Sie sich bei Ihrem Kundenkonto an</p>
                            </div>

                            <!-- Session Status -->
                            @if (session('status'))
                                <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-sm text-green-800 dark:text-green-200 text-center">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <!-- Keycloak SSO Button -->
                            @if(config('services.keycloak.client_id'))
                                <div class="mb-6">
                                    <a href="{{ route('auth.keycloak.redirect') }}"
                                       class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#4e73df] hover:bg-[#3a5bc7] active:bg-[#2f4bb0] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-[#4e73df] focus:ring-offset-2 dark:focus:ring-offset-stone-950">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                                        </svg>
                                        Mit Passolution SSO anmelden
                                    </a>
                                </div>
                            @endif

                            <!-- Social Login Buttons -->
                            @php
                                $hasSocialLogin = config('services.google.client_id')
                                    || config('services.facebook.client_id')
                                    || config('services.linkedin.client_id')
                                    || config('services.twitter.client_id');
                            @endphp

                            @if($hasSocialLogin)
                                <div class="mb-6 space-y-3">
                                    @if(config('services.google.client_id'))
                                        <x-social-button provider="google" href="{{ route('customer.auth.redirect', 'google') }}" />
                                    @endif

                                    @if(config('services.facebook.client_id'))
                                        <x-social-button provider="facebook" href="{{ route('customer.auth.redirect', 'facebook') }}" />
                                    @endif

                                    @if(config('services.linkedin.client_id') || config('services.twitter.client_id'))
                                        <div class="grid grid-cols-2 gap-3">
                                            @if(config('services.linkedin.client_id'))
                                                <x-social-button provider="linkedin" href="{{ route('customer.auth.redirect', 'linkedin') }}">
                                                    <span class="hidden sm:inline">LinkedIn</span>
                                                    <span class="sm:hidden">In</span>
                                                </x-social-button>
                                            @endif

                                            @if(config('services.twitter.client_id'))
                                                <x-social-button provider="twitter" href="{{ route('customer.auth.redirect', 'twitter') }}">
                                                    <span class="hidden sm:inline">X</span>
                                                    <span class="sm:hidden">X</span>
                                                </x-social-button>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Divider -->
                                <div class="relative mb-6">
                                    <div class="absolute inset-0 flex items-center">
                                        <div class="w-full border-t border-stone-300 dark:border-stone-700"></div>
                                    </div>
                                    <div class="relative flex justify-center text-sm">
                                        <span class="bg-white dark:bg-stone-950 px-4 text-stone-500 dark:text-stone-400">Oder fortfahren mit E-Mail</span>
                                    </div>
                                </div>
                            @endif

                            <!-- Login Tabs -->
                            <div x-data="{ tab: '{{ old('_tab', 'email') }}' }">
                                <div class="flex border-b border-stone-200 dark:border-stone-700 mb-6">
                                    <button type="button"
                                            @click="tab = 'email'"
                                            :class="tab === 'email'
                                                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300'"
                                            class="w-1/2 py-2.5 text-center border-b-2 text-sm font-medium transition-colors">
                                        Login mit E-Mail
                                    </button>
                                    <button type="button"
                                            @click="tab = 'password'"
                                            :class="tab === 'password'
                                                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300'"
                                            class="w-1/2 py-2.5 text-center border-b-2 text-sm font-medium transition-colors">
                                        Login mit Passwort
                                    </button>
                                </div>

                                <!-- Tab: Login mit E-Mail (Magic Link) -->
                                <div x-show="tab === 'email'" x-cloak>
                                    <p class="text-sm text-stone-600 dark:text-stone-400 mb-5">
                                        Geben Sie Ihre E-Mail-Adresse ein, um sich mit einem Einmalcode anzumelden.
                                    </p>

                                    <form method="POST" action="{{ route('customer.magic-login.send') }}" class="space-y-5">
                                        @csrf
                                        <input type="hidden" name="_tab" value="email">

                                        <div>
                                            <label for="magic_email" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                                E-Mail-Adresse
                                            </label>
                                            <input
                                                id="magic_email"
                                                type="email"
                                                name="email"
                                                value="{{ old('email') }}"
                                                required
                                                autofocus
                                                autocomplete="email"
                                                placeholder="email@beispiel.de"
                                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                            >
                                            @error('email')
                                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <button
                                            type="submit"
                                            class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
                                        >
                                            Login-Link senden
                                        </button>
                                    </form>
                                </div>

                                <!-- Tab: Login mit Passwort -->
                                <div x-show="tab === 'password'" x-cloak>
                                    <form method="POST" action="{{ route('customer.login') }}" class="space-y-5">
                                        @csrf
                                        <input type="hidden" name="_tab" value="password">

                                        <!-- Email -->
                                        <div>
                                            <label for="email" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                                E-Mail-Adresse
                                            </label>
                                            <input
                                                id="email"
                                                type="email"
                                                name="email"
                                                value="{{ old('email') }}"
                                                required
                                                autofocus
                                                autocomplete="email"
                                                placeholder="email@beispiel.de"
                                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                            >
                                            @error('email')
                                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Password -->
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <label for="password" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                                                    Passwort
                                                </label>
                                                <a href="{{ route('customer.password.request') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                                    Passwort vergessen?
                                                </a>
                                            </div>
                                            <input
                                                id="password"
                                                type="password"
                                                name="password"
                                                required
                                                autocomplete="current-password"
                                                placeholder="Geben Sie Ihr Passwort ein"
                                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                            >
                                            @error('password')
                                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Remember Me -->
                                        <div class="flex items-center">
                                            <input
                                                id="remember"
                                                type="checkbox"
                                                name="remember"
                                                class="h-4 w-4 rounded border-stone-300 dark:border-stone-700 text-blue-600 focus:ring-2 focus:ring-blue-500/20 dark:bg-stone-900"
                                            >
                                            <label for="remember" class="ml-2 block text-sm text-stone-700 dark:text-stone-300">
                                                Angemeldet bleiben für 30 Tage
                                            </label>
                                        </div>

                                        <!-- Submit Button -->
                                        <button
                                            type="submit"
                                            class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
                                        >
                                            Anmelden
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Register Link -->
                    <div class="text-center text-sm text-stone-600 dark:text-stone-400">
                        Sie haben noch kein Konto?
                        <a href="{{ route('customer.register') }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                            Konto erstellen
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
