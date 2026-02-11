<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventApiResource extends JsonResource
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
                    'name' => $c->getName('de'),
                    'name_en' => $c->getName('en'),
                ])
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
