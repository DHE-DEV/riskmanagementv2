<?php

namespace App\Livewire\AdminV2\CustomerManagement\ApiClients;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\ApiClient;
use App\Models\CustomEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Kundenverwaltung > API-Kunden: einen API-Kunden anlegen oder ansehen und
 * bearbeiten – Kundendaten, Logo, Einstellungen, API-Tokens und die per API
 * angelegten Events samt Freigabe.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, QueriesLists, WithFileUploads, WithPagination;

    public const EVENTS_PER_PAGE = 10;

    public const LOGO_DISK = 'public';

    public const LOGO_DIRECTORY = 'api-client-logos';

    /** Review-Status eines Events => [Bezeichnung, Farbe der Markierung] */
    public const REVIEW_STATUSES = [
        'approved' => ['Freigegeben', 'green'],
        'pending_review' => ['Ausstehend', 'amber'],
        'rejected' => ['Abgelehnt', 'red'],
    ];

    /** Sortierbare Spalten der Event-Liste. */
    public const EVENT_SORTS = ['title', 'start_date', 'created_at'];

    /** Geoeffneter API-Kunde; null = neuer API-Kunde. */
    #[Locked]
    public ?int $apiClientId = null;

    // Kundeninformationen
    public string $name = '';

    public string $companyName = '';

    public string $contactEmail = '';

    public string $description = '';

    // Logo
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null neu hochgeladenes Logo */
    public $logo = null;

    /** Das gespeicherte Logo beim Speichern entfernen. */
    public bool $removeLogo = false;

    // Einstellungen
    public string $status = 'active';

    public bool $canCreateEvents = false;

    public bool $autoApproveEvents = false;

    public string $rateLimit = '60';

    /** Gerade erzeugter Token im Klartext – wird nur dieses eine Mal gezeigt. */
    public ?string $newToken = null;

    // Event-Liste
    public string $eventSearch = '';

    /** '' = alle, sonst ein Schluessel aus REVIEW_STATUSES */
    public string $eventReviewStatus = '';

    public string $eventSort = 'created_at';

    public string $eventDirection = 'desc';

    public function mount(?int $apiClient = null): void
    {
        if ($apiClient === null) {
            return;
        }

        $record = ApiClient::findOrFail($apiClient);

        $this->apiClientId = $record->id;
        $this->name = (string) $record->name;
        $this->companyName = (string) $record->company_name;
        $this->contactEmail = (string) $record->contact_email;
        $this->description = (string) $record->description;
        $this->status = (string) $record->status;
        $this->canCreateEvents = (bool) $record->can_create_events;
        $this->autoApproveEvents = (bool) $record->auto_approve_events;
        $this->rateLimit = (string) $record->rate_limit;
    }

    #[Computed]
    public function record(): ?ApiClient
    {
        return $this->apiClientId ? ApiClient::findOrFail($this->apiClientId) : null;
    }

    /**
     * Der geoeffnete API-Kunde – Aktionen an Tokens und Events gibt es erst nach dem Anlegen.
     */
    protected function client(): ApiClient
    {
        abort_unless($this->record, 404);

        return $this->record;
    }

    /**
     * Kennzahlen des API-Kunden: Events, Requests der letzten 30 Tage, Tokens.
     *
     * @return array{events: int, requests: int, tokens: int}
     */
    #[Computed]
    public function stats(): array
    {
        $record = $this->client();

        return [
            'events' => $record->customEvents()->count(),
            'requests' => $record->requestLogs()->where('created_at', '>=', now()->subDays(30))->count(),
            'tokens' => $record->tokens()->count(),
        ];
    }

    /**
     * Die per API angelegten Events dieses Kunden.
     */
    #[Computed]
    public function events(): LengthAwarePaginator
    {
        $query = $this->client()->customEvents();

        if (($term = trim($this->eventSearch)) !== '') {
            $this->whereEveryWord($query, $term, ['title']);
        }

        if (isset(self::REVIEW_STATUSES[$this->eventReviewStatus])) {
            $query->where('review_status', $this->eventReviewStatus);
        }

        $column = in_array($this->eventSort, self::EVENT_SORTS, true) ? $this->eventSort : 'created_at';

        $query
            ->orderBy($column, $this->eventDirection === 'asc' ? 'asc' : 'desc')
            ->orderBy('id');

        return $this->paginateWithinRange($query, self::EVENTS_PER_PAGE);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['eventSearch', 'eventReviewStatus'], true)) {
            $this->resetPage();
        }

        // Ein neu gewaehltes Logo ersetzt das bisherige – "entfernen" gilt dann nicht mehr.
        if ($property === 'logo') {
            $this->removeLogo = false;

            try {
                $this->validateOnly('logo');
            } catch (ValidationException $exception) {
                // Die ungueltige Datei nicht behalten – sonst scheitert jedes Speichern an ihr.
                $this->logo = null;

                throw $exception;
            }
        }
    }

    public function sortEvents(string $column): void
    {
        if (! in_array($column, self::EVENT_SORTS, true)) {
            return;
        }

        $this->eventDirection = $this->eventSort === $column && $this->eventDirection === 'asc' ? 'desc' : 'asc';
        $this->eventSort = $column;
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'companyName' => ['required', 'string', 'max:255'],
            'contactEmail' => ['required', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            // Max. 2 MB; PNG, JPG oder SVG.
            'logo' => ['nullable', 'file', 'mimetypes:image/png,image/jpeg,image/svg+xml', 'max:2048'],
            'status' => ['required', Rule::in(array_keys(Index::STATUSES))],
            'rateLimit' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'Name',
            'companyName' => 'Firma',
            'contactEmail' => 'E-Mail',
            'description' => 'Beschreibung',
            'logo' => 'Logo',
            'status' => 'Status',
            'rateLimit' => 'Rate Limit',
        ];
    }

    protected function messages(): array
    {
        return [
            'logo.mimetypes' => 'Das Logo muss eine PNG-, JPG- oder SVG-Datei sein.',
            'logo.max' => 'Das Logo darf höchstens 2 MB groß sein.',
        ];
    }

    /**
     * Das neu gewaehlte, noch nicht gespeicherte Logo wieder verwerfen.
     */
    public function discardLogo(): void
    {
        $this->logo = null;
        $this->resetValidation('logo');
    }

    public function save(): void
    {
        $this->validate();

        $record = $this->record ?? new ApiClient;
        $created = ! $record->exists;
        $oldLogo = $record->logo_path;

        $record->fill([
            'name' => trim($this->name),
            'company_name' => trim($this->companyName),
            'contact_email' => trim($this->contactEmail),
            'description' => trim($this->description) !== '' ? trim($this->description) : null,
            'status' => $this->status,
            'can_create_events' => $this->canCreateEvents,
            'auto_approve_events' => $this->autoApproveEvents,
            'rate_limit' => (int) $this->rateLimit,
        ]);

        if ($this->logo) {
            $record->logo_path = $this->logo->store(self::LOGO_DIRECTORY, self::LOGO_DISK);
        } elseif ($this->removeLogo) {
            $record->logo_path = null;
        }

        $record->save();

        // Ein ersetztes oder entferntes Logo nicht als Dateileiche liegen lassen.
        if ($oldLogo && $oldLogo !== $record->logo_path) {
            Storage::disk(self::LOGO_DISK)->delete($oldLogo);
        }

        $this->logo = null;
        $this->removeLogo = false;

        if ($created) {
            session()->flash('adminv2-toast', '„'.$record->name.'“ angelegt.');
            $this->redirectRoute('adminv2.customer-management.api-clients.edit', $record->id);

            return;
        }

        unset($this->record);
        $this->dispatch('adminv2-toast', message: 'Gespeichert.');
    }

    /**
     * Neuen API-Token erzeugen (ein Jahr gueltig, darf Events schreiben).
     * Der Klartext laesst sich spaeter nicht mehr abrufen.
     */
    public function generateToken(): void
    {
        $token = $this->client()->createToken('api-token', ['events:write'], now()->addYear());

        $this->newToken = $token->plainTextToken;
        unset($this->stats);

        $this->dispatch('adminv2-toast', message: 'API-Token erstellt. Bitte jetzt kopieren – er wird nicht erneut angezeigt.');
    }

    public function dismissToken(): void
    {
        $this->newToken = null;
    }

    /**
     * Alle API-Tokens des Kunden sofort ungueltig machen.
     */
    public function revokeTokens(): void
    {
        $count = $this->client()->tokens()->count();
        $this->client()->tokens()->delete();

        $this->newToken = null;
        unset($this->stats);

        $this->dispatch('adminv2-toast', message: $count.' Token(s) wurden widerrufen.');
    }

    public function approveEvent(int $eventId): void
    {
        $event = $this->pendingEvent($eventId);

        if (! $event) {
            return;
        }

        $event->approve((int) auth('web')->id());
        unset($this->events);

        $this->dispatch('adminv2-toast', message: 'Event freigegeben.');
    }

    public function rejectEvent(int $eventId): void
    {
        $event = $this->pendingEvent($eventId);

        if (! $event) {
            return;
        }

        $event->reject((int) auth('web')->id());
        unset($this->events);

        $this->dispatch('adminv2-toast', message: 'Event abgelehnt.');
    }

    /**
     * Ein Event dieses Kunden, das noch auf die Freigabe wartet – sonst null.
     */
    protected function pendingEvent(int $eventId): ?CustomEvent
    {
        $event = $this->client()->customEvents()->findOrFail($eventId);

        return $event->review_status === 'pending_review' ? $event : null;
    }

    public function delete(): void
    {
        $record = $this->client();
        $record->delete();

        session()->flash('adminv2-toast', '„'.$record->name.'“ gelöscht.');

        $this->redirectRoute('adminv2.customer-management.api-clients.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.api-clients.editor')
            ->title($this->record ? 'API-Kunde: '.$this->client()->name : 'Neuer API-Kunde');
    }
}
