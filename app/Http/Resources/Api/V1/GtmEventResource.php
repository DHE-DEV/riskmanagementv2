<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GtmEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description ? strip_tags($this->description) : null,
            'priority' => $this->priority,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'event_types' => $this->whenLoaded('eventTypes', fn() =>
                $this->eventTypes->map(fn($t) => [
                    'code' => $t->code,
                    'name' => $t->name,
                    'color' => $t->color,
                    'icon' => $t->icon,
                ])
            ),
            'event_type' => $this->whenLoaded('eventType', fn() =>
                $this->eventType ? [
                    'code' => $this->eventType->code,
                    'name' => $this->eventType->name,
                    'color' => $this->eventType->color,
                    'icon' => $this->eventType->icon,
                ] : null
            ),
            'category' => $this->whenLoaded('eventCategory', fn() =>
                $this->eventCategory ? [
                    'id' => $this->eventCategory->id,
                    'name' => $this->eventCategory->name,
                    'color' => $this->eventCategory->color,
                ] : null
            ),
            'countries' => $this->whenLoaded('countries', fn() =>
                $this->countries->map(fn($c) => [
                    'iso_code' => $c->iso_code,
                    'iso3_code' => $c->iso3_code,
                    'name_de' => $c->getName('de'),
                    'name_en' => $c->getName('en'),
                    'continent' => $c->continent?->getName('en'),
                ])
            ),
            'country' => $this->whenLoaded('country', fn() =>
                $this->country ? [
                    'iso_code' => $this->country->iso_code,
                    'iso3_code' => $this->country->iso3_code,
                    'name_de' => $this->country->getName('de'),
                    'name_en' => $this->country->getName('en'),
                    'continent' => $this->country->continent?->getName('en'),
                ] : null
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
