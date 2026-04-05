<div>
    @if($success)
        {{-- Erfolgsanzeige --}}
        <div class="px-8 py-8 sm:px-10">
            <div class="flex justify-center mb-6">
                <div class="rounded-full bg-green-100 dark:bg-green-900/30 p-4">
                    <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>

            <div class="flex w-full flex-col text-center mb-6">
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-white mb-2">Registrierung erfolgreich!</h1>
                <p class="text-sm text-stone-600 dark:text-stone-400">
                    Wir haben Ihnen eine E-Mail mit dem Betreff<br><strong class="text-stone-900 dark:text-white">"E-Mail-Adresse bestätigen"</strong> gesendet.
                </p>
            </div>

            <div class="mb-6 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4 text-sm text-blue-800 dark:text-blue-200">
                <p class="mb-2">Bitte bestätigen Sie Ihre E-Mail-Adresse, indem Sie in der E-Mail auf die Schaltfläche<br><strong>"E-Mail-Adresse bestätigen"</strong> klicken.</p>
                <p>Erst danach ist Ihr Account aktiviert und Sie können sich einloggen.</p>
            </div>

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

            <a href="{{ route('customer.login') }}"
                class="w-full inline-flex justify-center rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950">
                Zum Login
            </a>
        </div>
    @else
        <div class="px-8 py-8 sm:px-10">
            {{-- Header --}}
            <div class="flex w-full flex-col text-center mb-6">
                <h1 class="text-xl font-semibold text-stone-900 dark:text-white whitespace-nowrap mb-1">Passolution Travel Information Platform</h1>
                <p class="text-lg font-semibold text-stone-900 dark:text-white mb-2">Konto erstellen</p>
            </div>

            {{-- Step Indicator --}}
            <div class="flex items-center justify-center mb-8">
                @for($i = 1; $i <= $totalSteps; $i++)
                    {{-- Step circle --}}
                    <button
                        wire:click="goToStep({{ $i }})"
                        @class([
                            'flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold transition-colors',
                            'bg-blue-600 text-white' => $i === $step,
                            'bg-green-600 text-white cursor-pointer' => $i < $step,
                            'bg-stone-200 dark:bg-stone-700 text-stone-500 dark:text-stone-400 cursor-default' => $i > $step,
                        ])
                        {{ $i > $step ? 'disabled' : '' }}
                    >
                        @if($i < $step)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            {{ $i }}
                        @endif
                    </button>

                    @if($i < $totalSteps)
                        {{-- Connector line --}}
                        <div @class([
                            'w-12 sm:w-16 h-0.5 mx-1',
                            'bg-green-600' => $i < $step,
                            'bg-stone-200 dark:bg-stone-700' => $i >= $step,
                        ])></div>
                    @endif
                @endfor
            </div>

            {{-- Step labels --}}
            <div class="flex justify-between mb-6 px-1">
                <span @class(['text-xs text-center w-20', 'text-blue-600 dark:text-blue-400 font-medium' => $step === 1, 'text-green-600 dark:text-green-400' => $step > 1, 'text-stone-400' => $step < 1])>Konto</span>
                <span @class(['text-xs text-center w-20', 'text-blue-600 dark:text-blue-400 font-medium' => $step === 2, 'text-green-600 dark:text-green-400' => $step > 2, 'text-stone-400' => $step < 2])>Kundentyp</span>
                <span @class(['text-xs text-center w-20', 'text-blue-600 dark:text-blue-400 font-medium' => $step === 3, 'text-stone-400' => $step < 3])>
                    {{ $customer_type === 'business' ? 'Firmendaten' : 'Abschluss' }}
                </span>
            </div>

            {{-- Error from submit --}}
            @error('submit')
                <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-sm text-red-800 dark:text-red-200 text-center">
                    {{ $message }}
                </div>
            @enderror

            {{-- ========== STEP 1: Konto ========== --}}
            <div x-show="$wire.step === 1" x-cloak>
                <div class="space-y-5">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">Vollständiger Name</label>
                        <input id="name" type="text" wire:model="name" autocomplete="name" placeholder="Max Mustermann"
                            class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                        @error('name') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">E-Mail-Adresse</label>
                        <input id="email" type="email" wire:model="email" autocomplete="email" placeholder="ihre.email@beispiel.de"
                            class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                        @error('email') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">Passwort</label>
                        <input id="password" type="password" wire:model="password" autocomplete="new-password" placeholder="Sicheres Passwort erstellen"
                            class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                        @error('password') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Password Confirmation --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">Passwort bestätigen</label>
                        <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" placeholder="Passwort wiederholen"
                            class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                    </div>

                    {{-- Terms --}}
                    <div class="flex items-start">
                        <input id="terms" type="checkbox" wire:model="terms"
                            class="mt-1 h-4 w-4 rounded border-stone-300 dark:border-stone-700 text-blue-600 focus:ring-2 focus:ring-blue-500/20 dark:bg-stone-900">
                        <label for="terms" class="ml-2 block text-sm text-stone-700 dark:text-stone-300">
                            Ich stimme den
                            <a href="https://www.passolution.de/agb/" target="_blank" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 transition-colors">Nutzungsbedingungen</a>
                            und der
                            <a href="https://www.passolution.de/datenschutz/" target="_blank" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 transition-colors">Datenschutzerklärung</a>
                            zu
                        </label>
                    </div>
                    @error('terms') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                    {{-- Next --}}
                    <button wire:click="nextStep" type="button"
                        class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950">
                        Weiter
                    </button>
                </div>
            </div>

            {{-- ========== STEP 2: Kundentyp ========== --}}
            <div x-show="$wire.step === 2" x-cloak>
                <div class="space-y-6">
                    {{-- Kundentyp --}}
                    <div>
                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-3">Ich bin...</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" wire:click="$set('customer_type', 'private')"
                                @class([
                                    'flex flex-col items-center gap-2 rounded-lg border-2 p-4 text-sm font-medium transition-colors',
                                    'border-blue-600 bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400' => $customer_type === 'private',
                                    'border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-400 hover:border-stone-300 dark:hover:border-stone-600' => $customer_type !== 'private',
                                ])>
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Privatkunde
                            </button>
                            <button type="button" wire:click="$set('customer_type', 'business')"
                                @class([
                                    'flex flex-col items-center gap-2 rounded-lg border-2 p-4 text-sm font-medium transition-colors',
                                    'border-blue-600 bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400' => $customer_type === 'business',
                                    'border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-400 hover:border-stone-300 dark:hover:border-stone-600' => $customer_type !== 'business',
                                ])>
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Geschäftskunde
                            </button>
                        </div>
                        @error('customer_type') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Geschäftstyp (nur bei Business) --}}
                    @if($customer_type === 'business')
                        <div>
                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-3">Geschäftstyp (Mehrfachauswahl möglich)</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(self::BUSINESS_TYPES as $key => $label)
                                    <button type="button" wire:click="toggleBusinessType('{{ $key }}')"
                                        @class([
                                            'rounded-full px-4 py-2 text-sm font-medium border transition-colors',
                                            'bg-blue-600 text-white border-blue-600' => in_array($key, $business_type),
                                            'bg-white dark:bg-stone-900 text-stone-600 dark:text-stone-400 border-stone-300 dark:border-stone-700 hover:border-blue-400' => !in_array($key, $business_type),
                                        ])>
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                            @error('business_type') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    {{-- Navigation --}}
                    <div class="flex gap-3">
                        <button wire:click="previousStep" type="button"
                            class="flex-1 rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 hover:bg-stone-50 dark:hover:bg-stone-800 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950">
                            Zurück
                        </button>
                        <button wire:click="nextStep" type="button"
                            class="flex-1 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950">
                            Weiter
                        </button>
                    </div>
                </div>
            </div>

            {{-- ========== STEP 3: Firma/Abschluss ========== --}}
            <div x-show="$wire.step === 3" x-cloak>
                <div class="space-y-5">
                    @if($customer_type === 'business')
                        {{-- Firmenname --}}
                        <div>
                            <label for="company_name" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">Firmenname</label>
                            <input id="company_name" type="text" wire:model="company_name" placeholder="Muster GmbH"
                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                            @error('company_name') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        {{-- Straße + Hausnummer --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <label for="company_street" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">Straße</label>
                                <input id="company_street" type="text" wire:model="company_street" placeholder="Musterstraße"
                                    class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                                @error('company_street') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="company_house_number" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">Nr.</label>
                                <input id="company_house_number" type="text" wire:model="company_house_number" placeholder="1a"
                                    class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                                @error('company_house_number') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- PLZ + Ort --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label for="company_postal_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">PLZ</label>
                                <input id="company_postal_code" type="text" wire:model="company_postal_code" placeholder="12345"
                                    class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                                @error('company_postal_code') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <label for="company_city" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">Ort</label>
                                <input id="company_city" type="text" wire:model="company_city" placeholder="Musterstadt"
                                    class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                                @error('company_city') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Land --}}
                        <div>
                            <label for="company_country" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">Land</label>
                            <select id="company_country" wire:model="company_country"
                                class="block w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-4 py-2.5 text-stone-900 dark:text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors">
                                @foreach(self::COUNTRIES as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        {{-- Privatkunde: Zusammenfassung --}}
                        <div class="rounded-lg bg-stone-50 dark:bg-stone-900/50 border border-stone-200 dark:border-stone-800 p-5">
                            <h3 class="text-sm font-semibold text-stone-900 dark:text-white mb-3">Ihre Angaben</h3>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-stone-500 dark:text-stone-400">Name</dt>
                                    <dd class="text-stone-900 dark:text-white font-medium">{{ $name }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-stone-500 dark:text-stone-400">E-Mail</dt>
                                    <dd class="text-stone-900 dark:text-white font-medium">{{ $email }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-stone-500 dark:text-stone-400">Kundentyp</dt>
                                    <dd class="text-stone-900 dark:text-white font-medium">Privatkunde</dd>
                                </div>
                            </dl>
                        </div>
                    @endif

                    {{-- Navigation --}}
                    <div class="flex gap-3">
                        <button wire:click="previousStep" type="button"
                            class="flex-1 rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 hover:bg-stone-50 dark:hover:bg-stone-800 px-4 py-2.5 text-sm font-semibold text-stone-700 dark:text-stone-300 shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950">
                            Zurück
                        </button>
                        <button wire:click="submit" type="button"
                            class="flex-1 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950">
                            Konto erstellen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
