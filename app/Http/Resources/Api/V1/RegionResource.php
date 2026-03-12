<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_de' => $this->getName('de'),
            'name_en' => $this->getName('en'),
            'code' => $this->code,
            'country_iso_code' => $this->whenLoaded('country', fn () => $this->country?->iso_code),
            'country_name_de' => $this->whenLoaded('country', fn () => $this->country?->getName('de')),
            'lat' => $this->lat ? (float) $this->lat : null,
            'lng' => $this->lng ? (float) $this->lng : null,
        ];
    }
}
