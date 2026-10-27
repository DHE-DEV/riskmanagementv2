@php
    use App\Models\Product;
    use App\Models\ProductVersionPrice;

    $product = $this->product;
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('adminv2.system.products.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Produkte
            </a>
            <flux:heading size="xl" level="1" class="mt-1">{{ $product ? $product->name : 'Neues Produkt' }}</flux:heading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" :href="route('adminv2.system.products.index')">Abbrechen</flux:button>
            <flux:button type="submit" variant="primary" icon="check">Speichern</flux:button>
        </div>
    </div>

    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle" heading="Bitte die markierten Angaben prüfen." />
    @endif

    <x-adminv2.card heading="Produkt">
        <div class="flex flex-col gap-5">
            <div class="grid items-start gap-5 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_8rem]">
                <div>
                    <flux:input wire:model="name" label="Name" placeholder="z. B. Travel Alert" maxlength="255" />
                    <flux:error name="name" />
                </div>
                <div>
                    <flux:input wire:model="key" label="Kürzel" placeholder="travel_alert" maxlength="50" class="font-mono" description="Für Abo und Abrechnung." />
                    <flux:error name="key" />
                </div>
                <div>
                    <flux:input wire:model="sortOrder" label="Sortierung" inputmode="numeric" />
                    <flux:error name="sortOrder" />
                </div>
            </div>
            <flux:input wire:model="subtitle" label="Untertitel" maxlength="255" placeholder="Kurzbeschreibung in einer Zeile" />
            <flux:textarea wire:model="description" label="Beschreibung" rows="3" />
            <div class="grid items-start gap-5 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Preis gilt</flux:label>
                    <flux:select wire:model="priceBasis">
                        @foreach (Product::PRICE_BASES as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="priceBasis" />
                </flux:field>
                <flux:field>
                    <flux:label>Nur buchbar zusammen mit</flux:label>
                    <flux:select wire:model="requiresProductId">
                        <flux:select.option value="">– keine Voraussetzung –</flux:select.option>
                        @foreach ($this->requirementOptions as $option)
                            <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="requiresProductId" />
                </flux:field>
            </div>
            <flux:switch wire:model="isActive" label="Aktiv" align="left" />
        </div>
    </x-adminv2.card>

    {{-- Auspraegungen mit Preisen --}}
    <x-adminv2.card heading="Ausprägungen und Preise" description="Eine Ausprägung „Allgemein“ oder z. B. Standard und Premium – jede mit eigenen Preisen ab einem Stichtag oder kostenlos. Beträge in Euro netto.">
        <x-slot:actions>
            <flux:button size="sm" icon="plus" wire:click="addVersion">Ausprägung</flux:button>
        </x-slot:actions>

        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($versions as $vIndex => $version)
                <section wire:key="version-{{ $version['uid'] }}" class="flex flex-col gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="flex items-start gap-2">
                        <div class="min-w-0 flex-1">
                            <flux:input wire:model.blur="versions.{{ $vIndex }}.name" aria-label="Name der Ausprägung" placeholder="z. B. Premium" />
                            <flux:error name="versions.{{ $vIndex }}.name" />
                        </div>
                        <flux:switch wire:model="versions.{{ $vIndex }}.is_active" label="Aktiv" align="left" class="mt-2" />
                        @if (count($versions) > 1)
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeVersion('{{ $version['uid'] }}')" wire:confirm="Ausprägung „{{ $version['name'] }}“ mit ihren Preisen entfernen? Gespeichert wird erst mit „Speichern“." aria-label="Ausprägung entfernen" />
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-medium uppercase tracking-wide text-zinc-500">Preise</span>
                        <flux:button size="xs" variant="ghost" icon="plus" wire:click="addPrice({{ $vIndex }})">Neuer Preis ab …</flux:button>
                    </div>

                    @forelse ($version['prices'] as $pIndex => $price)
                        <div wire:key="price-{{ $version['uid'] }}-{{ $price['id'] ?? $pIndex }}" class="flex flex-col gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                            <div class="flex flex-wrap items-end gap-3">
                                <div class="w-40">
                                    <flux:input type="date" wire:model="versions.{{ $vIndex }}.prices.{{ $pIndex }}.valid_from" label="Gültig ab" size="sm" />
                                </div>
                                <flux:checkbox wire:model.live="versions.{{ $vIndex }}.prices.{{ $pIndex }}.is_free" label="Kostenlos" class="mb-1.5" />
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="removePrice({{ $vIndex }}, {{ $pIndex }})" class="mb-1 ms-auto" aria-label="Preis entfernen" />
                            </div>
                            <flux:error name="versions.{{ $vIndex }}.prices.{{ $pIndex }}.valid_from" />
                            @unless ($price['is_free'])
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    @foreach (ProductVersionPrice::AMOUNTS as $column => $label)
                                        <div>
                                            <flux:input wire:model="versions.{{ $vIndex }}.prices.{{ $pIndex }}.{{ $column }}" :label="'€ '.$label" size="sm" inputmode="decimal" />
                                            <flux:error name="versions.{{ $vIndex }}.prices.{{ $pIndex }}.{{ $column }}" />
                                        </div>
                                    @endforeach
                                </div>
                            @endunless
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">Noch kein Preis – „Neuer Preis ab …“ legt einen an.</p>
                    @endforelse
                </section>
            @endforeach
        </div>
    </x-adminv2.card>

    {{-- Leistungsumfang je Auspraegung --}}
    <x-adminv2.card heading="Leistungsumfang" description="Was in welcher Ausprägung enthalten ist – Grundlage der Gegenüberstellung. Optional mit Hinweis, z. B. „bis 5 Nutzer“." flush>
        <x-slot:actions>
            <flux:button size="sm" icon="plus" wire:click="addFeature">Leistung</flux:button>
        </x-slot:actions>

        @if ($features === [])
            <p class="px-5 py-6 text-sm text-zinc-500">Noch keine Leistungen erfasst.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800">
                            <th class="px-5 py-2 font-medium">Leistung</th>
                            @foreach ($versions as $version)
                                <th class="px-3 py-2 text-center font-medium" wire:key="head-{{ $version['uid'] }}">{{ $version['name'] ?: '–' }}</th>
                            @endforeach
                            <th class="w-24 px-3 py-2"><span class="sr-only">Aktionen</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($features as $fIndex => $feature)
                            <tr wire:key="feature-{{ $feature['uid'] }}" class="align-top">
                                <td class="px-5 py-3">
                                    <flux:input wire:model="features.{{ $fIndex }}.name" size="sm" placeholder="z. B. Einreisebestimmungen als PDF" aria-label="Leistung" />
                                    <flux:error name="features.{{ $fIndex }}.name" />
                                    <flux:input wire:model="features.{{ $fIndex }}.description" size="sm" placeholder="Erklärung (optional)" class="mt-1.5" aria-label="Erklärung" />
                                </td>
                                @foreach ($versions as $version)
                                    <td class="px-3 py-3" wire:key="cell-{{ $feature['uid'] }}-{{ $version['uid'] }}">
                                        <div class="flex flex-col items-center gap-1.5">
                                            <flux:checkbox wire:model="features.{{ $fIndex }}.values.{{ $version['uid'] }}.included" aria-label="{{ $feature['name'] ?: 'Leistung' }} in {{ $version['name'] }} enthalten" />
                                            <flux:input wire:model="features.{{ $fIndex }}.values.{{ $version['uid'] }}.note" size="xs" placeholder="Hinweis" class="w-28" aria-label="Hinweis für {{ $version['name'] }}" />
                                        </div>
                                    </td>
                                @endforeach
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-end gap-0.5">
                                        <flux:button size="xs" variant="ghost" icon="chevron-up" wire:click="moveFeature({{ $fIndex }}, -1)" :disabled="$fIndex === 0" aria-label="Nach oben" />
                                        <flux:button size="xs" variant="ghost" icon="chevron-down" wire:click="moveFeature({{ $fIndex }}, 1)" :disabled="$fIndex === count($features) - 1" aria-label="Nach unten" />
                                        <flux:button size="xs" variant="ghost" icon="trash" wire:click="removeFeature('{{ $feature['uid'] }}')" aria-label="Leistung entfernen" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-adminv2.card>
</form>
