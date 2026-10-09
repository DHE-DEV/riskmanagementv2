<?php

use App\Models\Continent;
use App\Models\Country;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function boundaryToken(): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('Test', ['gtm:read'])->plainTextToken;
}

function boundaryContinent(): Continent
{
    return Continent::firstOrCreate(['code' => 'XX'], ['name_translations' => ['de' => 'Test', 'en' => 'Test'], 'sort_order' => 1]);
}

/** Land mit einem einfachen Viereck als Grenze (wie der Import: GeoJSON lng/lat, SRID 4326). */
function boundaryCountry(string $iso, string $iso3, array $names, array $ring): Country
{
    $country = Country::create(['name_translations' => $names, 'iso_code' => $iso, 'iso3_code' => $iso3, 'continent_id' => boundaryContinent()->id]);
    $lngs = array_column($ring, 0);
    $lats = array_column($ring, 1);

    DB::statement(
        'insert into country_boundaries (country_id, iso_a2, iso_a3, name, source, source_features, boundary, min_lat, max_lat, min_lng, max_lng, created_at, updated_at)
         values (?, ?, ?, ?, ?, 1, ST_GeomFromGeoJSON(?, 1, 4326), ?, ?, ?, ?, now(), now())',
        [$country->id, $iso, $iso3, $names['en'], 'Test', json_encode(['type' => 'MultiPolygon', 'coordinates' => [[$ring]]]), min($lats), max($lats), min($lngs), max($lngs)],
    );

    return $country;
}

beforeEach(fn () => Cache::flush());

it('liefert die Grenze eines Landes als GeoJSON-Feature', function () {
    boundaryCountry('EG', 'EGY', ['de' => 'Ägypten', 'en' => 'Egypt'], [[25, 22], [36, 22], [36, 31], [25, 31], [25, 22]]);

    $data = $this->withToken(boundaryToken())->getJson('/api/v1/countries/eg/boundary?lang=de')->assertOk()->json('data');

    expect($data['type'])->toBe('Feature')
        ->and($data['properties'])->toBe(['iso_code' => 'EG', 'iso3_code' => 'EGY', 'name' => 'Ägypten'])
        ->and($data['bbox'])->toEqual([25, 22, 36, 31])
        ->and($data['geometry']['type'])->toBe('MultiPolygon')
        ->and($data['geometry']['coordinates'][0][0])->toHaveCount(5)
        ->and($data['geometry']['coordinates'][0][0])->toContainEqual([25, 22]);
});

it('liefert mehrere Grenzen als FeatureCollection und laesst Laender ohne Grenze weg', function () {
    boundaryCountry('EG', 'EGY', ['de' => 'Ägypten', 'en' => 'Egypt'], [[25, 22], [36, 22], [36, 31], [25, 31], [25, 22]]);
    boundaryCountry('DE', 'DEU', ['de' => 'Deutschland', 'en' => 'Germany'], [[6, 47], [15, 47], [15, 55], [6, 55], [6, 47]]);
    Country::create(['name_translations' => ['de' => 'Nirgendwo', 'en' => 'Nowhere'], 'iso_code' => 'XX', 'iso3_code' => 'XXX', 'continent_id' => boundaryContinent()->id]);

    $response = $this->withToken(boundaryToken())->getJson('/api/v1/boundaries?codes=eg,DEU,xx,ZZ')->assertOk();

    expect($response->json('data.type'))->toBe('FeatureCollection')
        ->and(collect($response->json('data.features'))->pluck('properties.iso_code')->all())->toBe(['DE', 'EG'])
        ->and($response->json('data.features.0.properties.name'))->toBe(['de' => 'Deutschland', 'en' => 'Germany'])
        ->and($response->json('meta'))->toBe(['requested' => 4, 'found' => 2]);
});

it('weist fehlende Grenzen, unbekannte Laender und leere Code-Listen ab', function () {
    Country::create(['name_translations' => ['de' => 'Nirgendwo', 'en' => 'Nowhere'], 'iso_code' => 'XX', 'iso3_code' => 'XXX', 'continent_id' => boundaryContinent()->id]);
    $token = boundaryToken();

    $this->withToken($token)->getJson('/api/v1/countries/XX/boundary')->assertNotFound();
    $this->withToken($token)->getJson('/api/v1/countries/QQ/boundary')->assertNotFound();
    $this->withToken($token)->getJson('/api/v1/boundaries?codes=1,--')->assertStatus(422);
    app('auth')->forgetGuards();
    $this->withoutToken()->getJson('/api/v1/boundaries?codes=EG')->assertUnauthorized();
});
