<?php

namespace App\Livewire\AdminV2\CustomerManagement\ApiClients;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\ApiClient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kundenverwaltung > API-Kunden: Liste mit Suche, Statusfilter, Sortierung
 * und Loeschen (einzeln oder mehrere auf einmal).
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('API-Kunden')]
class Index extends Component
{
    use AuthorizesAdminV2, QueriesLists, WithPagination;

    public const PER_PAGE = 25;

    /** Status => [Bezeichnung, Farbe der Markierung] */
    public const STATUSES = [
        'active' => ['Aktiv', 'green'],
        'inactive' => ['Inaktiv', 'zinc'],
        'suspended' => ['Gesperrt', 'red'],
    ];

    #[Url(except: '')]
    public string $search = '';

    /** '' = alle, sonst ein Schluessel aus STATUSES */
    #[Url(except: '')]
    public string $status = '';

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    /** @var array<int, string> fuer das gemeinsame Loeschen angehakte API-Kunden */
    public array $selected = [];

    /**
     * Sortierungen: Spalte => Bezeichnung; die erste ist die Vorgabe.
     *
     * @return array<string, string>
     */
    public function sortOptions(): array
    {
        return ['created_at' => 'Erstellt', 'name' => 'Name', 'company_name' => 'Firma'];
    }

    public function updated(string $property): void
    {
        // Neue Suche, neuer Filter oder neue Sortierung: zurueck auf Seite 1, Auswahl verwerfen.
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
        $query = ApiClient::query()->withCount('customEvents');

        if (($term = trim($this->search)) !== '') {
            $this->whereEveryWord($query, $term, ['name', 'company_name']);
        }

        if (isset(self::STATUSES[$this->status])) {
            $query->where('status', $this->status);
        }

        $column = isset($this->sortOptions()[$this->sort]) ? $this->sort : 'created_at';

        $query
            ->orderBy($column, $this->direction === 'asc' ? 'asc' : 'desc')
            ->orderBy('id');

        return $this->paginateWithinRange($query, self::PER_PAGE);
    }

    /**
     * Alle API-Kunden der aktuellen Seite anhaken.
     */
    public function selectPage(): void
    {
        $this->selected = $this->rows->getCollection()->map(fn (ApiClient $client) => (string) $client->id)->all();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function delete(int $apiClientId): void
    {
        $client = ApiClient::findOrFail($apiClientId);
        $client->delete();

        $this->selected = array_values(array_diff($this->selected, [(string) $apiClientId]));
        unset($this->rows);

        $this->dispatch('adminv2-toast', message: '„'.$client->name.'“ gelöscht.');
    }

    /**
     * Die angehakten API-Kunden loeschen.
     */
    public function deleteSelected(): void
    {
        $ids = array_filter(array_map('intval', $this->selected));

        // Einzeln loeschen, damit Modell-Ereignisse wie beim Einzelloeschen laufen.
        $clients = $ids ? ApiClient::whereKey($ids)->get() : collect();
        $clients->each->delete();

        $this->selected = [];
        unset($this->rows);

        $count = $clients->count();

        $this->dispatch('adminv2-toast', message: $count === 1 ? '1 API-Kunde gelöscht.' : $count.' API-Kunden gelöscht.');
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.api-clients.index');
    }
}
