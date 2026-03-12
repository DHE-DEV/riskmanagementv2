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
            'description' => $this->popup_content ? strip_tags($this->popup_content) : null,
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
            'countries' => $this->whenLoaded('countries', fn() =>
                $this->countries->map(fn($c) => [
                    'iso_code' => $c->iso_code,
                    'iso3_code' => $c->iso3_code,
                    'name_de' => $c->getName('de'),
                    'name_en' => $c->getName('en'),
                    'continent' => $c->continent?->getName('en'),
                ])
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
