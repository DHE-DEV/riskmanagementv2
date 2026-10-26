<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Customer;
use App\Services\PdsAccountImportService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

/**
 * Kundenverwaltung > Kunden > Account aus pds-admin importieren.
 *
 * Einen einzelnen Account per PDS Account-ID abrufen und pruefen, ob es ihn
 * schon als Kunden gibt (per Account-ID, sonst per E-Mail). Nur wenn nicht,
 * wird er angelegt. Ist er Unteraccount eines Hauptaccounts, den es schon als
 * Kunden gibt, wird er diesem zugeordnet. Unteraccounts gesammelt importiert
 * der Kunden-Editor (Tab „Zugriff auf andere Accounts“, PdsChildrenImport).
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Account aus pds-admin importieren')]
class PdsImport extends Component
{
    use AuthorizesAdminV2;

    public string $pdsAccountId = '';

    /** @var array<string, mixed>|null Abgerufener Account */
    #[Locked]
    public ?array $account = null;

    /** Der vorhandene Kunde zu diesem Account (per Account-ID bzw. E-Mail). */
    #[Locked]
    public ?int $customerId = null;

    public ?string $error = null;

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

        $this->reset(['account', 'customerId', 'error']);

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
        $this->customerId = $service->findCustomer($account)?->id;
    }

    /**
     * Der Kunde des Hauptaccounts, falls der Account ein Unteraccount ist.
     */
    public function parentCustomer(): ?Customer
    {
        $parentId = (int) data_get($this->account, 'parent.id');

        return $parentId > 0
            ? Customer::where('pds_account_id', $parentId)->orderBy('id')->first()
            : null;
    }

    public function import(PdsAccountImportService $service): void
    {
        if (! $this->account) {
            return;
        }

        ['status' => $status, 'customer' => $customer] = $service->import($this->account, $this->parentCustomer());

        $this->customerId = $customer?->id;

        $this->dispatch('adminv2-toast', message: match ($status) {
            PdsAccountImportService::STATUS_CREATED => 'Kunde angelegt.',
            PdsAccountImportService::STATUS_UNCHANGED, PdsAccountImportService::STATUS_INVALID => PdsAccountImportService::STATUS_LABELS[$status],
            default => 'Kunde ergänzt.',
        });
    }

    public function render()
    {
        $service = app(PdsAccountImportService::class);
        $customer = $this->customerId ? Customer::withTrashed()->find($this->customerId) : null;
        $parent = $this->account ? $this->parentCustomer() : null;

        return view('livewire.admin-v2.customer-management.customers.pds-import', [
            'customer' => $customer,
            'parent' => $parent,
            // Was der Import am vorhandenen Kunden noch aendern wuerde.
            'plan' => $this->account && $customer ? $service->plan($this->account, $parent) : null,
            'labels' => PdsAccountImportService::STATUS_LABELS,
        ]);
    }
}
