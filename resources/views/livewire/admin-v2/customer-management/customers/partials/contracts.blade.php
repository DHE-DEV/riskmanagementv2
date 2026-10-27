{{-- Tab „Abo“: Vertraege mit Laufzeit, Bedingungen und individuellen Preisen (Matrix ueber den Produktkatalog) --}}
@php
    use App\Models\ProductVersionPrice;

    $contracts = $this->contracts;
    $today = today();
@endphp

<x-adminv2.card heading="Verträge und individuelle Preise" description="Z. B. Rahmenverträge mit großen Firmen: Laufzeit, Bedingungen und je Produkt und Ausprägung ein eigener Preis – ohne Eintrag gilt der Standardpreis aus dem Produktkatalog.">
    <x-slot:actions>
        <flux:button size="sm" icon="plus" wire:click="newContract">Neuer Vertrag</flux:button>
    </x-slot:actions>

    @if ($contracts->isEmpty() && ! $showContractForm)
        <p class="text-sm text-zinc-500">Kein Vertrag – es gelten die Standardpreise.</p>
    @endif

    @if ($contracts->isNotEmpty())
        <ol class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach ($contracts as $contract)
                @php
                    $isCurrent = $contract->starts_on->lte($today) && (! $contract->ends_on || $contract->ends_on->gte($today));
                @endphp
                <li wire:key="contract-{{ $contract->id }}" class="flex flex-wrap items-start justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $contract->name }}</span>
                            <span class="text-sm tabular-nums text-zinc-500">{{ $contract->rangeLabel() }}</span>
                            @if ($isCurrent)
                                <flux:badge size="sm" color="green" inset="top bottom">Aktuell</flux:badge>
                            @elseif ($contract->starts_on->gt($today))
                                <flux:badge size="sm" color="sky" inset="top bottom">Geplant</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc" inset="top bottom">Beendet</flux:badge>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $contract->prices->count() }} individuelle {{ $contract->prices->count() === 1 ? 'Preis' : 'Preise' }}
                            @if ($contract->minimum_term_months) · Mindestlaufzeit {{ $contract->minimum_term_months }} Monate @endif
                            @if ($contract->notice_period) · Kündigung {{ $contract->notice_period }} @endif
                            @if ($contract->auto_renews) · verlängert sich automatisch @endif
                            @if ($increase = $contract->increaseLabel()) · Preiserhöhung {{ $increase }} (nächste {{ $contract->nextAnniversary()->format('d.m.Y') }}) @endif
                        </p>
                        @if ($contract->conditions)
                            <ul class="mt-1 list-inside list-disc text-sm text-zinc-600 dark:text-zinc-400">
                                @foreach ($contract->conditions as $condition)
                                    <li>{{ $condition }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editContract({{ $contract->id }})" aria-label="Vertrag {{ $contract->name }} bearbeiten" />
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteContract({{ $contract->id }})" wire:confirm="Vertrag „{{ $contract->name }}“ mit allen individuellen Preisen löschen?" aria-label="Vertrag {{ $contract->name }} löschen" />
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</x-adminv2.card>

@if ($showContractForm)
    @php
        $priceDay = $contractForm['starts_on'] ?: $today->toDateString();
    @endphp
    <x-adminv2.card :heading="$contractForm['id'] ? 'Vertrag bearbeiten' : 'Neuer Vertrag'">
        <form wire:submit="saveContract" class="flex flex-col gap-6">
            <div class="grid items-start gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2">
                    <flux:input wire:model="contractForm.name" label="Bezeichnung" placeholder="z. B. Rahmenvertrag 2027" />
                    <flux:error name="contractForm.name" />
                </div>
                <div>
                    <flux:input type="date" wire:model.live="contractForm.starts_on" label="Beginn" />
                    <flux:error name="contractForm.starts_on" />
                </div>
                <div>
                    <flux:input type="date" wire:model="contractForm.ends_on" label="Ende" description="Leer = offen." />
                    <flux:error name="contractForm.ends_on" />
                </div>
                <div>
                    <flux:input wire:model="contractForm.minimum_term_months" label="Mindestlaufzeit (Monate)" inputmode="numeric" />
                    <flux:error name="contractForm.minimum_term_months" />
                </div>
                <div class="sm:col-span-2">
                    <flux:input wire:model="contractForm.notice_period" label="Kündigungsfrist" placeholder="z. B. 3 Monate zum Laufzeitende" />
                    <flux:error name="contractForm.notice_period" />
                </div>
                <div>
                    <flux:input wire:model="contractForm.annual_increase_percent" label="Preiserhöhung % pro Jahr" inputmode="decimal" placeholder="z. B. 3" description="Nur Erinnerung per Mail, 30 Tage vor jedem Jahrestag." />
                    <flux:error name="contractForm.annual_increase_percent" />
                </div>
                <div class="flex items-end pb-2 sm:col-span-2 lg:col-span-4">
                    <flux:switch wire:model="contractForm.auto_renews" label="Verlängert sich automatisch" align="left" />
                </div>
            </div>

            {{-- Bedingungen, an die der Vertrag gebunden ist --}}
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">Bedingungen</span>
                    <flux:button size="xs" variant="ghost" icon="plus" wire:click="addContractCondition">Bedingung</flux:button>
                </div>
                @foreach ($contractForm['conditions'] as $cIndex => $condition)
                    <div class="flex items-start gap-2" wire:key="condition-{{ $cIndex }}">
                        <div class="min-w-0 flex-1">
                            <flux:input wire:model="contractForm.conditions.{{ $cIndex }}" size="sm" placeholder="z. B. Mindestens 50 Büros angeschlossen; Preise gelten bei Jahreszahlung" aria-label="Bedingung {{ $cIndex + 1 }}" />
                            <flux:error name="contractForm.conditions.{{ $cIndex }}" />
                        </div>
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeContractCondition({{ $cIndex }})" aria-label="Bedingung entfernen" />
                    </div>
                @endforeach
            </div>

            {{-- Preismatrix ueber alle Produkte und Auspraegungen --}}
            <div class="flex flex-col gap-2">
                <div>
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">Preise</span>
                    <p class="text-sm text-zinc-500">Leer = Standardpreis (Stand {{ \Illuminate\Support\Carbon::parse($priceDay)->format('d.m.Y') }}). Beträge in Euro netto; PAX = Anzahl Reisende, an die der Preis gebunden ist.</p>
                </div>
                <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <table class="w-full min-w-[64rem] text-sm">
                        <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-900">
                            <tr>
                                <th class="px-3 py-2 font-medium">Produkt · Ausprägung</th>
                                <th class="px-3 py-2 font-medium">Standard</th>
                                @foreach (ProductVersionPrice::AMOUNTS as $label)
                                    <th class="px-2 py-2 font-medium">€ {{ $label }}</th>
                                @endforeach
                                <th class="px-2 py-2 text-center font-medium">Kostenlos</th>
                                <th class="px-2 py-2 font-medium">PAX</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($this->contractCatalog as $catalogProduct)
                                @foreach ($catalogProduct->versions as $version)
                                    @php
                                        $standard = $version->priceOn($priceDay);
                                        $row = $contractForm['prices'][$version->id] ?? [];
                                        $isFree = (bool) ($row['is_free'] ?? false);
                                    @endphp
                                    <tr wire:key="matrix-{{ $version->id }}" @class(['bg-sky-50/50 dark:bg-sky-950/20' => array_filter($row)])>
                                        <td class="px-3 py-2">
                                            <span class="font-medium text-zinc-900 dark:text-white">{{ $catalogProduct->name }}</span>
                                            @if ($catalogProduct->versions->count() > 1 || $version->name !== 'Allgemein')
                                                <span class="text-zinc-500">· {{ $version->name }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-xs text-zinc-500">{{ $standard?->label() ?? 'offen' }}</td>
                                        @foreach (array_keys(ProductVersionPrice::AMOUNTS) as $column)
                                            <td class="px-2 py-1.5">
                                                <flux:input wire:model.blur="contractForm.prices.{{ $version->id }}.{{ $column }}" size="sm" inputmode="decimal" class="w-24" :disabled="$isFree" aria-label="{{ $catalogProduct->name }} {{ $version->name }}: {{ ProductVersionPrice::AMOUNTS[$column] }}" />
                                                <flux:error name="contractForm.prices.{{ $version->id }}.{{ $column }}" />
                                            </td>
                                        @endforeach
                                        <td class="px-2 py-1.5 text-center">
                                            <flux:checkbox wire:model.live="contractForm.prices.{{ $version->id }}.is_free" aria-label="{{ $catalogProduct->name }} {{ $version->name }} kostenlos" />
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <flux:input wire:model.blur="contractForm.prices.{{ $version->id }}.pax" size="sm" inputmode="numeric" placeholder="z. B. 10.000.000" class="w-36" aria-label="{{ $catalogProduct->name }} {{ $version->name }}: PAX" />
                                            <flux:error name="contractForm.prices.{{ $version->id }}.pax" />
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <flux:textarea wire:model="contractForm.notes" label="Notizen" rows="2" />

            <div class="flex items-center gap-2">
                <flux:button type="submit" variant="primary" icon="check">Vertrag speichern</flux:button>
                <flux:button variant="ghost" wire:click="$set('showContractForm', false)">Abbrechen</flux:button>
            </div>
        </form>
    </x-adminv2.card>
@endif
