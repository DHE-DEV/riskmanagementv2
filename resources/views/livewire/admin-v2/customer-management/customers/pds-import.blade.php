@php
    use App\Services\PdsAccountImportService as Import;

    $index = route('adminv2.customer-management.customers.index');
    $link = 'text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white';
@endphp

<div class="flex flex-col gap-6">
    <div>
        <a href="{{ $index }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
            <flux:icon.arrow-left variant="micro" /> Kunden
        </a>
        <flux:heading size="xl" level="1" class="mt-1">Account aus pds-admin importieren</flux:heading>
        <flux:subheading>Einen einzelnen Account per PDS Account-ID abrufen. Gibt es ihn schon als Kunden (gleiche Account-ID oder E-Mail), wird er nicht doppelt angelegt. Unteraccounts einer Agentur importierst du im Kunden im Tab „Unteraccounts“.</flux:subheading>
    </div>

    @unless ($this->isConfigured())
        <flux:callout variant="warning" icon="exclamation-triangle" heading="Import nicht eingerichtet">
            <flux:callout.text>In der .env fehlen PASSOLUTION_INTERNAL_TOKEN und/oder PASSOLUTION_INTERNAL_ADMIN_SECRET (Admin-Service-Secret der pds-api).</flux:callout.text>
        </flux:callout>
    @endunless

    @if ($error)
        <flux:callout variant="danger" icon="x-circle" :heading="$error" />
    @endif

    <x-adminv2.card heading="Account" description="Die Account-ID aus pds-admin.">
        <form wire:submit="fetch" class="flex flex-wrap items-start gap-3">
            <div class="w-56">
                <flux:input wire:model="pdsAccountId" type="number" min="1" step="1" inputmode="numeric" placeholder="z. B. 26054" aria-label="PDS Account-ID" />
            </div>
            <flux:button type="submit" icon="arrow-down-tray" wire:loading.attr="disabled" wire:target="fetch">Abrufen und prüfen</flux:button>
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
                    <dt class="text-zinc-500">Status</dt>
                    <dd>{{ data_get($account, 'status.active') ? 'aktiv' : 'inaktiv' }}{{ data_get($account, 'status.deleted') ? ', gelöscht' : '' }}</dd>
                    <dt class="text-zinc-500">Hauptaccount</dt>
                    <dd>
                        @if (data_get($account, 'parent.id'))
                            {{ data_get($account, 'parent.id') }} · {{ data_get($account, 'parent.name') }}
                        @else
                            –
                        @endif
                    </dd>
                </dl>

                <div class="flex flex-col items-start gap-3 text-sm">
                    @if ($customer)
                        <flux:badge color="green" icon="check-circle">Schon in der Plattform</flux:badge>
                        <p>
                            <a href="{{ route('adminv2.customer-management.customers.edit', $customer->id) }}" class="{{ $link }}">{{ $customer->company_name ?: $customer->name }}</a>
                            <span class="text-zinc-500">({{ $customer->email }}, Kunde #{{ $customer->id }}{{ $customer->trashed() ? ', im Papierkorb' : '' }})</span>
                        </p>
                        @if ($plan !== Import::STATUS_UNCHANGED)
                            <p class="text-zinc-500">Fehlt noch: {{ collect([
                                    $customer->pds_account_id === null ? 'Account-ID' : null,
                                    $parent && (int) $customer->assign_to !== $parent->id ? 'Zuordnung zu '.($parent->company_name ?: $parent->name) : null,
                                ])->filter()->join(', ') ?: 'Account-Zugriff der Agentur' }}</p>
                            <flux:button size="sm" icon="link" wire:click="import">Am Kunden ergänzen</flux:button>
                        @endif
                    @else
                        <flux:badge color="zinc">Noch nicht in der Plattform</flux:badge>
                        @if ($parent)
                            <p class="text-zinc-500">Wird dem Hauptaccount zugeordnet: <a href="{{ route('adminv2.customer-management.customers.edit', $parent->id) }}" class="{{ $link }}">{{ $parent->company_name ?: $parent->name }}</a> (Kunde #{{ $parent->id }})</p>
                        @elseif (data_get($account, 'parent.id'))
                            <p class="text-zinc-500">Den Hauptaccount {{ data_get($account, 'parent.id') }} gibt es noch nicht als Kunden – der Account wird ohne Agentur angelegt.</p>
                        @endif
                        <flux:button variant="primary" icon="plus" wire:click="import" wire:loading.attr="disabled" wire:target="import">Als Kunde anlegen</flux:button>
                    @endif
                </div>
            </div>
        @endif
    </x-adminv2.card>
</div>
