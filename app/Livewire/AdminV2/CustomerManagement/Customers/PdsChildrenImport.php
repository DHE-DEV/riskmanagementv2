<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Customer;
use App\Services\PdsAccountImportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * Kunde > Unteraccounts > Unteraccounts aus pds-admin importieren.
 *
 * Die Unteraccounts des PDS-Accounts dieses Kunden – direkt aus pds-admin
 * (__internal/admin/accounts/{ID}/children) oder aus einem CSV-Export – mit
 * den Kunden abgleichen (Account-ID, dann E-Mail). Nur fehlende werden
 * angelegt; alle werden diesem Kunden zugeordnet (assign_to) und fuer ihn
 * freigegeben (Account-Zugriff) – auch vorhandene, solche im Papierkorb und
 * solche einer anderen Agentur.
 *
 * Der Import laeuft in Portionen (BATCH_SIZE je Anfrage), damit er nicht an
 * die maximale Laufzeit einer Anfrage stoesst: die geladenen Unteraccounts
 * liegen solange im Cache, der Browser ruft importNext() bis zum Ende auf.
 * Bricht er ab, setzt ein neuer Lauf einfach fort – Vorhandenes wird erkannt.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Unteraccounts aus pds-admin importieren')]
class PdsChildrenImport extends Component
{
    use AuthorizesAdminV2;
    use WithFileUploads;

    public const SOURCES = [
        'api' => 'Direkt aus pds-admin (Schnittstelle)',
        'csv' => 'Aus Importdatei (CSV-Export)',
    ];

    /** Unteraccounts je Anfrage beim Import. */
    public const BATCH_SIZE = 20;

    public const FILTERS = [
        'all' => 'Alle',
        'existing' => 'Schon in der Plattform',
        'new' => 'Werden neu angelegt',
        'skipped' => 'Werden übersprungen (ohne E-Mail, doppelt)',
    ];

    #[Locked]
    public int $customerId;

    /** PDS Account-ID des Kunden; fehlt sie, wird sie hier eingetragen. */
    public string $pdsAccountId = '';

    /** Woher die Unteraccounts kommen, siehe SOURCES. */
    public string $source = 'api';

    /** Nur aktive, nicht geloeschte Unteraccounts importieren. */
    public bool $onlyActive = true;

    public ?TemporaryUploadedFile $csv = null;

    /** Vorschau-Filter, siehe FILTERS. */
    public string $childFilter = 'all';

    /** @var array<string, int> Status => Anzahl der Unteraccounts */
    #[Locked]
    public array $childCounts = [];

    /** @var list<array{id: int, name: ?string, email: ?string, city: ?string, status: string, customer_id: ?int}> */
    #[Locked]
    public array $childRows = [];

    /** CSV-Zeilen, die nicht zu diesem Hauptaccount passen bzw. inaktiv sind. */
    #[Locked]
    public int $csvSkipped = 0;

    #[Locked]
    public bool $childrenLoaded = false;

    /** @var array<string, int>|null Ergebnis des letzten bzw. laufenden Imports */
    #[Locked]
    public ?array $result = null;

    /** Cache-Schluessel des laufenden Imports; null, wenn keiner laeuft. */
    #[Locked]
    public ?string $runKey = null;

    #[Locked]
    public int $runDone = 0;

    #[Locked]
    public int $runTotal = 0;

    public ?string $error = null;

    public function mount(int $customer, PdsAccountImportService $service): void
    {
        $model = Customer::findOrFail($customer);

        $this->customerId = $model->id;
        $this->pdsAccountId = (string) ($model->pds_account_id ?? '');
        $this->source = $service->isConfigured() ? 'api' : 'csv';
    }

    public function customer(): Customer
    {
        return Customer::findOrFail($this->customerId);
    }

    public function isConfigured(): bool
    {
        return app(PdsAccountImportService::class)->isConfigured();
    }

    /**
     * Account-ID am Kunden eintragen, wenn sie noch fehlt.
     */
    public function savePdsAccountId(): void
    {
        $this->validate(['pdsAccountId' => ['required', 'regex:/^\s*[1-9]\d{0,18}\s*$/']], [
            'pdsAccountId.required' => 'Bitte die PDS Account-ID des Kunden eingeben.',
            'pdsAccountId.regex' => 'Die PDS Account-ID besteht nur aus Ziffern.',
        ]);

        $customer = $this->customer();

        if ($customer->pds_account_id === null) {
            $customer->forceFill(['pds_account_id' => (int) trim($this->pdsAccountId)])->save();
        }

        $this->pdsAccountId = (string) $customer->pds_account_id;
        $this->resetChildren();
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

    protected function resetChildren(): void
    {
        $this->reset(['childCounts', 'childRows', 'csvSkipped', 'childrenLoaded', 'result', 'error']);
    }

    public function loadChildren(PdsAccountImportService $service): void
    {
        $agency = $this->customer();
        $children = $this->children($service, $agency);

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

    /**
     * Startet den Import: laedt die Unteraccounts einmal und arbeitet sie in
     * Portionen ab (importNext).
     */
    public function importChildren(PdsAccountImportService $service): void
    {
        if ($this->runKey) {
            return;
        }

        $children = $this->children($service, $this->customer());

        if ($children === null) {
            return;
        }

        // Je Unteraccount: [Account, vorab feststehender Status (doppelte E-Mail) oder null].
        $queue = [];
        $this->process($children, function (array $child, ?string $status) use (&$queue) {
            $queue[] = [$child, $status];
        });

        $this->runKey = 'adminv2:pds-children-import:'.Str::uuid();
        $this->runDone = 0;
        $this->runTotal = count($queue);
        $this->result = [];
        $this->error = null;

        Cache::put($this->runKey, $queue, now()->addHours(2));

        $this->importNext($service);
    }

    /**
     * Die naechste Portion des laufenden Imports.
     */
    public function importNext(PdsAccountImportService $service): void
    {
        if (! $this->runKey) {
            return;
        }

        $queue = Cache::get($this->runKey);

        if (! is_array($queue)) {
            $this->runKey = null;
            $this->error = 'Der Import wurde unterbrochen. Einfach erneut prüfen und importieren – bereits Importiertes wird erkannt.';

            return;
        }

        $agency = $this->customer();
        $result = $this->result ?? [];

        foreach (array_slice($queue, $this->runDone, self::BATCH_SIZE) as [$child, $status]) {
            $status ??= $service->import($child, $agency)['status'];
            $result[$status] = ($result[$status] ?? 0) + 1;
            $this->runDone++;
        }

        $this->result = $result;

        if ($this->runDone < $this->runTotal) {
            $this->js('$wire.importNext()');

            return;
        }

        Cache::forget($this->runKey);
        $this->runKey = null;

        // Vorschau auf den neuen Stand bringen.
        $this->loadChildren($service);
        $this->result = $result;

        $created = $result[PdsAccountImportService::STATUS_CREATED] ?? 0;
        $this->dispatch('adminv2-toast', message: "{$created} Kunden angelegt, ".($this->runTotal - $created).' geprüft bzw. zugeordnet.');
    }

    /**
     * Die Unteraccounts aus der pds-api bzw. der CSV; null bei einem Fehler.
     *
     * @return list<array<string, mixed>>|null
     */
    protected function children(PdsAccountImportService $service, Customer $agency): ?array
    {
        $parentId = (int) $agency->pds_account_id;

        if ($parentId <= 0) {
            $this->error = 'Der Kunde hat noch keine PDS Account-ID.';

            return null;
        }

        try {
            if ($this->source === 'api') {
                return $service->fetchChildren($parentId, $this->onlyActive);
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

        return view('livewire.admin-v2.customer-management.customers.pds-children-import', [
            'agency' => $this->customer(),
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
