<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-gradient-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="flex min-h-screen flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-xl flex-col gap-6">
                <!-- Card -->
                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-lg">
                        <div class="px-8 py-8 sm:px-10">
                            <!-- Logo -->
                            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium mb-4">
                                <img src="/logo.png" alt="Passolution" class="h-12 w-auto" />
                            </a>

                            <!-- Header -->
                            <div class="flex w-full flex-col text-center mb-6">
                                <h1 class="text-xl font-semibold text-stone-900 dark:text-white whitespace-nowrap mb-1">Passolution Travel Information Platform</h1>
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

                            {{-- Social Login + Divider temporär deaktiviert
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
                            --}}

                            <!-- Login Tabs -->
                            <div x-data="{ tab: '{{ old('_tab', 'email') }}' }">
                                <div class="flex gap-2 border-b-2 border-stone-200 dark:border-stone-700 mb-6">
                                    <button type="button"
                                            @click="tab = 'email'"
                                            :class="tab === 'email'
                                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/30'
                                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-900'"
                                            class="flex-1 py-3 text-center border-b-[3px] -mb-[2px] rounded-t-lg text-sm font-semibold transition-colors">
                                        Login mit E-Mail
                                    </button>
                                    <button type="button"
                                            @click="tab = 'password'"
                                            :class="tab === 'password'
                                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/30'
                                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-900'"
                                            class="flex-1 py-3 text-center border-b-[3px] -mb-[2px] rounded-t-lg text-sm font-semibold transition-colors">
                                        Login mit Passwort
                                    </button>
                                </div>

                                <!-- Tab: Login mit E-Mail (Code) -->
                                <div x-show="tab === 'email'" x-cloak>

                                    @if(request('code_sent'))
                                        {{-- Step 2: Code eingeben --}}
                                        <div>
                                            <p class="text-sm text-stone-600 dark:text-stone-400 mb-2 text-center">
                                                Wir haben einen 6-stelligen Code an diese E-Mail-Adresse gesendet:
                                            </p>
                                            <p class="text-sm font-semibold text-stone-900 dark:text-white mb-5 text-center">{{ request('email', '') }}</p>

                                            <!-- 6 digit inputs -->
                                            <div id="code-inputs" class="flex justify-center gap-2 sm:gap-3 mb-2">
                                                @for($i = 0; $i < 6; $i++)
                                                <input
                                                    type="text"
                                                    inputmode="numeric"
                                                    maxlength="1"
                                                    data-code-input
                                                    data-index="{{ $i }}"
                                                    class="w-11 h-14 sm:w-12 sm:h-16 text-center text-xl sm:text-2xl font-bold rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-900 dark:text-white focus:outline-none focus:ring-2 focus:border-blue-500 focus:ring-blue-500/20 transition-colors"
                                                >
                                                @endfor
                                            </div>

                                            <!-- Error message -->
                                            <p id="code-error" class="text-sm text-red-600 dark:text-red-400 text-center mb-4" style="display: none;"></p>

                                            <!-- Loading state -->
                                            <div id="code-loading" class="flex items-center justify-center gap-2 py-3" style="display: none;">
                                                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                </svg>
                                                <span class="text-sm text-stone-600 dark:text-stone-400">Wird geprüft...</span>
                                            </div>

                                            <!-- Actions -->
                                            <div id="code-actions" class="mt-5 space-y-3">
                                                <button
                                                    id="code-resend"
                                                    disabled
                                                    class="w-full rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 hover:bg-stone-50 dark:hover:bg-stone-800 px-4 py-2.5 text-sm font-medium text-stone-700 dark:text-stone-300 shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950 disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    <span id="resend-text" style="display: none;">Code erneut senden</span>
                                                    <span id="resend-countdown">Erneut senden in <span id="cooldown-seconds">30</span>s</span>
                                                </button>

                                                <a href="{{ route('customer.login') }}"
                                                    class="block w-full text-center text-sm text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 transition-colors"
                                                    Andere E-Mail-Adresse verwenden
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        {{-- Step 1: Email eingeben --}}
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
                                                Login-Code senden
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Tab: Login mit Passwort -->
                                <div x-show="tab === 'password'" x-cloak>
                                    <p class="text-sm text-stone-600 dark:text-stone-400 mb-5">
                                        Sie werden zur Passolution-Anmeldeseite weitergeleitet, um sich mit Ihren Zugangsdaten anzumelden.
                                    </p>

                                    <button type="button"
                                            onclick="window.location.href='{{ route('auth.keycloak.redirect') }}'"
                                            class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-stone-950">
                                        Weiter zur Anmeldung
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Register Link -->
                    <div class="text-center text-sm text-stone-600 dark:text-stone-400">
                        Sie haben noch kein Konto?
                        <a href="{{ route('customer.register') }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                            Konto erstellen
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(!request('code_sent'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var el = document.getElementById('magic_email');
                if (el) setTimeout(function() { el.focus(); }, 100);
            });
        </script>
        @endif

        @if(request('code_sent'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var inputs = document.querySelectorAll('#code-inputs input[data-code-input]');
                var email = @json(request('email', ''));
                var verifyUrl = @json(route('customer.magic-login.verify-code'));
                var resendUrl = @json(route('customer.magic-login.send'));
                var errorEl = document.getElementById('code-error');
                var loadingEl = document.getElementById('code-loading');
                var actionsEl = document.getElementById('code-actions');
                var resendBtn = document.getElementById('code-resend');
                var resendCountdown = document.getElementById('resend-countdown');
                var resendText = document.getElementById('resend-text');
                var verifying = false;

                // Focus first empty input (delayed to avoid Livewire/Flux stealing focus)
                function focusFirstEmpty() {
                    for (var i = 0; i < inputs.length; i++) {
                        if (!inputs[i].value) { inputs[i].focus(); return; }
                    }
                }
                setTimeout(focusFirstEmpty, 150);

                // Re-focus when window regains focus
                window.addEventListener('focus', function() {
                    setTimeout(focusFirstEmpty, 50);
                });

                // Resend cooldown
                var cooldown = 30;
                var timer = setInterval(function() {
                    cooldown--;
                    if (cooldown <= 0) {
                        clearInterval(timer);
                        resendBtn.disabled = false;
                        resendCountdown.style.display = 'none';
                        resendText.style.display = '';
                    } else {
                        document.getElementById('cooldown-seconds').textContent = cooldown;
                    }
                }, 1000);

                inputs.forEach(function(input, index) {
                    input.addEventListener('input', function(e) {
                        if (verifying) return;
                        var value = e.target.value.replace(/\D/g, '');
                        e.target.value = value.charAt(0) || '';
                        hideError();

                        if (e.target.value && index < 5) {
                            inputs[index + 1].focus();
                        }

                        // Check if all filled
                        var code = getCode();
                        if (code.length === 6) {
                            submitCode(code);
                        }
                    });

                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Backspace' && !e.target.value && index > 0) {
                            inputs[index - 1].value = '';
                            inputs[index - 1].focus();
                        }
                    });

                    input.addEventListener('paste', function(e) {
                        e.preventDefault();
                        var text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                        if (!text) return;
                        for (var i = 0; i < 6; i++) {
                            inputs[i].value = text.charAt(i) || '';
                        }
                        hideError();
                        inputs[Math.min(text.length, 5)].focus();
                        if (text.length === 6) {
                            submitCode(text);
                        }
                    });

                    input.addEventListener('focus', function(e) {
                        e.target.select();
                    });
                });

                function getCode() {
                    var code = '';
                    inputs.forEach(function(input) { code += input.value; });
                    return code;
                }

                function showError(msg) {
                    errorEl.textContent = msg;
                    errorEl.style.display = '';
                    inputs.forEach(function(input) {
                        input.classList.remove('border-stone-300', 'dark:border-stone-700');
                        input.classList.add('border-red-500', 'dark:border-red-500');
                    });
                }

                function hideError() {
                    errorEl.style.display = 'none';
                    inputs.forEach(function(input) {
                        input.classList.remove('border-red-500', 'dark:border-red-500');
                        input.classList.add('border-stone-300', 'dark:border-stone-700');
                    });
                }

                function setVerifying(state) {
                    verifying = state;
                    loadingEl.style.display = state ? '' : 'none';
                    actionsEl.style.display = state ? 'none' : '';
                    inputs.forEach(function(input) { input.disabled = state; });
                }

                function clearInputs() {
                    inputs.forEach(function(input) { input.value = ''; });
                    inputs[0].focus();
                }

                function submitCode(code) {
                    setVerifying(true);
                    hideError();

                    fetch(verifyUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ email: email, code: code }),
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            window.location.href = data.redirect;
                        } else {
                            showError(data.message || 'Der Code ist ungültig oder abgelaufen.');
                            setVerifying(false);
                            clearInputs();
                        }
                    })
                    .catch(function() {
                        showError('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
                        setVerifying(false);
                    });
                }

                // Resend button
                resendBtn.addEventListener('click', function() {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = resendUrl;

                    var t = document.createElement('input');
                    t.type = 'hidden'; t.name = '_token';
                    t.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    form.appendChild(t);

                    var e = document.createElement('input');
                    e.type = 'hidden'; e.name = 'email'; e.value = email;
                    form.appendChild(e);

                    var tb = document.createElement('input');
                    tb.type = 'hidden'; tb.name = '_tab'; tb.value = 'email';
                    form.appendChild(tb);

                    document.body.appendChild(form);
                    form.submit();
                });
            });
        </script>
        @endif

        @fluxScripts
    </body>
</html>
