<?php

namespace App\Livewire\AdminV2\System\Products;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * System > Produkte: Produkte und Features des Travel Information Service mit
 * ihren Auspraegungen und den heute geltenden Standardpreisen.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Produkte')]
class Index extends Component
{
    use AuthorizesAdminV2, QueriesLists;

    #[Url(except: '')]
    public string $search = '';

    #[Computed]
    public function products(): Collection
    {
        $query = Product::query()->with(['versions.prices', 'requiredProduct:id,name', 'requiredVersion:id,name'])->withCount('features')->ordered();

        if (($term = trim($this->search)) !== '') {
            $this->whereEveryWord($query, $term, ['name', 'key', 'subtitle']);
        }

        return $query->get();
    }

    public function toggleActive(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_active' => ! $product->is_active]);

        unset($this->products);
        $this->dispatch('adminv2-toast', message: '„'.$product->name.'“ ist '.($product->is_active ? 'aktiv.' : 'inaktiv.'));
    }

    public function delete(int $productId): void
    {
        $product = Product::findOrFail($productId);

        if ($product->key && \App\Models\CustomerBillingInterval::where('module', $product->key)->exists()) {
            $this->dispatch('adminv2-toast', message: '„'.$product->name.'“ wird in Abo-Zeiträumen von Kunden verwendet und kann nicht gelöscht werden. Deaktiviere es stattdessen.');

            return;
        }

        $product->delete();

        unset($this->products);
        $this->dispatch('adminv2-toast', message: '„'.$product->name.'“ gelöscht.');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.products.index');
    }
}
