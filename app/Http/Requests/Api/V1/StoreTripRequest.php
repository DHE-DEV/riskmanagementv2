<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by Sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Schema version
            'schema_version' => ['required', 'string', Rule::in(['1.0', '1.1'])],

            // Provider validation
            'provider' => ['required', 'array'],
            'provider.id' => ['required', 'string', 'max:64'],
            'provider.name' => ['nullable', 'string', 'max:255'],
            'provider.sent_at' => ['required', 'date'],

            // Trip validation
            'trip' => ['required', 'array'],
            'trip.external_trip_id' => ['required', 'string', 'max:128'],
            'trip.booking_reference' => ['nullable', 'string', 'max:64'],

            // Itinerary validation
            'trip.itinerary' => ['required', 'array', 'min:1'],
            'trip.itinerary.*.type' => ['required', Rule::in(['travel', 'stay'])],

            // Travel items (air legs)
            'trip.itinerary.*.mode' => [
                'required_if:trip.itinerary.*.type,travel',
                Rule::in(['air', 'rail', 'bus', 'ferry', 'car']),
            ],
            'trip.itinerary.*.leg_id' => ['required_if:trip.itinerary.*.type,travel', 'string', 'max:64'],
            'trip.itinerary.*.segments' => ['required_if:trip.itinerary.*.type,travel', 'array', 'min:1'],

            // Flight segments validation
            'trip.itinerary.*.segments.*.segment_id' => ['required', 'string', 'max:64'],

            // Departure validation
            'trip.itinerary.*.segments.*.departure' => ['required', 'array'],
            'trip.itinerary.*.segments.*.departure.airport' => ['required', 'array'],
            'trip.itinerary.*.segments.*.departure.airport.code' => ['required', 'string', 'size:3'],
            'trip.itinerary.*.segments.*.departure.airport.geocode' => ['nullable', 'array'],
            'trip.itinerary.*.segments.*.departure.airport.geocode.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'trip.itinerary.*.segments.*.departure.airport.geocode.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'trip.itinerary.*.segments.*.departure.time' => ['required', 'date'],
            'trip.itinerary.*.segments.*.departure.terminal' => ['nullable', 'string', 'max:16'],

            // Arrival validation
            'trip.itinerary.*.segments.*.arrival' => ['required', 'array'],
            'trip.itinerary.*.segments.*.arrival.airport' => ['required', 'array'],
            'trip.itinerary.*.segments.*.arrival.airport.code' => ['required', 'string', 'size:3'],
            'trip.itinerary.*.segments.*.arrival.airport.geocode' => ['nullable', 'array'],
            'trip.itinerary.*.segments.*.arrival.airport.geocode.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'trip.itinerary.*.segments.*.arrival.airport.geocode.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'trip.itinerary.*.segments.*.arrival.time' => ['required', 'date'],
            'trip.itinerary.*.segments.*.arrival.terminal' => ['nullable', 'string', 'max:16'],

            // Marketing carrier
            'trip.itinerary.*.segments.*.marketing_carrier' => ['nullable', 'array'],
            'trip.itinerary.*.segments.*.marketing_carrier.airline_code' => ['nullable', 'string', 'max:10'],
            'trip.itinerary.*.segments.*.marketing_carrier.flight_number' => ['nullable', 'string', 'max:16'],

            // Operating carrier
            'trip.itinerary.*.segments.*.operating_carrier' => ['nullable', 'array'],
            'trip.itinerary.*.segments.*.operating_carrier.airline_code' => ['nullable', 'string', 'max:10'],

            // Transfer role hint
            'trip.itinerary.*.segments.*.transfer_role_hint' => ['nullable', Rule::in(['in', 'out', 'none'])],

            // Stay items validation
            'trip.itinerary.*.stay_type' => [
                'required_if:trip.itinerary.*.type,stay',
                Rule::in(['hotel', 'apartment', 'resort', 'hostel', 'other']),
            ],
            'trip.itinerary.*.stay_id' => ['required_if:trip.itinerary.*.type,stay', 'string', 'max:64'],
            'trip.itinerary.*.location' => ['required_if:trip.itinerary.*.type,stay', 'array'],
            'trip.itinerary.*.location.name' => ['nullable', 'string', 'max:255'],
            'trip.itinerary.*.location.giata_id' => ['nullable', 'integer'],
            'trip.itinerary.*.location.geocode' => ['nullable', 'array'],
            'trip.itinerary.*.location.geocode.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'trip.itinerary.*.location.geocode.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'trip.itinerary.*.location.country_code' => ['nullable', 'string', 'size:2'],
            'trip.itinerary.*.check_in' => ['required_if:trip.itinerary.*.type,stay', 'date'],
            'trip.itinerary.*.check_out' => ['required_if:trip.itinerary.*.type,stay', 'date', 'after:trip.itinerary.*.check_in'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'schema_version.required' => 'Schema version is required',
            'schema_version.in' => 'Schema version must be 1.0 or 1.1',

            'provider.id.required' => 'Provider ID is required',
            'provider.sent_at.required' => 'Provider sent_at timestamp is required',
            'provider.sent_at.date' => 'Provider sent_at must be a valid ISO8601 date',

            'trip.external_trip_id.required' => 'External trip ID is required',

            'trip.itinerary.required' => 'Itinerary is required',
            'trip.itinerary.min' => 'Itinerary must have at least one item',
            'trip.itinerary.*.type.required' => 'Itinerary item type is required',
            'trip.itinerary.*.type.in' => 'Itinerary item type must be travel or stay',

            'trip.itinerary.*.segments.*.departure.airport.code.required' => 'Departure airport code is required',
            'trip.itinerary.*.segments.*.departure.airport.code.size' => 'Departure airport code must be 3 characters (IATA)',
            'trip.itinerary.*.segments.*.departure.time.required' => 'Departure time is required',

            'trip.itinerary.*.segments.*.arrival.airport.code.required' => 'Arrival airport code is required',
            'trip.itinerary.*.segments.*.arrival.airport.code.size' => 'Arrival airport code must be 3 characters (IATA)',
            'trip.itinerary.*.segments.*.arrival.time.required' => 'Arrival time is required',

            'trip.itinerary.*.check_in.required_if' => 'Check-in date is required for stays',
            'trip.itinerary.*.check_out.required_if' => 'Check-out date is required for stays',
            'trip.itinerary.*.check_out.after' => 'Check-out must be after check-in',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'trip.itinerary.*.segments.*.departure.airport.code' => 'departure airport code',
            'trip.itinerary.*.segments.*.arrival.airport.code' => 'arrival airport code',
        ];
    }
}
