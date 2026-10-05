<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Zugriff auf andere Accounts: Konten, mit denen dieser Kunde zusaetzlich zu
 * seinem eigenen arbeiten darf (Tabelle customer_access).
 */
trait ManagesCustomerAccess
{
    public string $accessSearch = '';

    /** Suchbegriff fuer das Hinzufuegen eines weiteren Accounts. */
    public string $accessCandidateSearch = '';

    /** @var array<int, string> IDs der angehakten Accounts */
    public array $selectedAccess = [];

    #[Computed]
    public function accessibleAccounts(): Collection
    {
        $query = $this->customer->accessibleAccounts();

        if (($term = trim($this->accessSearch)) !== '') {
            $this->whereEveryWord($query, $term, ['customers.app_code', 'customers.company_name', 'customers.email']);
        }

        return $query->orderByPivot('created_at', 'desc')->orderBy('customers.id')->get();
    }

    /**
     * Treffer fuer das Hinzufuegen: andere Kunden, die noch nicht berechtigt sind.
     */
    #[Computed]
    public function accessCandidates(): Collection
    {
        if (mb_strlen($term = trim($this->accessCandidateSearch)) < 2) {
            return collect();
        }

        $query = Customer::query()
            ->whereKeyNot($this->customer->id)
            ->whereNotIn('id', $this->customer->accessibleAccounts()->select('customers.id'));

        $this->whereEveryWord($query, $term, ['company_name', 'email', 'app_code']);

        return $query
            ->orderBy('company_name')
            ->orderBy('email')
            ->limit(10)
            ->get(['id', 'company_name', 'email', 'app_code']);
    }

    public function attachAccess(int $accountId): void
    {
        // Nicht der Kunde selbst, keine geloeschten Kunden.
        $account = Customer::query()->whereKeyNot($this->customer->id)->findOrFail($accountId);

        $this->customer->accessibleAccounts()->syncWithoutDetaching([$account->id]);

        $this->accessCandidateSearch = '';
        unset($this->accessibleAccounts, $this->accessCandidates);

        $this->dispatch('adminv2-toast', message: 'Zugriff auf „'.self::accountLabel($account).'“ hinzugefügt.');
    }

    public function detachAccess(int $accountId): void
    {
        $this->customer->accessibleAccounts()->detach($accountId);

        $this->selectedAccess = array_values(array_diff($this->selectedAccess, [(string) $accountId]));
        unset($this->accessibleAccounts, $this->accessCandidates);

        $this->dispatch('adminv2-toast', message: 'Account-Zugriff entfernt.');
    }

    public function detachSelectedAccess(): void
    {
        $ids = array_values(array_filter(array_map('intval', $this->selectedAccess)));
        $count = $ids === [] ? 0 : $this->customer->accessibleAccounts()->detach($ids);

        $this->selectedAccess = [];
        unset($this->accessibleAccounts, $this->accessCandidates);

        $this->dispatch('adminv2-toast', message: $count.' '.($count === 1 ? 'Account-Zugriff' : 'Account-Zugriffe').' entfernt.');
    }

    public static function accountLabel(Customer $account): string
    {
        return trim(($account->company_name ?: '–').' ('.$account->email.')');
    }
}
