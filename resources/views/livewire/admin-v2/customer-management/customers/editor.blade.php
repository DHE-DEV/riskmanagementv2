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

                    <x-adminv2.card heading="Einstellungen">
                        <div class="flex flex-col gap-4">
                            <flux:switch wire:model="directoryListingActive" label="Adressverzeichnis aktiv" align="left" />
                            <flux:switch wire:model="branchManagementActive" label="Filialen-Verwaltung aktiv" align="left" />
                            <flux:switch wire:model="hideProfileCompletion" label="Profil-Vervollständigung ausblenden" align="left" />
                        </div>
                    </x-adminv2.card>

                    <x-adminv2.card heading="GTM API Einstellungen" collapsible collapse-key="customer-gtm-api">
                        <div class="flex flex-col gap-5">
                            <div>
                                <flux:switch wire:model="gtmApiEnabled" label="GTM API Zugang aktiv" align="left" />
                                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Aktiviert den Zugang zur Global Travel Monitor JSON API. Der Kunde muss seinen API-Token neu generieren.</p>
                            </div>
                            <flux:field class="sm:max-w-xs">
                                <flux:label>Rate Limit (Anfragen/Minute)</flux:label>
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
                            <div class="grid items-start gap-5 sm:grid-cols-2">
                                @foreach (CustomerFeatureOverride::getFeatureLabels() as $key => $label)
                                    <flux:field wire:key="feature-{{ $key }}">
                                        <flux:label>{{ $label }}</flux:label>
                                        <flux:select wire:model="featureOverrides.{{ $key }}">
                                            <flux:select.option value="">Standard (.env)</flux:select.option>
                                            <flux:select.option value="1">Aktiviert</flux:select.option>
                                            <flux:select.option value="0">Deaktiviert</flux:select.option>
                                        </flux:select>
                                        <flux:error name="featureOverrides.{{ $key }}" />
                                    </flux:field>
                                @endforeach
                            </div>
                        </div>
                    </x-adminv2.card>

                    {{-- Nur lesend: diese Werte kommen aus dem Login bzw. dem Abgleich mit Passolution. --}}
                    <x-adminv2.card heading="Passolution Integration" collapsible collapse-key="customer-passolution">
                        <div class="grid items-start gap-5 sm:grid-cols-2">
                            @if ($this->hasPdsAccountId())
                                <flux:field class="sm:col-span-2">
                                    <flux:label>PDS Account-ID</flux:label>
                                    <flux:input :value="(string) $customer->pds_account_id" placeholder="noch nicht angemeldet" disabled />
                                    <flux:description>Wird beim Login gesetzt. Diese Nummer wird für Feature-Vormerkungen verwendet.</flux:description>
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

                    <x-adminv2.record-tasks :record="$customer" />
                </div>
            </div>
        </form>

        {{-- Was am Kunden haengt: Filialen, Account-Zugriffe, API Tokens, GTM API Logs --}}
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
                @if ($tab === 'access')
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
