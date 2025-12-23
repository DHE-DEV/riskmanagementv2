<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <title>E-Mail verifizieren - Global Travel Monitor</title>
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-gradient-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="flex min-h-screen flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium">
                    <img src="/logo.png" alt="Global Travel Monitor" class="h-12 w-auto" />
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <!-- Card -->
                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-lg">
                        <div class="px-8 py-8 sm:px-10">
                            <!-- Header -->
                            <div class="flex w-full flex-col text-center mb-8">
                                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                </div>
                                <h1 class="text-2xl font-semibold text-stone-900 dark:text-white mb-2">E-Mail verifizieren</h1>
                                <p class="text-sm text-stone-600 dark:text-stone-400">
                                    Wir haben einen 6-stelligen Verifizierungscode an<br>
                                    <strong class="text-stone-900 dark:text-white">{{ $email }}</strong><br>
                                    gesendet.
                                </p>
                            </div>

                            @if (session('success'))
                                <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                                    <p class="text-sm text-green-600 dark:text-green-400">{{ session('success') }}</p>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ session('error') }}</p>
                                </div>
                            @endif

                            <!-- Verification Form -->
                            <form method="POST" action="{{ route('plugin.verify-email.verify', $token) }}" class="space-y-6">
                                @csrf

                                <!-- Code Input -->
                                <div>
                                    <label for="code" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                        Verifizierungscode
                                    </label>
                                    <input
                                        id="code"
                                        type="text"
                                        name="code"
                                        value="{{ old('code') }}"
                                        required
                                        autocomplete="one-time-code"
                                        inputmode="numeric"
                                        pattern="[0-9]{6}"
                                        maxlength="6"
                                        placeholder="000000"
                                        class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-3 text-center text-2xl font-mono tracking-[0.5em] text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                    >
                                    @error('code')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Remaining Attempts -->
                                @if ($remainingAttempts < 5)
                                    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
                                        <p class="text-sm text-amber-700 dark:text-amber-400">
                                            <strong>{{ $remainingAttempts }}</strong> {{ $remainingAttempts === 1 ? 'Versuch' : 'Versuche' }} verbleibend
                                        </p>
                                    </div>
                                @endif

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
                                >
                                    Code bestätigen
                                </button>
                            </form>

                            <!-- Spam Hint -->
                            <div class="mt-6 rounded-lg bg-stone-50 dark:bg-stone-900 border border-stone-200 dark:border-stone-800 p-4">
                                <div class="flex gap-3">
                                    <svg class="h-5 w-5 text-stone-500 dark:text-stone-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                    <div class="text-sm text-stone-600 dark:text-stone-400">
                                        <p class="font-medium text-stone-700 dark:text-stone-300 mb-1">E-Mail nicht erhalten?</p>
                                        <p>Bitte überprüfen Sie auch Ihren <strong>Spam-Ordner</strong>. Der Code ist {{ $expiryMinutes }} Minuten gültig.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Resend Code -->
                            <div class="mt-6 text-center">
                                <form method="POST" action="{{ route('plugin.verify-email.resend', $token) }}" class="inline">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                                    >
                                        Neuen Code senden
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Back Link -->
                    <div class="text-center text-sm text-stone-600 dark:text-stone-400">
                        <a href="{{ route('plugin.register') }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                            &larr; Zurück zur Registrierung
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
