@php
    use App\Models\Cooperation;

    $cooperations = $this->cooperations;
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Kooperationen</flux:heading>
            <flux:subheading>System · Kooperationen, über die Kunden für bestimmte Zeiträume abgerechnet werden. Die Zuordnung geschieht beim Kunden im Tab „Abrechnung“.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('adminv2.system.cooperations.create')">Neue Kooperation</flux:button>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 max-w-md flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Kooperation suchen …" aria-label="Suche" clearable />
        </div>
        <span class="text-sm text-zinc-500 tabular-nums">{{ number_format($cooperations->total(), 0, ',', '.') }} {{ $cooperations->total() === 1 ? 'Kooperation' : 'Kooperationen' }}</span>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($cooperations as $cooperation)
            @php $editUrl = route('adminv2.system.cooperations.edit', $cooperation); @endphp
            <article wire:key="cooperation-{{ $cooperation->id }}" @class([
                'group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                'opacity-70' => ! $cooperation->is_active,
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                            <a href="{{ $editUrl }}" class="after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $cooperation->name }}</a>
                        </h2>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge size="sm" :color="$cooperation->is_active ? 'green' : 'zinc'" inset="top bottom">{{ $cooperation->is_active ? 'Aktiv' : 'Inaktiv' }}</flux:badge>
                            <flux:badge size="sm" color="zinc" inset="top bottom">{{ $cooperation->members_count }} {{ $cooperation->members_count === 1 ? 'Mitglied' : 'Mitglieder' }}</flux:badge>
                            @if ($kickback = $cooperation->kickbackLabel())
                                <flux:badge size="sm" color="amber" inset="top bottom">Kickback {{ $kickback }}</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                        <flux:dropdown align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen für {{ $cooperation->name }}" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square" :href="$editUrl">Bearbeiten</flux:menu.item>
                                <flux:menu.item :icon="$cooperation->is_active ? 'pause' : 'play'" wire:click="toggleActive({{ $cooperation->id }})">{{ $cooperation->is_active ? 'Deaktivieren' : 'Aktivieren' }}</flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $cooperation->id }})" wire:confirm="„{{ $cooperation->name }}“ löschen?">Löschen</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>

                <dl class="mt-3 flex flex-col gap-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <div class="flex gap-2"><dt class="text-zinc-400">Mitglieder:</dt><dd>{{ Cooperation::MEMBER_BILLING[$cooperation->member_billing] ?? $cooperation->member_billing }}</dd></div>
                    <div class="flex gap-2"><dt class="text-zinc-400">Rechnung:</dt><dd>{{ Cooperation::BILLING_INTERVALS[$cooperation->billing_interval] ?? $cooperation->billing_interval }}</dd></div>
                    @if ($cooperation->customer)
                        <div class="relative z-10 flex gap-2"><dt class="text-zinc-400">Account:</dt><dd class="min-w-0 truncate"><a href="{{ route('adminv2.customer-management.customers.edit', $cooperation->customer) }}" class="underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">{{ $cooperation->customer->company_name ?: $cooperation->customer->name }}</a></dd></div>
                    @endif
                </dl>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 px-5 py-16 text-center dark:border-zinc-700">
                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                    <flux:icon.building-office-2 class="size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $search !== '' ? 'Keine Kooperation zur Suche' : 'Noch keine Kooperationen' }}</p>
                    <p class="text-sm text-zinc-500">{{ $search !== '' ? 'Zur Suche passt kein Eintrag.' : 'Lege Kooperationen wie solamento oder RTK an. Danach lassen sich Kunden im Tab „Abrechnung“ für einen Zeitraum zuordnen.' }}</p>
                    @if ($search === '')
                        <flux:button size="sm" icon="plus" :href="route('adminv2.system.cooperations.create')" class="mt-1">Neue Kooperation</flux:button>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    @if ($cooperations->isNotEmpty())
        <x-adminv2.pagination :paginator="$cooperations" />
    @endif
</div>
