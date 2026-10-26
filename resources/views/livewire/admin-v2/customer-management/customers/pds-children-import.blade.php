@php
    use App\Services\PdsAccountImportService as Import;

    $back = route('adminv2.customer-management.customers.edit', ['customer' => $agency->id, 'tab' => 'access']);
    $link = 'text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white';
    $importable = collect($childCounts)->only([Import::STATUS_CREATED, Import::STATUS_UPDATED, Import::STATUS_REASSIGNED, Import::STATUS_TRASHED])->sum();
    $childTotal = array_sum($childCounts);
    $agencyName = $agency->company_name ?: $agency->name;
@endphp

<div class="flex flex-col gap-6">
    <div>
        <a href="{{ $back }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
            <flux:icon.arrow-left variant="micro" /> {{ $agencyName }}
        </a>
        <flux:heading size="xl" level="1" class="mt-1">Unteraccounts aus pds-admin importieren</flux:heading>
        <flux:subheading>Die Unteraccounts von {{ $agencyName }} (Kunde #{{ $agency->id }}) werden per Account-ID bzw. E-Mail mit den Kunden abgeglichen. Alle werden diesem Kunden zugeordnet und für ihn freigegeben (Account-Zugriff) – auch vorhandene, solche im Papierkorb und solche einer anderen Agentur. Neu angelegt werden nur die fehlenden.</flux:subheading>
    </div>

    @if ($error)
        <flux:callout variant="danger" icon="x-circle" :heading="$error" />
    @endif

    <x-adminv2.card heading="1. Hauptaccount und Quelle">
        @if ($agency->pds_account_id)
            <p class="text-sm">PDS Account-ID von {{ $agencyName }}: <span class="font-medium tabular-nums text-zinc-900 dark:text-white">{{ $agency->pds_account_id }}</span></p>
        @else
            <form wire:submit="savePdsAccountId" class="flex flex-wrap items-start gap-3">
                <div class="w-64">
                    <flux:input wire:model="pdsAccountId" type="number" min="1" step="1" inputmode="numeric" label="PDS Account-ID des Kunden" description="Fehlt noch – wird am Kunden gespeichert." />
                </div>
                <flux:button type="submit" class="mt-6" icon="check">Speichern</flux:button>
            </form>
        @endif

        <flux:radio.group wire:model.live="source" label="Quelle" class="mt-5">
            @foreach (\App\Livewire\AdminV2\CustomerManagement\Customers\PdsChildrenImport::SOURCES as $value => $label)
                <flux:radio
                    :value="$value"
                    :label="$label"
                    :description="$value === 'api'
                        ? ($this->isConfigured() ? 'Liest die Unteraccounts über __internal/admin/accounts/{ID}/children, alle Seiten.' : 'Nicht verfügbar: PASSOLUTION_INTERNAL_ADMIN_SECRET fehlt in der .env.')
                        : 'Eine CSV aus der Accountliste in pds-admin (Spalten id, email, …).'"
                    :disabled="$value === 'api' && ! $this->isConfigured()"
                />
            @endforeach
        </flux:radio.group>
    </x-adminv2.card>

    @if ($agency->pds_account_id)
        <x-adminv2.card heading="2. Unteraccounts prüfen und importieren">
            <div class="mb-4 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
                @if ($source === 'csv')
                    @if ($csv)
                        <flux:badge icon="document-text">Importdatei: {{ $csv->getClientOriginalName() }}</flux:badge>
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeCsv">Andere Datei</flux:button>
                    @else
                        <div class="w-72">
                            <flux:input type="file" wire:model="csv" accept=".csv,text/csv,text/plain" aria-label="Importdatei" />
                        </div>
                    @endif
                    <flux:error name="csv" />
                @else
                    <flux:badge icon="cloud-arrow-down">Quelle: pds-admin, Account {{ $agency->pds_account_id }}</flux:badge>
                @endif
                <flux:checkbox wire:model.live="onlyActive" label="Nur aktive Unteraccounts" />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <flux:button icon="magnifying-glass" wire:click="loadChildren" :disabled="$source === 'csv' && ! $csv" wire:loading.attr="disabled" wire:target="loadChildren">{{ $childrenLoaded ? 'Neu prüfen' : 'Unteraccounts laden und prüfen' }}</flux:button>

                @if ($childrenLoaded && $importable > 0)
                    <flux:button
                        variant="primary"
                        icon="arrow-down-tray"
                        wire:click="importChildren"
                        wire:confirm="{{ $importable }} Unteraccounts jetzt anlegen bzw. ergänzen und {{ $agencyName }} zuordnen?"
                        wire:loading.attr="disabled"
                        wire:target="importChildren"
                    >{{ $importable }} importieren und zuordnen</flux:button>
                @endif

                <span wire:loading wire:target="csv, loadChildren, importChildren" class="text-sm text-zinc-500">Läuft … das kann bei vielen Accounts eine Weile dauern.</span>
            </div>

            @if ($result)
                <flux:callout variant="success" icon="check-circle" class="mt-4" heading="Import abgeschlossen">
                    <flux:callout.text>
                        @foreach ($result as $status => $count)
                            {{ $labels[$status] }}: {{ $count }}@if (! $loop->last), @endif
                        @endforeach
                    </flux:callout.text>
                </flux:callout>
            @endif

            @if ($childrenLoaded)
                <div class="mt-4 flex flex-wrap gap-2 text-sm">
                    <flux:badge>{{ $childTotal }} {{ $onlyActive ? 'aktive ' : '' }}Unteraccounts</flux:badge>
                    @if ($csvSkipped > 0)
                        <flux:badge color="zinc">{{ $csvSkipped }} Zeilen der Datei ignoriert ({{ $onlyActive ? 'inaktiv, gelöscht oder ' : '' }}anderer Hauptaccount)</flux:badge>
                    @endif
                    @foreach ($childCounts as $status => $count)
                        <flux:badge :color="match ($status) { Import::STATUS_CREATED => 'green', Import::STATUS_UPDATED => 'sky', Import::STATUS_UNCHANGED => 'zinc', Import::STATUS_INVALID, Import::STATUS_DUPLICATE => 'red', default => 'amber' }">{{ $labels[$status] }}: {{ $count }}</flux:badge>
                    @endforeach
                </div>

                @if ($childRows !== [])
                    <div class="mt-4 w-64">
                        <flux:select wire:model.live="childFilter" aria-label="Vorschau filtern">
                            @foreach (\App\Livewire\AdminV2\CustomerManagement\Customers\PdsChildrenImport::FILTERS as $value => $label)
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="mt-2 max-h-[32rem] overflow-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="sticky top-0 bg-white text-xs text-zinc-500 dark:bg-zinc-900">
                                <tr>
                                    <th class="py-2 pe-3 font-medium">Account</th>
                                    <th class="py-2 pe-3 font-medium">Name</th>
                                    <th class="py-2 pe-3 font-medium">E-Mail</th>
                                    <th class="py-2 pe-3 font-medium">Ort</th>
                                    <th class="py-2 font-medium">Beim Import</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @forelse ($visibleRows as $row)
                                    <tr wire:key="child-{{ $row['id'] }}">
                                        <td class="py-2 pe-3 tabular-nums">{{ $row['id'] }}</td>
                                        <td class="py-2 pe-3">
                                            @if ($row['customer_id'])
                                                <a href="{{ route('adminv2.customer-management.customers.edit', $row['customer_id']) }}" class="{{ $link }}">{{ $row['name'] }}</a>
                                            @else
                                                {{ $row['name'] }}
                                            @endif
                                        </td>
                                        <td class="py-2 pe-3">{{ $row['email'] }}</td>
                                        <td class="py-2 pe-3">{{ $row['city'] }}</td>
                                        <td class="py-2">{{ $labels[$row['status']] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-3 text-zinc-500">Keine Unteraccounts in dieser Auswahl.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </x-adminv2.card>
    @endif
</div>
