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
                    <span class="flex h-9 w-9 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <!-- Card -->
                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-lg">
                        <div class="px-8 py-8 sm:px-10">
                            <!-- Header -->
                            <div class="flex w-full flex-col text-center mb-8">
                                <h1 class="text-2xl font-semibold text-stone-900 dark:text-white mb-2">Two-Factor Authentication</h1>
                                <p class="text-sm text-stone-600 dark:text-stone-400" x-data="{ recovery: false }" x-show="!recovery">
                                    Please confirm access to your account by entering the authentication code provided by your authenticator application.
                                </p>
                                <p class="text-sm text-stone-600 dark:text-stone-400" x-data="{ recovery: false }" x-show="recovery" style="display: none;">
                                    Please confirm access to your account by entering one of your emergency recovery codes.
                                </p>
                            </div>

                            <!-- Form -->
                            <form method="POST" action="{{ route('customer.two-factor.login') }}" x-data="{ recovery: false }" class="space-y-5">
                                @csrf

                                <!-- Code Input -->
                                <div x-show="!recovery">
                                    <label for="code" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                        Authentication Code
                                    </label>
                                    <input
                                        id="code"
                                        type="text"
                                        name="code"
                                        inputmode="numeric"
                                        autofocus
                                        autocomplete="one-time-code"
                                        placeholder="000000"
                                        x-ref="code"
                                        class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                    >
                                    @error('code')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Recovery Code Input -->
                                <div x-show="recovery" style="display: none;">
                                    <label for="recovery_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                        Recovery Code
                                    </label>
                                    <input
                                        id="recovery_code"
                                        type="text"
                                        name="recovery_code"
                                        autocomplete="one-time-code"
                                        placeholder="XXXXX-XXXXX"
                                        x-ref="recovery_code"
                                        class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                    >
                                    @error('recovery_code')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Toggle Recovery -->
                                <div class="flex justify-end">
                                    <button
                                        type="button"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                                        x-show="!recovery"
                                        x-on:click="
                                            recovery = true;
                                            $nextTick(() => { $refs.recovery_code.focus() })
                                        "
                                    >
                                        Use a recovery code
                                    </button>

                                    <button
                                        type="button"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                                        x-show="recovery"
                                        style="display: none;"
                                        x-on:click="
                                            recovery = false;
                                            $nextTick(() => { $refs.code.focus() })
                                        "
                                    >
                                        Use an authentication code
                                    </button>
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
                                >
                                    Verify
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
