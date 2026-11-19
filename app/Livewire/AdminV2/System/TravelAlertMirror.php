<?php

namespace App\Livewire\AdminV2\System;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Customer;
use App\Models\TravelAlertPdsAccount;
use App\Models\TravelAlertPdsTrip;
use App\Models\TravelDetail\PdsGlobalSyncState;
use App\Services\TravelDetail\TravelAlertTripMirror;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * System > Travel-Alert-Spiegel: welche pds-Konten lokal gespiegelt werden (TravelAlertTripMirror),
 * mit Kunden, Anzahl Reisen, letztem Aufruf und Stand von Vollabruf und Delta-Feed.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Travel-Alert-Spiegel')]
class TravelAlertMirror extends Component
{
    use AuthorizesAdminV2;

    /** Nach so vielen Stunden ohne Vollabruf gilt ein Konto als veraltet (nächtlicher Abgleich + Puffer). */
    public const STALE_AFTER_HOURS = 26;

    /** Delta-Feed gilt als hängend, wenn der letzte Lauf länger her ist. */
    public const DELTA_STALE_AFTER_MINUTES = 10;

    public string $search = '';

    /** @return Collection<int, TravelAlertPdsAccount> */
    #[Computed]
    public function accounts(): Collection
    {
        $accounts = TravelAlertPdsAccount::query()->orderByDesc('trips_count')->orderBy('pds_account_id')->get();
        $term = mb_strtolower(trim($this->search));

        if ($term === '') {
            return $accounts;
        }

        return $accounts->filter(fn (TravelAlertPdsAccount $account) => str_contains((string) $account->pds_account_id, $term)
            || ($this->customers->get($account->pds_account_id) ?? collect())->contains(
                fn (Customer $customer) => str_contains(mb_strtolower($this->customerLabel($customer).' '.$customer->email), $term)
            ))->values();
    }

    /** @return Collection<int, Collection<int, Customer>> Kunden je pds-Konto */
    #[Computed]
    public function customers(): Collection
    {
        return Customer::query()
            ->whereIn('pds_account_id', TravelAlertPdsAccount::query()->select('pds_account_id'))
            ->orderBy('id')
            ->get(['id', 'pds_account_id', 'company_name', 'name', 'email', 'assign_to'])
            ->groupBy('pds_account_id');
    }

    /** @return array<int, int> gespeicherte Reisen je Konto */
    #[Computed]
    public function storedTrips(): array
    {
        return TravelAlertPdsTrip::query()->selectRaw('pds_account_id, count(*) as trips')
            ->groupBy('pds_account_id')->pluck('trips', 'pds_account_id')->map(fn ($count) => (int) $count)->all();
    }

    #[Computed]
    public function delta(): PdsGlobalSyncState
    {
        return PdsGlobalSyncState::forKey(TravelAlertTripMirror::SYNC_STATE_KEY);
    }

    /** @return array{synced: int, waiting: int, stale: int, trips: int} */
    #[Computed]
    public function summary(): array
    {
        $all = TravelAlertPdsAccount::query()->get();

        return [
            'synced' => $all->whereNotNull('full_synced_at')->count(),
            'waiting' => $all->whereNull('full_synced_at')->count(),
            'stale' => $all->filter(fn (TravelAlertPdsAccount $account) => $this->status($account) === 'stale')->count(),
            'trips' => array_sum($this->storedTrips),
        ];
    }

    /**
     * @return 'waiting'|'stale'|'active'
     */
    public function status(TravelAlertPdsAccount $account): string
    {
        return match (true) {
            $account->full_synced_at === null => 'waiting',
            $account->full_synced_at->lt(now()->subHours(self::STALE_AFTER_HOURS)) => 'stale',
            default => 'active',
        };
    }

    public function deltaIsStale(): bool
    {
        return $this->summary['synced'] > 0
            && (! $this->delta->last_run_at || $this->delta->last_run_at->lt(now()->subMinutes(self::DELTA_STALE_AFTER_MINUTES)));
    }

    public function customerLabel(Customer $customer): string
    {
        return $customer->company_name ?: ($customer->name ?: $customer->email ?: 'Kunde #'.$customer->id);
    }

    public function render()
    {
        return view('livewire.admin-v2.system.travel-alert-mirror');
    }
}
