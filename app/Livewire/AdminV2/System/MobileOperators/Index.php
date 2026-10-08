<?php

namespace App\Livewire\AdminV2\System\MobileOperators;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\MobileOperator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * System > Mobilfunkanbieter: die Anbieter, die sich Laendern zuordnen
 * lassen – als Karten mit Suche.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Mobilfunkanbieter')]
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
    public function operators(): LengthAwarePaginator
    {
        $query = MobileOperator::query()->withCount('countries')->ordered();

        if (($term = trim($this->search)) !== '') {
            $this->whereEveryWord($query, $term, ['name']);
        }

        return $this->paginateWithinRange($query, self::PER_PAGE);
    }

    public function toggleActive(int $operatorId): void
    {
        $operator = MobileOperator::findOrFail($operatorId);
        $operator->update(['is_active' => ! $operator->is_active]);

        unset($this->operators);
        $this->dispatch('adminv2-toast', message: $operator->is_active ? '„'.$operator->name.'“ ist aktiv.' : '„'.$operator->name.'“ ist inaktiv und wird bei Ländern nicht mehr angeboten.');
    }

    public function delete(int $operatorId): void
    {
        $operator = MobileOperator::findOrFail($operatorId);
        $operator->delete();

        unset($this->operators);
        $this->dispatch('adminv2-toast', message: '„'.$operator->name.'“ gelöscht.');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.mobile-operators.index');
    }
}
