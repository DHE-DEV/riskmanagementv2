@php
    $rows = $this->rows;
    $labels = $this->featureLabels();
    $openCount = $this->openCount;
    $searching = trim($search) !== '';
    $selectedCount = count($selected);
    $pageIds = $rows->getCollection()->map(fn ($row) => (string) $row->id)->all();
    $pageSelected = $pageIds !== [] && array_diff($pageIds, $selected) === [];
    $link = 'relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white';
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.customer-management.list-header section="feature-preauthorizations" create-label="Einzeln vormerken">
        @if ($openCount > 0)
            <flux:badge color="amber" class="me-1" title="Noch nicht eingelöste Vormerkungen">{{ number_format($openCount, 0, ',', '.') }} offen</flux:badge>
        @endif
        <flux:modal.trigger name="preauthorization-apply-pending">
            <flux:button icon="bolt">Offene anwenden</flux:button>
        </flux:modal.trigger>
        <flux:modal.trigger name="preauthorization-import">
            <flux:button icon="arrow-up-tray">IDs importieren</flux:button>
        </flux:modal.trigger>
    </x-adminv2.customer-management.list-header>

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="PDS Account-ID suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-56">
            <flux:select wire:model.live="feature" aria-label="Feature">
                <flux:select.option value="">Alle Features</flux:select.option>
                @foreach ($labels as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-56">
            <flux:select wire:model.live="enabled" aria-label="Freischalten">
                <flux:select.option value="">Freischaltungen und Sperren</flux:select.option>
                <flux:select.option value="yes">Nur Freischaltungen</flux:select.option>
                <flux:select.option value="no">Nur Sperren</flux:select.option>
            </flux:select>
        </div>
        <div class="w-52">
            {{-- Eine Suche gilt fuer alle Vormerkungen; die Auswahl ruht solange. --}}
            <flux:select wire:model.live="status" aria-label="Eingelöst" :disabled="$searching" :title="$searching ? 'Die Suche berücksichtigt offene und eingelöste Vormerkungen.' : null">
                <flux:select.option value="open">Nur offene</flux:select.option>
                <flux:select.option value="applied">Nur eingelöste</flux:select.option>
                <flux:select.option value="all">Offene und eingelöste</flux:select.option>
            </flux:select>
        </div>
        <flux:checkbox wire:model.live="withoutAccount" label="Ohne Kundenkonto" />
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Vormerkung', 'Vormerkungen']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            @if ($this->hasFilters())
                <x-adminv2.master-data.empty filtered noun="Vormerkungen" />
            @else
                <div class="flex flex-col items-center gap-3 px-4 py-14 text-center">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
                        <flux:icon.bookmark class="size-6" />
                    </span>
                    @if ($this->appliedCount > 0)
                        {{-- Nichts mehr offen: die eingeloesten stehen beim Oeffnen nicht in der Liste. --}}
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine offenen Vormerkungen</p>
                            <p class="mt-1 text-sm text-zinc-500">
                                {{ $this->appliedCount === 1 ? 'Die einzige Vormerkung ist bereits eingelöst.' : 'Alle '.number_format($this->appliedCount, 0, ',', '.').' Vormerkungen sind bereits eingelöst.' }}
                            </p>
                        </div>
                        <flux:button size="sm" icon="eye" wire:click="$set('status', 'applied')">Eingelöste anzeigen</flux:button>
                    @else
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine Vormerkungen</p>
                            <p class="mt-1 text-sm text-zinc-500">Über „IDs importieren“ lassen sich ganze Account-Listen auf einmal vormerken.</p>
                        </div>
                    @endif
                </div>
            @endif
        </x-adminv2.card>
    @else
        {{-- Auswahl: alle der Seite anhaken, Aktionen fuer die Auswahl --}}
        <div class="flex min-h-9 flex-wrap items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
            <flux:button size="sm" variant="ghost" :icon="$pageSelected ? 'minus-circle' : 'check-circle'" wire:click="togglePage">
                {{ $pageSelected ? 'Seite abwählen' : 'Alle auf dieser Seite auswählen' }}
            </flux:button>

            @if ($selectedCount > 0)
                <span class="tabular-nums">{{ $selectedCount }} ausgewählt</span>
                <flux:modal.trigger name="preauthorization-apply-selected">
                    <flux:button size="sm" icon="bolt">Auswahl anwenden</flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="preauthorization-delete-selected">
                    <flux:button size="sm" variant="danger" icon="trash">Auswahl löschen</flux:button>
                </flux:modal.trigger>
                <flux:button size="sm" variant="ghost" wire:click="clearSelection">Auswahl aufheben</flux:button>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, feature, enabled, status, withoutAccount, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage, apply, applySelected, deleteSelected, applyPending, import">
            @foreach ($rows as $row)
                @php $editUrl = route('adminv2.customer-management.feature-preauthorizations.edit', $row->id); @endphp
                <article
                    wire:key="preauthorization-{{ $row->id }}"
                    class="group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <input type="checkbox" wire:model.live="selected" value="{{ $row->id }}" aria-label="Vormerkung für Account {{ $row->pds_account_id }} auswählen" class="relative z-10 mt-1 size-4 shrink-0 rounded accent-[var(--color-accent)]" />
                            <div class="min-w-0">
                                <div class="flex items-center gap-1">
                                    <h2 class="min-w-0 text-base font-semibold leading-snug text-zinc-900 tabular-nums dark:text-white">
                                        <a href="{{ $editUrl }}" class="after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $row->pds_account_id }}</a>
                                    </h2>
                                    {{-- Account-ID in die Zwischenablage --}}
                                    <button
                                        type="button"
                                        x-data="{ copied: false }"
                                        x-on:click="navigator.clipboard.writeText(@js((string) $row->pds_account_id)); copied = true; setTimeout(() => copied = false, 1500)"
                                        class="relative z-10 rounded p-0.5 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200"
                                        aria-label="Account-ID kopieren"
                                        title="Account-ID kopieren"
                                    >
                                        <flux:icon.clipboard variant="micro" x-show="! copied" />
                                        <flux:icon.check variant="micro" x-show="copied" x-cloak class="text-green-600" />
                                    </button>
                                </div>
                                <p class="mt-0.5 text-xs text-zinc-500">PDS Account-ID</p>
                            </div>
                        </div>

                        <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen für Account {{ $row->pds_account_id }}" />

                                <flux:menu>
                                    {{-- Ohne Konto gibt es nichts anzuwenden – dann greift der Login. --}}
                                    @if ($row->customers_count > 0)
                                        <flux:menu.item icon="bolt" wire:click="apply({{ $row->id }})" wire:confirm="Vormerkung jetzt anwenden? Setzt das Feature für alle bestehenden Konten dieser Account-ID. Bereits im Kunden gesetzte Werte bleiben unangetastet.">Jetzt anwenden</flux:menu.item>
                                    @endif
                                    <flux:menu.item icon="pencil-square" :href="$editUrl">Bearbeiten</flux:menu.item>
                                    <flux:menu.item icon="arrow-top-right-on-square" :href="$editUrl" target="_blank">In neuem Tab öffnen</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $labels[$row->feature_key] ?? $row->feature_key }}</flux:badge>
                        @if ($row->enabled)
                            <flux:badge size="sm" color="green" icon="check-circle" inset="top bottom">Freischalten</flux:badge>
                        @else
                            <flux:badge size="sm" color="red" icon="no-symbol" inset="top bottom">Sperren</flux:badge>
                        @endif
                        @if ($row->applied_at)
                            <flux:badge size="sm" color="green" inset="top bottom">eingelöst</flux:badge>
                        @else
                            <flux:badge size="sm" color="amber" inset="top bottom">offen</flux:badge>
                        @endif
                    </div>

                    <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                        <x-adminv2.master-data.card-row icon="users" label="Kundenkonten mit dieser Account-ID">
                            @if ($row->customers_count > 0)
                                <span class="font-medium text-green-700 dark:text-green-400">{{ $row->customers_count }} {{ $row->customers_count === 1 ? 'Konto' : 'Konten' }}:</span>
                                @foreach ($row->customers->take(3) as $customer)
                                    <a href="{{ route('adminv2.customer-management.customers.edit', $customer->id) }}" class="{{ $link }}">{{ $customer->company_name ?: ($customer->name ?: '#'.$customer->id) }}</a>@if (! $loop->last), @endif
                                @endforeach
                                @if ($row->customers_count > 3) <span class="text-zinc-500">und {{ $row->customers_count - 3 }} weitere</span> @endif
                            @else
                                Konten: noch keins
                            @endif
                        </x-adminv2.master-data.card-row>
                        <x-adminv2.master-data.card-row icon="bolt" label="Eingelöst">
                            @if ($row->applied_at)
                                <span class="tabular-nums">Eingelöst {{ $row->applied_at->format('d.m.Y H:i') }}</span>
                            @else
                                <span class="text-amber-700 dark:text-amber-400">Eingelöst: offen</span>
                            @endif
                        </x-adminv2.master-data.card-row>
                        <x-adminv2.master-data.card-row icon="chat-bubble-left" label="Notiz">
                            <span title="{{ $row->note }}">{{ filled($row->note) ? \Illuminate\Support\Str::limit($row->note, 40) : '–' }}</span>
                        </x-adminv2.master-data.card-row>
                    </dl>

                    <div class="mt-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pt-4 text-xs text-zinc-500">
                        <span class="tabular-nums">angelegt {{ $row->created_at?->format('d.m.Y H:i') ?? '–' }}</span>
                    </div>
                </article>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    {{-- Import einer ID-Liste --}}
    <flux:modal name="preauthorization-import" class="md:w-[34rem]">
        <form wire:submit="import" class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Account-IDs vormerken</flux:heading>
                <flux:text class="mt-1">Eine Liste von PDS Account-IDs auf einmal vormerken. Bereits vorgemerkte IDs werden aktualisiert, nicht doppelt angelegt.</flux:text>
            </div>

            <flux:textarea
                wire:model="importIds"
                label="Account-IDs"
                description="Eine ID pro Zeile oder durch Komma getrennt. Alles, was keine Zahl ist, wird ignoriert."
                rows="10"
                :placeholder="implode(PHP_EOL, ['25893', '25427', '23854'])"
            />

            <flux:select wire:model="importFeatureKey" label="Feature">
                @foreach ($labels as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:switch wire:model="importEnabled" label="Freischalten" description="Aus = Feature für diese Accounts sperren statt freischalten." align="left" />

            <flux:input wire:model="importNote" label="Notiz" placeholder="z. B. Travel-Alert-Liste August 2026" maxlength="255" />

            <flux:switch wire:model="importApplyNow" label="Auf bestehende Konten sofort anwenden" description="Aus = greift erst beim nächsten Login. Bestehende Werte bleiben in beiden Fällen unangetastet." align="left" />

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="arrow-up-tray" wire:loading.attr="disabled" wire:target="import">Vormerken</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Rueckfrage: alle offenen anwenden --}}
    <flux:modal name="preauthorization-apply-pending" class="md:w-[32rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Offene Vormerkungen anwenden</flux:heading>
                <flux:text class="mt-2">Wendet alle Vormerkungen auf Konten an, die es bereits gibt. Bereits gesetzte Werte bleiben unangetastet.</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" icon="bolt" wire:click="applyPending" wire:loading.attr="disabled" wire:target="applyPending">Anwenden</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Rueckfrage: Auswahl anwenden --}}
    <flux:modal name="preauthorization-apply-selected" class="md:w-[32rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Auswahl anwenden</flux:heading>
                <flux:text class="mt-2">Setzt die Features für alle bestehenden Konten der {{ $selectedCount }} ausgewählten Account-IDs. Bereits gesetzte Werte bleiben unangetastet.</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" icon="bolt" wire:click="applySelected" wire:loading.attr="disabled" wire:target="applySelected">Anwenden</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Rueckfrage: Auswahl loeschen --}}
    <flux:modal name="preauthorization-delete-selected" class="md:w-[32rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">{{ $selectedCount === 1 ? 'Vormerkung löschen?' : $selectedCount.' Vormerkungen löschen?' }}</flux:heading>
                <flux:text class="mt-2">Löscht nur die Vormerkungen. Bereits erteilte Freischaltungen bleiben bestehen.</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="deleteSelected" wire:loading.attr="disabled" wire:target="deleteSelected">Löschen</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
