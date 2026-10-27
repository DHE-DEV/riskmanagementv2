@php
    $products = $this->products;
    $day = $date !== '' ? $date : today()->toDateString();
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4 print:hidden">
        <div class="min-w-0">
            <a href="{{ route('adminv2.system.products.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Produkte
            </a>
            <flux:heading size="xl" level="1" class="mt-1">Gegenüberstellung</flux:heading>
            <flux:subheading>Was in welcher Ausprägung enthalten ist und was sie kostet.</flux:subheading>
        </div>
        <flux:button icon="printer" x-on:click="window.print()">Drucken</flux:button>
    </div>

    <div class="flex flex-wrap items-end gap-3 print:hidden">
        <div class="w-72">
            <flux:select wire:model.live="product" label="Produkt">
                <flux:select.option value="">Alle Produkte</flux:select.option>
                @foreach ($this->productOptions as $option)
                    <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-44">
            <flux:input type="date" wire:model.live="date" label="Preise am" />
        </div>
    </div>

    <p class="hidden text-sm text-zinc-600 print:block">Preise am {{ \Illuminate\Support\Carbon::parse($day)->format('d.m.Y') }}, in Euro netto.</p>

    @forelse ($products as $item)
        <section wire:key="compare-{{ $item->id }}" class="break-inside-avoid overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <header class="flex flex-wrap items-baseline justify-between gap-2 border-b border-zinc-100 px-5 py-3 dark:border-zinc-800">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ $item->name }}</h2>
                    @if ($item->subtitle)
                        <p class="text-sm text-zinc-500">{{ $item->subtitle }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-1.5 text-xs text-zinc-500">
                    <span>Preis {{ \App\Models\Product::PRICE_BASES[$item->price_basis] ?? '' }}</span>
                    @if ($item->requiredProduct)
                        <flux:badge size="sm" color="amber">nur mit {{ $item->requiredProduct->name }}</flux:badge>
                    @endif
                    <a href="{{ route('adminv2.system.products.edit', $item) }}" class="ms-2 underline underline-offset-2 print:hidden">Bearbeiten</a>
                </div>
            </header>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 bg-zinc-50 text-left dark:border-zinc-800 dark:bg-zinc-900">
                            <th class="px-5 py-2 font-medium text-zinc-500">Leistung</th>
                            @foreach ($item->versions as $version)
                                <th class="px-4 py-2 text-center font-semibold text-zinc-900 dark:text-white">{{ $version->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <tr>
                            <th scope="row" class="px-5 py-2 text-left font-medium text-zinc-700 dark:text-zinc-300">Preis</th>
                            @foreach ($item->versions as $version)
                                @php
                                    $price = $version->priceOn($day);
                                @endphp
                                <td @class(['px-4 py-2 text-center tabular-nums', 'font-semibold text-green-700 dark:text-green-400' => $price?->is_free, 'font-semibold text-zinc-900 dark:text-white' => $price && ! $price->is_free, 'text-zinc-400' => ! $price])>
                                    {!! $price ? str_replace(' · ', '<br>', e($price->label())) : 'offen' !!}
                                </td>
                            @endforeach
                        </tr>
                        @forelse ($item->features as $feature)
                            <tr>
                                <th scope="row" class="px-5 py-2 text-left font-normal text-zinc-700 dark:text-zinc-300">
                                    {{ $feature->name }}
                                    @if ($feature->description)
                                        <span class="block text-xs text-zinc-500">{{ $feature->description }}</span>
                                    @endif
                                </th>
                                @foreach ($item->versions as $version)
                                    @php
                                        $value = $feature->values->firstWhere('product_version_id', $version->id);
                                    @endphp
                                    <td class="px-4 py-2 text-center">
                                        @if ($value?->included)
                                            <flux:icon.check variant="mini" class="mx-auto text-green-600" aria-label="enthalten" />
                                        @else
                                            <flux:icon.minus variant="mini" class="mx-auto text-zinc-300" aria-label="nicht enthalten" />
                                        @endif
                                        @if ($value?->note)
                                            <span class="block text-xs text-zinc-500">{{ $value->note }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $item->versions->count() + 1 }}" class="px-5 py-3 text-sm text-zinc-400">Noch kein Leistungsumfang erfasst.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <p class="text-sm text-zinc-500">Keine aktiven Produkte.</p>
    @endforelse
</div>
