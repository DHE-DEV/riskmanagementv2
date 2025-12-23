<?php

namespace App\Http\Requests\Plugin;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PluginRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Account
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . Customer::class],
            'password' => ['required', 'confirmed', Password::defaults()],

            // Contact
            'contact_name' => ['required', 'string', 'max:255'],

            // Company
            'company_name' => ['required', 'string', 'max:255'],
            'company_street' => ['required', 'string', 'max:255'],
            'company_house_number' => ['required', 'string', 'max:20'],
            'company_postal_code' => ['required', 'string', 'max:20'],
            'company_city' => ['required', 'string', 'max:255'],
            'company_country' => ['required', 'string', 'max:255'],

            // Business Type
            'business_types' => ['nullable', 'array'],
            'business_types.*' => ['in:travel_agency,organizer,online_provider,mobile_travel_consultant,software_provider,other'],

            // Domain
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/',
            ],

            // Terms
            'terms' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Diese E-Mail-Adresse ist bereits registriert.',
            'domain.regex' => 'Bitte geben Sie eine gültige Domain ein (z.B. example.com).',
            'terms.accepted' => 'Sie müssen die Nutzungsbedingungen akzeptieren.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'E-Mail-Adresse',
            'password' => 'Passwort',
            'contact_name' => 'Ansprechpartner',
            'company_name' => 'Firmenname',
            'company_street' => 'Straße',
            'company_house_number' => 'Hausnummer',
            'company_postal_code' => 'PLZ',
            'company_city' => 'Ort',
            'company_country' => 'Land',
            'domain' => 'Domain',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('domain')) {
            $domain = $this->input('domain');
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = preg_replace('#^www\.#', '', $domain);
            $domain = explode('/', $domain)[0];
            $domain = explode(':', $domain)[0];
            $domain = strtolower(trim($domain));

            $this->merge(['domain' => $domain]);
        }
    }
}
