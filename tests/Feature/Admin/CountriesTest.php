<?php

declare(strict_types=1);

use App\Models\{User, Country, Continent};

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
        'is_active' => true,
    ]);
});

test('admin can view countries index page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/countries')
        ->assertSuccessful()
        ->assertSee('Countries');
});

test('admin can create a new country', function () {
    $continent = Continent::factory()->create();

    $countryData = [
        'name' => 'Test Country',
        'code' => 'TC',
        'iso3' => 'TST',
        'continent_id' => $continent->id,
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/countries', $countryData)
        ->assertRedirect();

    $this->assertDatabaseHas('countries', [
        'name' => 'Test Country',
        'code' => 'TC',
        'iso3' => 'TST',
    ]);
});

test('admin can view a country', function () {
    $country = Country::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/countries/{$country->id}")
        ->assertSuccessful()
        ->assertSee($country->name);
});

test('admin can edit a country', function () {
    $country = Country::factory()->create();
    $updatedData = [
        'name' => 'Updated Country Name',
        'code' => 'UC',
        'iso3' => 'UPD',
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->put("/admin/countries/{$country->id}", $updatedData)
        ->assertRedirect();

    $this->assertDatabaseHas('countries', [
        'id' => $country->id,
        'name' => 'Updated Country Name',
        'code' => 'UC',
        'iso3' => 'UPD',
    ]);
});

test('admin can delete a country', function () {
    $country = Country::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/countries/{$country->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('countries', [
        'id' => $country->id,
    ]);
});

test('admin can bulk delete countries', function () {
    $countries = Country::factory()->count(3)->create();
    $countryIds = $countries->pluck('id')->toArray();

    $this->actingAs($this->admin)
        ->post('/admin/countries/bulk-delete', [
            'ids' => $countryIds,
        ])
        ->assertRedirect();

    foreach ($countryIds as $id) {
        $this->assertDatabaseMissing('countries', ['id' => $id]);
    }
});

test('non-admin users cannot access countries', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/countries')
        ->assertForbidden();
});

test('country form validation works', function () {
    $this->actingAs($this->admin)
        ->post('/admin/countries', [])
        ->assertSessionHasErrors([
            'name',
            'code',
            'iso3',
            'continent_id',
        ]);
});

test('country with unique codes validation works', function () {
    $existingCountry = Country::factory()->create([
        'code' => 'TC',
        'iso3' => 'TST',
    ]);

    $newCountryData = [
        'name' => 'Another Country',
        'code' => 'TC', // Duplicate
        'iso3' => 'TST', // Duplicate
        'continent_id' => $existingCountry->continent_id,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/countries', $newCountryData)
        ->assertSessionHasErrors([
            'code',
            'iso3',
        ]);
});

test('country search works', function () {
    $country1 = Country::factory()->create(['name' => 'Germany']);
    $country2 = Country::factory()->create(['name' => 'France']);

    $this->actingAs($this->admin)
        ->get('/admin/countries?search=Germany')
        ->assertSuccessful()
        ->assertSee($country1->name)
        ->assertDontSee($country2->name);
});

test('country filtering by continent works', function () {
    $continent1 = Continent::factory()->create(['name' => 'Europe']);
    $continent2 = Continent::factory()->create(['name' => 'Asia']);
    
    $country1 = Country::factory()->create(['continent_id' => $continent1->id]);
    $country2 = Country::factory()->create(['continent_id' => $continent2->id]);

    $this->actingAs($this->admin)
        ->get("/admin/countries?continent_id={$continent1->id}")
        ->assertSuccessful()
        ->assertSee($country1->name)
        ->assertDontSee($country2->name);
});

test('country filtering by active status works', function () {
    $activeCountry = Country::factory()->create(['is_active' => true]);
    $inactiveCountry = Country::factory()->create(['is_active' => false]);

    $this->actingAs($this->admin)
        ->get('/admin/countries?is_active=1')
        ->assertSuccessful()
        ->assertSee($activeCountry->name)
        ->assertDontSee($inactiveCountry->name);
});

test('country with continent relationship works', function () {
    $continent = Continent::factory()->create();
    $country = Country::factory()->create(['continent_id' => $continent->id]);

    $this->actingAs($this->admin)
        ->get("/admin/countries/{$country->id}")
        ->assertSuccessful()
        ->assertSee($country->name)
        ->assertSee($continent->name);
});
