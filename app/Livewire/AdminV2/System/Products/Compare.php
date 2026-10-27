<?php

namespace App\Livewire\AdminV2\System\Products;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * System > Produkte > Gegenueberstellung: je Produkt eine Tabelle mit den
 * Auspraegungen als Spalten – Preis zum Stichtag und was enthalten ist.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Gegenüberstellung der Produkte')]
class Compare extends Component
{
    use AuthorizesAdminV2;

    /** Nur dieses Produkt (leer = alle aktiven) */
    #[Url(except: '')]
    public string $product = '';

    /** Preise zu diesem Tag */
    #[Url(except: '')]
    public string $date = '';

    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->when($this->product !== '', fn ($q) => $q->whereKey((int) $this->product))
            ->with([
                'versions' => fn ($q) => $q->where('is_active', true),
                'versions.prices',
                'features.values',
                'requiredProduct:id,name',
            ])
            ->ordered()
            ->get();
    }

    #[Computed]
    public function productOptions(): Collection
    {
        return Product::query()->where('is_active', true)->ordered()->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.admin-v2.system.products.compare');
    }
}
