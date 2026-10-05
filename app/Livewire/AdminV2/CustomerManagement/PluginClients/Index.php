<?php

namespace App\Livewire\AdminV2\CustomerManagement\PluginClients;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Customer;
use App\Models\PluginClient;
use App\Models\PluginUsageEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Kundenverwaltung > Plugin-Kunden: Kennzahlen und Liste mit Suche, Filtern,
 * Sortierung, Statuswechsel und Loeschen (einzeln oder mehrere).
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Plugin-Kunden')]
class Index extends Component
{
    use AuthorizesAdminV2, ManagesPluginList;

    public const STATUSES = [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'suspended' => 'Gesperrt',
    ];

    public const STATUS_COLORS = [
        'active' => 'green',
        'inactive' => 'amber',
        'suspended' => 'red',
    ];

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    /** '' = alle, sonst active | inactive | suspended */
    #[Url(except: '')]
    public string $status = '';

    /** '' = alle, 'yes' = nur Kunden mit Aufrufen */
    #[Url(except: '')]
    public string $usage = '';

    /** '' = alle, 'month' = in diesem Monat registriert */
    #[Url(except: '')]
    public string $registered = '';

    public function sortOptions(): array
    {
        return [
            'created_at' => 'Registriert am',
            'company_name' => 'Firma',
            'contact_name' => 'Ansprechpartner',
            'email' => 'E-Mail',
            'city' => 'Ort',
            'domains_count' => 'Domains',
            'usage_events_count' => 'Aufrufe',
        ];
    }

    protected function filterDefaults(): array
    {
        return ['status' => '', 'usage' => '', 'registered' => ''];
    }

    /**
     * Kennzahlen ueber der Liste – unabhaengig von Suche und Filtern.
     *
     * @return array{clients: int, active: int, new: int, today: int, month: int, total: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'clients' => PluginClient::count(),
            'active' => PluginClient::where('status', 'active')->count(),
            'new' => PluginClient::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'today' => PluginUsageEvent::whereDate('created_at', today())->count(),
            'month' => PluginUsageEvent::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'total' => PluginUsageEvent::count(),
        ];
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = PluginClient::query()
            ->with(['customer', 'activeKey'])
            ->withCount(['domains', 'usageEvents']);

        if (($term = trim($this->search)) !== '') {
            $this->whereEveryWord($query, $term, ['company_name', 'contact_name', 'email']);
        }

        if (isset(self::STATUSES[$this->status])) {
            $query->where('status', $this->status);
        }

        if ($this->usage === 'yes') {
            $query->has('usageEvents');
        }

        if ($this->registered === 'month') {
            $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        $direction = $this->sortDirection();

        match ($column = $this->sortColumn()) {
            // Ort des Plugin-Kunden, ersatzweise der des verknuepften Kunden; ohne Ort ans Ende.
            'city' => $query
                ->orderByRaw('('.$this->citySql().' is null) asc')
                ->orderByRaw($this->citySql().' '.$direction),
            default => $query->orderBy($column, $direction),
        };

        return $this->paginateWithinRange($query->orderByDesc('plugin_clients.id'), self::PER_PAGE);
    }

    protected function citySql(): string
    {
        $customerCity = Customer::query()
            ->selectRaw("nullif(company_city, '')")
            ->whereColumn('customers.id', 'plugin_clients.customer_id')
            ->limit(1)
            ->toRawSql();

        return "coalesce(nullif(plugin_clients.city, ''), (".$customerCity.'))';
    }

    /**
     * Aktiv <-> inaktiv. Ein gesperrter Kunde wird dabei wieder aktiv.
     */
    public function toggleStatus(int $clientId): void
    {
        $client = PluginClient::findOrFail($clientId);
        $client->update(['status' => $client->status === 'active' ? 'inactive' : 'active']);

        unset($this->rows, $this->stats);

        $this->dispatch('adminv2-toast', message: '„'.$client->company_name.'“ ist jetzt '.($client->status === 'active' ? 'aktiv' : 'inaktiv').'.');
    }

    public function delete(int $clientId): void
    {
        $client = PluginClient::findOrFail($clientId);
        $client->delete();

        $this->selected = array_values(array_diff($this->selected, [(string) $clientId]));
        unset($this->rows, $this->stats);

        $this->dispatch('adminv2-toast', message: 'Plugin-Kunde „'.$client->company_name.'“ wurde gelöscht.');
    }

    protected function deleteRecords(array $ids): int
    {
        // Einzeln loeschen, damit Domains, API-Keys und Aufrufe mit entfernt werden.
        $clients = PluginClient::whereIn('id', $ids)->get();
        $clients->each->delete();

        unset($this->stats);

        return $clients->count();
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.plugin-clients.index');
    }
}
