{{-- Tab „Abrechnung“: selbststaendig oder ueber eine Kooperation, je Zeitraum (ohne Ueberschneidung) --}}
@php
    $periods = $this->billingPeriods;
    $cooperations = $this->cooperationOptions;
    $today = today();
@endphp

<div class="flex flex-col gap-4">
    <x-adminv2.card heading="Abrechnung" description="In welchem Zeitraum der Kunde selbstständig abgerechnet wird oder über eine Kooperation. Zeiträume dürfen sich nicht überschneiden; ohne Enddatum läuft ein Zeitraum offen weiter.">
        <x-slot:actions>
            <flux:button size="sm" icon="plus" wire:click="newBillingPeriod">Neuer Zeitraum</flux:button>
        </x-slot:actions>

        @if ($periods->isEmpty() && ! $showBillingForm)
            <p class="text-sm text-zinc-500">Noch kein Zeitraum erfasst. Lege z. B. „selbstständig ab 01.01.2026“ oder die Mitgliedschaft in einer Kooperation an.</p>
        @endif

        @if ($periods->isNotEmpty())
            <ol class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($periods as $period)
                    @php
                        $isCurrent = $period->starts_on->lte($today) && (! $period->ends_on || $period->ends_on->gte($today));
                        $isFuture = $period->starts_on->gt($today);
                    @endphp
                    <li wire:key="billing-period-{{ $period->id }}" class="flex flex-wrap items-start justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium tabular-nums text-zinc-900 dark:text-white">{{ $period->rangeLabel() }}</span>
                                @if ($isCurrent)
                                    <flux:badge size="sm" color="green" inset="top bottom">Aktuell</flux:badge>
                                @elseif ($isFuture)
                                    <flux:badge size="sm" color="sky" inset="top bottom">Geplant</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc" inset="top bottom">Beendet</flux:badge>
                                @endif
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                                @if ($period->cooperation)
                                    <flux:icon.building-office-2 variant="micro" class="text-zinc-400" />
                                    <a href="{{ route('adminv2.system.cooperations.edit', $period->cooperation) }}" class="font-medium text-zinc-800 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-200">{{ $period->cooperation->name }}</a>
                                    <span>·</span>
                                @else
                                    <flux:icon.user variant="micro" class="text-zinc-400" />
                                    <span>Selbstständig ·</span>
                                @endif
                                <flux:badge size="sm" :color="$period->billing_mode === 'cooperation' ? 'indigo' : 'violet'" inset="top bottom">{{ \App\Models\CustomerBillingPeriod::BILLING_MODES[$period->billing_mode] ?? $period->billing_mode }}</flux:badge>
                                @if ($fixedPrice = $period->cooperation?->fixedPriceLabel())
                                    <flux:badge size="sm" color="zinc" inset="top bottom">Festbetrag {{ $fixedPrice }}</flux:badge>
                                @endif
                            </div>
                            @if ($period->notes)
                                <p class="mt-1 text-sm text-zinc-500">{{ $period->notes }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editBillingPeriod({{ $period->id }})" aria-label="Zeitraum {{ $period->rangeLabel() }} bearbeiten" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteBillingPeriod({{ $period->id }})" wire:confirm="Zeitraum {{ $period->rangeLabel() }} löschen?" aria-label="Zeitraum {{ $period->rangeLabel() }} löschen" />
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-adminv2.card>

    @if ($showBillingForm)
        <x-adminv2.card :heading="$billingForm['id'] ? 'Zeitraum bearbeiten' : 'Neuer Zeitraum'">
            <form wire:submit="saveBillingPeriod" class="flex flex-col gap-5">
                <div class="grid items-start gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Abrechnung über</flux:label>
                        <flux:select wire:model.live="billingForm.cooperation_id">
                            <flux:select.option value="">Selbstständig (keine Kooperation)</flux:select.option>
                            @foreach ($cooperations as $cooperation)
                                <flux:select.option value="{{ $cooperation->id }}">{{ $cooperation->name }}{{ $cooperation->is_active ? '' : ' (inaktiv)' }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @if ($cooperations->isEmpty())
                            <flux:description>Noch keine Kooperation angelegt – unter <a href="{{ route('adminv2.system.cooperations.index') }}" class="underline">System › Kooperationen</a>.</flux:description>
                        @endif
                        <flux:error name="billingForm.cooperation_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Wer zahlt?</flux:label>
                        <flux:select wire:model="billingForm.billing_mode" :disabled="$billingForm['cooperation_id'] === ''">
                            @foreach (\App\Models\CustomerBillingPeriod::BILLING_MODES as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @php
                            $chosenCooperation = $billingForm['cooperation_id'] !== '' ? $cooperations->firstWhere('id', (int) $billingForm['cooperation_id']) : null;
                        @endphp
                        <flux:description>
                            {{ $chosenCooperation ? 'Vorbelegt wie bei der Kooperation eingestellt; als Ausnahme auch einzeln.' : 'Ohne Kooperation zahlt der Kunde selbst.' }}
                            @if ($chosenCooperation?->fixedPriceLabel())
                                Festbetrag {{ $chosenCooperation->fixedPriceLabel() }} – {{ $billingForm['billing_mode'] === 'cooperation' ? 'rechnet die Kooperation ab' : 'bekommt das Büro einzeln berechnet' }}.
                            @endif
                        </flux:description>
                        <flux:error name="billingForm.billing_mode" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Startdatum</flux:label>
                        <flux:input type="date" wire:model="billingForm.starts_on" />
                        <flux:error name="billingForm.starts_on" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Enddatum</flux:label>
                        <flux:input type="date" wire:model="billingForm.ends_on" />
                        <flux:description>Leer = offen.</flux:description>
                        <flux:error name="billingForm.ends_on" />
                    </flux:field>
                </div>

                @if (! $billingForm['id'] && ($open = $this->openBillingPeriod))
                    <flux:checkbox wire:model="billingForm.close_previous" label="Laufenden Zeitraum ({{ $open->rangeLabel() }}, {{ $open->label() }}) am Vortag des Startdatums beenden" />
                @endif

                <flux:textarea wire:model="billingForm.notes" label="Notiz" rows="2" placeholder="z. B. Wechsel laut Mail vom …" />

                <div class="flex items-center gap-2">
                    <flux:button type="submit" variant="primary" icon="check">Speichern</flux:button>
                    <flux:button variant="ghost" wire:click="$set('showBillingForm', false)">Abbrechen</flux:button>
                </div>
            </form>
        </x-adminv2.card>
    @endif
</div>
