{{-- Tab „Abo“: Abrechnungsintervall je Zeitraum – fuer alle abonnierten Dienste oder einzelne Module --}}
@php
    use App\Models\CustomerBillingInterval;

    $intervals = $this->billingIntervals;
    $today = today();
    $grouped = $intervals->groupBy(fn ($interval) => $interval->module ?? '');
@endphp

<div class="flex flex-col gap-4">
    @include('livewire.admin-v2.customer-management.customers.partials.contracts')

    <x-adminv2.card heading="Abrechnungsintervall" description="Monatlich, halbjährlich oder jährlich – für alle abonnierten Dienste oder einzelne Module, bei Jahres- oder Halbjahreszahlung mit Rabatt. Ein Eintrag für ein Modul geht dem für alle Dienste vor. Zeiträume desselben Moduls dürfen sich nicht überschneiden.">
        <x-slot:actions>
            <flux:button size="sm" icon="plus" wire:click="newBillingInterval">Neuer Zeitraum</flux:button>
        </x-slot:actions>

        @if ($intervals->isEmpty() && ! $showIntervalForm)
            <p class="text-sm text-zinc-500">Noch kein Intervall erfasst. Lege z. B. „Alle abonnierten Dienste · monatlich ab 01.01.2026“ an und stelle später auf jährlich um.</p>
        @endif

        <div class="flex flex-col gap-5">
            @foreach ($grouped as $module => $moduleIntervals)
                <section wire:key="interval-group-{{ $module ?: 'all' }}">
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ CustomerBillingInterval::labelForModule($module ?: null) }}</h3>
                        <flux:button size="xs" variant="ghost" icon="arrows-right-left" wire:click="newBillingInterval('{{ $module }}')">Umstellen</flux:button>
                    </div>
                    <ol class="flex flex-col divide-y divide-zinc-100 rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-800">
                        @foreach ($moduleIntervals as $interval)
                            @php
                                $isCurrent = $interval->starts_on->lte($today) && (! $interval->ends_on || $interval->ends_on->gte($today));
                                $isFuture = $interval->starts_on->gt($today);
                            @endphp
                            <li wire:key="billing-interval-{{ $interval->id }}" class="flex flex-wrap items-center justify-between gap-3 px-3 py-2">
                                <div class="flex min-w-0 flex-wrap items-center gap-2">
                                    <flux:badge size="sm" :color="['monthly' => 'sky', 'half_yearly' => 'amber', 'yearly' => 'green'][$interval->interval] ?? 'zinc'" inset="top bottom">{{ $interval->intervalLabel() }}</flux:badge>
                                    @if ($discount = $interval->discountLabel())
                                        <flux:badge size="sm" color="amber" inset="top bottom">{{ $discount }}</flux:badge>
                                    @endif
                                    <span class="text-sm tabular-nums text-zinc-700 dark:text-zinc-300">{{ $interval->rangeLabel() }}</span>
                                    @if ($isCurrent)
                                        <flux:badge size="sm" color="green" variant="outline" inset="top bottom">Aktuell</flux:badge>
                                    @elseif ($isFuture)
                                        <flux:badge size="sm" color="sky" variant="outline" inset="top bottom">Geplant</flux:badge>
                                    @endif
                                    @if ($interval->notes)
                                        <span class="text-sm text-zinc-500">· {{ $interval->notes }}</span>
                                    @endif
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editBillingInterval({{ $interval->id }})" aria-label="Zeitraum {{ $interval->rangeLabel() }} bearbeiten" />
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteBillingInterval({{ $interval->id }})" wire:confirm="{{ $interval->moduleLabel() }}: Zeitraum {{ $interval->rangeLabel() }} löschen?" aria-label="Zeitraum {{ $interval->rangeLabel() }} löschen" />
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
        </div>
    </x-adminv2.card>

    @if ($showIntervalForm)
        <x-adminv2.card :heading="$intervalForm['id'] ? 'Zeitraum bearbeiten' : 'Intervall festlegen oder umstellen'">
            <form wire:submit="saveBillingInterval" class="flex flex-col gap-5">
                <div class="grid items-start gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Gilt für</flux:label>
                        <flux:select wire:model.live="intervalForm.module">
                            <flux:select.option value="">Alle abonnierten Dienste</flux:select.option>
                            @foreach (\App\Models\Product::moduleOptions() as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="intervalForm.module" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Intervall</flux:label>
                        <flux:select wire:model.live="intervalForm.interval">
                            @foreach (CustomerBillingInterval::INTERVALS as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="intervalForm.interval" />
                    </flux:field>

                    @if ($intervalForm['interval'] !== 'monthly')
                        <flux:field>
                            <flux:label>Rabatt bei {{ $intervalForm['interval'] === 'yearly' ? 'Jahreszahlung' : 'Halbjahreszahlung' }}</flux:label>
                            <flux:input wire:model="intervalForm.discount_percent" inputmode="decimal" placeholder="z. B. 10" />
                            <flux:description>In Prozent, leer = kein Rabatt.</flux:description>
                            <flux:error name="intervalForm.discount_percent" />
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:label>Startdatum</flux:label>
                        <flux:input type="date" wire:model="intervalForm.starts_on" />
                        <flux:error name="intervalForm.starts_on" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Enddatum</flux:label>
                        <flux:input type="date" wire:model="intervalForm.ends_on" />
                        <flux:description>Leer = offen.</flux:description>
                        <flux:error name="intervalForm.ends_on" />
                    </flux:field>
                </div>

                @unless ($intervalForm['id'])
                    <flux:checkbox wire:model="intervalForm.close_previous" label="Einen laufenden offenen Zeitraum für dasselbe Modul am Vortag des Startdatums beenden" />
                @endunless

                <flux:textarea wire:model="intervalForm.notes" label="Notiz" rows="2" placeholder="z. B. auf Jahresrechnung umgestellt, Mail vom …" />

                <div class="flex items-center gap-2">
                    <flux:button type="submit" variant="primary" icon="check">Speichern</flux:button>
                    <flux:button variant="ghost" wire:click="$set('showIntervalForm', false)">Abbrechen</flux:button>
                </div>
            </form>
        </x-adminv2.card>
    @endif
</div>
