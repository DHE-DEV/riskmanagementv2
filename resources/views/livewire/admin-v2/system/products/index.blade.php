@php
    $products = $this->products;
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Produkte</flux:heading>
            <flux:subheading>System · Produkte und Features des Travel Information Service mit Ausprägungen, Standardpreisen und Leistungsumfang.</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button icon="table-cells" :href="route('adminv2.system.products.compare')">Gegenüberstellung</flux:button>
            <flux:button variant="primary" icon="plus" :href="route('adminv2.system.products.create')">Neues Produkt</flux:button>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 max-w-md flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Produkt suchen …" aria-label="Suche" clearable />
        </div>
        <span class="text-sm text-zinc-500 tabular-nums">{{ $products->count() }} {{ $products->count() === 1 ? 'Produkt' : 'Produkte' }}</span>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($products as $product)
            @php
                $editUrl = route('adminv2.system.products.edit', $product);
            @endphp
            <article wire:key="product-{{ $product->id }}" @class([
                'group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                'opacity-70' => ! $product->is_active,
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                            <a href="{{ $editUrl }}" class="after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $product->name }}</a>
                        </h2>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            @if ($product->key)
                                <flux:badge size="sm" color="zinc" inset="top bottom" class="font-mono">{{ $product->key }}</flux:badge>
                            @endif
                            @unless ($product->is_active)
                                <flux:badge size="sm" color="zinc" inset="top bottom">Inaktiv</flux:badge>
                            @endunless
                            @if ($product->features_count)
                                <flux:badge size="sm" color="sky" inset="top bottom">{{ $product->features_count }} Leistungen</flux:badge>
                            @endif
                            @if ($product->requiredProduct)
                                <flux:badge size="sm" color="amber" inset="top bottom">nur mit {{ $product->requirementLabel() }}</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                        <flux:dropdown align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen für {{ $product->name }}" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square" :href="$editUrl">Bearbeiten</flux:menu.item>
                                <flux:menu.item :icon="$product->is_active ? 'pause' : 'play'" wire:click="toggleActive({{ $product->id }})">{{ $product->is_active ? 'Deaktivieren' : 'Aktivieren' }}</flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $product->id }})" wire:confirm="„{{ $product->name }}“ mit allen Ausprägungen, Preisen und Leistungen löschen?">Löschen</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>

                <ul class="mt-3 flex flex-col gap-1 text-sm">
                    @foreach ($product->versions as $version)
                        @php
                            $price = $version->priceOn();
                        @endphp
                        <li class="flex items-baseline justify-between gap-3">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $version->name }}</span>
                            <span @class(['text-right tabular-nums', 'font-medium text-zinc-900 dark:text-white' => $price && ! $price->is_free, 'text-green-700 dark:text-green-400' => $price?->is_free, 'text-zinc-400' => ! $price])>{{ $price?->label() ?? 'Preis offen' }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-auto pt-3 text-xs text-zinc-400">{{ \App\Models\Product::PRICE_BASES[$product->price_basis] ?? '' }}</p>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 px-5 py-16 text-center dark:border-zinc-700">
                <p class="text-sm text-zinc-500">{{ $search !== '' ? 'Zur Suche passt kein Produkt.' : 'Noch keine Produkte.' }}</p>
            </div>
        @endforelse
    </div>
</div>
