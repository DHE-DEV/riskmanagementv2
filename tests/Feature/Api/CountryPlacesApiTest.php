<?php

use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function placesToken(): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('Test', ['gtm:read'])->plainTextToken;
}

function placesCountry(): Country
{
    $europe = Continent::create(['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'code' => 'EU', 'sort_order' => 1]);

    return Country::create(['name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'], 'iso_code' => 'DE', 'iso3_code' => 'DEU', 'continent_id' => $europe->id]);
}

it('liefert die Regionen eines Landes mit Anzahl der Staedte', function () {
    $country = placesCountry();
    $nrw = Region::create(['country_id' => $country->id, 'code' => 'DE-NW', 'name_translations' => ['de' => 'Nordrhein-Westfalen', 'en' => 'North Rhine-Westphalia'], 'lat' => 51.4332, 'lng' => 7.6616, 'is_major' => true]);
    $bw = Region::create(['country_id' => $country->id, 'code' => 'DE-BW', 'name_translations' => ['de' => 'Baden-Württemberg', 'en' => 'Baden-Württemberg']]);
    City::create(['country_id' => $country->id, 'region_id' => $nrw->id, 'name_translations' => ['de' => 'Düsseldorf', 'en' => 'Dusseldorf'], 'is_regional_capital' => true, 'population' => 620000, 'lat' => 51.2277, 'lng' => 6.7735]);
    City::create(['country_id' => $country->id, 'region_id' => $nrw->id, 'name_translations' => ['de' => 'Köln', 'en' => 'Cologne']]);

    // Ohne Token: 401 – vor der ersten angemeldeten Anfrage, weil der Guard den Benutzer im Test behält.
    $this->getJson('/api/v1/countries/DE/regions')->assertUnauthorized();

    $response = $this->withToken(placesToken())->getJson('/api/v1/countries/de/regions?lang=de')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.total', 2)
        // Alphabetisch: Baden-Württemberg vor Nordrhein-Westfalen.
        ->assertJsonPath('data.0.name', 'Baden-Württemberg')
        ->assertJsonPath('data.0.cities_count', 0)
        ->assertJsonPath('data.1.name', 'Nordrhein-Westfalen')
        ->assertJsonPath('data.1.code', 'DE-NW')
        ->assertJsonPath('data.1.cities_count', 2)
        ->assertJsonPath('data.1.is_popular', true)
        ->assertJsonPath('data.1.coordinates.lat', 51.4332);

    expect($response->json('data.0.coordinates'))->toBeNull();

    // Ohne lang: Name als Objekt je Sprache.
    $this->withToken(placesToken())->getJson('/api/v1/countries/DEU/regions')
        ->assertOk()
        ->assertJsonPath('data.1.name.en', 'North Rhine-Westphalia');

    $this->withToken(placesToken())->getJson('/api/v1/countries/XX/regions')->assertNotFound();
});

it('liefert die Staedte eines Landes, optional nur die einer Region', function () {
    $country = placesCountry();
    $nrw = Region::create(['country_id' => $country->id, 'code' => 'DE-NW', 'name_translations' => ['de' => 'Nordrhein-Westfalen', 'en' => 'North Rhine-Westphalia']]);
    $be = Region::create(['country_id' => $country->id, 'code' => 'DE-BE', 'name_translations' => ['de' => 'Berlin', 'en' => 'Berlin']]);
    City::create(['country_id' => $country->id, 'region_id' => $nrw->id, 'name_translations' => ['de' => 'Köln', 'en' => 'Cologne'], 'is_major' => true]);
    City::create(['country_id' => $country->id, 'region_id' => $nrw->id, 'name_translations' => ['de' => 'Düsseldorf', 'en' => 'Dusseldorf'], 'is_regional_capital' => true, 'population' => 620000]);
    City::create(['country_id' => $country->id, 'region_id' => $be->id, 'name_translations' => ['de' => 'Berlin', 'en' => 'Berlin'], 'is_capital' => true, 'is_regional_capital' => true]);
    City::create(['country_id' => $country->id, 'region_id' => null, 'name_translations' => ['de' => 'Zugspitzdorf', 'en' => 'Zugspitzdorf']]);

    $this->withToken(placesToken())->getJson('/api/v1/countries/DE/cities?lang=de')
        ->assertOk()
        ->assertJsonPath('meta.total', 4)
        ->assertJsonPath('data.0.name', 'Berlin')
        ->assertJsonPath('data.0.is_capital', true)
        ->assertJsonPath('data.0.is_popular', false)
        ->assertJsonPath('data.0.region.name', 'Berlin')
        ->assertJsonPath('data.1.name', 'Düsseldorf')
        ->assertJsonPath('data.1.population', 620000)
        ->assertJsonPath('data.1.region.code', 'DE-NW')
        ->assertJsonPath('data.2.name', 'Köln')
        ->assertJsonPath('data.2.is_popular', true)
        ->assertJsonPath('data.3.name', 'Zugspitzdorf')
        ->assertJsonPath('data.3.region', null);

    $this->withToken(placesToken())->getJson('/api/v1/countries/DE/cities?lang=en&region='.$nrw->id)
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.name', 'Cologne')
        ->assertJsonPath('data.1.name', 'Dusseldorf');

    $this->withToken(placesToken())->getJson('/api/v1/countries/DE/cities?region=abc')->assertStatus(422);
});

it('bietet Regionen und Staedte auch auf der API-Subdomain an', function () {
    $country = placesCountry();
    Region::create(['country_id' => $country->id, 'code' => 'DE-HH', 'name_translations' => ['de' => 'Hamburg', 'en' => 'Hamburg']]);

    $host = 'http://'.config('app.api_domain');

    $this->withToken(placesToken())->getJson($host.'/v1/countries/DE/regions?lang=de')->assertOk()->assertJsonPath('data.0.name', 'Hamburg');
    $this->withToken(placesToken())->getJson($host.'/v1/countries/DE/cities')->assertOk()->assertJsonPath('meta.total', 0);
});
