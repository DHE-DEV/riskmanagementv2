<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Jobs\GeocodeSubAccounts;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;

/**
 * Unteraccounts: Kunden, die diesem Kunden als Agentur zugeordnet sind
 * (customers.assign_to) – z. B. per Import aus pds-admin.
 */
trait ListsCustomerSubAccounts
{
    /** Unteraccounts je Seite */
    public const SUB_ACCOUNTS_PER_PAGE = 20;

    public string $subAccountSearch = '';

    /** Hinweis, was die Unteraccounts sind (z. B. eigenstaendige Bueros einer Kette). */
    public string $subAccountsNote = '';

    public function saveSubAccountsNote(): void
    {
        $this->validateOnly('subAccountsNote', ['subAccountsNote' => ['nullable', 'string', 'max:2000']], [
            'subAccountsNote.max' => 'Der Hinweis darf höchstens 2.000 Zeichen lang sein.',
        ]);

        $note = trim($this->subAccountsNote);
        $this->customer->forceFill(['sub_accounts_note' => $note === '' ? null : $note])->save();
        $this->subAccountsNote = $note;

        $this->dispatch('adminv2-toast', message: 'Hinweis zu den Unteraccounts gespeichert.');
    }

    public function updatedSubAccountSearch(): void
    {
        $this->resetPage('subAccountsPage');
    }

    /**
     * Stand der Koordinaten fuer die Karte "Filialen & Standorte".
     *
     * @return array{total: int, located: int, pending: int, missing: int}
     */
    #[Computed]
    public function subAccountGeoStats(): array
    {
        $query = fn () => Customer::query()->where('assign_to', $this->customerId);
        $total = $query()->count();
        $located = $query()->whereNotNull('company_latitude')->count();
        $pending = $query()->whereNull('company_geocoded_at')->count();

        return ['total' => $total, 'located' => $located, 'pending' => $pending, 'missing' => $total - $located - $pending];
    }

    /**
     * Fehlende Koordinaten im Hintergrund ermitteln; mit $retry auch die,
     * fuer die bisher nichts gefunden wurde.
     */
    public function geocodeSubAccounts(bool $retry = false): void
    {
        if ($retry) {
            Customer::query()->where('assign_to', $this->customerId)
                ->whereNotNull('company_geocoded_at')->whereNull('company_latitude')
                ->update(['company_geocoded_at' => null]);
        }

        if (GeocodeSubAccounts::pending($this->customerId)->exists()) {
            GeocodeSubAccounts::dispatch($this->customerId);
        }

        unset($this->subAccountGeoStats);
        $this->dispatch('adminv2-toast', message: 'Koordinaten werden im Hintergrund ermittelt (etwa eine Adresse pro Sekunde).');
    }

    #[Computed]
    public function subAccounts(): LengthAwarePaginator
    {
        $query = $this->customer->assignedCustomers()->withTrashed();

        if (($term = trim($this->subAccountSearch)) !== '') {
            $this->whereEveryWord($query, $term, ['app_code', 'company_name', 'email', 'company_postal_code', 'company_city', 'pds_account_id']);
        }

        return $this->paginateWithinRange($query->orderBy('company_name')->orderBy('id'), self::SUB_ACCOUNTS_PER_PAGE, 'subAccountsPage');
    }
}
