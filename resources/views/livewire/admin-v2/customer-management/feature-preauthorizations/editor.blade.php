@php
    $preauthorization = $this->preauthorization;
    $index = route('adminv2.customer-management.feature-preauthorizations.index');
    $hint = $this->accountHint();
    $accounts = $this->accounts;
    $link = 'text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white';
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ $index }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Feature-Vormerkungen
            </a>
            <flux:heading size="xl" level="1" class="mt-1">{{ $preauthorization ? 'Vormerkung für Account '.$preauthorization->pds_account_id : 'Feature einzeln vormerken' }}</flux:heading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($preauthorization)
                <flux:modal.trigger name="preauthorization-delete">
                    <flux:button variant="ghost" icon="trash" class="me-2">Löschen</flux:button>
                </flux:modal.trigger>
            @endif
            <flux:button variant="ghost" :href="$index">{{ $preauthorization ? 'Zur Liste' : 'Abbrechen' }}</flux:button>
            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save">Speichern</flux:button>
        </div>
    </div>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <x-adminv2.card heading="Vormerkung" description="Beim ersten Login des Accounts wird die Vormerkung in eine Freischaltung am Kunden übersetzt.">
            <div class="grid items-start gap-5 sm:grid-cols-2">
                <div>
                    <flux:input
                        wire:model.live.debounce.400ms="pdsAccountId"
                        type="number"
                        min="1"
                        step="1"
                        inputmode="numeric"
                        label="PDS Account-ID"
                        description="Die Account-ID aus dem Login. Ein Kundenkonto muss dafür noch nicht existieren."
                    />
                    @if ($hint)
                        <p @class(['mt-2 text-xs font-medium', 'text-green-700 dark:text-green-400' => $accounts->isNotEmpty(), 'text-amber-700 dark:text-amber-400' => $accounts->isEmpty()])>{{ $hint }}</p>
                    @endif
                </div>

                <flux:select wire:model.live="featureKey" label="Feature">
                    @foreach ($this->featureLabels() as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:switch wire:model="enabled" label="Freischalten" description="Aus = Feature wird beim ersten Login gesperrt statt freigeschaltet." align="left" />

                <flux:input wire:model="note" label="Notiz" placeholder="z. B. Herkunft der Liste" maxlength="255" />
            </div>
        </x-adminv2.card>

        <aside class="flex flex-col gap-6">
            <x-adminv2.card heading="Kundenkonten" description="Konten mit dieser Account-ID">
                @if ($accounts->isEmpty())
                    <p class="text-sm text-zinc-500">{{ $hint ? 'Noch kein Konto – die Vormerkung greift beim ersten Login.' : 'Nach Eingabe der Account-ID stehen hier die zugehörigen Konten.' }}</p>
                @else
                    <ul class="-my-1 flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($accounts as $account)
                            <li wire:key="account-{{ $account->id }}" class="py-2">
                                <a href="{{ route('adminv2.customer-management.customers.edit', $account->id) }}" class="text-sm font-medium {{ $link }}">{{ $account->company_name ?: ($account->name ?: 'Kunde #'.$account->id) }}</a>
                                <div class="mt-0.5 text-xs text-zinc-500">{{ $account->email }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-adminv2.card>

            @if ($preauthorization)
                <x-adminv2.card heading="Stand">
                    <dl class="flex flex-col gap-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500">Eingelöst</dt>
                            <dd class="text-end tabular-nums text-zinc-900 dark:text-white">
                                @if ($preauthorization->applied_at)
                                    {{ $preauthorization->applied_at->format('d.m.Y H:i') }}
                                    @if ($preauthorization->applied_customer_id)
                                        <br><a href="{{ route('adminv2.customer-management.customers.edit', $preauthorization->applied_customer_id) }}" class="{{ $link }}">Kunde #{{ $preauthorization->applied_customer_id }}</a>
                                    @endif
                                @else
                                    <span class="text-amber-700 dark:text-amber-400">offen</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500">Angelegt</dt>
                            <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $preauthorization->created_at?->format('d.m.Y H:i') ?? '–' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500">Geändert</dt>
                            <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $preauthorization->updated_at?->format('d.m.Y H:i') ?? '–' }}</dd>
                        </div>
                    </dl>
                </x-adminv2.card>

                <x-adminv2.record-tasks :record="$preauthorization" />
            @endif
        </aside>
    </div>

    {{-- Rueckfrage vor dem Loeschen: klarstellen, dass Loeschen nichts zurueckdreht. --}}
    @if ($preauthorization)
        <flux:modal name="preauthorization-delete" class="md:w-[32rem]">
            <div class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">Vormerkung löschen?</flux:heading>
                    <flux:text class="mt-2">Löscht nur die Vormerkung. Eine bereits erteilte Freischaltung bleibt beim Kunden bestehen.</flux:text>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                    <flux:button variant="danger" icon="trash" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Löschen</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</form>
