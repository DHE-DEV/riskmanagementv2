<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kundenverwaltung > Kunden: Liste mit Suche, Filtern, Sortierung, Papierkorb
 * und Sammelaktionen. Angelegt werden Kunden nicht hier – sie entstehen bei der
 * Registrierung bzw. beim ersten Login.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Kunden')]
class Index extends Component
{
    use AuthorizesAdminV2, QueriesLists, WithPagination;

    public const PER_PAGE = 25;

    public const CUSTOMER_TYPES = ['private' => 'Privat', 'business' => 'Geschäftlich'];

    /** Aktionen mit Rueckfrage: einzeln und fuer die Auswahl. */
    protected const ACTIONS = ['delete', 'restore', 'force', 'bulk-delete', 'bulk-restore', 'bulk-force'];

    #[Url(except: '')]
    public string $search = '';

    /** '' = ohne Papierkorb, 'with' = mit, 'only' = nur Papierkorb */
    #[Url(except: '')]
    public string $trashed = '';

    /** '' = alle, 'private', 'business' */
    #[Url(as: 'type', except: '')]
    public string $customerType = '';

    /** '' = alle, 'verified', 'unverified' */
    #[Url(as: 'verified', except: '')]
    public string $emailVerified = '';

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    /** @var array<int, string> IDs der angehakten Kunden */
    public array $selected = [];

    /** Aktion, zu der gerade die Rueckfrage offen ist. */
    #[Locked]
    public ?string $pendingAction = null;

    #[Locked]
    public ?int $pendingId = null;

    /**
     * Sortierungen: Spalte => Bezeichnung; die erste ist die Vorgabe.
     *
     * @return array<string, string>
     */
    public function sortOptions(): array
    {
        return array_filter([
            'created_at' => 'Registriert am',
            'name' => 'Name',
            'email' => 'E-Mail',
            'pds_account_id' => $this->hasPdsAccountId() ? 'PDS Account-ID' : null,
            'customer_type' => 'Kundentyp',
            'company_name' => 'Firma',
            'email_verified_at' => 'E-Mail verifiziert',
            'branch_management_active' => 'Filialen aktiv',
            'branches_count' => 'Anzahl Filialen',
            'passolution_subscription_type' => 'Passolution Abo',
            'deleted_at' => 'Gelöscht am',
        ]);
    }

    /**
     * Faengt einen Stand ab, auf dem die Spalte noch fehlt – Suche und
     * Sortierung auf einer unbekannten Spalte wuerden die Liste zerlegen.
     */
    protected function hasPdsAccountId(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasColumn('customers', 'pds_account_id');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'trashed', 'customerType', 'emailVerified', 'sort'], true)) {
            $this->resetPage();
            // Die Auswahl gilt nur fuer das, was gerade zu sehen ist.
            $this->selected = [];
        }
    }

    public function toggleDirection(): void
    {
        $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'trashed', 'customerType', 'emailVerified', 'selected']);
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->trashed !== '' || $this->customerType !== '' || $this->emailVerified !== '';
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = Customer::query()->withCount('branches');

        match ($this->trashed) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };

        if (($term = trim($this->search)) !== '') {
            // Die Liste ist auch ueber die reine Account-ID durchsuchbar.
            $this->whereEveryWord($query, $term, ['name', 'email', 'company_name', ...($this->hasPdsAccountId() ? ['pds_account_id'] : [])]);
        }

        if (isset(self::CUSTOMER_TYPES[$this->customerType])) {
            $query->where('customer_type', $this->customerType);
        }

        match ($this->emailVerified) {
            'verified' => $query->whereNotNull('email_verified_at'),
            'unverified' => $query->whereNull('email_verified_at'),
            default => null,
        };

        $sort = array_key_exists($this->sort, $this->sortOptions()) ? $this->sort : 'created_at';

        $query
            ->orderBy($sort, $this->direction === 'asc' ? 'asc' : 'desc')
            ->orderBy('customers.id');

        return $this->paginateWithinRange($query, self::PER_PAGE);
    }

    /**
     * Alle Kunden der aktuellen Seite an- bzw. abwaehlen.
     */
    public function toggleSelectPage(): void
    {
        $pageIds = $this->rows->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->selected = array_diff($pageIds, $this->selected) === []
            ? array_values(array_diff($this->selected, $pageIds))
            : array_values(array_unique([...$this->selected, ...$pageIds]));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /**
     * Rueckfrage oeffnen – fuer einen Kunden ($id) oder die Auswahl ("bulk-…").
     */
    public function confirmAction(string $action, ?int $id = null): void
    {
        if (! in_array($action, self::ACTIONS, true)) {
            return;
        }

        $bulk = str_starts_with($action, 'bulk-');

        if ($bulk ? $this->selectedIds() === [] : ! Customer::withTrashed()->whereKey($id)->exists()) {
            return;
        }

        $this->pendingAction = $action;
        $this->pendingId = $bulk ? null : $id;
        unset($this->pending);

        $this->modal('customer-confirm')->show();
    }

    /**
     * Angaben fuer die Rueckfrage.
     *
     * @return array{action: string, bulk: bool, label: string, count: int}|null
     */
    #[Computed]
    public function pending(): ?array
    {
        if (! $this->pendingAction) {
            return null;
        }

        $bulk = str_starts_with($this->pendingAction, 'bulk-');
        $customer = $bulk ? null : Customer::withTrashed()->find($this->pendingId);

        if (! $bulk && ! $customer) {
            return null;
        }

        return [
            'action' => $bulk ? substr($this->pendingAction, 5) : $this->pendingAction,
            'bulk' => $bulk,
            'label' => $customer ? self::label($customer) : '',
            'count' => $bulk ? count($this->selectedIds()) : 1,
        ];
    }

    public function runPendingAction(): void
    {
        $action = $this->pendingAction;
        $id = $this->pendingId;

        $this->modal('customer-confirm')->close();
        $this->pendingAction = null;
        $this->pendingId = null;
        unset($this->pending);

        if (! $action) {
            return;
        }

        if (str_starts_with($action, 'bulk-')) {
            $this->runBulk(substr($action, 5));
        } else {
            $this->runSingle($action, Customer::withTrashed()->findOrFail($id));
        }

        unset($this->rows);
    }

    protected function runSingle(string $action, Customer $customer): void
    {
        $label = self::label($customer);

        if ($action === 'delete') {
            $customer->delete();
            $this->dispatch('adminv2-toast', message: '„'.$label.'“ gelöscht – der Kunde liegt jetzt im Papierkorb.');

            return;
        }

        // Wiederherstellen und endgueltig loeschen gibt es nur aus dem Papierkorb.
        if (! $customer->trashed()) {
            return;
        }

        if ($action === 'restore') {
            $customer->restore();
            $this->dispatch('adminv2-toast', message: '„'.$label.'“ wurde wiederhergestellt.');

            return;
        }

        try {
            $customer->forceDelete();
        } catch (QueryException) {
            $this->dispatch('adminv2-toast', message: '„'.$label.'“ wird an anderer Stelle noch verwendet und lässt sich nicht endgültig löschen.', variant: 'danger');

            return;
        }

        $this->selected = array_values(array_diff($this->selected, [(string) $customer->id]));
        $this->dispatch('adminv2-toast', message: '„'.$label.'“ wurde endgültig gelöscht.');
    }

    /**
     * Sammelaktion fuer die Auswahl. Jeder Kunde wird einzeln behandelt, damit
     * die Modell-Ereignisse laufen.
     */
    protected function runBulk(string $action): void
    {
        $customers = Customer::withTrashed()->whereKey($this->selectedIds())->get();
        $done = 0;
        $failed = 0;

        foreach ($customers as $customer) {
            if ($action === 'delete' && ! $customer->trashed()) {
                $customer->delete();
                $done++;
            } elseif ($action === 'restore' && $customer->trashed()) {
                $customer->restore();
                $done++;
            } elseif ($action === 'force') {
                try {
                    $customer->forceDelete();
                    $done++;
                } catch (QueryException) {
                    $failed++;
                }
            }
        }

        $this->selected = [];

        $message = $done.' '.($done === 1 ? 'Kunde' : 'Kunden').' '.match ($action) {
            'delete' => 'gelöscht.',
            'restore' => 'wiederhergestellt.',
            default => 'endgültig gelöscht.',
        };

        if ($failed > 0) {
            $message .= ' '.$failed.' '.($failed === 1 ? 'Kunde wird' : 'Kunden werden').' an anderer Stelle noch verwendet und '.($failed === 1 ? 'blieb' : 'blieben').' erhalten.';
        }

        $this->dispatch('adminv2-toast', message: $message, variant: $failed > 0 ? 'danger' : 'success');
    }

    /**
     * @return array<int, int>
     */
    protected function selectedIds(): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $this->selected))));
    }

    public static function label(Customer $customer): string
    {
        return $customer->name ?: ($customer->email ?: 'Kunde #'.$customer->id);
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.customers.index');
    }
}
