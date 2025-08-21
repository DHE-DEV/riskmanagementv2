<?php

declare(strict_types=1);

use App\Models\{User, Airport, Country, City};

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
        'is_active' => true,
    ]);
});

test('admin can view airports index page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/airports')
        ->assertSuccessful()
        ->assertSee('Airports');
});

test('admin can create a new airport', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $airportData = [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'type' => 'international',
        'latitude' => 50.1109,
        'longitude' => 8.6821,
        'country_id' => $country->id,
        'city_id' => $city->id,
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/airports', $airportData)
        ->assertRedirect();

    $this->assertDatabaseHas('airports', [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
    ]);
});

test('admin can view an airport', function () {
    $airport = Airport::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/airports/{$airport->id}")
        ->assertSuccessful()
        ->assertSee($airport->name);
});

test('admin can edit an airport', function () {
    $airport = Airport::factory()->create();
    $updatedData = [
        'name' => 'Updated Airport Name',
        'iata_code' => 'UPD',
        'icao_code' => 'UPDT',
        'type' => 'domestic',
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->put("/admin/airports/{$airport->id}", $updatedData)
        ->assertRedirect();

    $this->assertDatabaseHas('airports', [
        'id' => $airport->id,
        'name' => 'Updated Airport Name',
        'iata_code' => 'UPD',
    ]);
});

test('admin can delete an airport', function () {
    $airport = Airport::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/airports/{$airport->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('airports', [
        'id' => $airport->id,
    ]);
});

test('admin can bulk delete airports', function () {
    $airports = Airport::factory()->count(3)->create();
    $airportIds = $airports->pluck('id')->toArray();

    $this->actingAs($this->admin)
        ->post('/admin/airports/bulk-delete', [
            'ids' => $airportIds,
        ])
        ->assertRedirect();

    foreach ($airportIds as $id) {
        $this->assertDatabaseMissing('airports', ['id' => $id]);
    }
});

test('non-admin users cannot access airports', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/airports')
        ->assertForbidden();
});

test('airport form validation works', function () {
    $this->actingAs($this->admin)
        ->post('/admin/airports', [])
        ->assertSessionHasErrors([
            'name',
            'iata_code',
            'icao_code',
            'type',
        ]);
});

test('airport with unique codes validation works', function () {
    $existingAirport = Airport::factory()->create([
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
    ]);

    $newAirportData = [
        'name' => 'Another Airport',
        'iata_code' => 'TST', // Duplicate
        'icao_code' => 'TEST', // Duplicate
        'type' => 'international',
    ];

    $this->actingAs($this->admin)
        ->post('/admin/airports', $newAirportData)
        ->assertSessionHasErrors([
            'iata_code',
            'icao_code',
        ]);
});

test('airport filtering by type works', function () {
    $internationalAirport = Airport::factory()->create(['type' => 'international']);
    $domesticAirport = Airport::factory()->create(['type' => 'domestic']);

    $this->actingAs($this->admin)
        ->get('/admin/airports?type=international')
        ->assertSuccessful()
        ->assertSee($internationalAirport->name)
        ->assertDontSee($domesticAirport->name);
});

test('airport search works', function () {
    $airport1 = Airport::factory()->create(['name' => 'Frankfurt Airport']);
    $airport2 = Airport::factory()->create(['name' => 'Berlin Airport']);

    $this->actingAs($this->admin)
        ->get('/admin/airports?search=Frankfurt')
        ->assertSuccessful()
        ->assertSee($airport1->name)
        ->assertDontSee($airport2->name);
});

test('airport with coordinates works', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $airportData = [
        'name' => 'Test Airport with Coordinates',
        'iata_code' => 'COO',
        'icao_code' => 'COOR',
        'type' => 'international',
        'latitude' => 50.1109,
        'longitude' => 8.6821,
        'country_id' => $country->id,
        'city_id' => $city->id,
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/airports', $airportData)
        ->assertRedirect();

    $this->assertDatabaseHas('airports', [
        'name' => 'Test Airport with Coordinates',
        'latitude' => 50.1109,
        'longitude' => 8.6821,
    ]);
});
