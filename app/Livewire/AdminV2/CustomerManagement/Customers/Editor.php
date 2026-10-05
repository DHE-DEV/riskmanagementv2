<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kundenverwaltung > Kunde bearbeiten: Stammdaten, Einstellungen, GTM API,
 * Feature-Ueberschreibungen, Firmen- und Rechnungsadresse – darunter Filialen,
 * Account-Zugriffe, API Tokens und GTM API Logs des Kunden.
 *
 * Angelegt werden Kunden nicht im Admin: sie entstehen bei der Registrierung
 * bzw. beim ersten Login. Die "Neu"-Route fuehrt deshalb zurueck zur Liste.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, ListsCustomerGtmLogs, ManagesCustomerAccess, ManagesCustomerBranches, ManagesCustomerTokens, QueriesLists, WithPagination;

    /** Eintraege je Seite in den Listen unter dem Formular. */
    public const RELATION_PER_PAGE = 10;

    public const CUSTOMER_TYPES = ['private' => 'Privatkunde', 'business' => 'Firmenkunde'];

    public const BUSINESS_TYPES = [
        'travel_agency' => 'Reisebüro',
        'organizer' => 'Veranstalter',
        'online_provider' => 'Online Anbieter',
        'mobile_travel_consultant' => 'Mobiler Reiseberater',
        'cooperation' => 'Kooperation',
        'software_provider' => 'Softwareanbieter',
        'other' => 'Sonstiges',
    ];

    public const TABS = [
        'branches' => 'Filialen',
        'access' => 'Zugriff auf andere Accounts',
        'tokens' => 'API Tokens',
        'gtm-logs' => 'GTM API Logs',
    ];

    protected const ADDRESS_FIELDS = ['name', 'additional', 'street', 'house_number', 'postal_code', 'city', 'country'];

    #[Locked]
    public ?int $customerId = null;

    /** Liste unter dem Formular, die gerade gezeigt wird. */
    #[Url(except: 'branches')]
    public string $tab = 'branches';

    // Allgemeine Informationen
    public string $name = '';

    public string $email = '';

    public string $customerType = '';

    /** @var array<int, string> */
    public array $businessTypes = [];

    /** Format des Eingabefelds: Y-m-d\TH:i */
    public string $emailVerifiedAt = '';

    // Einstellungen
    public bool $directoryListingActive = false;

    public bool $branchManagementActive = false;

    public bool $hideProfileCompletion = false;

    // GTM API
    public bool $gtmApiEnabled = false;

    public string $gtmApiRateLimit = '60';

    /** @var array<string, string> Feature => '' (Standard), '1' (aktiviert), '0' (deaktiviert) */
    public array $featureOverrides = [];

    /** @var array<string, string> Firmenadresse (company_*) */
    public array $company = [];

    /** @var array<string, string> Rechnungsadresse (billing_*) */
    public array $billing = [];

    /** Aktion, zu der gerade die Rueckfrage offen ist: delete, restore, force. */
    #[Locked]
    public ?string $pendingAction = null;

    public function mount(?int $customer = null)
    {
        if ($customer === null) {
            session()->flash('adminv2-toast', 'Kunden werden nicht im Admin angelegt – sie entstehen bei der Registrierung bzw. beim ersten Login.');

            return $this->redirectRoute('adminv2.customer-management.customers.index');
        }

        // Auch geloeschte Kunden lassen sich oeffnen (und wiederherstellen).
        $model = Customer::withTrashed()->with('featureOverrides')->findOrFail($customer);

        $this->customerId = $model->id;
        $this->name = (string) $model->name;
        $this->email = (string) $model->email;
        $this->customerType = (string) $model->customer_type;
        // Nur Werte der Auswahlliste – Altbestand ausserhalb davon bleibt beim Speichern erhalten.
        $this->businessTypes = array_values(array_intersect(array_keys(self::BUSINESS_TYPES), $model->business_type ?? []));
        $this->emailVerifiedAt = $model->email_verified_at?->format('Y-m-d\TH:i') ?? '';
        $this->directoryListingActive = (bool) $model->directory_listing_active;
        $this->branchManagementActive = (bool) $model->branch_management_active;
        $this->hideProfileCompletion = (bool) $model->hide_profile_completion;
        $this->gtmApiEnabled = (bool) $model->gtm_api_enabled;
        $this->gtmApiRateLimit = (string) ($model->gtm_api_rate_limit ?? 60);

        foreach (CustomerFeatureOverride::getFeatureKeys() as $key) {
            $value = $model->featureOverrides?->{$key};
            $this->featureOverrides[$key] = $value === null ? '' : ($value ? '1' : '0');
        }

        foreach (self::ADDRESS_FIELDS as $field) {
            $this->company[$field] = (string) $model->{'company_'.$field};
            $this->billing[$field] = (string) $model->{'billing_'.($field === 'name' ? 'company_name' : $field)};
        }

        if (! isset(self::TABS[$this->tab])) {
            $this->tab = 'branches';
        }

        $this->branchForm = $this->emptyBranchForm();
    }

    #[Computed]
    public function customer(): ?Customer
    {
        return $this->customerId ? Customer::withTrashed()->findOrFail($this->customerId) : null;
    }

    /**
     * Laender fuer Firmen- und Rechnungsadresse: ISO-Code => deutscher Name.
     *
     * @return array<int, array{value: string, label: string, code: string}>
     */
    #[Computed]
    public function countryOptions(): array
    {
        return Country::all()
            ->filter(fn (Country $country) => filled($country->iso_code))
            ->sortBy(fn (Country $country) => $country->getName('de'), SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn (Country $country) => ['value' => $country->iso_code, 'label' => $country->getName('de'), 'code' => $country->iso_code])
            ->values()
            ->all();
    }

    /**
     * Laenderliste fuer ein Adressfeld. Ein gespeicherter Wert, der kein
     * bekannter ISO-Code ist (z. B. ein ausgeschriebener Name aus einem Import),
     * bleibt als eigener Eintrag waehlbar und geht beim Speichern nicht verloren.
     *
     * @return array<int, array{value: string, label: string, code: string}>
     */
    public function countryOptionsFor(string $current): array
    {
        $options = $this->countryOptions;

        if ($current !== '' && ! collect($options)->contains('value', $current)) {
            array_unshift($options, ['value' => $current, 'label' => $current.' (gespeicherter Wert)', 'code' => '']);
        }

        return $options;
    }

    /**
     * Faengt einen Stand ab, auf dem die Spalte noch fehlt.
     */
    public function hasPdsAccountId(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasColumn('customers', 'pds_account_id');
    }

    public function showTab(string $tab): void
    {
        if (isset(self::TABS[$tab])) {
            $this->tab = $tab;
        }
    }

    protected function rules(): array
    {
        $address = fn (string $prefix) => [
            $prefix.'.name' => ['nullable', 'string', 'max:255'],
            $prefix.'.additional' => ['nullable', 'string', 'max:255'],
            $prefix.'.street' => ['nullable', 'string', 'max:255'],
            $prefix.'.house_number' => ['nullable', 'string', 'max:20'],
            $prefix.'.postal_code' => ['nullable', 'string', 'max:20'],
            $prefix.'.city' => ['nullable', 'string', 'max:255'],
            $prefix.'.country' => ['nullable', 'string', 'max:255'],
        ];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'customerType' => ['nullable', Rule::in(['', ...array_keys(self::CUSTOMER_TYPES)])],
            'businessTypes' => ['array'],
            'businessTypes.*' => [Rule::in(array_keys(self::BUSINESS_TYPES))],
            'emailVerifiedAt' => ['nullable', 'date'],
            'gtmApiRateLimit' => ['required', 'integer', 'min:1', 'max:1000'],
            'featureOverrides.*' => ['nullable', Rule::in(['', '0', '1'])],
            ...$address('company'),
            ...$address('billing'),
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Bitte einen Namen eingeben.',
            'email.required' => 'Bitte eine E-Mail-Adresse eingeben.',
            'email.email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
            'emailVerifiedAt.date' => 'Bitte ein gültiges Datum angeben.',
            'gtmApiRateLimit.required' => 'Bitte ein Rate Limit angeben.',
            'gtmApiRateLimit.integer' => 'Das Rate Limit ist eine ganze Zahl.',
            'gtmApiRateLimit.min' => 'Das Rate Limit muss mindestens 1 sein.',
            'gtmApiRateLimit.max' => 'Das Rate Limit darf höchstens 1000 sein.',
            '*.house_number.max' => 'Höchstens 20 Zeichen.',
            '*.postal_code.max' => 'Höchstens 20 Zeichen.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $customer = $this->customer;
        $text = fn ($value) => trim((string) $value) === '' ? null : trim((string) $value);

        $attributes = [
            'name' => trim($this->name),
            'email' => trim($this->email),
            'customer_type' => $this->customerType === '' ? null : $this->customerType,
            'directory_listing_active' => $this->directoryListingActive,
            'branch_management_active' => $this->branchManagementActive,
            'hide_profile_completion' => $this->hideProfileCompletion,
            'gtm_api_enabled' => $this->gtmApiEnabled,
            'gtm_api_rate_limit' => (int) $this->gtmApiRateLimit,
        ];

        // Ein bisher leerer Geschaeftstyp bleibt leer (null), solange nichts gewaehlt wird.
        $businessTypes = array_values(array_intersect(array_keys(self::BUSINESS_TYPES), $this->businessTypes));
        // Bisherige Werte ausserhalb der Auswahlliste (Altbestand) bleiben erhalten.
        $legacyTypes = array_values(array_diff($customer->business_type ?? [], array_keys(self::BUSINESS_TYPES)));
        $attributes['business_type'] = $customer->business_type === null && $businessTypes === []
            ? null
            : [...$legacyTypes, ...$businessTypes];

        // Der Zeitpunkt wird nur angefasst, wenn er im Formular geaendert wurde –
        // das Eingabefeld kennt keine Sekunden.
        if ($this->emailVerifiedAt !== ($customer->email_verified_at?->format('Y-m-d\TH:i') ?? '')) {
            $attributes['email_verified_at'] = $this->emailVerifiedAt === '' ? null : Carbon::parse($this->emailVerifiedAt);
        }

        foreach (self::ADDRESS_FIELDS as $field) {
            $attributes['company_'.$field] = $text($this->company[$field] ?? '');
            $attributes['billing_'.($field === 'name' ? 'company_name' : $field)] = $text($this->billing[$field] ?? '');
        }

        // Ueber save() – der CustomerObserver gleicht bei geaenderter Adresse
        // bzw. geaendertem Adressverzeichnis die Standorte ab.
        $customer->fill($attributes)->save();

        $this->saveFeatureOverrides($customer);

        unset($this->customer);

        $this->dispatch('adminv2-toast', message: 'Gespeichert.');
    }

    /**
     * Feature-Ueberschreibungen: '' = globale Einstellung (null). Ein Datensatz
     * entsteht erst, wenn mindestens ein Feature ueberschrieben wird.
     */
    protected function saveFeatureOverrides(Customer $customer): void
    {
        $values = [];

        foreach (CustomerFeatureOverride::getFeatureKeys() as $key) {
            $values[$key] = match ($this->featureOverrides[$key] ?? '') {
                '1' => true,
                '0' => false,
                default => null,
            };
        }

        $overrides = $customer->featureOverrides()->first();

        if (! $overrides && array_filter($values, fn ($value) => $value !== null) === []) {
            return;
        }

        ($overrides ?? $customer->featureOverrides()->make())->fill($values)->save();
    }

    /**
     * Rueckfrage zum Loeschen, Wiederherstellen bzw. endgueltigen Loeschen.
     */
    public function confirmAction(string $action): void
    {
        $trashed = $this->customer->trashed();

        // Loeschen nur, solange der Kunde nicht im Papierkorb liegt – alles andere nur dort.
        if (! in_array($action, $trashed ? ['restore', 'force'] : ['delete'], true)) {
            return;
        }

        $this->pendingAction = $action;

        $this->modal('customer-confirm')->show();
    }

    public function runPendingAction()
    {
        $action = $this->pendingAction;
        $customer = $this->customer;
        $label = Index::label($customer);

        $this->modal('customer-confirm')->close();
        $this->pendingAction = null;

        if ($action === 'delete' && ! $customer->trashed()) {
            $customer->delete();
            session()->flash('adminv2-toast', '„'.$label.'“ gelöscht – der Kunde liegt jetzt im Papierkorb.');

            return $this->redirectRoute('adminv2.customer-management.customers.index');
        }

        if ($action === 'restore' && $customer->trashed()) {
            $customer->restore();
            unset($this->customer);
            $this->dispatch('adminv2-toast', message: '„'.$label.'“ wurde wiederhergestellt.');

            return null;
        }

        if ($action === 'force' && $customer->trashed()) {
            try {
                $customer->forceDelete();
            } catch (QueryException) {
                $this->dispatch('adminv2-toast', message: '„'.$label.'“ wird an anderer Stelle noch verwendet und lässt sich nicht endgültig löschen.', variant: 'danger');

                return null;
            }

            session()->flash('adminv2-toast', '„'.$label.'“ wurde endgültig gelöscht.');

            return $this->redirectRoute('adminv2.customer-management.customers.index', ['trashed' => 'only']);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.customers.editor')
            ->title($this->customer ? Index::label($this->customer) : 'Kunden');
    }
}
