<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Landesgrenze als Polygon (Quelle: Natural Earth, ne_10m_admin_0_countries).
 *
 * Die Spalte `boundary` ist eine MULTIPOLYGON-Geometrie mit SRID 4326 und wird
 * nie ueber Eloquent geschrieben - der Import laeuft ueber ST_GeomFromGeoJSON
 * (siehe ImportCountryBoundaries).
 */
class CountryBoundary extends Model
{
    protected $fillable = [
        'country_id',
        'iso_a2',
        'iso_a3',
        'name',
        'source',
        'source_features',
        'min_lat',
        'max_lat',
        'min_lng',
        'max_lng',
    ];

    protected $casts = [
        'source_features' => 'integer',
        'min_lat' => 'float',
        'max_lat' => 'float',
        'min_lng' => 'float',
        'max_lng' => 'float',
    ];

    /**
     * Geometrie-Spalte aus Selects heraushalten - der Binaerwert gehoert nicht ins Model.
     */
    protected $hidden = ['boundary'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Aus der Geometrie berechnete Kennzahlen.
     *
     * Bewusst per Abfrage statt als Spalte: die Werte kommen direkt aus der
     * gespeicherten Geometrie und koennen deshalb nicht davon abweichen.
     *
     * ST_NumPoints entfaellt - MySQL liefert dafuer bei MultiPolygon NULL.
     * Ersatzweise dient die WKB-Groesse als Mass fuer den Detailgrad.
     *
     * @return array{parts: ?int, area_km2: ?float, is_valid: ?bool, bytes: ?int}
     */
    public function spatialStats(): array
    {
        $row = DB::selectOne(
            'select ST_NumGeometries(boundary) as parts,
                    ST_Area(boundary) as area_m2,
                    ST_IsValid(boundary) as is_valid,
                    length(ST_AsBinary(boundary)) as bytes
             from country_boundaries
             where id = ?',
            [$this->id],
        );

        if (! $row) {
            return ['parts' => null, 'area_km2' => null, 'is_valid' => null, 'bytes' => null];
        }

        return [
            'parts' => $row->parts !== null ? (int) $row->parts : null,
            'area_km2' => $row->area_m2 !== null ? ((float) $row->area_m2) / 1_000_000 : null,
            'is_valid' => $row->is_valid !== null ? (bool) $row->is_valid : null,
            'bytes' => $row->bytes !== null ? (int) $row->bytes : null,
        ];
    }
}
