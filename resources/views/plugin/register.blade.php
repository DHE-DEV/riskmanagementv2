<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <title>Plugin-Zugang registrieren - Global Travel Monitor</title>
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-gradient-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="flex min-h-screen flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-xl flex-col gap-6">
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
                                <h1 class="text-2xl font-semibold text-stone-900 dark:text-white mb-2">Plugin-Zugang registrieren</h1>
                                <p class="text-sm text-stone-600 dark:text-stone-400">Durch Ihre Registrierung schalten Sie jetzt kostenlos das Global Travel Monitor Plugin zur Nutzung in Ihrer Website oder Anwendung frei.</p>
                            </div>

                            @if (session('error'))
                                <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ session('error') }}</p>
                                </div>
                            @endif

                            <!-- Registration Form -->
                            <form method="POST" action="{{ route('plugin.register.store') }}" class="space-y-6">
                                @csrf

                                <!-- Section: Account -->
                                <div class="space-y-4">
                                    <h2 class="text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wide">Zugangsdaten</h2>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                            E-Mail-Adresse *
                                        </label>
                                        <input
                                            id="email"
                                            type="email"
                                            name="email"
                                            value="{{ old('email') }}"
                                            required
                                            autocomplete="email"
                                            placeholder="ihre@email.de"
                                            class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                        >
                                        @error('email')
                                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Password Row -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="password" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                                Passwort *
                                            </label>
                                            <input
                                                id="password"
                                                type="password"
                                                name="password"
                                                required
                                                autocomplete="new-password"
                                                placeholder="Min. 8 Zeichen"
                                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                            >
                                            @error('password')
                                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="password_confirmation" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                                Passwort bestätigen *
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
                                        </div>
                                    </div>
                                </div>

                                <!-- Section: Company -->
                                <div class="space-y-4 pt-4 border-t border-stone-200 dark:border-stone-800">
                                    <h2 class="text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wide">Firmendaten</h2>

                                    <!-- Contact Name & Company Name -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="contact_name" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                                Ansprechpartner *
                                            </label>
                                            <input
                                                id="contact_name"
                                                type="text"
                                                name="contact_name"
                                                value="{{ old('contact_name') }}"
                                                required
                                                autocomplete="name"
                                                placeholder="Max Mustermann"
                                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                            >
                                            @error('contact_name')
                                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="company_name" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                                Firmenname *
                                            </label>
                                            <input
                                                id="company_name"
                                                type="text"
                                                name="company_name"
                                                value="{{ old('company_name') }}"
                                                required
                                                autocomplete="organization"
                                                placeholder="Muster GmbH"
                                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                            >
                                            @error('company_name')
                                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Street & House Number -->
                                    <div class="grid grid-cols-3 gap-4">
                                        <div class="col-span-2">
                                            <label for="company_street" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                                Straße *
                                            </label>
                                            <input
                                                id="company_street"
                                                type="text"
                                                name="company_street"
                                                value="{{ old('company_street') }}"
                                                required
                                                autocomplete="address-line1"
                                                placeholder="Musterstraße"
                                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                            >
                                            @error('company_street')
                                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="company_house_number" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                                Nr. *
                                            </label>
                                            <input
                                                id="company_house_number"
                                                type="text"
                                                name="company_house_number"
                                                value="{{ old('company_house_number') }}"
                                                required
                                                placeholder="123"
                                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                            >
                                            @error('company_house_number')
                                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- PLZ, City, Country -->
                                    <div class="grid grid-cols-4 gap-4">
                                        <div>
                                            <label for="company_postal_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                                PLZ *
                                            </label>
                                            <input
                                                id="company_postal_code"
                                                type="text"
                                                name="company_postal_code"
                                                value="{{ old('company_postal_code') }}"
                                                required
                                                autocomplete="postal-code"
                                                placeholder="12345"
                                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                            >
                                            @error('company_postal_code')
                                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="col-span-2">
                                            <label for="company_city" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                                Ort *
                                            </label>
                                            <input
                                                id="company_city"
                                                type="text"
                                                name="company_city"
                                                value="{{ old('company_city') }}"
                                                required
                                                autocomplete="address-level2"
                                                placeholder="Musterstadt"
                                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                            >
                                            @error('company_city')
                                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="company_country" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                                Land *
                                            </label>
                                            <input
                                                id="company_country"
                                                type="text"
                                                name="company_country"
                                                value="{{ old('company_country', 'Deutschland') }}"
                                                required
                                                autocomplete="country-name"
                                                placeholder="Deutschland"
                                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                            >
                                            @error('company_country')
                                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Section: Business Type -->
                                <div class="space-y-4 pt-4 border-t border-stone-200 dark:border-stone-800">
                                    <h2 class="text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wide">Geschäftstyp</h2>

                                    <div x-data="{
                                        selected: {{ json_encode(old('business_types', [])) }},
                                        toggle(value) {
                                            if (this.selected.includes(value)) {
                                                this.selected = this.selected.filter(v => v !== value);
                                            } else {
                                                this.selected.push(value);
                                            }
                                        },
                                        isSelected(value) {
                                            return this.selected.includes(value);
                                        }
                                    }">
                                        <p class="text-sm text-stone-600 dark:text-stone-400 mb-3">
                                            Wählen Sie Ihren Geschäftstyp (Mehrfachauswahl möglich)
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="type in [
                                                { value: 'travel_agency', label: 'Reisebüro' },
                                                { value: 'organizer', label: 'Veranstalter' },
                                                { value: 'online_provider', label: 'Online Anbieter' },
                                                { value: 'mobile_travel_consultant', label: 'Mobiler Reiseberater' },
                                                { value: 'software_provider', label: 'Softwareanbieter' },
                                                { value: 'other', label: 'Sonstiges' }
                                            ]" :key="type.value">
                                                <button
                                                    type="button"
                                                    @click="toggle(type.value)"
                                                    :class="isSelected(type.value)
                                                        ? 'bg-blue-600 text-white border-blue-600 dark:bg-blue-600 dark:border-blue-600'
                                                        : 'bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-300 border-stone-300 dark:border-stone-700 hover:border-blue-400 dark:hover:border-blue-500'"
                                                    class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors"
                                                    x-text="type.label"
                                                ></button>
                                            </template>
                                        </div>
                                        <!-- Hidden inputs for form submission -->
                                        <template x-for="value in selected" :key="value">
                                            <input type="hidden" name="business_types[]" :value="value">
                                        </template>
                                    </div>
                                    @error('business_types')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Section: Usage Type & Domain -->
                                <div class="space-y-4 pt-4 border-t border-stone-200 dark:border-stone-800" x-data="{
                                    usageType: '{{ old('usage_type', 'website') }}',
                                    needsDomain() {
                                        return this.usageType === 'website' || this.usageType === 'both';
                                    }
                                }">
                                    <h2 class="text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wide">Plugin-Einbindung</h2>

                                    <!-- Usage Type Selection -->
                                    <div>
                                        <p class="text-sm text-stone-600 dark:text-stone-400 mb-3">
                                            Wie möchten Sie das Plugin nutzen?
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                @click="usageType = 'website'"
                                                :class="usageType === 'website'
                                                    ? 'bg-blue-600 text-white border-blue-600 dark:bg-blue-600 dark:border-blue-600'
                                                    : 'bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-300 border-stone-300 dark:border-stone-700 hover:border-blue-400 dark:hover:border-blue-500'"
                                                class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors"
                                            >
                                                Website
                                            </button>
                                            <button
                                                type="button"
                                                @click="usageType = 'app'"
                                                :class="usageType === 'app'
                                                    ? 'bg-blue-600 text-white border-blue-600 dark:bg-blue-600 dark:border-blue-600'
                                                    : 'bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-300 border-stone-300 dark:border-stone-700 hover:border-blue-400 dark:hover:border-blue-500'"
                                                class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors"
                                            >
                                                App (WebView)
                                            </button>
                                            <button
                                                type="button"
                                                @click="usageType = 'both'"
                                                :class="usageType === 'both'
                                                    ? 'bg-blue-600 text-white border-blue-600 dark:bg-blue-600 dark:border-blue-600'
                                                    : 'bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-300 border-stone-300 dark:border-stone-700 hover:border-blue-400 dark:hover:border-blue-500'"
                                                class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors"
                                            >
                                                Beides
                                            </button>
                                        </div>
                                        <input type="hidden" name="usage_type" :value="usageType">
                                        @error('usage_type')
                                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Domain Field (conditional) -->
                                    <div x-show="needsDomain()" x-transition>
                                        <label for="domain" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                            Ihre Website-Domain <span x-show="needsDomain()">*</span>
                                        </label>
                                        <input
                                            id="domain"
                                            type="text"
                                            name="domain"
                                            value="{{ old('domain') }}"
                                            :required="needsDomain()"
                                            placeholder="ihre-website.de"
                                            class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                                        >
                                        <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">
                                            Die Domain, auf der Sie das Plugin einbinden möchten (ohne https:// oder www.)
                                        </p>
                                        @error('domain')
                                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- App Info -->
                                    <div x-show="usageType === 'app'" x-transition class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                                        <div class="flex items-start gap-3">
                                            <svg class="h-5 w-5 text-blue-500 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                                Nach der Registrierung erhalten Sie eine URL, die Sie in Ihrer App (Android WebView, iOS WKWebView, Electron, etc.) laden können.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Terms -->
                                <div class="pt-4 border-t border-stone-200 dark:border-stone-800">
                                    <div class="flex items-start">
                                        <input
                                            id="terms"
                                            type="checkbox"
                                            name="terms"
                                            required
                                            class="mt-1 h-4 w-4 rounded border-stone-300 dark:border-stone-700 text-blue-600 focus:ring-2 focus:ring-blue-500/20 dark:bg-stone-900"
                                        >
                                        <label for="terms" class="ml-2 block text-sm text-stone-700 dark:text-stone-300">
                                            Ich akzeptiere die
                                            <a href="https://www.passolution.de/agb/" target="_blank" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">Allgemeinen Geschäftsbedingungen</a>
                                            und die
                                            <a href="https://www.passolution.de/datenschutz/" target="_blank" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">Datenschutzerklärung</a>
                                        </label>
                                    </div>
                                    @error('terms')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
                                >
                                    Plugin-Zugang erstellen
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Login Link -->
                    <div class="text-center text-sm text-stone-600 dark:text-stone-400">
                        Bereits registriert?
                        <a href="{{ route('customer.login') }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                            Jetzt anmelden
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
