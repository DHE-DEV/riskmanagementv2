<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Customer;
use App\Services\PdsAccountImportService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * Kundenverwaltung > Kunden > Aus pds-admin importieren.
 *
 * Schritt 1: Hauptaccount per PDS Account-ID abrufen und als Kunde anlegen
 * (bzw. den vorhandenen Kunden erkennen). Schritt 2: dessen Unteraccounts –
 * aus der pds-api oder aus einem CSV-Export – mit den Kunden abgleichen und
 * nur die fehlenden anlegen; alle werden der Agentur zugeordnet (assign_to)
 * und fuer sie freigegeben (Account-Zugriff).
 *
 * Ohne Zugang zur pds-api (Admin-Secret) laesst sich als Hauptaccount nur ein
 * vorhandener Kunde mit dieser Account-ID waehlen, die Unteraccounts nur per CSV.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Kunden aus pds-admin importieren')]
class PdsImport extends Component
{
    use AuthorizesAdminV2;
    use WithFileUploads;

    public const SOURCES = [
        'api' => 'Direkt aus pds-admin (Schnittstelle)',
        'csv' => 'Aus Importdatei (CSV-Export)',
    ];

    public const FILTERS = [
        'all' => 'Alle',
        'existing' => 'Schon in der Plattform',
        'new' => 'Werden neu angelegt',
        'skipped' => 'Werden übersprungen (ohne E-Mail, doppelt)',
    ];

    public string $pdsAccountId = '';

    /** Woher die Unteraccounts kommen, siehe SOURCES. */
    public string $source = 'api';

    /** Nur aktive, nicht geloeschte Unteraccounts importieren. */
    public bool $onlyActive = true;

    /** Plattform-Kunde, dem die Unteraccounts zugeordnet werden (vorbelegt mit dem Hauptaccount). */
    public string $targetCustomerId = '';

    /** Optionaler CSV-Export der Unteraccounts statt Abruf aus der pds-api. */
    public ?TemporaryUploadedFile $csv = null;

    /** Vorschau-Filter, siehe FILTERS. */
    public string $childFilter = 'all';

    /** @var array<string, mixed>|null Abgerufener Hauptaccount */
    #[Locked]
    public ?array $account = null;

    /** Der Kunde, der zum Hauptaccount gehoert (per Account-ID bzw. E-Mail). */
    #[Locked]
    public ?int $matchedCustomerId = null;

    /** Die gewaehlte Agentur (Zielkunde). */
    #[Locked]
    public ?int $agencyId = null;

    /** @var array<string, int> Status => Anzahl der Unteraccounts */
    #[Locked]
    public array $childCounts = [];

    /** @var list<array{id: int, name: ?string, email: ?string, city: ?string, status: string, customer_id: ?int}> */
    #[Locked]
    public array $childRows = [];

    /** CSV-Zeilen, die kein aktiver Unteraccount dieses Hauptaccounts sind. */
    #[Locked]
    public int $csvSkipped = 0;

    #[Locked]
    public bool $childrenLoaded = false;

    /** @var array<string, int>|null Ergebnis des letzten Unteraccount-Imports */
    #[Locked]
    public ?array $result = null;

    public ?string $error = null;

    public function mount(PdsAccountImportService $service): void
    {
        $this->source = $service->isConfigured() ? 'api' : 'csv';
    }

    public function updatedSource(): void
    {
        if (! array_key_exists($this->source, self::SOURCES) || ($this->source === 'api' && ! $this->isConfigured())) {
            $this->source = 'csv';
        }

        $this->resetChildren();
    }

    public function updatedOnlyActive(): void
    {
        $this->resetChildren();
    }

    /**
     * Anderen Plattform-Kunden als Agentur waehlen.
     */
    public function updatedTargetCustomerId(): void
    {
        $this->resetChildren();
        $id = trim($this->targetCustomerId);

        if ($id === '') {
            $this->agencyId = null;

            return;
        }

        $customer = ctype_digit($id) ? Customer::withTrashed()->find((int) $id) : null;

        if (! $customer) {
            $this->addError('targetCustomerId', "Kunde #{$id} gibt es nicht.");

            return;
        }

        $this->resetErrorBag('targetCustomerId');
        $this->agencyId = $customer->id;
    }

    protected function resetChildren(): void
    {
        $this->reset(['childCounts', 'childRows', 'csvSkipped', 'childrenLoaded', 'result', 'error']);
    }

    public function isConfigured(): bool
    {
        return app(PdsAccountImportService::class)->isConfigured();
    }

    public function fetch(PdsAccountImportService $service): void
    {
        $this->validate(['pdsAccountId' => ['required', 'regex:/^\s*[1-9]\d{0,18}\s*$/']], [
            'pdsAccountId.required' => 'Bitte eine PDS Account-ID eingeben.',
            'pdsAccountId.regex' => 'Die PDS Account-ID besteht nur aus Ziffern.',
        ]);

        $this->reset(['account', 'matchedCustomerId', 'agencyId', 'targetCustomerId']);
        $this->resetChildren();
        $this->resetErrorBag('targetCustomerId');

        if (! $service->isConfigured()) {
            $this->useLocalAccount((int) trim($this->pdsAccountId));
            $this->matchedCustomerId = $this->agencyId;
            $this->targetCustomerId = (string) ($this->agencyId ?? '');

            return;
        }

        try {
            $account = $service->fetchAccount((int) trim($this->pdsAccountId));
        } catch (RuntimeException $e) {
            $this->error = $e->getMessage();

            return;
        }

        if ($account === null) {
            $this->error = 'Account '.trim($this->pdsAccountId).' gibt es in pds-admin nicht.';

            return;
        }

        $this->account = $account;
        $this->agencyId = $this->matchedCustomerId = $service->findCustomer($account)?->id;
        $this->targetCustomerId = (string) ($this->agencyId ?? '');
    }

    /**
     * Ohne pds-api: den vorhandenen Kunden mit dieser Account-ID als Hauptaccount nehmen.
     */
    protected function useLocalAccount(int $accountId): void
    {
        $customer = Customer::withTrashed()->where('pds_account_id', $accountId)->orderBy('id')->first();

        if (! $customer) {
            $this->error = "Ohne Zugang zur pds-api lässt sich nur ein vorhandener Kunde wählen – keiner hat die Account-ID {$accountId}.";

            return;
        }

        $this->account = [
            'id' => $accountId,
            'name' => $customer->company_name ?: $customer->name,
            'email' => $customer->email,
            'address_line_1' => trim($customer->company_street.' '.$customer->company_house_number) ?: null,
            'zip_code' => $customer->company_postal_code,
            'city' => $customer->company_city,
            'local' => true,
        ];
        $this->agencyId = $customer->id;
    }

    public function updatedCsv(): void
    {
        $this->validateOnly('csv', ['csv' => ['nullable', 'file', 'mimes:csv,txt', 'max:10240']], [
            'csv.mimes' => 'Bitte eine CSV-Datei wählen.',
            'csv.max' => 'Die CSV-Datei ist zu groß (max. 10 MB).',
        ]);

        $this->resetChildren();
    }

    public function removeCsv(): void
    {
        $this->csv = null;
        $this->resetChildren();
    }

    public function agency(): ?Customer
    {
        return $this->agencyId ? Customer::withTrashed()->find($this->agencyId) : null;
    }

    public function importAccount(PdsAccountImportService $service): void
    {
        if (! $this->account || ($this->account['local'] ?? false)) {
            return;
        }

        ['status' => $status, 'customer' => $customer] = $service->import($this->account);

        $this->agencyId = $this->matchedCustomerId = $customer?->id;
        $this->targetCustomerId = (string) ($this->agencyId ?? '');
        $this->resetChildren();

        $this->dispatch('adminv2-toast', message: match ($status) {
            PdsAccountImportService::STATUS_CREATED => 'Kunde angelegt.',
            PdsAccountImportService::STATUS_UPDATED => 'Kunde ergänzt.',
            default => PdsAccountImportService::STATUS_LABELS[$status],
        });
    }

    public function loadChildren(PdsAccountImportService $service): void
    {
        $agency = $this->agency();

        if (! $this->account || ! $agency) {
            return;
        }

        $children = $this->children($service);

        if ($children === null) {
            return;
        }

        $this->childCounts = [];
        $this->childRows = [];

        $this->process($children, function (array $child, ?string $status) use ($service, $agency) {
            $customer = $service->findCustomer($child);
            $status ??= $service->plan($child, $agency);
            $this->childCounts[$status] = ($this->childCounts[$status] ?? 0) + 1;

            $this->childRows[] = [
                'id' => (int) $child['id'],
                'name' => $child['name'] ?? null,
                'email' => $child['email'] ?? null,
                'city' => trim(($child['zip_code'] ?? '').' '.($child['city'] ?? '')) ?: null,
                'status' => $status,
                'customer_id' => $customer?->id,
            ];
        });

        $this->childrenLoaded = true;
        $this->result = null;
        $this->error = null;
    }

    public function importChildren(PdsAccountImportService $service): void
    {
        $agency = $this->agency();

        if (! $this->account || ! $agency || $agency->trashed()) {
            return;
        }

        // Mehrere hundert Kunden samt Gruppen und Inhaber-Mitarbeiter.
        set_time_limit(600);

        $children = $this->children($service);

        if ($children === null) {
            return;
        }

        $result = [];

        $this->process($children, function (array $child, ?string $status) use ($service, $agency, &$result) {
            $status ??= $service->import($child, $agency)['status'];
            $result[$status] = ($result[$status] ?? 0) + 1;
        });

        $this->result = $result;

        // Vorschau auf den neuen Stand bringen.
        $this->loadChildren($service);
        $this->result = $result;

        $this->dispatch('adminv2-toast', message: ($result[PdsAccountImportService::STATUS_CREATED] ?? 0).' Kunden angelegt, '
            .($result[PdsAccountImportService::STATUS_UPDATED] ?? 0).' ergänzt.');
    }

    /**
     * Die Unteraccounts aus der CSV bzw. der pds-api; null bei einem Fehler.
     *
     * @return list<array<string, mixed>>|null
     */
    protected function children(PdsAccountImportService $service): ?array
    {
        try {
            if ($this->source === 'api') {
                return $service->fetchChildren((int) $this->account['id'], $this->onlyActive);
            }

            if (! $this->csv) {
                $this->error = 'Bitte zuerst die Importdatei wählen.';

                return null;
            }

            $rows = $service->parseCsv($this->csv->getRealPath());
        } catch (RuntimeException $e) {
            $this->error = $e->getMessage();

            return null;
        }

        // Nur Unteraccounts dieses Hauptaccounts, auf Wunsch nur aktive.
        $parentId = (int) $this->account['id'];
        $children = array_values(array_filter($rows, fn (array $row) => (data_get($row, 'parent.id') === null || (int) data_get($row, 'parent.id') === $parentId)
            && (! $this->onlyActive || (! filter_var(data_get($row, 'status.deleted'), FILTER_VALIDATE_BOOL)
                && (data_get($row, 'status.active') === null || filter_var(data_get($row, 'status.active'), FILTER_VALIDATE_BOOL))))));

        $this->csvSkipped = count($rows) - count($children);

        return $children;
    }

    /**
     * Ruft $callback je Unteraccount auf; eine E-Mail, die in der Liste schon
     * vorkam, kommt mit STATUS_DUPLICATE (sonst null) und wird nicht importiert.
     *
     * @param  list<array<string, mixed>>  $children
     * @param  callable(array<string, mixed>, ?string): void  $callback
     */
    protected function process(array $children, callable $callback): void
    {
        $seen = [];

        foreach ($children as $child) {
            $email = strtolower(trim((string) ($child['email'] ?? '')));
            $duplicate = $email !== '' && isset($seen[$email]);
            $seen[$email] = true;

            $callback($child, $duplicate ? PdsAccountImportService::STATUS_DUPLICATE : null);
        }
    }

    public function render()
    {
        $rows = collect($this->childRows);

        return view('livewire.admin-v2.customer-management.customers.pds-import', [
            'agency' => $this->agency(),
            'matched' => $this->matchedCustomerId ? Customer::withTrashed()->find($this->matchedCustomerId) : null,
            'labels' => PdsAccountImportService::STATUS_LABELS,
            'visibleRows' => match ($this->childFilter) {
                'existing' => $rows->whereNotNull('customer_id'),
                'new' => $rows->where('status', PdsAccountImportService::STATUS_CREATED),
                'skipped' => $rows->whereIn('status', [PdsAccountImportService::STATUS_INVALID, PdsAccountImportService::STATUS_DUPLICATE]),
                default => $rows,
            },
        ]);
    }
}
