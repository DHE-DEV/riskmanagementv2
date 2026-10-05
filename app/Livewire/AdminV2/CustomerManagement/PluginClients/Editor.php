<?php

namespace App\Livewire\AdminV2\CustomerManagement\PluginClients;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\Customer;
use App\Models\PluginClient;
use App\Models\PluginDomain;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kundenverwaltung > Plugin-Kunden: einen Plugin-Kunden anlegen oder ansehen
 * und bearbeiten – Kundendaten, Adresse, API-Key, registrierte Domains und
 * die Aufrufe des Plugins.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, QueriesLists, WithPagination;

    public const EVENTS_PER_PAGE = 25;

    /** Adresse, unter der Kunden das Plugin einbinden. */
    public const EMBED_BASE = 'https://global-travel-monitor.eu/embed/';

    public const EMBED_VIEWS = [
        'events' => 'Ereignisliste',
        'map' => 'Kartenansicht',
        'dashboard' => 'Komplettansicht',
    ];

    public const EVENT_TYPES = [
        'page_load' => 'Page Load',
        'click' => 'Click',
    ];

    public const BUSINESS_TYPES = [
        'travel_agency' => 'Reisebüro',
        'organizer' => 'Veranstalter',
        'online_provider' => 'Online Anbieter',
        'mobile_travel_consultant' => 'Mobiler Reiseberater',
        'software_provider' => 'Softwareanbieter',
        'other' => 'Sonstiges',
    ];

    #[Locked]
    public ?int $clientId = null;

    // Kundendaten
    public string $customerId = '';

    public string $companyName = '';

    public string $contactName = '';

    public string $email = '';

    public string $status = 'active';

    public bool $allowAppAccess = false;

    // Adresse
    public string $street = '';

    public string $houseNumber = '';

    public string $postalCode = '';

    public string $city = '';

    public string $country = '';

    // Neue Domain
    public string $newDomain = '';

    public bool $newDomainActive = true;

    // Aufrufe: Suche, Filter, Sortierung
    public string $eventSearch = '';

    public string $eventType = '';

    public string $eventDomain = '';

    /** '' = alle, today | week | month */
    public string $eventPeriod = '';

    /** created_at | domain */
    public string $eventSort = 'created_at';

    public string $eventDirection = 'desc';

    public function mount(?int $pluginClient = null): void
    {
        if ($pluginClient === null) {
            return;
        }

        $record = PluginClient::findOrFail($pluginClient);

        $this->clientId = $record->id;
        $this->customerId = (string) $record->customer_id;
        $this->companyName = (string) $record->company_name;
        $this->contactName = (string) $record->contact_name;
        $this->email = (string) $record->email;
        $this->status = (string) $record->status;
        $this->allowAppAccess = (bool) $record->allow_app_access;
        $this->street = (string) $record->street;
        $this->houseNumber = (string) $record->house_number;
        $this->postalCode = (string) $record->postal_code;
        $this->city = (string) $record->city;
        $this->country = (string) $record->country;
    }

    #[Computed]
    public function client(): ?PluginClient
    {
        return $this->clientId
            ? PluginClient::with(['customer', 'activeKey'])->findOrFail($this->clientId)
            : null;
    }

    /**
     * Kunden der Plattform, mit denen sich der Plugin-Kunde verknuepfen laesst.
     */
    #[Computed]
    public function customerOptions(): Collection
    {
        return Customer::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'email']);
    }

    /**
     * Aufrufe gesamt, in den letzten 30 Tagen und heute.
     *
     * @return array{total: int, days30: int, today: int}
     */
    #[Computed]
    public function usage(): array
    {
        $client = $this->client;

        return [
            'total' => $client ? $client->usageEvents()->count() : 0,
            'days30' => $client ? $client->usageEvents()->where('created_at', '>=', now()->subDays(30))->count() : 0,
            'today' => $client ? $client->usageEvents()->whereDate('created_at', today())->count() : 0,
        ];
    }

    #[Computed]
    public function domains(): Collection
    {
        return $this->client
            ? $this->client->domains()->orderByDesc('created_at')->orderByDesc('id')->get()
            : collect();
    }

    /**
     * Event-Typen fuer den Filter: die bekannten und alle, die bei diesem Kunden vorkommen.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function eventTypeOptions(): array
    {
        $types = $this->client
            ? $this->client->usageEvents()->distinct()->orderBy('event_type')->pluck('event_type')->filter()->all()
            : [];

        $options = self::EVENT_TYPES;
        foreach ($types as $type) {
            $options[$type] ??= $type;
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function eventDomainOptions(): array
    {
        return $this->client
            ? $this->client->usageEvents()->distinct()->orderBy('domain')->pluck('domain')->filter()->values()->all()
            : [];
    }

    /**
     * Die Aufrufe des Plugins, gefiltert und sortiert.
     */
    #[Computed]
    public function events(): ?LengthAwarePaginator
    {
        if (! $this->client) {
            return null;
        }

        $query = $this->client->usageEvents();

        if (($term = trim($this->eventSearch)) !== '') {
            $this->whereEveryWord($query, $term, ['domain', 'path']);
        }

        if ($this->eventType !== '') {
            $query->where('event_type', $this->eventType);
        }

        if ($this->eventDomain !== '') {
            $query->where('domain', $this->eventDomain);
        }

        match ($this->eventPeriod) {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->where('created_at', '>=', now()->startOfWeek()),
            'month' => $query->where('created_at', '>=', now()->startOfMonth()),
            default => null,
        };

        $direction = $this->eventDirection === 'asc' ? 'asc' : 'desc';

        if ($this->eventSort === 'domain') {
            $query->orderBy('domain', $direction);
        }

        $query->orderBy('created_at', $this->eventSort === 'domain' ? 'desc' : $direction)
            ->orderBy('id', $this->eventSort === 'domain' ? 'desc' : $direction);

        return $this->paginateWithinRange($query, self::EVENTS_PER_PAGE);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['eventSearch', 'eventType', 'eventDomain', 'eventPeriod', 'eventSort'], true)) {
            $this->resetPage();
        }
    }

    public function toggleEventDirection(): void
    {
        $this->eventDirection = $this->eventDirection === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function hasEventFilters(): bool
    {
        return $this->eventSearch !== '' || $this->eventType !== '' || $this->eventDomain !== '' || $this->eventPeriod !== '';
    }

    public function resetEventFilters(): void
    {
        $this->reset(['eventSearch', 'eventType', 'eventDomain', 'eventPeriod']);
        $this->resetPage();
    }

    public function save()
    {
        $this->email = trim($this->email);

        $this->validate([
            'customerId' => ['nullable', Rule::exists('customers', 'id')],
            'companyName' => ['required', 'string', 'max:255'],
            'contactName' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Index::STATUSES))],
            'street' => ['nullable', 'string', 'max:255'],
            'houseNumber' => ['nullable', 'string', 'max:20'],
            'postalCode' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
        ], [
            'customerId.exists' => 'Diesen Kunden gibt es nicht mehr.',
            'companyName.required' => 'Bitte die Firma angeben.',
            'email.required' => 'Bitte eine E-Mail-Adresse angeben.',
            'email.email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
            'status.in' => 'Bitte einen Status wählen.',
            'houseNumber.max' => 'Die Hausnummer darf höchstens 20 Zeichen haben.',
            'postalCode.max' => 'Die PLZ darf höchstens 20 Zeichen haben.',
        ]);

        $text = fn (string $value) => trim($value) === '' ? null : trim($value);

        $record = $this->client ?? new PluginClient;
        $created = ! $record->exists;

        $record->fill([
            'customer_id' => $this->customerId === '' ? null : (int) $this->customerId,
            'company_name' => trim($this->companyName),
            // Die Spalte erlaubt kein NULL – ohne Ansprechpartner bleibt sie leer.
            'contact_name' => trim($this->contactName),
            'email' => $this->email,
            'status' => $this->status,
            'allow_app_access' => $this->allowAppAccess,
            'street' => $text($this->street),
            'house_number' => $text($this->houseNumber),
            'postal_code' => $text($this->postalCode),
            'city' => $text($this->city),
            'country' => $text($this->country),
        ])->save();

        if ($created) {
            // Ein neuer Plugin-Kunde bekommt sofort seinen API-Key.
            $record->generateKey();

            session()->flash('adminv2-toast', 'Plugin-Kunde wurde erstellt.');

            return $this->redirectRoute('adminv2.customer-management.plugin-clients.edit', $record->id);
        }

        unset($this->client);

        $this->dispatch('adminv2-toast', message: 'Plugin-Kunde wurde aktualisiert.');
    }

    /**
     * Neuen API-Key erzeugen – der bisherige wird sofort ungueltig.
     */
    public function regenerateKey(): void
    {
        if (! $this->client) {
            return;
        }

        $this->client->generateKey();

        unset($this->client);

        $this->dispatch('adminv2-toast', message: 'Neuer API-Key wurde generiert.');
    }

    public function delete()
    {
        if (! $this->client) {
            return;
        }

        // Domains, API-Keys und Aufrufe loescht das Modell mit.
        $this->client->delete();

        session()->flash('adminv2-toast', 'Plugin-Kunde wurde gelöscht.');

        return $this->redirectRoute('adminv2.customer-management.plugin-clients.index');
    }

    public function addDomain(): void
    {
        if (! $this->client) {
            return;
        }

        $this->newDomain = $this->normalizeDomain($this->newDomain);

        $this->validate([
            'newDomain' => ['required', 'string', 'max:255', Rule::unique('plugin_domains', 'domain')],
        ], [
            'newDomain.required' => 'Bitte eine Domain angeben, z. B. beispiel.de.',
            'newDomain.unique' => 'Diese Domain ist bereits registriert.',
        ]);

        $this->client->domains()->create([
            'domain' => $this->newDomain,
            'is_active' => $this->newDomainActive,
        ]);

        $this->reset(['newDomain', 'newDomainActive']);
        unset($this->domains);

        $this->dispatch('adminv2-toast', message: 'Domain wurde hinzugefügt.');
    }

    public function toggleDomain(int $domainId): void
    {
        $domain = $this->findDomain($domainId);
        $domain->update(['is_active' => ! $domain->is_active]);

        unset($this->domains);

        $this->dispatch('adminv2-toast', message: $domain->is_active ? 'Domain aktiviert.' : 'Domain deaktiviert.');
    }

    public function deleteDomain(int $domainId): void
    {
        $this->findDomain($domainId)->delete();

        unset($this->domains);

        $this->dispatch('adminv2-toast', message: 'Domain wurde gelöscht.');
    }

    /**
     * Nur Domains dieses Plugin-Kunden lassen sich hier aendern.
     */
    protected function findDomain(int $domainId): PluginDomain
    {
        abort_unless($this->client, 404);

        return $this->client->domains()->find($domainId) ?? abort(404);
    }

    /**
     * Wie im Modell: ohne Protokoll, "www.", Pfad und Port, klein geschrieben.
     */
    protected function normalizeDomain(string $domain): string
    {
        $domain = preg_replace('#^https?://#i', '', trim($domain));
        $domain = preg_replace('#^www\.#i', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = explode(':', $domain)[0];

        return mb_strtolower(trim($domain));
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.plugin-clients.editor')
            ->title($this->client ? $this->client->company_name : 'Neuer Plugin-Kunde');
    }
}
