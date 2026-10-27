<?php

namespace App\Livewire\AdminV2\System\Cooperations;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\Cooperation;
use App\Models\Customer;
use App\Models\CustomerBillingPeriod;
use App\Rules\EmailList;
use App\Support\OrgMailSettings;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Eine Kooperation anlegen oder bearbeiten: Name, eigener Account, wie
 * Mitglieder abgerechnet werden, Intervall, Kickback – dazu die Mitglieder
 * (Abrechnungszeitraeume der Kunden).
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, QueriesLists;

    #[Locked]
    public ?int $cooperationId = null;

    public string $name = '';

    public string $customerId = '';

    public string $customerSearch = '';

    public string $memberBilling = 'cooperation';

    public string $billingInterval = 'monthly';

    /** Festbetrag pro Buero und Monat (leer = keiner) */
    public string $fixedPricePerOffice = '';

    public string $kickbackType = 'none';

    public string $kickbackValue = '';

    /** @var array{to: string, cc: string, bcc: string} Rechnungsempfaenger, je Feld kommagetrennt */
    public array $invoiceEmails = ['to' => '', 'cc' => '', 'bcc' => ''];

    public string $notes = '';

    public bool $isActive = true;

    /** Mitglieder: aktuelle, kuenftige oder alle Zeitraeume */
    public string $memberFilter = 'current';

    public function mount($cooperation = null): void
    {
        if ($cooperation === null) {
            return;
        }

        $model = Cooperation::findOrFail((int) $cooperation);

        $this->cooperationId = $model->id;
        $this->name = $model->name;
        $this->customerId = (string) ($model->customer_id ?? '');
        $this->memberBilling = $model->member_billing;
        $this->billingInterval = $model->billing_interval;
        $this->fixedPricePerOffice = $model->fixed_price_per_office !== null ? str_replace('.', ',', (string) $model->fixed_price_per_office) : '';
        $this->kickbackType = $model->kickback_type;
        $this->kickbackValue = $model->kickback_value !== null ? str_replace('.', ',', (string) $model->kickback_value) : '';
        foreach (['to', 'cc', 'bcc'] as $kind) {
            $this->invoiceEmails[$kind] = implode(', ', $model->invoice_emails[$kind] ?? []);
        }
        $this->notes = (string) $model->notes;
        $this->isActive = (bool) $model->is_active;
    }

    #[Computed]
    public function cooperation(): ?Cooperation
    {
        return $this->cooperationId ? Cooperation::find($this->cooperationId) : null;
    }

    #[Computed]
    public function selectedCustomer(): ?Customer
    {
        return $this->customerId !== '' ? Customer::find((int) $this->customerId, ['id', 'company_name', 'name', 'email', 'app_code']) : null;
    }

    #[Computed]
    public function customerCandidates(): Collection
    {
        if (mb_strlen($term = trim($this->customerSearch)) < 2) {
            return collect();
        }

        $query = Customer::query();
        $this->whereEveryWord($query, $term, ['company_name', 'email', 'app_code']);

        return $query->orderBy('company_name')->limit(10)->get(['id', 'company_name', 'name', 'email', 'app_code']);
    }

    public function chooseCustomer(?int $customerId): void
    {
        $this->customerId = $customerId ? (string) $customerId : '';
        $this->customerSearch = '';
        unset($this->selectedCustomer);
    }

    #[Computed]
    public function members(): Collection
    {
        if (! $this->cooperationId) {
            return collect();
        }

        $query = CustomerBillingPeriod::query()->where('cooperation_id', $this->cooperationId)
            ->with('customer:id,company_name,name,email,app_code')
            ->orderByDesc('starts_on');

        return match ($this->memberFilter) {
            'current' => $query->current()->get(),
            'future' => $query->whereDate('starts_on', '>', today())->get(),
            default => $query->get(),
        };
    }

    public function save()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'customerId' => ['nullable', Rule::exists('customers', 'id')],
            'memberBilling' => ['required', Rule::in(array_keys(Cooperation::MEMBER_BILLING))],
            'billingInterval' => ['required', Rule::in(array_keys(Cooperation::BILLING_INTERVALS))],
            'fixedPricePerOffice' => ['nullable', 'regex:/^\d{1,8}([.,]\d{1,2})?$/'],
            'kickbackType' => ['required', Rule::in(array_keys(Cooperation::KICKBACK_TYPES))],
            'kickbackValue' => ['nullable', 'required_unless:kickbackType,none', 'regex:/^\d{1,8}([.,]\d{1,2})?$/'],
            'invoiceEmails.*' => [new EmailList],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'name.required' => 'Bitte den Namen der Kooperation eingeben.',
            'kickbackValue.required_unless' => 'Bitte den Kickback angeben (Prozent oder Betrag).',
            'fixedPricePerOffice.regex' => 'Der Festbetrag ist ein Betrag mit höchstens zwei Nachkommastellen, z. B. 4,90.',
            'kickbackValue.regex' => 'Der Kickback ist eine Zahl mit höchstens zwei Nachkommastellen, z. B. 10 oder 2,50.',
        ]);

        if ($this->kickbackType === 'percent' && (float) str_replace(',', '.', $this->kickbackValue) > 100) {
            $this->addError('kickbackValue', 'Mehr als 100 % Kickback sind nicht möglich.');

            return null;
        }

        $invoiceEmails = array_map(fn ($value) => OrgMailSettings::emails($value), $this->invoiceEmails);

        $model = $this->cooperation ?? new Cooperation;
        $created = ! $model->exists;

        $model->fill([
            'name' => trim($this->name),
            'customer_id' => $this->customerId !== '' ? (int) $this->customerId : null,
            'member_billing' => $this->memberBilling,
            'billing_interval' => $this->billingInterval,
            'fixed_price_per_office' => trim($this->fixedPricePerOffice) !== '' ? str_replace(',', '.', trim($this->fixedPricePerOffice)) : null,
            'kickback_type' => $this->kickbackType,
            'kickback_value' => $this->kickbackType === 'none' ? null : str_replace(',', '.', $this->kickbackValue),
            'invoice_emails' => array_filter($invoiceEmails) ? $invoiceEmails : null,
            'notes' => trim($this->notes) ?: null,
            'is_active' => $this->isActive,
        ])->save();

        session()->flash('adminv2-toast', $created ? '„'.$model->name.'“ angelegt.' : '„'.$model->name.'“ gespeichert.');

        return $this->redirectRoute('adminv2.system.cooperations.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.cooperations.editor')
            ->title($this->cooperation ? $this->cooperation->name : 'Neue Kooperation');
    }
}
