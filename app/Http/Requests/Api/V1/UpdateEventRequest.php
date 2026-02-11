<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['nullable', 'string', 'in:info,low,medium,high'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'event_type_codes' => ['sometimes', 'required', 'array', 'min:1'],
            'event_type_codes.*' => ['string', 'exists:event_types,code'],
            'country_codes' => ['sometimes', 'required', 'array', 'min:1'],
            'country_codes.*' => ['string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
