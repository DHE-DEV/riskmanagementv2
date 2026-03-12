<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContinentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name_de' => $this->getName('de'),
            'name_en' => $this->getName('en'),
            'lat' => $this->lat ? (float) $this->lat : null,
            'lng' => $this->lng ? (float) $this->lng : null,
        ];
    }
}
