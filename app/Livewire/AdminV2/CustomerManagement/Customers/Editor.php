<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Services\CustomerFeaturePreauthorizationService;
use App\Services\CustomerFeatureService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
 * Feature-Ueberschreibungen, Firmen- und Rechnungsadresse – darunter Filialen, Unteraccounts,
 * Account-Zugriffe, API Tokens und GTM API Logs des Kunden.
 *
 * Angelegt werden Kunden nicht im Admin: sie entstehen bei der Registrierung
 * bzw. beim ersten Login. Die "Neu"-Route fuehrt deshalb zurueck zur Liste.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, ListsCustomerGtmLogs, ListsCustomerSubAccounts, ManagesCustomerAccess, ManagesCustomerBranches, ManagesCustomerTokens, QueriesLists, WithPagination;

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
        'sub-accounts' => 'Unteraccounts',
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

    /** Zugeordnete Agenturen auch als Adressen anzeigen (Organisation, /branches). */
    public bool $showSubAccountsAsBranches = false;

    /** Zentrale: Feature-Ueberschreibungen an die Unteraccounts vererben. */
    public bool $inheritFeaturesToSubAccounts = false;

    public bool $hideProfileCompletion = false;

    // GTM API
    public bool $gtmApiEnabled = false;

    public string $gtmApiRateLimit = '60';

    /** @var array<string, string> Feature => '' (Standard), '1' (aktiviert), '0' (deaktiviert) */
    public array $featureOverrides = [];

    /**
     * Account-ID aus dem Travel Requirements Service (pds-admin). Kommt sonst
     * beim SSO-Login mit; hier laesst sie sich vorab von Hand zuordnen.
     */
    public string $pdsAccountId = '';

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
        $this->showSubAccountsAsBranches = (bool) $model->show_sub_accounts_as_branches;
        $this->inheritFeaturesToSubAccounts = (bool) $model->inherit_features_to_sub_accounts;
        $this->hideProfileCompletion = (bool) $model->hide_profile_completion;
        $this->gtmApiEnabled = (bool) $model->gtm_api_enabled;
        $this->gtmApiRateLimit = (string) ($model->gtm_api_rate_limit ?? 60);

        $this->fillFeatureOverrides($model->featureOverrides);
        $this->subAccountsNote = (string) ($model->sub_accounts_note ?? '');

        if ($this->hasPdsAccountId()) {
            $this->pdsAccountId = (string) $model->pds_account_id;
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

    /**
     * Weitere Kunden mit derselben PDS Account-ID – erlaubt (z. B. mehrere
     * Logins eines Accounts), aber als Hinweis gegen Tippfehler sichtbar.
     *
     * @return Collection<int, Customer>
     */
    #[Computed]
    public function sharedPdsAccountCustomers(): Collection
    {
        $accountId = trim($this->pdsAccountId);

        if (! $this->hasPdsAccountId() || ! ctype_digit($accountId)) {
            return collect();
        }

        return Customer::query()
            ->where('pds_account_id', (int) $accountId)
            ->whereKeyNot($this->customerId)
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'name', 'company_name', 'email', 'app_code']);
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
            'pdsAccountId' => ['nullable', 'regex:/^\s*[1-9]\d{0,18}\s*$/'],
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
            'pdsAccountId.regex' => 'Die PDS Account-ID besteht nur aus Ziffern.',
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
            'show_sub_accounts_as_branches' => $this->showSubAccountsAsBranches,
            'inherit_features_to_sub_accounts' => $this->inheritFeaturesToSubAccounts,
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

        $previousPdsAccountId = null;

        if ($this->hasPdsAccountId()) {
            $previousPdsAccountId = $customer->pds_account_id;
            $attributes['pds_account_id'] = trim($this->pdsAccountId) === '' ? null : (int) trim($this->pdsAccountId);
        }

        foreach (self::ADDRESS_FIELDS as $field) {
            $attributes['company_'.$field] = $text($this->company[$field] ?? '');
            $attributes['billing_'.($field === 'name' ? 'company_name' : $field)] = $text($this->billing[$field] ?? '');
        }

        // Ueber save() – der CustomerObserver gleicht bei geaenderter Adresse
        // bzw. geaendertem Adressverzeichnis die Standorte ab.
        $customer->fill($attributes)->save();

        $this->saveFeatureOverrides($customer);
        // Vererbung an die Unteraccounts kann sich geaendert haben.
        app(CustomerFeatureService::class)->clearCache($customer->id);
        unset($this->subAccountFeatureDeviations);

        // Neu zugeordnete Account-ID: Vormerkungen sofort einloesen, nicht erst
        // beim naechsten Login. Erst nach den Overrides, sonst ueberschriebe das
        // Formular die eingeloesten Werte wieder.
        $applied = [];

        if ($customer->pds_account_id && (int) $customer->pds_account_id !== (int) $previousPdsAccountId) {
            $applied = app(CustomerFeaturePreauthorizationService::class)->applyForCustomer($customer);

            if ($applied !== []) {
                $this->fillFeatureOverrides($customer->featureOverrides()->first());
            }
        }

        unset($this->customer, $this->sharedPdsAccountCustomers);

        $this->dispatch('adminv2-toast', message: $applied === []
            ? 'Gespeichert.'
            : 'Gespeichert. '.count($applied).' vorgemerkte Feature-Freischaltung(en) übernommen.');
    }

    /**
     * Zentrale: je Feature, wie viele Unteraccounts selbst einen Wert gesetzt
     * haben und damit von der Zentrale abweichen.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function subAccountFeatureDeviations(): array
    {
        $subAccountIds = Customer::query()->where('assign_to', $this->customerId)->select('id');
        $deviations = [];

        foreach (CustomerFeatureOverride::getFeatureKeys() as $key) {
            $count = CustomerFeatureOverride::query()->whereIn('customer_id', $subAccountIds)->whereNotNull($key)->count();

            if ($count > 0) {
                $deviations[$key] = $count;
            }
        }

        return $deviations;
    }

    /**
     * Abweichungen der Unteraccounts bei einem Feature (oder allen) auf
     * "Standard" zuruecksetzen – danach gilt dort wieder die Zentrale.
     */
    public function resetSubAccountFeatures(string $key): void
    {
        $keys = $key === 'all' ? CustomerFeatureOverride::getFeatureKeys() : [$key];

        if (array_diff($keys, CustomerFeatureOverride::getFeatureKeys()) !== []) {
            return;
        }

        $subAccountIds = Customer::query()->where('assign_to', $this->customerId)->pluck('id');
        $service = app(CustomerFeatureService::class);
        $count = 0;

        // Einzeln speichern, damit der Observer den Cache je Unteraccount leert.
        CustomerFeatureOverride::query()->whereIn('customer_id', $subAccountIds)
            ->where(function ($query) use ($keys) {
                foreach ($keys as $column) {
                    $query->orWhereNotNull($column);
                }
            })
            ->each(function (CustomerFeatureOverride $override) use ($keys, $service, &$count) {
                $override->forceFill(array_fill_keys($keys, null))->save();
                $service->clearCache($override->customer_id);
                $count++;
            });

        unset($this->subAccountFeatureDeviations);

        $label = $key === 'all' ? 'allen Features' : '„'.(CustomerFeatureOverride::getFeatureLabels()[$key] ?? $key).'“';
        $this->dispatch('adminv2-toast', message: "Abweichungen bei {$label} zurückgesetzt ({$count} Unteraccounts).");
    }

    protected function fillFeatureOverrides(?CustomerFeatureOverride $overrides): void
    {
        foreach (CustomerFeatureOverride::getFeatureKeys() as $key) {
            $value = $overrides?->{$key};
            $this->featureOverrides[$key] = $value === null ? '' : ($value ? '1' : '0');
        }
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
