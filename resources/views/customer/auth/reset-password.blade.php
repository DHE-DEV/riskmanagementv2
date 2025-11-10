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
                                <h1 class="text-2xl font-semibold text-stone-900 dark:text-white mb-2">Create new password</h1>
                                <p class="text-sm text-stone-600 dark:text-stone-400">
                                    Enter your new password below
                                </p>
                            </div>

                            <!-- Password Reset Form -->
                            <form method="POST" action="{{ route('customer.password.update') }}" class="space-y-5">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">

                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                        Email address
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value="{{ old('email', $email ?? '') }}"
                                        required
                                        autofocus
                                        autocomplete="email"
                                        placeholder="email@example.com"
                                        class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                    >
                                    @error('email')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Password -->
                                <div>
                                    <label for="password" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                        New password
                                    </label>
                                    <input
                                        id="password"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Create a strong password"
                                        class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                    >
                                    @error('password')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Password Confirmation -->
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                        Confirm new password
                                    </label>
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        name="password_confirmation"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Confirm your password"
                                        class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                    >
                                    @error('password_confirmation')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
                                >
                                    Reset password
                                </button>
                            </form>

                            <!-- Back to Login -->
                            <div class="mt-6 text-center">
                                <a href="{{ route('customer.login') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    Back to login
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
