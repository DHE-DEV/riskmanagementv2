<?php

declare(strict_types=1);

use App\Models\{Airport, Country, City, Region};

it('returns empty data when query is empty', function () {
    $response = $this->getJson('/api/airports/search');

    $response->assertSuccessful();
    expect($response->json('data'))->toBeArray()->toBeEmpty();
});

it('finds airports by name substring', function () {
    $country = Country::factory()->create(['iso3' => 'DEU']);
    $region = Region::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['country_id' => $country->id, 'region_id' => $region->id]);

    $fra = Airport::factory()->create(['name' => 'Frankfurt Airport', 'iata_code' => 'FRA', 'country_id' => $country->id, 'city_id' => $city->id]);
    Airport::factory()->create(['name' => 'Berlin Airport', 'iata_code' => 'BER', 'country_id' => $country->id, 'city_id' => $city->id]);

    $response = $this->getJson('/api/airports/search?q=Frank');

    $response->assertSuccessful();
    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Frankfurt Airport');
});

it('finds airports by IATA code (3 letters, case-insensitive)', function () {
    $country = Country::factory()->create(['iso3' => 'DEU']);
    $region = Region::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['country_id' => $country->id, 'region_id' => $region->id]);

    $fra = Airport::factory()->create(['name' => 'Frankfurt Airport', 'iata_code' => 'FRA', 'country_id' => $country->id, 'city_id' => $city->id]);

    $responseLower = $this->getJson('/api/airports/search?q=fra');
    $responseUpper = $this->getJson('/api/airports/search?q=FRA');

    foreach ([$responseLower, $responseUpper] as $response) {
        $response->assertSuccessful();
        $codes = collect($response->json('data'))->pluck('iata_code');
        expect($codes)->toContain('FRA');
    }
});


