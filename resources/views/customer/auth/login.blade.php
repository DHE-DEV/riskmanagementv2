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
                                <h1 class="text-2xl font-semibold text-stone-900 dark:text-white mb-2">Willkommen zurück</h1>
                                <p class="text-sm text-stone-600 dark:text-stone-400">Melden Sie sich bei Ihrem Kundenkonto an</p>
                            </div>

                            <!-- Session Status -->
                            @if (session('status'))
                                <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-sm text-green-800 dark:text-green-200 text-center">
                                    {{ session('status') }}
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

                            <!-- Login Form -->
                            <form method="POST" action="{{ route('customer.login') }}" class="space-y-5">
                                @csrf

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
