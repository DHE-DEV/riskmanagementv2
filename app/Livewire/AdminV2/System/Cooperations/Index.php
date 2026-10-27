<?php

namespace App\Livewire\AdminV2\System\Cooperations;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\Cooperation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * System > Kooperationen: Reisebuero-Kooperationen, ueber die Kunden fuer
 * bestimmte Zeitraeume abgerechnet werden – als Karten mit Suche.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Kooperationen')]
class Index extends Component
{
    use AuthorizesAdminV2, QueriesLists, WithPagination;

    public const PER_PAGE = 30;

    #[Url(except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function cooperations(): LengthAwarePaginator
    {
        $query = Cooperation::query()->with('customer:id,company_name,name,app_code')
            ->withCount(['currentPeriods as members_count', 'billingPeriods'])
            ->ordered();

        if (($term = trim($this->search)) !== '') {
            $this->whereEveryWord($query, $term, ['name', 'contact_email']);
        }

        return $this->paginateWithinRange($query, self::PER_PAGE);
    }

    public function toggleActive(int $cooperationId): void
    {
        $cooperation = Cooperation::findOrFail($cooperationId);
        $cooperation->update(['is_active' => ! $cooperation->is_active]);

        unset($this->cooperations);
        $this->dispatch('adminv2-toast', message: '„'.$cooperation->name.'“ ist '.($cooperation->is_active ? 'aktiv.' : 'inaktiv.'));
    }

    public function delete(int $cooperationId): void
    {
        $cooperation = Cooperation::withCount('billingPeriods')->findOrFail($cooperationId);

        if ($cooperation->billing_periods_count > 0) {
            $this->dispatch('adminv2-toast', message: '„'.$cooperation->name.'“ hat '.$cooperation->billing_periods_count.' Abrechnungszeiträume und kann nicht gelöscht werden. Deaktiviere sie stattdessen.');

            return;
        }

        $cooperation->delete();

        unset($this->cooperations);
        $this->dispatch('adminv2-toast', message: '„'.$cooperation->name.'“ gelöscht.');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.cooperations.index');
    }
}
