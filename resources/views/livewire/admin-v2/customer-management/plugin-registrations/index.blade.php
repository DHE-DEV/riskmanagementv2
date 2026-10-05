@php
    use App\Livewire\AdminV2\CustomerManagement\PluginRegistrations\Index;

    $rows = $this->rows;
    $pending = $this->pendingCount;
    $copy = 'livewire.admin-v2.customer-management.plugin-clients.copy';
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.customer-management.list-header section="plugin-registrations">
        @if ($pending > 0)
            <flux:badge color="amber">{{ $pending }} {{ $pending === 1 ? 'wartet' : 'warten' }} auf Bestätigung</flux:badge>
        @endif
        <flux:button
            variant="danger"
            icon="trash"
            wire:click="cleanup"
            wire:confirm="Alte Einträge bereinigen? Hiermit werden alle abgelaufenen und bereits verifizierten Einträge gelöscht, die älter als 24 Stunden sind."
        >
            Alte Einträge bereinigen
        </flux:button>
    </x-adminv2.customer-management.list-header>

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="E-Mail, Firma, Ansprechpartner oder Domain suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-60">
            <flux:select wire:model.live="status" aria-label="Status">
                <flux:select.option value="open">Nur nicht verifizierte</flux:select.option>
                @foreach (Index::STATUSES as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
                <flux:select.option value="all">Alle Einträge</flux:select.option>
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="created" aria-label="Erstellt">
                <flux:select.option value="">Erstellt: jederzeit</flux:select.option>
                <flux:select.option value="today">Heute erstellt</flux:select.option>
            </flux:select>
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Registrierung', 'Registrierungen']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            @if ($this->hasFilters())
                <x-adminv2.master-data.empty filtered noun="Registrierungen" />
            @else
                <div class="flex flex-col items-center gap-3 px-4 py-14 text-center">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
                        <flux:icon.envelope class="size-6" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine ausstehenden Registrierungen</p>
                        <p class="mt-1 text-sm text-zinc-500">Es gibt derzeit keine offenen Registrierungsversuche.</p>
                    </div>
                </div>
            @endif
        </x-adminv2.card>
    @else
        {{-- Auswahl mehrerer Eintraege --}}
        <div class="-mb-2 flex min-h-8 flex-wrap items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
            @if ($selected === [])
                <flux:button variant="ghost" size="sm" icon="check-circle" wire:click="selectPage">Alle auf dieser Seite auswählen</flux:button>
            @else
                <span class="font-medium tabular-nums text-zinc-900 dark:text-white">{{ count($selected) }} ausgewählt</span>
                <flux:button variant="ghost" size="sm" wire:click="selectPage">Alle auf dieser Seite</flux:button>
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearSelection">Auswahl aufheben</flux:button>
                <flux:button variant="danger" size="sm" icon="trash" wire:click="deleteSelected" wire:confirm="Sind Sie sicher, dass Sie die ausgewählten Einträge löschen möchten?">Ausgewählte löschen</flux:button>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" wire:loading.class="opacity-60" wire:target="search, status, created, sort, toggleDirection, resetFilters, delete, deleteSelected, cleanup, gotoPage, nextPage, previousPage">
            @foreach ($rows as $registration)
                @php
                    $showUrl = route('adminv2.customer-management.plugin-registrations.show', $registration->id);
                    $state = Index::statusOf($registration);
                    $form = Index::formData($registration);
                    $unreadable = $form === null;
                    $form ??= [];
                    $expired = $registration->expires_at->isPast();
                @endphp
                <article
                    wire:key="registration-{{ $registration->id }}"
                    @class([
                        'group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                        'opacity-70' => $state !== 'pending',
                    ])
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-2.5">
                            <input type="checkbox" wire:model.live="selected" value="{{ $registration->id }}" aria-label="{{ $registration->email }} auswählen" class="relative z-10 mt-1 size-4 shrink-0 rounded accent-[var(--color-accent)]" />
                            <div class="min-w-0">
                                <h2 class="min-w-0 text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                                    <a href="{{ $showUrl }}" class="line-clamp-2 break-all after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $registration->email }}</a>
                                </h2>
                                @if (filled($form['company_name'] ?? null))
                                    <p class="mt-0.5 truncate text-sm text-zinc-500">{{ $form['company_name'] }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="relative z-10 -me-1.5 -mt-1 flex shrink-0 items-center">
                            @include($copy, ['text' => $registration->email, 'label' => 'E-Mail kopieren'])
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen für {{ $registration->email }}" />

                                <flux:menu>
                                    <flux:menu.item icon="eye" :href="$showUrl">Details</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $registration->id }})" wire:confirm="Registrierungsversuch löschen? Der Nutzer muss sich erneut registrieren.">Löschen</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                        <flux:badge size="sm" inset="top bottom" :color="Index::STATUS_COLORS[$state]">{{ Index::STATUSES[$state] }}</flux:badge>
                        <flux:badge size="sm" inset="top bottom" :color="Index::attemptsColor((int) $registration->attempts)">{{ $registration->attempts }} {{ (int) $registration->attempts === 1 ? 'Fehlversuch' : 'Fehlversuche' }}</flux:badge>
                    </div>

                    <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                        @if ($unreadable)
                            <x-adminv2.master-data.card-row icon="lock-closed" label="Angaben">
                                <span class="text-amber-700 dark:text-amber-400">Angaben des Formulars nicht lesbar</span>
                            </x-adminv2.master-data.card-row>
                        @endif
                        @if (filled($form['contact_name'] ?? null))
                            <x-adminv2.master-data.card-row icon="user" label="Ansprechpartner">{{ $form['contact_name'] }}</x-adminv2.master-data.card-row>
                        @endif
                        @if (filled($form['domain'] ?? null))
                            <x-adminv2.master-data.card-row icon="globe-alt" label="Domain"><span class="break-all">{{ $form['domain'] }}</span></x-adminv2.master-data.card-row>
                        @endif
                        <x-adminv2.master-data.card-row icon="clock" label="Läuft ab">
                            <span class="tabular-nums">Code gültig bis {{ $registration->expires_at->format('d.m.Y H:i') }}</span>
                            <span class="text-zinc-400">·</span>
                            {{ $expired ? 'Abgelaufen' : 'Noch '.now()->locale('de')->diffForHumans($registration->expires_at, true) }}
                        </x-adminv2.master-data.card-row>
                    </dl>

                    <div class="mt-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pt-4 text-xs text-zinc-500">
                        <span class="tabular-nums">erstellt {{ $registration->created_at?->format('d.m.Y H:i') ?? '–' }}</span>
                    </div>
                </article>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif
</div>
