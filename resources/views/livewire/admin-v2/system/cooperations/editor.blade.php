@php
    use App\Models\Cooperation;
    use App\Models\CustomerBillingPeriod;

    $cooperation = $this->cooperation;
    $selectedCustomer = $this->selectedCustomer;
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('adminv2.system.cooperations.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Kooperationen
            </a>
            <flux:heading size="xl" level="1" class="mt-1">{{ $cooperation ? $cooperation->name : 'Neue Kooperation' }}</flux:heading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" :href="route('adminv2.system.cooperations.index')">Abbrechen</flux:button>
            <flux:button type="submit" variant="primary" icon="check">Speichern</flux:button>
        </div>
    </div>

    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle" heading="Bitte die markierten Angaben prüfen." />
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Kooperation">
                <div class="flex flex-col gap-5">
                    <div>
                        <flux:input wire:model="name" label="Name" placeholder="z. B. solamento" maxlength="255" />
                        <flux:error name="name" />
                    </div>

                    <flux:field>
                        <flux:label>Account der Kooperation</flux:label>
                        @if ($selectedCustomer)
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('adminv2.customer-management.customers.edit', $selectedCustomer) }}" class="text-sm font-medium text-zinc-900 underline decoration-zinc-300 underline-offset-2 dark:text-white">{{ $selectedCustomer->company_name ?: $selectedCustomer->name }}</a>
                                <flux:badge size="sm" color="sky" class="font-mono">{{ $selectedCustomer->app_code }}</flux:badge>
                                <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="chooseCustomer(null)">Entfernen</flux:button>
                            </div>
                        @else
                            <div class="relative">
                                <flux:input wire:model.live.debounce.300ms="customerSearch" icon="magnifying-glass" placeholder="Firma, E-Mail oder Kundennummer …" />
                                @if ($this->customerCandidates->isNotEmpty())
                                    <ul class="absolute z-20 mt-1 w-full overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                                        @foreach ($this->customerCandidates as $candidate)
                                            <li>
                                                <button type="button" wire:click="chooseCustomer({{ $candidate->id }})" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                                    <span class="min-w-0 truncate">{{ $candidate->company_name ?: $candidate->name }} <span class="text-zinc-400">· {{ $candidate->email }}</span></span>
                                                    <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $candidate->app_code }}</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                        <flux:description>Optional: der eigene Platform-Account der Kooperation, z. B. die Zentrale.</flux:description>
                        <flux:error name="customerId" />
                    </flux:field>

                    <flux:textarea wire:model="notes" label="Notizen" rows="3" placeholder="Vertrag, Ansprechpartner, Besonderheiten …" />
                    <flux:switch wire:model="isActive" label="Aktiv" description="Inaktive Kooperationen bleiben bei bestehenden Zeiträumen erhalten." align="left" />
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Rechnungsempfänger" description="An wen die Rechnungen der Kooperation per E-Mail gehen. Mehrere Adressen je Feld durch Komma trennen.">
                <div class="flex flex-col gap-5">
                    @foreach (['to' => 'An', 'cc' => 'CC', 'bcc' => 'BCC'] as $kind => $label)
                        <flux:field>
                            <flux:label>{{ $label }}</flux:label>
                            <flux:input wire:model="invoiceEmails.{{ $kind }}" placeholder="buchhaltung@…, geschaeftsfuehrung@…" />
                            <flux:error name="invoiceEmails.{{ $kind }}" />
                        </flux:field>
                    @endforeach
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Abrechnung" description="Gilt als Vorgabe, wenn ein Kunde der Kooperation zugeordnet wird – beim Kunden lässt sich je Zeitraum abweichen.">
                <div class="grid items-start gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Mitglieder werden abgerechnet</flux:label>
                        <flux:select wire:model="memberBilling">
                            @foreach (Cooperation::MEMBER_BILLING as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="memberBilling" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Rechnung an die Kooperation</flux:label>
                        <flux:select wire:model="billingInterval">
                            @foreach (Cooperation::BILLING_INTERVALS as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="billingInterval" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Festbetrag pro Büro</flux:label>
                        <flux:input wire:model="fixedPricePerOffice" inputmode="decimal" placeholder="z. B. 4,90" />
                        <flux:description>€ pro Büro und Monat, leer = kein Festbetrag. Bezahlt wird er gesammelt über die Kooperation oder vom Büro einzeln – je nach „Wer zahlt?“ beim Kunden.</flux:description>
                        <flux:error name="fixedPricePerOffice" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Kickback</flux:label>
                        <flux:select wire:model.live="kickbackType">
                            @foreach (Cooperation::KICKBACK_TYPES as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="kickbackType" />
                    </flux:field>
                    @if ($kickbackType !== 'none')
                        <flux:field>
                            <flux:label>{{ $kickbackType === 'percent' ? 'Kickback in %' : 'Kickback in € je Abrechnung' }}</flux:label>
                            <flux:input wire:model="kickbackValue" inputmode="decimal" :placeholder="$kickbackType === 'percent' ? 'z. B. 10' : 'z. B. 2,50'" />
                            <flux:error name="kickbackValue" />
                        </flux:field>
                    @endif
                </div>
            </x-adminv2.card>
        </div>

        <div class="flex flex-col gap-6">
            @if ($cooperation)
                @php $members = $this->members; @endphp
                <x-adminv2.card heading="Mitglieder" :description="$members->count().' '.($members->count() === 1 ? 'Zeitraum' : 'Zeiträume')">
                    <x-slot:actions>
                        <flux:select wire:model.live="memberFilter" size="sm" class="w-36" aria-label="Mitglieder filtern">
                            <flux:select.option value="current">Aktuell</flux:select.option>
                            <flux:select.option value="future">Geplant</flux:select.option>
                            <flux:select.option value="all">Alle</flux:select.option>
                        </flux:select>
                    </x-slot:actions>
                    @if ($members->isEmpty())
                        <p class="text-sm text-zinc-500">Keine Kunden in diesem Zeitraum. Zugeordnet wird beim Kunden im Tab „Abrechnung“.</p>
                    @else
                        <ul class="flex flex-col divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                            @foreach ($members as $period)
                                <li class="flex flex-col gap-0.5 py-2" wire:key="member-{{ $period->id }}">
                                    <a href="{{ route('adminv2.customer-management.customers.edit', ['customer' => $period->customer_id, 'tab' => 'billing']) }}" class="truncate font-medium text-zinc-900 hover:underline dark:text-white">{{ $period->customer?->company_name ?: ($period->customer?->name ?? '#'.$period->customer_id) }}</a>
                                    <span class="flex flex-wrap items-center gap-1.5 text-xs text-zinc-500">
                                        <span class="tabular-nums">{{ $period->rangeLabel() }}</span>
                                        <flux:badge size="sm" :color="$period->billing_mode === 'cooperation' ? 'indigo' : 'violet'" inset="top bottom">{{ CustomerBillingPeriod::BILLING_MODES[$period->billing_mode] ?? $period->billing_mode }}</flux:badge>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-adminv2.card>
            @else
                <x-adminv2.card heading="Nach dem Speichern">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Sobald die Kooperation angelegt ist, lassen sich Kunden im Kundendatensatz unter „Abrechnung“ für einen Zeitraum zuordnen.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>
</form>
