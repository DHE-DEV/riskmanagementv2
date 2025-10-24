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
                                <h1 class="text-2xl font-semibold text-stone-900 dark:text-white mb-2">Create account</h1>
                                <p class="text-sm text-stone-600 dark:text-stone-400">Get started with your customer account</p>
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
                                        <span class="bg-white dark:bg-stone-950 px-4 text-stone-500 dark:text-stone-400">Or register with email</span>
                                    </div>
                                </div>
                            @endif

                            <!-- Registration Form -->
                            <form method="POST" action="{{ route('customer.register') }}" class="space-y-5">
                                @csrf

                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                        Full name
                                    </label>
                                    <input
                                        id="name"
                                        type="text"
                                        name="name"
                                        value="{{ old('name') }}"
                                        required
                                        autofocus
                                        autocomplete="name"
                                        placeholder="John Doe"
                                        class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                    >
                                    @error('name')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                        Email address
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
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
                                        Password
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
                                        Confirm password
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
                                        I agree to the
                                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">Terms of Service</a>
                                        and
                                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">Privacy Policy</a>
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
                                    Create account
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Login Link -->
                    <div class="text-center text-sm text-stone-600 dark:text-stone-400">
                        Already have an account?
                        <a href="{{ route('customer.login') }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                            Sign in
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
