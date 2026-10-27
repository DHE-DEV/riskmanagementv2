@php
    use App\Livewire\AdminV2\CustomerManagement\Customers\Editor;
    use App\Livewire\AdminV2\CustomerManagement\Customers\Index;
    use App\Models\CustomerFeatureOverride;

    $customer = $this->customer;
    $indexUrl = route('adminv2.customer-management.customers.index');
    $readonly = fn ($value) => $value instanceof \DateTimeInterface ? $value->format('d.m.Y H:i') : (string) $value;
@endphp

<div class="flex flex-col gap-6">
    {{-- Ohne Kunde (Aufruf der "Neu"-Route) wird zur Liste weitergeleitet. --}}
    @if ($customer)
        <form wire:submit="save" class="flex flex-col gap-6">
            {{-- Kopf: Rueckweg, Titel, Aktionen --}}
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <a href="{{ $indexUrl }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                            <flux:icon.arrow-left variant="micro" /> Kunden
                        </a>
                        <flux:heading size="xl" level="1" class="mt-1">{{ Index::label($customer) }}</flux:heading>
                        <flux:subheading>
                            {{ $customer->email }}
                            @if ($customer->app_code) <span class="text-zinc-400">·</span> App-Code <span class="font-mono">{{ $customer->app_code }}</span> @endif
                        </flux:subheading>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button variant="ghost" :href="$indexUrl">Zur Liste</flux:button>
                        @if ($customer->trashed())
                            <flux:button icon="arrow-uturn-left" wire:click="confirmAction('restore')">Wiederherstellen</flux:button>
                            <flux:button variant="danger" icon="trash" wire:click="confirmAction('force')">Endgültig löschen</flux:button>
                        @else
                            <flux:button variant="danger" icon="trash" wire:click="confirmAction('delete')">Löschen</flux:button>
                        @endif
                        <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save">Speichern</flux:button>
                    </div>
                </div>

                @if ($agency = $customer->assignedTo)
                    {{-- Unteraccount: zu welcher Agentur er gehoert und was das bedeutet --}}
                    <div class="flex items-start gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-5 py-3 text-sm text-sky-900 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200">
                        <flux:icon.building-storefront variant="mini" class="mt-0.5 shrink-0" />
                        <div>
                            Unteraccount von
                            <a href="{{ route('adminv2.customer-management.customers.edit', ['customer' => $agency->id, 'tab' => 'sub-accounts']) }}" class="font-medium underline decoration-sky-300 underline-offset-2 hover:decoration-sky-900">{{ $agency->company_name ?: Index::label($agency) }}</a>@if ($agency->sub_accounts_note):
                                <span class="whitespace-pre-line">{{ $agency->sub_accounts_note }}</span>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($customer->trashed())
                    <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                        <flux:icon.trash variant="mini" />
                        Dieser Kunde wurde am {{ $customer->deleted_at->format('d.m.Y H:i') }} gelöscht (Soft Delete) und kann wiederhergestellt werden.
                    </div>
                @endif
            </div>

            <div class="grid items-start gap-6 lg:grid-cols-2">
                {{-- Linke Spalte --}}
                <div class="flex flex-col gap-6">
                    @foreach (['company' => 'Firmeninformationen', 'billing' => 'Rechnungsadresse'] as $prefix => $heading)
                        <x-adminv2.card :heading="$heading" wire:key="address-{{ $prefix }}">
                            <div class="flex flex-col gap-5">
                                @if ($prefix === 'billing')
                                    <flux:switch wire:model.live="billingSameAsCompany" label="Wie Firmeninformation" description="Die Rechnungsadresse übernimmt beim Speichern die Firmeninformation – auch wenn sie sich später ändert." align="left" />
                                @endif
                                @if ($prefix === 'billing' && $billingSameAsCompany)
                                    <p class="rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300">
                                        {{ $company['name'] ?: '–' }}@if ($company['additional']), {{ $company['additional'] }}@endif<br>
                                        {{ trim($company['street'].' '.$company['house_number']) ?: '–' }}<br>
                                        {{ trim($company['postal_code'].' '.$company['city']) ?: '–' }}@if ($company['country']), {{ $company['country'] }}@endif
                                    </p>
                                @else
                                <flux:field>
                                    <flux:label>Firmenname</flux:label>
                                    <flux:input wire:model="{{ $prefix }}.name" maxlength="255" />
                                    <flux:error name="{{ $prefix }}.name" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Zusatz</flux:label>
                                    <flux:input wire:model="{{ $prefix }}.additional" maxlength="255" />
                                    <flux:error name="{{ $prefix }}.additional" />
                                </flux:field>
                                <div class="grid items-start gap-5 sm:grid-cols-2">
                                    <flux:field>
                                        <flux:label>Straße</flux:label>
                                        <flux:input wire:model="{{ $prefix }}.street" maxlength="255" />
                                        <flux:error name="{{ $prefix }}.street" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Hausnummer</flux:label>
                                        <flux:input wire:model="{{ $prefix }}.house_number" maxlength="20" />
                                        <flux:error name="{{ $prefix }}.house_number" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>PLZ</flux:label>
                                        <flux:input wire:model="{{ $prefix }}.postal_code" maxlength="20" />
                                        <flux:error name="{{ $prefix }}.postal_code" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Stadt</flux:label>
                                        <flux:input wire:model="{{ $prefix }}.city" maxlength="255" />
                                        <flux:error name="{{ $prefix }}.city" />
                                    </flux:field>
                                </div>
                                <flux:field>
                                    <flux:label>Land</flux:label>
                                    <x-adminv2.search-select
                                        :options="$this->countryOptionsFor(${$prefix}['country'] ?? '')"
                                        model="{{ $prefix }}.country"
                                        :selected="${$prefix}['country'] ?? ''"
                                        placeholder="Kein Land"
                                        search-placeholder="Land oder ISO-Code …"
                                        label="Land"
                                        live
                                        clearable
                                    />
                                    <flux:error name="{{ $prefix }}.country" />
                                </flux:field>
                                @endif

                                @if ($prefix === 'billing')
                                    {{-- Wohin Rechnungen per E-Mail gehen --}}
                                    <div class="flex flex-col gap-4 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                                        <div>
                                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Rechnung per E-Mail</h3>
                                            <p class="mt-0.5 text-sm text-zinc-500">Mehrere Adressen je Feld durch Komma trennen. Ohne „An“ geht die Rechnung an {{ $email ?: 'die E-Mail des Kunden' }}.</p>
                                        </div>
                                        @foreach (['to' => 'An', 'cc' => 'CC', 'bcc' => 'BCC'] as $kind => $label)
                                            <flux:field>
                                                <flux:label>{{ $label }}</flux:label>
                                                <flux:input wire:model="invoiceEmails.{{ $kind }}" placeholder="buchhaltung@…, chef@…" />
                                                <flux:error name="invoiceEmails.{{ $kind }}" />
                                            </flux:field>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </x-adminv2.card>
                    @endforeach

                    <x-adminv2.card heading="Allgemeine Informationen">
                        <div class="flex flex-col gap-5">
                            <div class="grid items-start gap-5 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label>Name</flux:label>
                                    <flux:input wire:model="name" maxlength="255" />
                                    <flux:error name="name" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>E-Mail</flux:label>
                                    <flux:input wire:model="email" type="email" maxlength="255" />
                                    <flux:error name="email" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Kundentyp</flux:label>
                                    <flux:select wire:model="customerType">
                                        <flux:select.option value="">Bitte wählen …</flux:select.option>
                                        @foreach (Editor::CUSTOMER_TYPES as $value => $label)
                                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="customerType" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Login via</flux:label>
                                    <flux:input :value="$customer->provider ? ucfirst($customer->provider) : 'E-Mail'" disabled />
                                </flux:field>
                                @if ($customer->exists)
                                    @php
                                        $currentBilling = $customer->currentBillingPeriod;
                                    @endphp
                                    <flux:field>
                                        <flux:label>Abrechnung heute</flux:label>
                                        <flux:input :value="$currentBilling?->label() ?? 'Kein Zeitraum erfasst'" disabled />
                                        <flux:description>
                                            Selbstständig oder über eine Kooperation, je Zeitraum.
                                            <button type="button" wire:click="showTab('billing')" class="font-medium text-zinc-900 underline underline-offset-2 dark:text-white">Zeiträume verwalten</button>
                                        </flux:description>
                                    </flux:field>
                                @endif
                            </div>

                            <flux:field>
                                <flux:label>Geschäftstyp</flux:label>
                                {{-- Spaltenweise, damit „Mobiler Reiseberater“ unter „Reisebüro“ steht --}}
                                <div class="gap-x-5 sm:columns-2 [&>*]:mb-2.5 [&>*]:break-inside-avoid">
                                    @foreach (Editor::BUSINESS_TYPES as $value => $label)
                                        <flux:checkbox wire:model="businessTypes" value="{{ $value }}" :label="$label" />
                                    @endforeach
                                </div>
                                @if ($legacyTypes = array_diff($customer->business_type ?? [], array_keys(Editor::BUSINESS_TYPES)))
                                    <flux:description>Weitere gespeicherte Werte außerhalb dieser Liste bleiben erhalten: {{ implode(', ', $legacyTypes) }}</flux:description>
                                @endif
                                <flux:error name="businessTypes" />
                            </flux:field>

                            <div class="grid items-start gap-5 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label>E-Mail verifiziert am</flux:label>
                                    <flux:input wire:model="emailVerifiedAt" type="datetime-local" />
                                    <flux:description>Leer = nicht verifiziert.</flux:description>
                                    <flux:error name="emailVerifiedAt" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Registriert am</flux:label>
                                    <flux:input :value="$readonly($customer->created_at)" disabled />
                                </flux:field>
                            </div>
                        </div>
                    </x-adminv2.card>

                    {{-- Die Account-ID laesst sich zuordnen, die uebrigen Werte kommen aus dem Login bzw. dem Abgleich mit Passolution. --}}
                    <x-adminv2.card heading="Passolution Integration" collapsible collapse-key="customer-passolution">
                        <div class="grid items-start gap-5 sm:grid-cols-2">
                            @if ($this->hasPdsAccountId())
                                <flux:field class="sm:col-span-2">
                                    <flux:label>PDS Account-ID</flux:label>
                                    <flux:input wire:model.live.debounce.400ms="pdsAccountId" inputmode="numeric" placeholder="noch nicht zugeordnet" />
                                    <flux:description>Account-ID aus dem Travel Requirements Service (pds-admin). Wird beim SSO-Login automatisch gesetzt und dabei ggf. überschrieben. Diese Nummer wird für Feature-Vormerkungen verwendet.</flux:description>
                                    <flux:error name="pdsAccountId" />
                                    @if ($this->sharedPdsAccountCustomers->isNotEmpty())
                                        <flux:callout variant="warning" icon="exclamation-triangle" class="mt-2">
                                            <flux:callout.text>
                                                Diese Account-ID ist bereits zugeordnet:
                                                @foreach ($this->sharedPdsAccountCustomers as $other)
                                                    <a href="{{ route('adminv2.customer-management.customers.edit', $other->id) }}" wire:navigate class="underline">{{ $other->company_name ?: $other->name }} ({{ $other->app_code }})</a>@if (! $loop->last), @endif
                                                @endforeach
                                            </flux:callout.text>
                                        </flux:callout>
                                    @endif
                                </flux:field>
                            @endif
                            <flux:field>
                                <flux:label>Abo-Typ</flux:label>
                                <flux:input :value="(string) $customer->passolution_subscription_type" disabled />
                            </flux:field>
                            <flux:field>
                                <flux:label>Abo aktualisiert am</flux:label>
                                <flux:input :value="$readonly($customer->passolution_subscription_updated_at)" disabled />
                            </flux:field>
                            <flux:field>
                                <flux:label>Token läuft ab</flux:label>
                                <flux:input :value="$readonly($customer->passolution_token_expires_at)" disabled />
                            </flux:field>
                            <flux:field>
                                <flux:label>Refresh Token läuft ab</flux:label>
                                <flux:input :value="$readonly($customer->passolution_refresh_token_expires_at)" disabled />
                            </flux:field>
                        </div>
                    </x-adminv2.card>
                </div>

                {{-- Rechte Spalte --}}
                <div class="flex flex-col gap-6">
                    <x-adminv2.record-tasks :record="$customer" />

                    @php
                        // Schalter mit ausfuehrlicher Erklaerung (Info-Symbol).
                        $settingSwitches = [
                            'directoryListingActive' => [
                                'label' => 'Adressverzeichnis aktiv',
                                'info' => [
                                    'Nimmt die Firmenadresse und alle Filialen des Kunden als Standorte in das Adressverzeichnis auf. Es ist auf der Seite „Buchungsmöglichkeit“ (/booking) der Travel Information Platform zu sehen. Die Kartenposition wird über OpenStreetMap ermittelt.',
                                    'Gilt nur für Geschäftskunden mit vollständiger Firmenadresse (Firmenname, PLZ, Ort). Beim Ausschalten werden die Einträge aus dem Verzeichnis entfernt; die Adressen selbst bleiben erhalten.',
                                    'Der Kunde kann die Einstellung auch selbst in seinem Profil ändern.',
                                ],
                            ],
                            'branchManagementActive' => [
                                'label' => 'Filialen-Verwaltung aktiv',
                                'info' => [
                                    'Schaltet für den Kunden die Verwaltung seiner Filialen und Standorte frei: den Menüpunkt „Filialen & Standorte“ (solange die Feature-Überschreibung „Filialen“ ihn nicht ausblendet), die Filialen-Seitenleiste und -Karte im Dashboard, den Filialen-Import und den Bereich „Organisation“ in den Einstellungen.',
                                    'Beim Ausschalten werden diese Bereiche nur ausgeblendet; angelegte Filialen bleiben erhalten.',
                                    'Der Kunde kann die Einstellung auch selbst ändern.',
                                ],
                            ],
                            'showSubAccountsAsBranches' => [
                                'label' => 'Agenturen als Adressen anzeigen',
                                'info' => [
                                    'Zeigt die zugeordneten Agenturen (Unteraccounts, Tab „Unteraccounts“) beim Kunden zusätzlich als Adressen an: unter Einstellungen › Organisationsstruktur › Übersicht und in der Seitenleiste von „Filialen & Standorte“. Auf der Karte erscheinen sie nicht.',
                                    'Die Agenturen sind dort nur aufgeführt, nicht bearbeitbar – es sind eigenständige Kunden mit eigenem Login. Die Kachel und der Tab „Agenturen“ in der Organisationsstruktur gibt es unabhängig von dieser Einstellung.',
                                ],
                            ],
                            'canEditOwnAddress' => [
                                'label' => 'Darf eigene Adresse ändern',
                                'info' => [
                                    'Der Kunde darf seine Firmenanschrift und Rechnungsadresse selbst ändern: im Dashboard über „Ändern“ in der Karte „Büro“ und in den Kästen „Firmenadresse“/„Rechnungsadresse“ sowie unter Einstellungen › Stammdaten › Adressen.',
                                    'Als Zentrale darf der Kunde damit auch die Firmenanschrift seiner zugeordneten Büros ändern: Stift-Symbol oben rechts in der jeweiligen Agentur-Karte unter Einstellungen › Organisationsstruktur (sofern „Agenturen als Adressen anzeigen“ aktiv ist).',
                                    'Ohne Freigabe kann der Kunde eine Adresse nur einmal erfassen, solange sie noch leer ist (Profil-Vervollständigung); eine vorhandene Adresse ändert dann nur Passolution.',
                                    'Bei jeder Änderung geht eine Infomail mit alten und neuen Daten an info@passolution.de (in Kopie an Daniel Henninger und Dennis Zimon).',
                                ],
                            ],
                            'subAccountsCanEditOwnAddress' => [
                                'label' => 'Unteraccounts dürfen eigene Adresse ändern',
                                'info' => [
                                    'Gibt die Freigabe „Darf eigene Adresse ändern“ an alle zugeordneten Büros (Tab „Unteraccounts“) weiter: Jedes Büro darf dann seine eigene Adresse ändern.',
                                    'Wirkt nur, wenn „Darf eigene Adresse ändern“ bei diesem Kunden eingeschaltet ist. Einzelne Büros lassen sich zusätzlich bei sich selbst freischalten.',
                                    'Auch hier geht bei jeder Änderung die Infomail an Passolution.',
                                ],
                            ],
                            'notifyAddressChanges' => [
                                'label' => 'E-Mail bei Änderungen der Agenturen',
                                'info' => [
                                    'Derselbe Schalter wie beim Kunden unter Einstellungen › Organisationsstruktur › Tab „Einstellungen“. Ist er an, geht eine E-Mail raus, wenn eine zugeordnete Agentur ihre Adresse ändert oder der Kunde unter „Neue Adresse“ eine Filiale anlegt, ändert oder löscht.',
                                    'Empfänger (An, CC, BCC), ob die Agentur selbst und wer die Änderung vorgenommen hat eine Mail bekommen, Betreff und Inhalt (auch als eigenes HTML) stellt der Kunde dort selbst ein. Ohne eigene Empfänger geht sie an die E-Mail-Adresse des Kunden.',
                                    'Die Infomail an Passolution geht unabhängig davon immer.',
                                ],
                            ],
                            'hideProfileCompletion' => [
                                'label' => 'Profil-Vervollständigung ausblenden',
                                'info' => [
                                    'Blendet im Dashboard des Kunden den Kasten zur Profil-Vervollständigung aus, der zeigt, welche Angaben (z. B. Kundentyp, Firmenadresse) noch fehlen.',
                                    'Der Kunde kann den Kasten auch selbst ausblenden; global lässt er sich zusätzlich über die Konfiguration abschalten.',
                                ],
                            ],
                        ];
                    @endphp
                    <x-adminv2.card heading="Einstellungen">
                        <div class="flex flex-col gap-4">
                            @foreach ($settingSwitches as $model => $setting)
                                <div x-data="{ info: false }" wire:key="setting-{{ $model }}">
                                    <div class="flex items-center gap-1">
                                        <flux:switch wire:model="{{ $model }}" :label="$setting['label']" align="left" />
                                        <button type="button" x-on:click="info = ! info" x-bind:aria-expanded="info" aria-label="Was bedeutet „{{ $setting['label'] }}“?" title="Was bedeutet das?" class="inline-flex size-7 shrink-0 items-center justify-center rounded-full text-sky-600 transition hover:bg-sky-50 hover:text-sky-800 focus-visible:outline-2 focus-visible:outline-sky-500 dark:text-sky-400 dark:hover:bg-sky-500/10 dark:hover:text-sky-300"><flux:icon.information-circle class="size-5" /></button>
                                    </div>
                                    <div x-show="info" x-collapse x-cloak class="mt-2 flex flex-col gap-1.5 rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300">
                                        @foreach ($setting['info'] as $paragraph)
                                            <p>{{ $paragraph }}</p>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-adminv2.card>

                    <x-adminv2.card heading="GTM API Einstellungen" description="Zugang zur Global Travel Monitor JSON-API (Ereignisse per Token abrufen) und Anfragen pro Minute." collapsible collapsed collapse-key="customer-gtm-api-v2">
                        <div class="flex flex-col gap-5">
                            <div x-data="{ info: false }">
                                <div class="flex items-center gap-1">
                                    <flux:switch wire:model="gtmApiEnabled" label="GTM API Zugang aktiv" align="left" />
                                    <button type="button" x-on:click="info = ! info" x-bind:aria-expanded="info" aria-label="Was bedeutet „GTM API Zugang aktiv“?" title="Was bedeutet das?" class="inline-flex size-7 shrink-0 items-center justify-center rounded-full text-sky-600 transition hover:bg-sky-50 hover:text-sky-800 focus-visible:outline-2 focus-visible:outline-sky-500 dark:text-sky-400 dark:hover:bg-sky-500/10 dark:hover:text-sky-300"><flux:icon.information-circle class="size-5" /></button>
                                </div>
                                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Aktiviert den Zugang zur Global Travel Monitor JSON API. Der Kunde muss seinen API-Token neu generieren.</p>
                                <div x-show="info" x-collapse x-cloak class="mt-2 flex flex-col gap-1.5 rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300">
                                    <p>Erlaubt dem Kunden, die Ereignisse des Global Travel Monitor per JSON-Schnittstelle (/api/v1/events …) in eigene Systeme zu übernehmen, etwa in eine Website oder ein Reisebüro-Programm.</p>
                                    <p>Den Zugriff erhält ein API-Token, den der Kunde in den Einstellungen erzeugt. Nur Token, die nach dem Einschalten erzeugt wurden, enthalten die Berechtigung – vorhandene Token muss der Kunde neu generieren. Beim Ausschalten werden alle Anfragen sofort abgewiesen (HTTP 403).</p>
                                    <p>Jede Anfrage wird protokolliert (Tab „GTM API Logs“).</p>
                                </div>
                            </div>
                            <flux:field class="sm:max-w-xs" x-data="{ info: false }">
                                <div class="flex items-center gap-1">
                                    <flux:label>Rate Limit (Anfragen/Minute)</flux:label>
                                    <button type="button" x-on:click="info = ! info" x-bind:aria-expanded="info" aria-label="Was bedeutet das Rate Limit?" title="Was bedeutet das?" class="inline-flex size-7 shrink-0 items-center justify-center rounded-full text-sky-600 transition hover:bg-sky-50 hover:text-sky-800 focus-visible:outline-2 focus-visible:outline-sky-500 dark:text-sky-400 dark:hover:bg-sky-500/10 dark:hover:text-sky-300"><flux:icon.information-circle class="size-5" /></button>
                                </div>
                                <div x-show="info" x-collapse x-cloak class="rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300">So viele Anfragen darf der Kunde pro Minute an die GTM API stellen (1–1000, Standard 60). Darüber antwortet die Schnittstelle mit HTTP 429 „Too Many Requests“, bis die Minute um ist. Gilt je Kunde für alle seine Token zusammen.</div>
                                <flux:input wire:model="gtmApiRateLimit" type="number" min="1" max="1000" step="1" />
                                <flux:description>Maximale API-Anfragen pro Minute für diesen Kunden</flux:description>
                                <flux:error name="gtmApiRateLimit" />
                            </flux:field>
                        </div>
                    </x-adminv2.card>

                    <x-adminv2.card
                        heading="Feature-Überschreibungen"
                        description="Überschreiben Sie die globalen .env-Einstellungen für diesen Kunden. Leere Felder verwenden die Standard-Einstellung."
                        collapsible
                        collapsed
                        collapse-key="customer-feature-overrides-v2"
                    >
                        <div class="flex flex-col gap-5">
                            <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                Aktiviert = Feature für diesen Kunden einblenden, auch wenn global deaktiviert. Deaktiviert = Feature ausblenden, auch wenn global aktiviert. Nicht gesetzt = Globale Einstellung verwenden.
                            </p>
                            @php
                                $subAccountCount = $customer->assignedCustomers()->count();
                                // Unteraccount: diese Features kommen von der Zentrale.
                                $agencyInherited = $customer->assign_to
                                    ? [...CustomerFeatureOverride::INHERITED_BY_SUB_ACCOUNTS, ...(array) $customer->assignedTo?->inherited_features]
                                    : [];
                                $inheritedKeys = $subAccountCount > 0 ? $this->inheritedFeatureKeys() : [];
                                $deviations = array_intersect_key($subAccountCount > 0 ? $this->subAccountFeatureDeviations : [], array_flip($inheritedKeys));
                            @endphp
                            @if ($subAccountCount > 0)
                                {{-- Zentrale: je Feature an die Unteraccounts vererben --}}
                                <div class="flex flex-col gap-2 rounded-xl border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700" x-data="{ info: false }">
                                    <div class="flex items-center gap-1">
                                        <span class="font-medium text-zinc-900 dark:text-white">Vererbung an {{ $subAccountCount }} Unteraccounts</span>
                                        <button type="button" x-on:click="info = ! info" x-bind:aria-expanded="info" aria-label="Was bedeutet „An Unteraccounts vererben“?" title="Was bedeutet das?" class="inline-flex size-7 shrink-0 items-center justify-center rounded-full text-sky-600 transition hover:bg-sky-50 hover:text-sky-800 focus-visible:outline-2 focus-visible:outline-sky-500 dark:text-sky-400 dark:hover:bg-sky-500/10 dark:hover:text-sky-300"><flux:icon.information-circle class="size-5" /></button>
                                    </div>
                                    <p class="text-zinc-600 dark:text-zinc-400">Je Feature lässt sich mit „An Unteraccounts vererben“ festlegen, ob die zugeordneten Unteraccounts die Einstellung dieses Kunden übernehmen.</p>
                                    <div x-show="info" x-collapse x-cloak class="flex flex-col gap-1.5 rounded-xl bg-zinc-50 px-4 py-3 text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300">
                                        <p>Ist bei einem Feature „An Unteraccounts vererben“ angehakt, übernehmen alle zugeordneten Unteraccounts – auch später zugeordnete – die Einstellung dieses Kunden: „Aktiviert“ schaltet es bei allen frei, „Deaktiviert“ entzieht es allen, „Standard“ lässt bei ihnen wieder die globale Einstellung gelten. Ohne Haken gilt bei den Unteraccounts ihre eigene bzw. die globale Einstellung.</p>
                                        <p>Hat ein Unteraccount bei einem vererbten Feature selbst einen Wert gesetzt, geht dieser vor; solche Abweichungen lassen sich je Feature oder insgesamt zurücksetzen.</p>
                                        <p>Die drei Einstellungsbereiche (Stammdaten, Organisationsstruktur, Benutzerverwaltung) werden immer vererbt. Änderungen wirken nach dem Speichern, spätestens nach 5 Minuten.</p>
                                    </div>
                                    @if ($deviations !== [])
                                        <div class="flex flex-wrap items-center gap-2 text-zinc-600 dark:text-zinc-300">
                                            <span>{{ array_sum($deviations) }} abweichende Werte bei Unteraccounts</span>
                                            <flux:button size="xs" icon="arrow-uturn-left" wire:click="resetSubAccountFeatures('all')" wire:confirm="Bei allen Unteraccounts die eigenen Werte aller vererbten Features auf „Standard“ zurücksetzen? Danach gilt dort die Einstellung dieses Kunden.">Alle zurücksetzen</flux:button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            <div class="grid items-start gap-5 sm:grid-cols-2">
                                @foreach (CustomerFeatureOverride::getFeatureLabels() as $key => $label)
                                    <flux:field wire:key="feature-{{ $key }}" x-data="{ info: false }">
                                        <div class="flex items-center gap-1">
                                            <flux:label>{{ $label }}</flux:label>
                                            @if ($featureInfo = CustomerFeatureOverride::getFeatureDescriptions()[$key] ?? null)
                                                <button type="button" x-on:click="info = ! info" x-bind:aria-expanded="info" aria-label="Was bedeutet „{{ $label }}“?" title="Was bedeutet das?" class="inline-flex size-7 shrink-0 items-center justify-center rounded-full text-sky-600 transition hover:bg-sky-50 hover:text-sky-800 focus-visible:outline-2 focus-visible:outline-sky-500 dark:text-sky-400 dark:hover:bg-sky-500/10 dark:hover:text-sky-300"><flux:icon.information-circle class="size-5" /></button>
                                            @endif
                                        </div>
                                        @php($fromAgency = in_array($key, $agencyInherited, true))
                                        @if ($featureInfo)
                                            <div x-show="info" x-collapse x-cloak class="flex flex-col gap-1.5 rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300">
                                                <p>{{ $featureInfo }}</p>
                                                @if ($fromAgency)
                                                    <p>Wird von der Zentrale vererbt: „Standard“ = Wert der Zentrale, sonst .env.</p>
                                                @endif
                                                @if (in_array($key, $inheritedKeys, true))
                                                    <p>Gilt auch für die Unteraccounts, solange dort nichts gesetzt ist.</p>
                                                @endif
                                            </div>
                                        @endif
                                        <flux:select wire:model="featureOverrides.{{ $key }}">
                                            <flux:select.option value="">{{ $fromAgency ? 'Standard (Zentrale bzw. .env)' : 'Standard (.env)' }}</flux:select.option>
                                            <flux:select.option value="1">Aktiviert</flux:select.option>
                                            <flux:select.option value="0">Deaktiviert</flux:select.option>
                                        </flux:select>
                                        <flux:error name="featureOverrides.{{ $key }}" />
                                        @if ($subAccountCount > 0)
                                            @if (in_array($key, CustomerFeatureOverride::INHERITED_BY_SUB_ACCOUNTS, true))
                                                <flux:checkbox checked disabled label="An Unteraccounts vererben (immer)" />
                                            @else
                                                <flux:checkbox wire:model.live="inheritedFeatures" value="{{ $key }}" label="An Unteraccounts vererben" />
                                            @endif
                                        @endif
                                        @if ($deviations[$key] ?? 0)
                                            <div class="flex flex-wrap items-center gap-2 text-xs text-amber-700 dark:text-amber-300">
                                                {{ $deviations[$key] }} {{ $deviations[$key] === 1 ? 'Unteraccount weicht' : 'Unteraccounts weichen' }} ab
                                                <flux:button size="xs" variant="ghost" wire:click="resetSubAccountFeatures('{{ $key }}')" wire:confirm="Bei allen Unteraccounts den eigenen Wert für „{{ $label }}“ auf „Standard“ zurücksetzen?">Zurücksetzen</flux:button>
                                            </div>
                                        @endif
                                    </flux:field>
                                @endforeach
                            </div>
                        </div>
                    </x-adminv2.card>
                </div>
            </div>
        </form>

        {{-- Was am Kunden haengt: Filialen, Unteraccounts, Account-Zugriffe, API Tokens, GTM API Logs --}}
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-800" role="tablist">
                @foreach (Editor::TABS as $key => $label)
                    <button
                        type="button"
                        role="tab"
                        wire:click="showTab('{{ $key }}')"
                        aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                        @class([
                            '-mb-px border-b-2 px-3 py-2 text-sm font-medium transition',
                            'border-[var(--color-accent)] text-zinc-900 dark:text-white' => $tab === $key,
                            'border-transparent text-zinc-500 hover:text-zinc-900 dark:hover:text-white' => $tab !== $key,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div wire:loading.class="opacity-60" wire:target="showTab">
                @if ($tab === 'sub-accounts')
                    @include('livewire.admin-v2.customer-management.customers.partials.sub-accounts')
                @elseif ($tab === 'billing')
                    @include('livewire.admin-v2.customer-management.customers.partials.billing')
                @elseif ($tab === 'subscription')
                    @include('livewire.admin-v2.customer-management.customers.partials.subscription')
                @elseif ($tab === 'access')
                    @include('livewire.admin-v2.customer-management.customers.partials.access')
                @elseif ($tab === 'tokens')
                    @include('livewire.admin-v2.customer-management.customers.partials.tokens')
                @elseif ($tab === 'gtm-logs')
                    @include('livewire.admin-v2.customer-management.customers.partials.gtm-logs')
                @else
                    @include('livewire.admin-v2.customer-management.customers.partials.branches')
                @endif
            </div>
        </div>

        @include('livewire.admin-v2.customer-management.customers.partials.branch-modal')
        @include('livewire.admin-v2.customer-management.customers.partials.token-modal')

        {{-- Rueckfrage vor dem Loeschen, Wiederherstellen und endgueltigen Loeschen --}}
        <flux:modal name="customer-confirm" class="md:w-[32rem]">
            <div class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">
                        {{ match ($pendingAction) { 'force' => 'Kunde endgültig löschen', 'restore' => 'Kunde wiederherstellen', default => 'Kunde löschen' } }}
                    </flux:heading>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        @if ($pendingAction === 'force')
                            ACHTUNG: Dies löscht den Kunden permanent aus der Datenbank. Der Benutzer kann sich danach erneut registrieren. Diese Aktion kann nicht rückgängig gemacht werden!
                        @elseif ($pendingAction === 'restore')
                            Möchten Sie diesen Kunden wiederherstellen?
                        @else
                            Möchten Sie diesen Kunden wirklich löschen? Dies ist ein Soft Delete – der Kunde lässt sich wiederherstellen.
                        @endif
                    </p>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                    @if ($pendingAction === 'restore')
                        <flux:button variant="primary" icon="arrow-uturn-left" wire:click="runPendingAction">Wiederherstellen</flux:button>
                    @else
                        <flux:button variant="danger" icon="trash" wire:click="runPendingAction">{{ $pendingAction === 'force' ? 'Endgültig löschen' : 'Löschen' }}</flux:button>
                    @endif
                </div>
            </div>
        </flux:modal>
    @endif
</div>
