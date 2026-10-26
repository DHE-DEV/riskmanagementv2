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
                            </div>

                            <flux:field>
                                <flux:label>Geschäftstyp</flux:label>
                                <div class="grid gap-x-5 gap-y-2.5 sm:grid-cols-2">
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
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            icon="information-circle"
                                            x-on:click="info = ! info"
                                            x-bind:aria-expanded="info"
                                            aria-label="Was bedeutet „{{ $setting['label'] }}“?"
                                            title="Was bedeutet das?"
                                        />
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

                    <x-adminv2.card heading="GTM API Einstellungen" collapsible collapse-key="customer-gtm-api">
                        <div class="flex flex-col gap-5">
                            <div x-data="{ info: false }">
                                <div class="flex items-center gap-1">
                                    <flux:switch wire:model="gtmApiEnabled" label="GTM API Zugang aktiv" align="left" />
                                    <flux:button size="xs" variant="ghost" icon="information-circle" x-on:click="info = ! info" x-bind:aria-expanded="info" aria-label="Was bedeutet „GTM API Zugang aktiv“?" title="Was bedeutet das?" />
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
                                    <flux:button size="xs" variant="ghost" icon="information-circle" x-on:click="info = ! info" x-bind:aria-expanded="info" aria-label="Was bedeutet das Rate Limit?" title="Was bedeutet das?" />
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
                        collapse-key="customer-feature-overrides"
                    >
                        <div class="flex flex-col gap-5">
                            <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                Aktiviert = Feature für diesen Kunden einblenden, auch wenn global deaktiviert. Deaktiviert = Feature ausblenden, auch wenn global aktiviert. Nicht gesetzt = Globale Einstellung verwenden.
                            </p>
                            @php
                                $subAccountCount = $customer->assignedCustomers()->count();
                                // Unteraccount einer vererbenden Zentrale: alle Features kommen von dort.
                                $agencyInheritsAll = (bool) $customer->assignedTo?->inherit_features_to_sub_accounts;
                                $deviations = $inheritFeaturesToSubAccounts ? $this->subAccountFeatureDeviations : [];
                            @endphp
                            @if ($subAccountCount > 0 || $inheritFeaturesToSubAccounts)
                                {{-- Zentrale: Features an die Unteraccounts vererben --}}
                                <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 px-4 py-3 dark:border-zinc-700" x-data="{ info: false }">
                                    <div class="flex items-center gap-1">
                                        <flux:switch wire:model.live="inheritFeaturesToSubAccounts" label="An Unteraccounts vererben ({{ $subAccountCount }})" align="left" />
                                        <flux:button size="xs" variant="ghost" icon="information-circle" x-on:click="info = ! info" x-bind:aria-expanded="info" aria-label="Was bedeutet „An Unteraccounts vererben“?" title="Was bedeutet das?" />
                                    </div>
                                    <div x-show="info" x-collapse x-cloak class="flex flex-col gap-1.5 rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300">
                                        <p>Eingeschaltet übernehmen alle zugeordneten Unteraccounts die Feature-Überschreibungen dieses Kunden – auch solche, die später zugeordnet werden. Ein Feature hier auf „Aktiviert“ schaltet es bei allen frei, „Deaktiviert“ entzieht es allen, „Standard“ lässt bei ihnen wieder die globale Einstellung gelten.</p>
                                        <p>Hat ein Unteraccount selbst einen Wert gesetzt, geht dieser vor. Solche Abweichungen lassen sich hier je Feature oder insgesamt zurücksetzen.</p>
                                        <p>Ausgeschaltet gelten für die Unteraccounts wieder ihre eigenen bzw. die globalen Einstellungen. Die drei Einstellungsbereiche (Stammdaten, Organisationsstruktur, Benutzerverwaltung) werden immer vererbt. Wirkt nach dem Speichern, spätestens nach 5 Minuten.</p>
                                    </div>
                                    @if ($deviations !== [])
                                        <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                            <span>{{ array_sum($deviations) }} abweichende Werte bei Unteraccounts</span>
                                            <flux:button size="xs" icon="arrow-uturn-left" wire:click="resetSubAccountFeatures('all')" wire:confirm="Bei allen Unteraccounts alle eigenen Feature-Werte auf „Standard“ zurücksetzen? Danach gilt überall die Einstellung dieses Kunden.">Alle zurücksetzen</flux:button>
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
                                                <flux:button size="xs" variant="ghost" icon="information-circle" x-on:click="info = ! info" x-bind:aria-expanded="info" aria-label="Was bedeutet „{{ $label }}“?" title="Was bedeutet das?" />
                                            @endif
                                        </div>
                                        @if ($featureInfo)
                                            <div x-show="info" x-collapse x-cloak class="rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300">{{ $featureInfo }}</div>
                                        @endif
                                        @php($fromAgency = $customer->assign_to && ($agencyInheritsAll || in_array($key, CustomerFeatureOverride::INHERITED_BY_SUB_ACCOUNTS, true)))
                                        @if ($fromAgency)
                                            <flux:description>Standard = Wert der Zentrale, sonst .env.</flux:description>
                                        @elseif ($inheritFeaturesToSubAccounts || in_array($key, CustomerFeatureOverride::INHERITED_BY_SUB_ACCOUNTS, true))
                                            <flux:description>Gilt auch für die Unteraccounts, solange dort nichts gesetzt ist.</flux:description>
                                        @endif
                                        <flux:select wire:model="featureOverrides.{{ $key }}">
                                            <flux:select.option value="">{{ $fromAgency ? 'Standard (Zentrale bzw. .env)' : 'Standard (.env)' }}</flux:select.option>
                                            <flux:select.option value="1">Aktiviert</flux:select.option>
                                            <flux:select.option value="0">Deaktiviert</flux:select.option>
                                        </flux:select>
                                        <flux:error name="featureOverrides.{{ $key }}" />
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

                    <x-adminv2.record-tasks :record="$customer" />
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
