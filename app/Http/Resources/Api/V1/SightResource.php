<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Sight;
use App\Support\AdminV2\SightInfo;

/**
 * Sehenswuerdigkeit fuer die Kunden-API. Mit "lang" kommen Texte als String
 * (Rueckfall Deutsch), ohne als Objekt je Sprache.
 */
class SightResource
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Sight $sight, ?string $lang): array
    {
        $info = (array) $sight->info;
        $texts = [];

        foreach (array_keys(SightInfo::TEXTS) as $field) {
            $value = AirportResource::text($info['texts'][$field] ?? null, $lang);
            $texts[$field] = $lang !== null ? $value : ((array) $value ?: null);
        }

        return [
            'id' => $sight->id,
            'name' => AirportResource::text($sight->name_translations, $lang),
            'category' => $sight->category,
            'category_name' => AirportResource::text(SightInfo::categoryNames($sight->category), $lang),
            'is_highlight' => (bool) $sight->is_highlight,
            'region' => $sight->region ? [
                'id' => $sight->region->id,
                'code' => $sight->region->code,
                'name' => AirportResource::text($sight->region->name_translations, $lang),
            ] : null,
            'city' => $sight->city ? [
                'id' => $sight->city->id,
                'name' => AirportResource::text($sight->city->name_translations, $lang),
            ] : null,
            'coordinates' => $sight->lat !== null && $sight->lng !== null ? ['lat' => (float) $sight->lat, 'lng' => (float) $sight->lng] : null,
            'address' => $sight->address,
            'website_url' => $sight->website_url,
            'ticket_url' => $sight->ticket_url,
            'visit_minutes' => isset($info['visit_minutes']) ? (int) $info['visit_minutes'] : null,
        ] + $texts + [
            'is_reviewed' => SightInfo::status($info) !== SightInfo::STATUS_AI,
        ];
    }
}
