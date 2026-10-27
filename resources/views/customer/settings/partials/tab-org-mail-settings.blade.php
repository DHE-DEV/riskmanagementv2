{{-- Einstellungen › Organisationsstruktur › Einstellungen: Mail bei Aenderungen der Agenturen und Filialen --}}
@php
    $orgMailSettings = \App\Support\OrgMailSettings::for($customer);
@endphp
<div x-data="{
        s: @js($orgMailSettings),
        input: { to: '', cc: '', bcc: '' },
        saving: false, message: '', error: '', errors: {},
        previewHtml: '', previewSubject: '', previewOpen: false,
        placeholders: @js(\App\Support\OrgMailSettings::PLACEHOLDERS),
        addEmails(field) {
            const parts = this.input[field].split(/[\s,;]+/).map(v => v.trim().toLowerCase()).filter(Boolean);
            const invalid = parts.filter(v => ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v));
            parts.filter(v => ! invalid.includes(v) && ! this.s[field].includes(v)).forEach(v => this.s[field].push(v));
            this.input[field] = invalid.join(', ');
            this.errors[field] = invalid.length ? 'Keine gültige E-Mail-Adresse: ' + invalid.join(', ') : '';
        },
        removeEmail(field, email) { this.s[field] = this.s[field].filter(v => v !== email); },
        insert(placeholder) {
            const el = this.$refs.body;
            const start = el.selectionStart ?? this.s.body.length, end = el.selectionEnd ?? start;
            this.s.body = this.s.body.slice(0, start) + placeholder + this.s.body.slice(end);
            this.$nextTick(() => { el.focus(); el.selectionStart = el.selectionEnd = start + placeholder.length; });
        },
        async request(url, method, body) {
            const res = await fetch(url, {
                method, body: JSON.stringify(body),
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
            });
            return [res.ok, await res.json()];
        },
        async save() {
            ['to', 'cc', 'bcc'].forEach(f => this.input[f] && this.addEmails(f));
            if (['to', 'cc', 'bcc'].some(f => this.errors[f])) return;
            this.saving = true; this.message = ''; this.error = '';
            try {
                const [ok, data] = await this.request(@js(route('customer.organization.mail-settings.update')), 'PUT', this.s);
                if (ok && data.success) { this.s = data.settings; this.message = data.message; setTimeout(() => this.message = '', 3000); }
                else { this.error = data.message || 'Speichern fehlgeschlagen'; }
            } catch (e) { this.error = 'Speichern fehlgeschlagen'; }
            this.saving = false;
        },
        async preview() {
            const [ok, data] = await this.request(@js(route('customer.organization.mail-settings.preview')), 'POST', { subject: this.s.subject, format: this.s.format, body: this.s.body });
            if (ok && data.success) { this.previewSubject = data.subject; this.previewHtml = data.html; this.previewOpen = true; }
        },
     }" class="space-y-5">

    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h4 class="text-sm font-semibold text-gray-900">E-Mail bei Änderungen Ihrer Agenturen</h4>
                <p class="text-xs text-gray-500 mt-1">Ändert eine zugeordnete Agentur ihre Adresse oder wird unter „Neue Adresse“ eine Filiale angelegt, geändert oder gelöscht, verschicken wir diese E-Mail.</p>
            </div>
            <label class="inline-flex items-center gap-2 cursor-pointer flex-shrink-0">
                <input type="checkbox" x-model="s.enabled" class="sr-only peer">
                <span class="relative w-10 h-6 bg-gray-200 rounded-full transition-colors peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:bg-white after:rounded-full after:transition-transform peer-checked:after:translate-x-4"></span>
                <span class="text-sm text-gray-700" x-text="s.enabled ? 'Aktiv' : 'Aus'"></span>
            </label>
        </div>
    </div>

    <div x-show="s.enabled" x-cloak class="space-y-5">
        {{-- Empfaenger --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5 space-y-4">
            <h4 class="text-sm font-semibold text-gray-900">Empfänger</h4>
            @foreach (['to' => ['An', 'Ohne Eintrag geht die E-Mail an '.($customer->email ?: 'Ihre Account-E-Mail').'.'], 'cc' => ['CC', 'Kopie, für alle Empfänger sichtbar.'], 'bcc' => ['BCC', 'Blindkopie, für andere Empfänger nicht sichtbar.']] as $field => [$label, $hint])
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1" for="org-mail-{{ $field }}">{{ $label }}</label>
                    <div class="flex flex-wrap items-center gap-1.5 px-2 py-1.5 border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500">
                        <template x-for="email in s.{{ $field }}" :key="email">
                            <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 rounded-full bg-blue-50 text-blue-800 text-xs">
                                <span x-text="email"></span>
                                <button type="button" @click="removeEmail('{{ $field }}', email)" class="w-4 h-4 inline-flex items-center justify-center rounded-full hover:bg-blue-100" :aria-label="email + ' entfernen'"><i class="fas fa-xmark text-[10px]"></i></button>
                            </span>
                        </template>
                        <input id="org-mail-{{ $field }}" type="text" x-model="input.{{ $field }}"
                               @keydown.enter.prevent="addEmails('{{ $field }}')" @keydown.comma.prevent="addEmails('{{ $field }}')" @blur="input.{{ $field }} && addEmails('{{ $field }}')"
                               placeholder="E-Mail-Adresse eingeben, mit Enter hinzufügen"
                               class="flex-1 min-w-48 border-0 p-1 text-sm focus:ring-0 focus:outline-none">
                    </div>
                    <p x-show="errors.{{ $field }}" x-text="errors.{{ $field }}" class="text-xs text-red-600 mt-1"></p>
                    <p class="text-xs text-gray-500 mt-1">{{ $hint }} Mehrere Adressen möglich.</p>
                </div>
            @endforeach

            <div class="border-t border-gray-100 pt-4 space-y-3">
                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input type="checkbox" x-model="s.notify_agency" class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span>Auch die betroffene Agentur bzw. Filiale erhält die E-Mail</span>
                </label>
                <div x-show="s.notify_agency" x-cloak class="pl-6">
                    <label class="block text-xs font-medium text-gray-700 mb-1" for="org-mail-agency-email">An welche Adresse der Agentur?</label>
                    <select id="org-mail-agency-email" x-model="s.agency_email" class="w-full sm:w-80 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach (\App\Support\OrgMailSettings::AGENCY_EMAIL_OPTIONS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Login-E-Mail: die Anmeldeadresse des Agentur-Accounts. Hinterlegte Adressen: unter Stammdaten bzw. bei der Filiale eingetragene E-Mail-Adressen. Eine Filiale ohne eigenen Login erhält die Mail an ihre Haupt-E-Mail-Adresse.</p>
                </div>
                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input type="checkbox" x-model="s.notify_actor" class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span>Wer die Änderung vornimmt, erhält eine Bestätigung per E-Mail</span>
                </label>
            </div>
        </div>

        {{-- Betreff und Inhalt --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h4 class="text-sm font-semibold text-gray-900">Betreff und Inhalt</h4>
                <div class="inline-flex rounded-lg border border-gray-200 p-0.5 text-xs" role="group" aria-label="Format der E-Mail">
                    <button type="button" @click="s.format = 'text'" :class="s.format === 'text' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-1 rounded-md">Text</button>
                    <button type="button" @click="s.format = 'html'" :class="s.format === 'html' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-1 rounded-md">HTML selbst gestalten</button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1" for="org-mail-subject">Betreff</label>
                <input id="org-mail-subject" type="text" x-model="s.subject" maxlength="255" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1" for="org-mail-body" x-text="s.format === 'html' ? 'Inhalt (HTML)' : 'Inhalt'"></label>
                <textarea id="org-mail-body" x-ref="body" x-model="s.body" rows="12" :class="s.format === 'html' ? 'font-mono text-xs' : 'text-sm'" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                <p class="text-xs text-gray-500 mt-1" x-show="s.format === 'text'">Der Text wird in unser E-Mail-Layout gesetzt; Zeilenumbrüche bleiben erhalten.</p>
                <p class="text-xs text-gray-500 mt-1" x-show="s.format === 'html'" x-cloak>Vollständiges HTML inkl. eigener Gestaltung (Inline-Styles empfohlen). Skripte werden entfernt.</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-700 mb-1.5">Platzhalter – klicken zum Einfügen</p>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="(label, key) in placeholders" :key="key">
                        <button type="button" @click="insert(key)" :title="label" class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs font-mono hover:bg-blue-50 hover:text-blue-700" x-text="key"></button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="button" @click="save()" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-60">Speichern</button>
        <button type="button" x-show="s.enabled" @click="preview()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50"><i class="fas fa-eye mr-1"></i>Vorschau</button>
        <span x-show="message" x-text="message" class="text-sm text-green-700"></span>
        <span x-show="error" x-text="error" class="text-sm text-red-600"></span>
    </div>

    {{-- Vorschau mit Beispieldaten --}}
    <div x-show="previewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="previewOpen = false" @click.self="previewOpen = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col" role="dialog" aria-modal="true" aria-label="Vorschau der E-Mail">
            <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-gray-200">
                <div class="min-w-0">
                    <p class="text-xs text-gray-500">Vorschau mit Beispieldaten · Betreff</p>
                    <p class="text-sm font-semibold text-gray-900 truncate" x-text="previewSubject"></p>
                </div>
                <button type="button" @click="previewOpen = false" class="p-2 text-gray-400 hover:text-gray-700 rounded-lg hover:bg-gray-100" aria-label="Vorschau schließen"><i class="fas fa-xmark"></i></button>
            </div>
            <iframe sandbox="" :srcdoc="previewHtml" class="w-full flex-1 min-h-[60vh] rounded-b-xl" title="Vorschau der E-Mail"></iframe>
        </div>
    </div>
</div>
