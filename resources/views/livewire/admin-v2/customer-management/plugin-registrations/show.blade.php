@php
    use App\Livewire\AdminV2\CustomerManagement\PluginRegistrations\Index;

    $registration = $this->registration;
    $form = Index::formData($registration);
    $unreadable = $form === null;
    $form ??= [];
    $state = Index::statusOf($registration);
    $index = route('adminv2.customer-management.plugin-registrations.index');
    $copy = 'livewire.admin-v2.customer-management.plugin-clients.copy';
    $value = fn (string $field) => filled($form[$field] ?? null) ? $form[$field] : '–';
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ $index }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Ausstehende Registrierungen
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <flux:heading size="xl" level="1" class="break-all">{{ $registration->email }}</flux:heading>
                <flux:badge size="sm" :color="Index::STATUS_COLORS[$state]">{{ Index::STATUSES[$state] }}</flux:badge>
            </div>
            <flux:subheading>Registrierung für das Plugin – die E-Mail-Adresse wird mit einem Code bestätigt. Hier nur zum Nachsehen.</flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="ghost" :href="$index">Zur Liste</flux:button>
            <flux:button
                variant="danger"
                icon="trash"
                wire:click="delete"
                wire:confirm="Sind Sie sicher, dass Sie diesen Registrierungsversuch löschen möchten? Der Nutzer muss sich erneut registrieren."
            >
                Löschen
            </flux:button>
        </div>
    </div>

    @if ($unreadable)
        <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            <flux:icon.lock-closed variant="mini" />
            Die Angaben des Formulars (Firma, Ansprechpartner, Domain, Adresse) lassen sich nicht entschlüsseln – sie wurden mit einem anderen App-Schlüssel gespeichert.
        </div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-2">
        <x-adminv2.card heading="Registrierungsdaten">
            <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                <dt class="text-zinc-500">E-Mail</dt>
                <dd class="flex items-start gap-1.5 text-zinc-900 dark:text-white">
                    <span class="break-all">{{ $registration->email }}</span>
                    @include($copy, ['text' => $registration->email, 'label' => 'E-Mail kopieren'])
                </dd>

                <dt class="text-zinc-500">Status</dt>
                <dd><flux:badge size="sm" :color="Index::STATUS_COLORS[$state]">{{ Index::STATUSES[$state] }}</flux:badge></dd>

                <dt class="text-zinc-500">Firma</dt>
                <dd class="text-zinc-900 dark:text-white">{{ $value('company_name') }}</dd>

                <dt class="text-zinc-500">Ansprechpartner</dt>
                <dd class="text-zinc-900 dark:text-white">{{ $value('contact_name') }}</dd>

                <dt class="text-zinc-500">Domain</dt>
                <dd class="break-all text-zinc-900 dark:text-white">{{ $value('domain') }}</dd>

                <dt class="text-zinc-500">Fehlversuche</dt>
                <dd><flux:badge size="sm" :color="Index::attemptsColor((int) $registration->attempts, 'green')">{{ $registration->attempts }}</flux:badge></dd>
            </dl>
        </x-adminv2.card>

        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Adresse" collapsible collapsed collapse-key="plugin-registration-address">
                <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                    @foreach (['company_street' => 'Straße', 'company_house_number' => 'Hausnummer', 'company_postal_code' => 'PLZ', 'company_city' => 'Ort', 'company_country' => 'Land'] as $field => $label)
                        <dt class="text-zinc-500">{{ $label }}</dt>
                        <dd class="text-zinc-900 dark:text-white">{{ $value($field) }}</dd>
                    @endforeach
                </dl>
            </x-adminv2.card>

            <x-adminv2.card heading="Zeitstempel" collapsible collapsed collapse-key="plugin-registration-timestamps">
                <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                    <dt class="text-zinc-500">Registrierung gestartet</dt>
                    <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $registration->created_at?->format('d.m.Y H:i') ?? '–' }}</dd>

                    <dt class="text-zinc-500">Code gültig bis</dt>
                    <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $registration->expires_at->format('d.m.Y H:i') }}</dd>

                    <dt class="text-zinc-500">Verifiziert am</dt>
                    <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $registration->verified_at?->format('d.m.Y H:i') ?? 'Noch nicht verifiziert' }}</dd>
                </dl>
            </x-adminv2.card>
        </div>
    </div>
</div>
