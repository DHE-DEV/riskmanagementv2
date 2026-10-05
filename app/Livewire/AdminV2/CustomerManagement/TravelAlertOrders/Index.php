<?php

namespace App\Livewire\AdminV2\CustomerManagement\TravelAlertOrders;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Livewire\AdminV2\CustomerManagement\TravelAlertOrders\Concerns\DecidesTravelAlertOrders;
use App\Models\TravelAlertOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kundenverwaltung > TravelAlert Bestellungen: Liste mit Suche, Statusfilter
 * und Sortierung; freischalten, ablehnen und loeschen direkt aus der Liste.
 * Bestellungen entstehen im Kundenbereich – angelegt wird hier nichts.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('TravelAlert Bestellungen')]
class Index extends Component
{
    use AuthorizesAdminV2, DecidesTravelAlertOrders, QueriesLists, WithPagination;

    public const PER_PAGE = 25;

    #[Url(except: '')]
    public string $search = '';

    /** '' = alle, sonst eine der STATUS_-Konstanten der Bestellung */
    #[Url(except: '')]
    public string $status = '';

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    /**
     * Angehakte Bestellungen (fuer das gemeinsame Loeschen).
     *
     * @var array<int, string>
     */
    public array $selected = [];

    /**
     * Sortierungen: Spalte => Bezeichnung; die erste ist die Vorgabe.
     *
     * @return array<string, string>
     */
    public function sortOptions(): array
    {
        return [
            'created_at' => 'Eingegangen',
            'id' => 'ID',
            'company' => 'Firma',
            'email' => 'E-Mail',
            'city' => 'Stadt',
            'country' => 'Land',
            'trial_expires_at' => 'Test läuft ab',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return [
            TravelAlertOrder::STATUS_PENDING_CONFIRMATION => 'Wartet auf Bestätigung',
            TravelAlertOrder::STATUS_PENDING_APPROVAL => 'Wartet auf Freischaltung',
            TravelAlertOrder::STATUS_ACTIVE => 'Freigeschaltet',
            TravelAlertOrder::STATUS_REJECTED => 'Abgelehnt',
        ];
    }

    public function updated(string $property): void
    {
        // Suche, Filter und Sortierung aendern die Trefferliste: zurueck auf
        // Seite 1, und die Auswahl gilt nicht mehr.
        if (in_array($property, ['search', 'status', 'sort'], true)) {
            $this->selected = [];
            $this->resetPage();
        }
    }

    public function toggleDirection(): void
    {
        $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'selected']);
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->status !== '';
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = TravelAlertOrder::query();

        if (($term = trim($this->search)) !== '') {
            $this->whereEveryWord($query, $term, ['company', 'first_name', 'last_name', 'email', 'city']);
        }

        // Der Status steht nicht in der Tabelle, er ergibt sich aus den Zeitstempeln.
        match ($this->status) {
            TravelAlertOrder::STATUS_PENDING_CONFIRMATION => $query->whereNull('confirmed_at')->whereNull('rejected_at'),
            TravelAlertOrder::STATUS_PENDING_APPROVAL => $query->whereNotNull('confirmed_at')->whereNull('approved_at')->whereNull('rejected_at'),
            TravelAlertOrder::STATUS_ACTIVE => $query->whereNotNull('approved_at')->whereNull('rejected_at'),
            TravelAlertOrder::STATUS_REJECTED => $query->whereNotNull('rejected_at'),
            default => null,
        };

        $column = array_key_exists($this->sort, $this->sortOptions()) ? $this->sort : 'created_at';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        return $this->paginateWithinRange($query->orderBy($column, $direction)->orderBy('id', $direction), self::PER_PAGE);
    }

    public function approve(int $orderId): void
    {
        $this->approveOrder(TravelAlertOrder::findOrFail($orderId));

        unset($this->rows);
    }

    public function reject(int $orderId): void
    {
        $this->rejectOrder(TravelAlertOrder::findOrFail($orderId));

        unset($this->rows);
    }

    /**
     * Alle Bestellungen der aktuellen Seite an- bzw. wieder abwaehlen.
     */
    public function togglePage(): void
    {
        $pageIds = $this->rows->getCollection()->map(fn (TravelAlertOrder $order) => (string) $order->id)->all();

        $this->selected = array_diff($pageIds, $this->selected) === []
            ? array_values(array_diff($this->selected, $pageIds))
            : array_values(array_unique([...$this->selected, ...$pageIds]));
    }

    /**
     * Die angehakten Bestellungen loeschen (Papierkorb der Tabelle; im
     * Admin-Bereich sind sie danach nicht mehr sichtbar).
     */
    public function deleteSelected(): void
    {
        $ids = array_values(array_filter(array_map('intval', $this->selected)));

        if ($ids === []) {
            return;
        }

        // Einzeln loeschen, damit Model-Ereignisse wie bisher ausgeloest werden.
        $orders = TravelAlertOrder::query()->whereIn('id', $ids)->get();
        $orders->each->delete();

        $this->selected = [];
        unset($this->rows);

        $count = $orders->count();

        $this->dispatch('adminv2-toast', message: $count === 1 ? 'Bestellung gelöscht.' : $count.' Bestellungen gelöscht.');
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.travel-alert-orders.index');
    }
}
