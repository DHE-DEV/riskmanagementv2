<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GtmEventResource extends JsonResource
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
            'riskLevel' => $this->priority,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'latitude' => $firstCoords['latitude'],
            'longitude' => $firstCoords['longitude'],
            'event_types' => $this->whenLoaded('eventTypes', fn() =>
                $this->eventTypes->map(fn($t) => [
                    'code' => $t->code,
                    'name' => $t->name,
                ])
            ),
            'countries' => $this->whenLoaded('countries', fn() =>
                $this->countries->map(function ($c) {
                    $coords = $this->getCoordinatesForCountry($c);

                    return [
                        'iso_code' => $c->iso_code,
                        'iso3_code' => $c->iso3_code,
                        'name_de' => $c->getName('de'),
                        'name_en' => $c->getName('en'),
                        'continent' => $c->continent?->getName('en'),
                        'latitude' => $coords['latitude'],
                        'longitude' => $coords['longitude'],
                    ];
                })
            ),
            'source' => [
                'type' => $this->data_source ?? 'manual',
                'name' => $this->whenLoaded('apiClient', fn() =>
                    $this->apiClient?->company_name ?? $this->apiClient?->name
                ),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
