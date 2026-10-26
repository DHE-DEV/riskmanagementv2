@php
    use App\Services\PdsAccountImportService as Import;

    $index = route('adminv2.customer-management.customers.index');
    $link = 'text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white';
    $importable = collect($childCounts)->only([Import::STATUS_CREATED, Import::STATUS_UPDATED, Import::STATUS_REASSIGNED, Import::STATUS_TRASHED])->sum();
    $childTotal = array_sum($childCounts);
@endphp

<div class="flex flex-col gap-6">
    <div>
        <a href="{{ $index }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
            <flux:icon.arrow-left variant="micro" /> Kunden
        </a>
        <flux:heading size="xl" level="1" class="mt-1">Aus pds-admin importieren</flux:heading>
        <flux:subheading>Hauptaccount als Kunde anlegen, danach seine Unteraccounts anlegen und der Agentur zuordnen. Vorhandene Kunden werden per Account-ID bzw. E-Mail erkannt und nicht doppelt angelegt.</flux:subheading>
    </div>

    @unless ($this->isConfigured())
        <flux:callout variant="warning" icon="exclamation-triangle" heading="Import nicht eingerichtet">
            <flux:callout.text>In der .env fehlen PASSOLUTION_INTERNAL_TOKEN und/oder PASSOLUTION_INTERNAL_ADMIN_SECRET (Admin-Service-Secret der pds-api). Ohne sie lässt sich nur ein vorhandener Kunde als Hauptaccount wählen und die Unteraccounts nur per CSV-Export abgleichen.</flux:callout.text>
        </flux:callout>
    @endunless

    @if ($error)
        <flux:callout variant="danger" icon="x-circle" :heading="$error" />
    @endif

    <x-adminv2.card heading="1. Hauptaccount" description="Die Account-ID aus pds-admin.">
        <form wire:submit="fetch" class="flex flex-wrap items-start gap-3">
            <div class="w-56">
                <flux:input wire:model="pdsAccountId" type="number" min="1" step="1" inputmode="numeric" placeholder="z. B. 3145" aria-label="PDS Account-ID" />
            </div>
            <flux:button type="submit" icon="arrow-down-tray" wire:loading.attr="disabled" wire:target="fetch">Abrufen</flux:button>
        </form>

        @if ($account)
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <dl class="grid grid-cols-[8rem_1fr] gap-x-3 gap-y-1.5 text-sm">
                    <dt class="text-zinc-500">Account</dt>
                    <dd class="font-medium text-zinc-900 dark:text-white">{{ $account['id'] }} · {{ $account['name'] }}</dd>
                    <dt class="text-zinc-500">Kontakt</dt>
                    <dd>{{ trim(($account['first_name'] ?? '').' '.($account['last_name'] ?? '')) ?: '–' }}</dd>
                    <dt class="text-zinc-500">E-Mail</dt>
                    <dd>{{ $account['email'] ?: '–' }}</dd>
                    <dt class="text-zinc-500">Adresse</dt>
                    <dd>{{ collect([$account['address_line_1'] ?? null, trim(($account['zip_code'] ?? '').' '.($account['city'] ?? ''))])->filter()->join(', ') ?: '–' }}</dd>
                    @if ($account['local'] ?? false)
                        <dt class="text-zinc-500">Quelle</dt>
                        <dd>Kunde in der Plattform (pds-api nicht angebunden)</dd>
                    @else
                        <dt class="text-zinc-500">Status</dt>
                        <dd>{{ data_get($account, 'status.active') ? 'aktiv' : 'inaktiv' }}{{ data_get($account, 'status.deleted') ? ', gelöscht' : '' }}</dd>
                        <dt class="text-zinc-500">Unteraccounts</dt>
                        <dd class="tabular-nums">{{ data_get($account, 'counts.children') ?? data_get($account, 'children_count') ?? '–' }}</dd>
                    @endif
                </dl>

                <div class="flex flex-col items-start gap-3">
                    @if ($agency)
                        <p class="text-sm">
                            In der Plattform:
                            <a href="{{ route('adminv2.customer-management.customers.edit', $agency->id) }}" class="{{ $link }}">{{ $agency->company_name ?: $agency->name }}</a>
                            <span class="text-zinc-500">({{ $agency->email }}, Kunde #{{ $agency->id }})</span>
                        </p>
                        @if ($agency->trashed())
                            <flux:badge color="amber">Im Papierkorb – erst wiederherstellen</flux:badge>
                        @elseif ((int) $agency->pds_account_id !== (int) $account['id'])
                            <flux:button size="sm" wire:click="importAccount" icon="link">Account-ID am Kunden ergänzen</flux:button>
                        @endif
                    @else
                        <p class="text-sm text-zinc-500">Noch kein Kunde mit dieser Account-ID oder E-Mail.</p>
                        <flux:button variant="primary" icon="plus" wire:click="importAccount" wire:loading.attr="disabled" wire:target="importAccount">Als Kunde anlegen</flux:button>
                    @endif
                </div>
            </div>
        @endif
    </x-adminv2.card>

    @if ($account && $agency && ! $agency->trashed())
        <x-adminv2.card heading="2. Unteraccounts" description="Aktive Unteraccounts werden per Account-ID bzw. E-Mail mit den Kunden abgeglichen. Alle werden der Agentur zugeordnet und für sie freigegeben (Account-Zugriff) – auch vorhandene, solche im Papierkorb und solche einer anderen Agentur. Neu angelegt werden nur die fehlenden.">
            <div class="mb-4 flex flex-wrap items-center gap-3 text-sm">
                @if ($csv)
                    <flux:badge icon="document-text">Quelle: {{ $csv->getClientOriginalName() }}</flux:badge>
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeCsv">{{ $this->isConfigured() ? 'Stattdessen aus der pds-api laden' : 'CSV entfernen' }}</flux:button>
                @else
                    <span class="text-zinc-500">{{ $this->isConfigured() ? 'Quelle: pds-api. Oder einen CSV-Export der Accountliste verwenden:' : 'Quelle: CSV-Export der Accountliste aus pds-admin (Spalten id, email, …).' }}</span>
                    <div class="w-72">
                        <flux:input type="file" wire:model="csv" accept=".csv,text/csv,text/plain" aria-label="CSV-Export" />
                    </div>
                @endif
                <flux:error name="csv" />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <flux:button icon="magnifying-glass" wire:click="loadChildren" :disabled="! $csv && ! $this->isConfigured()" wire:loading.attr="disabled" wire:target="loadChildren">{{ $childrenLoaded ? 'Neu prüfen' : 'Unteraccounts laden und prüfen' }}</flux:button>

                @if ($childrenLoaded && $importable > 0)
                    <flux:button
                        variant="primary"
                        icon="arrow-down-tray"
                        wire:click="importChildren"
                        wire:confirm="{{ $importable }} Unteraccounts jetzt anlegen bzw. ergänzen und {{ $agency->company_name ?: $agency->name }} zuordnen?"
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
                    <flux:badge>{{ $childTotal }} aktive Unteraccounts</flux:badge>
                    @if ($csvSkipped > 0)
                        <flux:badge color="zinc">{{ $csvSkipped }} CSV-Zeilen ignoriert (inaktiv, gelöscht oder anderer Hauptaccount)</flux:badge>
                    @endif
                    @foreach ($childCounts as $status => $count)
                        <flux:badge :color="match ($status) { Import::STATUS_CREATED => 'green', Import::STATUS_UPDATED => 'sky', Import::STATUS_UNCHANGED => 'zinc', Import::STATUS_INVALID, Import::STATUS_DUPLICATE => 'red', default => 'amber' }">{{ $labels[$status] }}: {{ $count }}</flux:badge>
                    @endforeach
                </div>

                @if ($childRows !== [])
                    <div class="mt-4 w-64">
                        <flux:select wire:model.live="childFilter" aria-label="Vorschau filtern">
                            @foreach (\App\Livewire\AdminV2\CustomerManagement\Customers\PdsImport::FILTERS as $value => $label)
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
