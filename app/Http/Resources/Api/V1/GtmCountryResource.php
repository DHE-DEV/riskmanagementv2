<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GtmCountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'iso_code' => $this->iso_code,
            'iso3_code' => $this->iso3_code,
            'name' => $this->getName('de'),
            'name_en' => $this->getName('en'),
            'continent' => $this->continent?->getName('en'),
            'continent_de' => $this->continent?->getName('de'),
            'lat' => $this->lat ? (float) $this->lat : null,
            'lng' => $this->lng ? (float) $this->lng : null,
            'is_eu_member' => (bool) $this->is_eu_member,
            'is_schengen_member' => (bool) $this->is_schengen_member,
            'active_events_count' => (int) ($this->active_events_count ?? 0),
        ];
    }
}
