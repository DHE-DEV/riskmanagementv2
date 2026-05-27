{{--
    Travel Requirements Service v2 — subscription ("Abo") modal.

    Self-contained Alpine v3 component. Mount once per page. It opens when a
    `trs:open-abo` window event is dispatched:

        window.dispatchEvent(new CustomEvent('trs:open-abo', { detail: {
            destinations: ['FR', 'DE'],
            titles: { FR: 'Frankreich', DE: 'Deutschland' },
        }}))

    Endpoints (routes/controller wired separately):
      GET  /travel-requirements-service-v2/abo/emails      -> {emails:[{email,language}]}
      POST /travel-requirements-service-v2/abo/emails/add  -> {ok:bool}
      POST /travel-requirements-service-v2/abo/save        -> {ok:bool, error?:string}
--}}
<div x-data="trsAbo()" x-init="init()" x-cloak>
    {{-- Backdrop + overlay --}}
    <div x-show="open"
         x-transition.opacity
         class="fixed inset-0 z-[1000] flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="trs-abo-title"
         @keydown.escape.window="close()">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-pds-blue/60 backdrop-blur-sm" @click="close()"></div>

        {{-- Card --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-100">
                <h2 id="trs-abo-title" class="text-lg font-semibold text-pds-blue flex items-center gap-2">
                    <i class="fa-solid fa-bell text-pds-blue"></i>
                    <span>{{ __('trs.Subscribe') }}</span>
                </h2>
                <button type="button" @click="close()"
                        class="text-gray-400 hover:text-pds-blue transition p-1 -mr-1"
                        aria-label="Schließen">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 max-h-[70vh] overflow-y-auto pds-scroll">

                {{-- SUCCESS state --}}
                <template x-if="done">
                    <div class="text-center py-6">
                        <div class="mx-auto mb-4 w-14 h-14 rounded-full bg-pds-green/30 flex items-center justify-center">
                            <i class="fa-solid fa-check text-2xl text-pds-blue"></i>
                        </div>
                        <p class="text-pds-blue font-medium">Ihr Abo wurde gespeichert.</p>
                        <p class="text-sm text-gray-500 mt-1">Sie erhalten künftig E-Mail-Benachrichtigungen bei Änderungen der Einreisebestimmungen.</p>
                    </div>
                </template>

                {{-- FORM state --}}
                <template x-if="!done">
                    <div class="space-y-4">

                        <p class="text-sm text-gray-500 -mt-1">{{ __('trs.EmailSubscriptionForEndusers') }}</p>

                        {{-- Subscription name --}}
                        <div>
                            <label for="trs-abo-name" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('trs.SubscriptionName') }}
                            </label>
                            <input id="trs-abo-name" type="text" x-model="name"
                                   placeholder="z. B. Sommerreisen 2026"
                                   maxlength="60"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pds-green focus:border-pds-blue transition">
                        </div>

                        {{-- Destinations checklist --}}
                        <div x-show="countries.length">
                            <span class="block text-sm font-medium text-gray-700 mb-1">Reiseziele</span>
                            <div class="space-y-1.5 rounded-lg border border-gray-200 p-3 max-h-40 overflow-y-auto pds-scroll">
                                <template x-for="c in countries" :key="c.code">
                                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                                        <input type="checkbox" :value="c.code" x-model="selectedCountries"
                                               class="rounded border-gray-300 text-pds-blue focus:ring-pds-green">
                                        <span x-text="c.title"></span>
                                        <span class="ml-auto text-[10px] text-gray-400 font-mono" x-text="c.code"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Email selection --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('trs.EmailAddress') }}
                            </label>

                            {{-- Loading emails --}}
                            <template x-if="emailsLoading">
                                <div class="text-sm text-gray-400 flex items-center gap-2 py-2">
                                    <i class="fa-solid fa-spinner fa-spin"></i><span>Wird geladen…</span>
                                </div>
                            </template>

                            {{-- Email select (when we have verified emails) --}}
                            <template x-if="!emailsLoading && emails.length">
                                <select x-model="selectedEmail"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pds-green focus:border-pds-blue transition">
                                    <template x-for="e in emails" :key="e.email">
                                        <option :value="e.email" x-text="e.email"></option>
                                    </template>
                                </select>
                            </template>

                            {{-- No verified emails: hint + add form --}}
                            <template x-if="!emailsLoading && !emails.length">
                                <div class="space-y-2">
                                    <p class="text-xs text-gray-500">
                                        Keine verifizierte E-Mail-Adresse vorhanden. Bitte fügen Sie eine hinzu –
                                        Sie erhalten eine Bestätigungs-E-Mail.
                                    </p>
                                    <div class="flex gap-2">
                                        <input type="email" x-model="newEmail"
                                               placeholder="{{ __('trs.EmailAddress') }}"
                                               class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pds-green focus:border-pds-blue transition">
                                        <button type="button" @click="addEmail()"
                                                :disabled="addingEmail || !newEmail"
                                                class="shrink-0 inline-flex items-center gap-1 rounded-lg bg-pds-blue px-3 py-2 text-sm text-white hover:opacity-90 disabled:opacity-50 transition">
                                            <i class="fa-solid" :class="addingEmail ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
                                            <span>Hinzufügen</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Error message --}}
                        <template x-if="error">
                            <div class="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700 flex items-start gap-2">
                                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                                <span x-text="error"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100 bg-gray-50">
                <template x-if="done">
                    <button type="button" @click="close()"
                            class="inline-flex items-center gap-2 rounded-lg bg-pds-blue px-4 py-2 text-sm font-medium text-white hover:opacity-90 transition">
                        Schließen
                    </button>
                </template>

                <template x-if="!done">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="close()"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
                            {{ __('trs.Reset') }}
                        </button>
                        <button type="button" @click="submit()"
                                :disabled="submitting || !canSubmit()"
                                class="inline-flex items-center gap-2 rounded-lg bg-pds-blue px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50 transition">
                            <i class="fa-solid" :class="submitting ? 'fa-spinner fa-spin' : 'fa-bell'"></i>
                            <span>Abo abschließen</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function trsAbo() {
        return {
            // ── base paths ──────────────────────────────────────────────
            paths: {
                emails: '/travel-requirements-service-v2/abo/emails',
                addEmail: '/travel-requirements-service-v2/abo/emails/add',
                save: '/travel-requirements-service-v2/abo/save',
            },

            // ── state ───────────────────────────────────────────────────
            open: false,
            done: false,
            error: '',

            name: '',
            countries: [],          // [{code, title}]
            selectedCountries: [],  // [code, …]

            emails: [],             // [{email, language}]
            selectedEmail: '',
            emailsLoading: false,

            newEmail: '',
            addingEmail: false,

            submitting: false,

            // ── lifecycle ───────────────────────────────────────────────
            init() {
                window.addEventListener('trs:open-abo', (event) => {
                    this.openModal(event.detail || {});
                });
            },

            openModal(detail) {
                const destinations = Array.isArray(detail.destinations) ? detail.destinations : [];
                const titles = detail.titles || {};

                this.countries = destinations.map((code) => ({
                    code: code,
                    title: titles[code] || code,
                }));
                this.selectedCountries = destinations.slice(); // all checked by default

                // reset form
                this.name = '';
                this.error = '';
                this.done = false;
                this.newEmail = '';
                this.selectedEmail = '';

                this.open = true;
                document.body.style.overflow = 'hidden';

                this.loadEmails();
            },

            close() {
                this.open = false;
                document.body.style.overflow = '';
            },

            // ── helpers ─────────────────────────────────────────────────
            csrf() {
                const el = document.querySelector('meta[name="csrf-token"]');
                return el ? el.getAttribute('content') : '';
            },

            canSubmit() {
                return this.name.trim().length >= 3 && !!this.selectedEmail;
            },

            // ── data ────────────────────────────────────────────────────
            async loadEmails() {
                this.emailsLoading = true;
                this.error = '';
                try {
                    const res = await fetch(this.paths.emails, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' },
                    });
                    const json = await res.json();
                    this.emails = Array.isArray(json.emails) ? json.emails : [];
                    this.selectedEmail = this.emails.length ? this.emails[0].email : '';
                } catch (e) {
                    this.emails = [];
                    this.error = 'E-Mail-Adressen konnten nicht geladen werden.';
                } finally {
                    this.emailsLoading = false;
                }
            },

            async addEmail() {
                if (!this.newEmail || this.addingEmail) return;
                this.addingEmail = true;
                this.error = '';
                try {
                    const res = await fetch(this.paths.addEmail, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                        body: JSON.stringify({ email: this.newEmail }),
                    });
                    const json = await res.json().catch(() => ({}));
                    if (res.ok && json.ok) {
                        // reload so the (now pending) address shows up once verified
                        this.newEmail = '';
                        await this.loadEmails();
                        if (!this.emails.length) {
                            this.error = 'Bitte bestätigen Sie zuerst die Verifizierungs-E-Mail, die wir Ihnen gesendet haben.';
                        }
                    } else {
                        this.error = 'Die E-Mail-Adresse konnte nicht hinzugefügt werden.';
                    }
                } catch (e) {
                    this.error = 'Die E-Mail-Adresse konnte nicht hinzugefügt werden.';
                } finally {
                    this.addingEmail = false;
                }
            },

            async submit() {
                if (!this.canSubmit() || this.submitting) return;
                this.submitting = true;
                this.error = '';
                try {
                    const res = await fetch(this.paths.save, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                        body: JSON.stringify({
                            name: this.name.trim(),
                            countries: this.selectedCountries,
                            emails: [this.selectedEmail],
                        }),
                    });
                    const json = await res.json().catch(() => ({}));
                    if (res.ok && json.ok) {
                        this.done = true;
                    } else {
                        this.error = json.error || 'Das Abo konnte nicht gespeichert werden.';
                    }
                } catch (e) {
                    this.error = 'Das Abo konnte nicht gespeichert werden.';
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>
@endpush
