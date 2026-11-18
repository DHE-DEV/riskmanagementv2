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
            // Sprache, in der title/description tatsaechlich vorliegen (bei fehlender Uebersetzung die Ausgangssprache).
            'language' => $this->resource->titleLocale(),
            'risk_level' => $this->priority,
            // Landesweit: gilt im gesamten Land, unabhaengig vom Abfrage-Radius.
            'is_nationwide' => (bool) $this->is_nationwide,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'latitude' => $firstCoords['latitude'],
            'longitude' => $firstCoords['longitude'],
            'event_categories' => $this->whenLoaded('eventTypes', fn () => $this->eventTypes->map(fn ($t) => [
                'code' => $t->code,
                'name' => $t->getName(app()->getLocale()),
            ])
            ),
            // name/continent_name folgen ?lang=; name_de, name_en und continent (Englisch) bleiben fest.
            'countries' => $this->whenLoaded('countries', fn () => $this->countries->map(function ($c) {
                $locale = app()->getLocale();
                $coords = $this->getCoordinatesForCountry($c);

                $region = $c->pivot?->region_id ? \App\Models\Region::find($c->pivot->region_id) : null;
                $city = $c->pivot?->city_id ? \App\Models\City::find($c->pivot->city_id) : null;

                return [
                    'iso_code' => $c->iso_code,
                    'iso3_code' => $c->iso3_code,
                    'name' => $c->getName($locale),
                    'name_de' => $c->getName('de'),
                    'name_en' => $c->getName('en'),
                    'continent' => $c->continent?->getName('en'),
                    'continent_name' => $c->continent?->getName($locale),
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    // Ein Land kann mehrere Standort-Datensaetze haben - je Eintrag eine Zeile.
                    'region' => $region ? [
                        'id' => $region->id,
                        'name' => $region->getName($locale),
                        'name_de' => $region->getName('de'),
                        'name_en' => $region->getName('en'),
                    ] : null,
                    'city' => $city ? [
                        'id' => $city->id,
                        'name' => $city->getName($locale),
                        'name_de' => $city->getName('de'),
                        'name_en' => $city->getName('en'),
                    ] : null,
                    'location_note' => $c->pivot?->location_note,
                ];
            })
            ),
            'locations' => $this->whenLoaded('countries', fn () => $this->resource->locationRecords(app()->getLocale())),
            'source' => [
                'type' => $this->data_source ?? 'manual',
                'name' => $this->whenLoaded('apiClient', fn () => $this->apiClient?->company_name ?? $this->apiClient?->name
                ),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
