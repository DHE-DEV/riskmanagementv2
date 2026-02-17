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
                    @if(session('registration_success'))
                        <div class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-lg">
                            <div class="px-8 py-8 sm:px-10">
                                <!-- Icon -->
                                <div class="flex justify-center mb-6">
                                    <div class="rounded-full bg-green-100 dark:bg-green-900/30 p-4">
                                        <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Header -->
                                <div class="flex w-full flex-col text-center mb-6">
                                    <h1 class="text-2xl font-semibold text-stone-900 dark:text-white mb-2">Registrierung erfolgreich!</h1>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">
                                        Wir haben Ihnen eine E-Mail mit dem Betreff<br><strong class="text-stone-900 dark:text-white">"E-Mail-Adresse bestätigen"</strong> gesendet.
                                    </p>
                                </div>

                                <!-- Instructions -->
                                <div class="mb-6 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4 text-sm text-blue-800 dark:text-blue-200">
                                    <p class="mb-2">Bitte bestätigen Sie Ihre E-Mail-Adresse, indem Sie in der E-Mail auf die Schaltfläche<br><strong>"E-Mail-Adresse bestätigen"</strong> klicken.</p>
                                    <p>Erst danach ist Ihr Account aktiviert und Sie können sich einloggen.</p>
                                </div>

                                <!-- Help Text -->
                                <div class="mb-6 rounded-lg bg-stone-100 dark:bg-stone-900/50 border border-stone-200 dark:border-stone-800 p-4">
                                    <div class="flex gap-3">
                                        <svg class="w-5 h-5 text-stone-600 dark:text-stone-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <div class="text-sm text-stone-600 dark:text-stone-400">
                                            <p class="font-medium mb-1">Keine E-Mail erhalten?</p>
                                            <p>Prüfen Sie Ihren Spam-Ordner.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Login Button -->
                                <a
                                    href="https://global-travel-monitor.eu/customer/login"
                                    class="w-full inline-flex justify-center rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
                                >
                                    Zum Login
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-lg">
                            <div class="px-8 py-8 sm:px-10">
                                <!-- Header -->
                                <div class="flex w-full flex-col text-center mb-8">
                                    <h1 class="text-2xl font-semibold text-stone-900 dark:text-white mb-2">Konto erstellen</h1>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Erstellen Sie Ihr Kundenkonto</p>
                                </div>

                                <!-- Social Registration Buttons -->
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
                                            <span class="bg-white dark:bg-stone-950 px-4 text-stone-500 dark:text-stone-400">Oder mit E-Mail registrieren</span>
                                        </div>
                                    </div>
                                @endif

                                <!-- Registration Form -->
                                <form method="POST" action="{{ route('customer.register') }}" class="space-y-5">
                                    @csrf

                                    <!-- Name -->
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                            Vollständiger Name
                                        </label>
                                        <input
                                            id="name"
                                            type="text"
                                            name="name"
                                            value="{{ old('name') }}"
                                            required
                                            autofocus
                                            autocomplete="name"
                                            placeholder="Max Mustermann"
                                            class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                        >
                                        @error('name')
                                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

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
                                            autocomplete="email"
                                            placeholder="ihre.email@beispiel.de"
                                            class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                        >
                                        @error('email')
                                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Password -->
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                            Passwort
                                        </label>
                                        <input
                                            id="password"
                                            type="password"
                                            name="password"
                                            required
                                            autocomplete="new-password"
                                            placeholder="Sicheres Passwort erstellen"
                                            class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                        >
                                        @error('password')
                                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Password Confirmation -->
                                    <div>
                                        <label for="password_confirmation" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                            Passwort bestätigen
                                        </label>
                                        <input
                                            id="password_confirmation"
                                            type="password"
                                            name="password_confirmation"
                                            required
                                            autocomplete="new-password"
                                            placeholder="Passwort wiederholen"
                                            class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                        >
                                        @error('password_confirmation')
                                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Terms Acceptance -->
                                    <div class="flex items-start">
                                        <input
                                            id="terms"
                                            type="checkbox"
                                            name="terms"
                                            required
                                            class="mt-1 h-4 w-4 rounded border-stone-300 dark:border-stone-700 text-blue-600 focus:ring-2 focus:ring-blue-500/20 dark:bg-stone-900"
                                        >
                                        <label for="terms" class="ml-2 block text-sm text-stone-700 dark:text-stone-300">
                                            Ich stimme den
                                            <a href="https://www.passolution.de/agb/" target="_blank" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">Nutzungsbedingungen</a>
                                            und der
                                            <a href="https://www.passolution.de/datenschutz/" target="_blank" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">Datenschutzerklärung</a>
                                            zu
                                        </label>
                                    </div>
                                    @error('terms')
                                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror

                                    <!-- Submit Button -->
                                    <button
                                        type="submit"
                                        class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
                                    >
                                        Konto erstellen
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Login Link -->
                        <div class="text-center text-sm text-stone-600 dark:text-stone-400">
                            Bereits ein Konto?
                            <a href="{{ route('customer.login') }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                Anmelden
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
