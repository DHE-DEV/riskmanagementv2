<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstCountry = $this->relationLoaded('countries') ? $this->countries->first() : null;
        $latitude = $this->latitude ? (float) $this->latitude : $this->resolveCountryCoordinate($firstCountry, 'lat');
        $longitude = $this->longitude ? (float) $this->longitude : $this->resolveCountryCoordinate($firstCountry, 'lng');

        return [
            'id' => $this->uuid,
            'title' => $this->title,
            'description' => $this->popup_content ? strip_tags($this->popup_content) : null,
            'priority' => $this->priority,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'review_status' => $this->review_status,
            'is_active' => $this->is_active,
            'tags' => $this->tags,
            'event_types' => $this->whenLoaded('eventTypes', fn () =>
                $this->eventTypes->map(fn ($t) => [
                    'code' => $t->code,
                    'name' => $t->name,
                    'color' => $t->color,
                    'icon' => $t->icon,
                ])
            ),
            'countries' => $this->whenLoaded('countries', fn () =>
                $this->countries->map(fn ($c) => [
                    'iso_code' => $c->iso_code,
                    'name_de' => $c->getName('de'),
                    'name_en' => $c->getName('en'),
                    'latitude' => $this->resolveCountryCoordinate($c, 'lat'),
                    'longitude' => $this->resolveCountryCoordinate($c, 'lng'),
                ])
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Resolve coordinate from pivot (custom) or country default.
     */
    private function resolveCountryCoordinate($country, string $field): ?float
    {
        if (!$country) {
            return null;
        }

        if ($country->pivot && !$country->pivot->use_default_coordinates && $country->pivot->$field) {
            return (float) $country->pivot->$field;
        }

        return $country->$field ? (float) $country->$field : null;
    }
}
