<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventApiResource extends JsonResource
{
    use ResolvesEventCoordinates;

    public function toArray(Request $request): array
    {
        $firstCountry = $this->relationLoaded('countries') ? $this->countries->first() : null;
        $firstCoords = $this->getCoordinatesForCountry($firstCountry);

        return [
            'id' => $this->uuid,
            'title' => $this->title,
            'description' => $this->popup_content ? strip_tags($this->popup_content) : null,
            'risk_level' => $this->priority,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'latitude' => $firstCoords['latitude'],
            'longitude' => $firstCoords['longitude'],
            'review_status' => $this->review_status,
            'is_active' => $this->is_active,
            'tags' => $this->tags,
            'event_categories' => $this->whenLoaded('eventTypes', fn () =>
                $this->eventTypes->map(fn ($t) => [
                    'code' => $t->code,
                    'name' => $t->name,
                    'color' => $t->color,
                    'icon' => $t->icon,
                ])
            ),
            'countries' => $this->whenLoaded('countries', fn () =>
                $this->countries->map(function ($c) {
                    $coords = $this->getCoordinatesForCountry($c);

                    return [
                        'iso_code' => $c->iso_code,
                        'name_de' => $c->getName('de'),
                        'name_en' => $c->getName('en'),
                        'latitude' => $coords['latitude'],
                        'longitude' => $coords['longitude'],
                    ];
                })
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
