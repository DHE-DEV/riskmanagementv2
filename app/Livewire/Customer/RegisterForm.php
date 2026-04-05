<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Services\KeycloakUserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class RegisterForm extends Component
{
    public int $step = 1;
    public int $totalSteps = 3;

    // Step 1: Konto
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $terms = false;

    // Step 2: Kundentyp
    public string $customer_type = '';
    public array $business_type = [];

    // Step 3: Firma/Adresse
    public string $company_name = '';
    public string $company_street = '';
    public string $company_house_number = '';
    public string $company_postal_code = '';
    public string $company_city = '';
    public string $company_country = 'DE';

    public bool $success = false;

    public const BUSINESS_TYPES = [
        'travel_agency' => 'Reisebüro',
        'organizer' => 'Veranstalter',
        'online_provider' => 'Online-Anbieter',
        'mobile_travel_consultant' => 'Mobiler Reiseberater',
        'software_provider' => 'Softwareanbieter',
        'cooperation' => 'Kooperation',
        'other' => 'Sonstiges',
    ];

    public const COUNTRIES = [
        'DE' => 'Deutschland',
        'AT' => 'Österreich',
        'CH' => 'Schweiz',
        'LU' => 'Luxemburg',
        'LI' => 'Liechtenstein',
    ];

    public function nextStep(): void
    {
        $this->validateStep();
        $this->step = min($this->step + 1, $this->totalSteps);
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function goToStep(int $step): void
    {
        // Only allow going back or to current step
        if ($step < $this->step) {
            $this->step = $step;
        }
    }

    public function updatedCustomerType(): void
    {
        if ($this->customer_type === 'private') {
            $this->business_type = [];
        }
    }

    public function toggleBusinessType(string $type): void
    {
        if (in_array($type, $this->business_type)) {
            $this->business_type = array_values(array_diff($this->business_type, [$type]));
        } else {
            $this->business_type[] = $type;
        }
    }

    public function submit(): void
    {
        $this->validateStep();

        try {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'customer_type' => $this->customer_type,
                'business_type' => $this->business_type,
            ];

            if ($this->customer_type === 'business') {
                $data['company_name'] = $this->company_name;
                $data['company_street'] = $this->company_street;
                $data['company_house_number'] = $this->company_house_number;
                $data['company_postal_code'] = $this->company_postal_code;
                $data['company_city'] = $this->company_city;
                $data['company_country'] = $this->company_country;
            }

            $customer = Customer::create($data);

            app(KeycloakUserService::class)->syncCustomer($customer);

            event(new Registered($customer));

            $this->success = true;
        } catch (\Exception $e) {
            $this->addError('submit', 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
        }
    }

    private function validateStep(): void
    {
        match ($this->step) {
            1 => $this->validate($this->step1Rules(), $this->step1Messages()),
            2 => $this->validate($this->step2Rules(), $this->step2Messages()),
            3 => $this->validate($this->step3Rules(), $this->step3Messages()),
        };
    }

    private function step1Rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ];
    }

    private function step1Messages(): array
    {
        return [
            'name.required' => 'Bitte geben Sie Ihren Namen ein.',
            'email.required' => 'Bitte geben Sie eine E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse ist bereits registriert.',
            'password.required' => 'Bitte geben Sie ein Passwort ein.',
            'password.confirmed' => 'Die Passwörter stimmen nicht überein.',
            'terms.accepted' => 'Bitte stimmen Sie den Nutzungsbedingungen zu.',
        ];
    }

    private function step2Rules(): array
    {
        $rules = [
            'customer_type' => ['required', 'in:private,business'],
        ];

        if ($this->customer_type === 'business') {
            $rules['business_type'] = ['required', 'array', 'min:1'];
            $rules['business_type.*'] = ['in:' . implode(',', array_keys(self::BUSINESS_TYPES))];
        }

        return $rules;
    }

    private function step2Messages(): array
    {
        return [
            'customer_type.required' => 'Bitte wählen Sie einen Kundentyp.',
            'business_type.required' => 'Bitte wählen Sie mindestens einen Geschäftstyp.',
        ];
    }

    private function step3Rules(): array
    {
        if ($this->customer_type !== 'business') {
            return [];
        }

        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_street' => ['required', 'string', 'max:255'],
            'company_house_number' => ['required', 'string', 'max:20'],
            'company_postal_code' => ['required', 'string', 'max:10'],
            'company_city' => ['required', 'string', 'max:255'],
            'company_country' => ['required', 'string', 'size:2'],
        ];
    }

    private function step3Messages(): array
    {
        return [
            'company_name.required' => 'Bitte geben Sie den Firmennamen ein.',
            'company_street.required' => 'Bitte geben Sie die Straße ein.',
            'company_house_number.required' => 'Bitte geben Sie die Hausnummer ein.',
            'company_postal_code.required' => 'Bitte geben Sie die Postleitzahl ein.',
            'company_city.required' => 'Bitte geben Sie den Ort ein.',
        ];
    }

    public function render()
    {
        return view('livewire.customer.register-form');
    }
}
