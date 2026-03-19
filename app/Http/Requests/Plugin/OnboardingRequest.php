<?php

namespace App\Http\Requests\Plugin;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $integrationType = $this->input('integration_type', 'website');
        $domainRequired = in_array($integrationType, ['website', 'both']) ? 'required' : 'nullable';

        return [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'integration_type' => ['sometimes', 'string', 'in:website,app,both'],
            'domain' => [
                $domainRequired,
                'string',
                'max:255',
                'regex:/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'domain.regex' => 'Bitte geben Sie eine gültige Domain ein (z.B. example.com).',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('domain')) {
            // Normalize domain input
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
