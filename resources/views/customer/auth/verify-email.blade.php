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
                            <!-- Icon -->
                            <div class="flex justify-center mb-6">
                                <div class="rounded-full bg-blue-100 dark:bg-blue-900/30 p-4">
                                    <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Header -->
                            <div class="flex w-full flex-col text-center mb-6">
                                <h1 class="text-2xl font-semibold text-stone-900 dark:text-white mb-2">Verify your email</h1>
                                <p class="text-sm text-stone-600 dark:text-stone-400">
                                    Thanks for signing up! Before getting started, please verify your email address by clicking on the link we just emailed to you.
                                </p>
                            </div>

                            <!-- Success Status -->
                            @if (session('status') == 'verification-link-sent')
                                <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-sm text-green-800 dark:text-green-200 text-center">
                                    A new verification link has been sent to your email address.
                                </div>
                            @endif

                            <!-- Resend Verification Email Form -->
                            <form method="POST" action="{{ route('customer.verification.send') }}" class="space-y-5">
                                @csrf

                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
                                >
                                    Resend verification email
                                </button>
                            </form>

                            <!-- Logout Form -->
                            <div class="mt-6 text-center">
                                <form method="POST" action="{{ route('customer.logout') }}" class="inline">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-2 text-sm font-medium text-stone-600 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        Log out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Help Text -->
                    <div class="rounded-lg bg-stone-100 dark:bg-stone-900/50 border border-stone-200 dark:border-stone-800 p-4">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-stone-600 dark:text-stone-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-stone-600 dark:text-stone-400">
                                <p class="font-medium mb-1">Didn't receive the email?</p>
                                <p>Check your spam folder or click the button above to resend the verification email.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
