<?php

declare(strict_types=1);

use App\Models\{User, City, Country, Region};

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
        'is_active' => true,
    ]);
});

test('admin can view cities index page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/cities')
        ->assertSuccessful()
        ->assertSee('Cities');
});

test('admin can create a new city', function () {
    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);

    $cityData = [
        'name' => 'Test City',
        'country_id' => $country->id,
        'region_id' => $region->id,
        'latitude' => 50.1109,
        'longitude' => 8.6821,
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/cities', $cityData)
        ->assertRedirect();

    $this->assertDatabaseHas('cities', [
        'name' => 'Test City',
        'country_id' => $country->id,
        'region_id' => $region->id,
    ]);
});

test('admin can view a city', function () {
    $city = City::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/cities/{$city->id}")
        ->assertSuccessful()
        ->assertSee($city->name);
});

test('admin can edit a city', function () {
    $city = City::factory()->create();
    $updatedData = [
        'name' => 'Updated City Name',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->put("/admin/cities/{$city->id}", $updatedData)
        ->assertRedirect();

    $this->assertDatabaseHas('cities', [
        'id' => $city->id,
        'name' => 'Updated City Name',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);
});

test('admin can delete a city', function () {
    $city = City::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/cities/{$city->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('cities', [
        'id' => $city->id,
    ]);
});

test('admin can bulk delete cities', function () {
    $cities = City::factory()->count(3)->create();
    $cityIds = $cities->pluck('id')->toArray();

    $this->actingAs($this->admin)
        ->post('/admin/cities/bulk-delete', [
            'ids' => $cityIds,
        ])
        ->assertRedirect();

    foreach ($cityIds as $id) {
        $this->assertDatabaseMissing('cities', ['id' => $id]);
    }
});

test('non-admin users cannot access cities', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/cities')
        ->assertForbidden();
});

test('city form validation works', function () {
    $this->actingAs($this->admin)
        ->post('/admin/cities', [])
        ->assertSessionHasErrors([
            'name',
            'country_id',
        ]);
});

test('city search works', function () {
    $city1 = City::factory()->create(['name' => 'Berlin']);
    $city2 = City::factory()->create(['name' => 'Munich']);

    $this->actingAs($this->admin)
        ->get('/admin/cities?search=Berlin')
        ->assertSuccessful()
        ->assertSee($city1->name)
        ->assertDontSee($city2->name);
});

test('city filtering by country works', function () {
    $country1 = Country::factory()->create(['name' => 'Germany']);
    $country2 = Country::factory()->create(['name' => 'France']);
    
    $city1 = City::factory()->create(['country_id' => $country1->id]);
    $city2 = City::factory()->create(['country_id' => $country2->id]);

    $this->actingAs($this->admin)
        ->get("/admin/cities?country_id={$country1->id}")
        ->assertSuccessful()
        ->assertSee($city1->name)
        ->assertDontSee($city2->name);
});

test('city filtering by region works', function () {
    $country = Country::factory()->create();
    $region1 = Region::factory()->create(['country_id' => $country->id, 'name' => 'Bavaria']);
    $region2 = Region::factory()->create(['country_id' => $country->id, 'name' => 'Hesse']);
    
    $city1 = City::factory()->create(['country_id' => $country->id, 'region_id' => $region1->id]);
    $city2 = City::factory()->create(['country_id' => $country->id, 'region_id' => $region2->id]);

    $this->actingAs($this->admin)
        ->get("/admin/cities?region_id={$region1->id}")
        ->assertSuccessful()
        ->assertSee($city1->name)
        ->assertDontSee($city2->name);
});

test('city filtering by active status works', function () {
    $activeCity = City::factory()->create(['is_active' => true]);
    $inactiveCity = City::factory()->create(['is_active' => false]);

    $this->actingAs($this->admin)
        ->get('/admin/cities?is_active=1')
        ->assertSuccessful()
        ->assertSee($activeCity->name)
        ->assertDontSee($inactiveCity->name);
});

test('city with relationships works', function () {
    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create([
        'country_id' => $country->id,
        'region_id' => $region->id,
    ]);

    $this->actingAs($this->admin)
        ->get("/admin/cities/{$city->id}")
        ->assertSuccessful()
        ->assertSee($city->name)
        ->assertSee($country->name)
        ->assertSee($region->name);
});

test('city with coordinates works', function () {
    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);

    $cityData = [
        'name' => 'Test City with Coordinates',
        'country_id' => $country->id,
        'region_id' => $region->id,
        'latitude' => 50.1109,
        'longitude' => 8.6821,
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/cities', $cityData)
        ->assertRedirect();

    $this->assertDatabaseHas('cities', [
        'name' => 'Test City with Coordinates',
        'latitude' => 50.1109,
        'longitude' => 8.6821,
    ]);
});
