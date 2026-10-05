@php
    use App\Models\TravelAlertOrder;

    $order = $this->order;
    $customer = $order->customer;
    $deciders = $this->deciders;
    $contact = trim(($order->first_name ?? '').' '.($order->last_name ?? ''));
    $statusColor = match ($order->status) {
        TravelAlertOrder::STATUS_ACTIVE => 'green',
        TravelAlertOrder::STATUS_PENDING_APPROVAL => 'amber',
        TravelAlertOrder::STATUS_REJECTED => 'red',
        default => 'zinc',
    };
    $canApprove = ! $order->isApproved() && ! $order->isRejected();

    // Ablauf der Testversion: Datum und was davon noch uebrig ist (in ganzen Kalendertagen).
    $trial = $order->trial_expires_at;
    $trialDays = $trial ? (int) now()->startOfDay()->diffInDays($trial) : 0;
    $trialText = match (true) {
        $trial === null => 'Nicht gesetzt',
        $trial->isPast() => $trial->format('d.m.Y').' (abgelaufen)',
        default => $trial->format('d.m.Y').' (noch '.$trialDays.' '.($trialDays === 1 ? 'Tag' : 'Tage').')',
    };
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('adminv2.customer-management.travel-alert-orders.index')" class="-ms-2 mb-1">TravelAlert Bestellungen</flux:button>
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl" level="1">Bestellung {{ $order->id }}</flux:heading>
                <flux:badge size="sm" :color="$statusColor">{{ $order->status_label }}</flux:badge>
            </div>
            <flux:subheading>
                {{ $order->company ?: ($contact ?: $order->email) }} · eingegangen {{ $order->created_at?->format('d.m.Y H:i') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($canApprove)
                @if ($order->isConfirmed())
                    <flux:button variant="primary" icon="check-circle" wire:click="approve" wire:confirm="Travel Alert freischalten? Der Kunde erhält eine E-Mail, dass sein Zugang bereitsteht.">Freischalten</flux:button>
                @else
                    <flux:tooltip content="Der Kunde hat die Bestellung noch nicht bestätigt.">
                        <div><flux:button variant="primary" icon="check-circle" disabled>Freischalten</flux:button></div>
                    </flux:tooltip>
                @endif
            @endif
            @unless ($order->isRejected())
                <flux:button icon="x-circle" wire:click="reject" wire:confirm="Bestellung ablehnen? Der Zugang wird nicht freigeschaltet. Der Kunde wird nicht automatisch benachrichtigt.">Ablehnen</flux:button>
            @endunless
            <flux:button icon="calendar" wire:click="openTrialExpiry">Testversion-Ablauf setzen</flux:button>
            <flux:button variant="danger" icon="trash" wire:click="delete" wire:confirm="Diese Bestellung löschen?">Löschen</flux:button>
        </div>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-2">
        {{-- Linke Spalte --}}
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Firmendaten">
                <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                    <dt class="text-zinc-500">Firmenname</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $order->company ?: '–' }}</dd>

                    <dt class="text-zinc-500">Ansprechpartner</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $contact ?: '–' }}</dd>

                    <dt class="text-zinc-500">E-Mail</dt>
                    <dd class="break-all text-zinc-900 dark:text-white">
                        <a href="mailto:{{ $order->email }}" class="underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">{{ $order->email }}</a>
                    </dd>

                    <dt class="text-zinc-500">Telefon</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $order->phone ?: '–' }}</dd>

                    <dt class="text-zinc-500">Kundenkonto</dt>
                    <dd class="text-zinc-900 dark:text-white">
                        @if ($order->customer_id && $customer)
                            <a href="{{ route('adminv2.customer-management.customers.edit', $order->customer_id) }}" class="underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">{{ trim((string) ($customer->company_name ?: $customer->name)) ?: $customer->email }}</a>
                        @elseif ($order->customer_id)
                            Gelöschter Kunde
                        @else
                            Kein Kundenkonto verknüpft
                        @endif
                    </dd>
                </dl>
            </x-adminv2.card>

            <x-adminv2.card heading="Adresse">
                <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                    <dt class="text-zinc-500">Straße</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $order->street ?: '–' }}</dd>

                    <dt class="text-zinc-500">PLZ / Stadt</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ trim($order->postal_code.' '.$order->city) ?: '–' }}</dd>

                    <dt class="text-zinc-500">Land</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $order->country ?: '–' }}</dd>
                </dl>
            </x-adminv2.card>
        </div>

        {{-- Rechte Spalte --}}
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Freischaltung">
                <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                    <dt class="text-zinc-500">Status</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $order->status_label }}</dd>

                    <dt class="text-zinc-500">Vom Kunden bestätigt</dt>
                    <dd class="tabular-nums text-zinc-900 dark:text-white">
                        {{ $order->confirmed_at?->format('d.m.Y H:i') ?? 'Noch nicht' }}
                        @if (! $order->isConfirmed() && $canApprove)
                            <span class="text-zinc-500">· Link {{ $order->confirmationHasExpired() ? 'abgelaufen am' : 'gültig bis' }} {{ $order->confirmationExpiresAt()->format('d.m.Y H:i') }}</span>
                        @endif
                    </dd>

                    <dt class="text-zinc-500">Freigeschaltet</dt>
                    <dd class="tabular-nums text-zinc-900 dark:text-white">
                        {{ $order->approved_at?->format('d.m.Y H:i') ?? 'Noch nicht' }}
                        @if ($order->approved_at)
                            <span class="text-zinc-500">· {{ $order->approved_by ? 'durch '.($deciders[$order->approved_by] ?? 'gelöschten Benutzer') : 'automatisch' }}</span>
                        @endif
                    </dd>

                    @if ($order->isRejected())
                        <dt class="text-zinc-500">Abgelehnt</dt>
                        <dd class="tabular-nums text-zinc-900 dark:text-white">
                            {{ $order->rejected_at->format('d.m.Y H:i') }}
                            @if ($order->rejected_by)
                                <span class="text-zinc-500">· durch {{ $deciders[$order->rejected_by] ?? 'gelöschten Benutzer' }}</span>
                            @endif
                        </dd>
                    @endif
                </dl>
            </x-adminv2.card>

            <x-adminv2.card heading="Abrechnung">
                <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                    <dt class="text-zinc-500">Bestehendes Abrechnungsverfahren</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $order->existing_billing === 'ja' ? 'Ja' : 'Nein' }}</dd>
                </dl>
            </x-adminv2.card>

            <x-adminv2.card heading="Testversion">
                <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                    <dt class="text-zinc-500">Ablaufdatum Testversion</dt>
                    <dd @class(['tabular-nums', 'font-medium text-red-600 dark:text-red-400' => $trial?->isPast(), 'text-zinc-900 dark:text-white' => ! $trial?->isPast()])>{{ $trialText }}</dd>
                </dl>
            </x-adminv2.card>

            <x-adminv2.card heading="Bemerkung">
                <p class="whitespace-pre-line text-sm {{ $order->remarks ? 'text-zinc-900 dark:text-white' : 'text-zinc-500' }}">{{ $order->remarks ?: 'Keine Bemerkung' }}</p>
            </x-adminv2.card>

            <x-adminv2.record-tasks :record="$order" />

            <x-adminv2.card heading="Meta">
                <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                    <dt class="text-zinc-500">Eingegangen am</dt>
                    <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $order->created_at?->format('d.m.Y H:i:s') ?? '–' }}</dd>

                    <dt class="text-zinc-500">Zuletzt aktualisiert</dt>
                    <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $order->updated_at?->format('d.m.Y H:i:s') ?? '–' }}</dd>
                </dl>
            </x-adminv2.card>
        </div>
    </div>

    <flux:modal name="trial-expiry" class="md:w-[28rem]">
        <form wire:submit="saveTrialExpiry" class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Testversion-Ablauf setzen</flux:heading>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Ein leeres Feld entfernt das Ablaufdatum.</p>
            </div>

            <flux:input wire:model="trialExpiresAt" type="date" label="Ablaufdatum Testversion" />

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Speichern</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
