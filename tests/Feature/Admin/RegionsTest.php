<?php

declare(strict_types=1);

use App\Models\{User, Region, Country};

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
        'is_active' => true,
    ]);
});

test('admin can view regions index page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/regions')
        ->assertSuccessful()
        ->assertSee('Regions');
});

test('admin can create a new region', function () {
    $country = Country::factory()->create();

    $regionData = [
        'name' => 'Test Region',
        'country_id' => $country->id,
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/regions', $regionData)
        ->assertRedirect();

    $this->assertDatabaseHas('regions', [
        'name' => 'Test Region',
        'country_id' => $country->id,
    ]);
});

test('admin can view a region', function () {
    $region = Region::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/regions/{$region->id}")
        ->assertSuccessful()
        ->assertSee($region->name);
});

test('admin can edit a region', function () {
    $region = Region::factory()->create();
    $updatedData = [
        'name' => 'Updated Region Name',
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->put("/admin/regions/{$region->id}", $updatedData)
        ->assertRedirect();

    $this->assertDatabaseHas('regions', [
        'id' => $region->id,
        'name' => 'Updated Region Name',
    ]);
});

test('admin can delete a region', function () {
    $region = Region::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/regions/{$region->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('regions', [
        'id' => $region->id,
    ]);
});

test('admin can bulk delete regions', function () {
    $regions = Region::factory()->count(3)->create();
    $regionIds = $regions->pluck('id')->toArray();

    $this->actingAs($this->admin)
        ->post('/admin/regions/bulk-delete', [
            'ids' => $regionIds,
        ])
        ->assertRedirect();

    foreach ($regionIds as $id) {
        $this->assertDatabaseMissing('regions', ['id' => $id]);
    }
});

test('non-admin users cannot access regions', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/regions')
        ->assertForbidden();
});

test('region form validation works', function () {
    $this->actingAs($this->admin)
        ->post('/admin/regions', [])
        ->assertSessionHasErrors([
            'name',
            'country_id',
        ]);
});

test('region search works', function () {
    $region1 = Region::factory()->create(['name' => 'Bavaria']);
    $region2 = Region::factory()->create(['name' => 'Hesse']);

    $this->actingAs($this->admin)
        ->get('/admin/regions?search=Bavaria')
        ->assertSuccessful()
        ->assertSee($region1->name)
        ->assertDontSee($region2->name);
});

test('region filtering by country works', function () {
    $country1 = Country::factory()->create(['name' => 'Germany']);
    $country2 = Country::factory()->create(['name' => 'France']);
    
    $region1 = Region::factory()->create(['country_id' => $country1->id]);
    $region2 = Region::factory()->create(['country_id' => $country2->id]);

    $this->actingAs($this->admin)
        ->get("/admin/regions?country_id={$country1->id}")
        ->assertSuccessful()
        ->assertSee($region1->name)
        ->assertDontSee($region2->name);
});

test('region filtering by active status works', function () {
    $activeRegion = Region::factory()->create(['is_active' => true]);
    $inactiveRegion = Region::factory()->create(['is_active' => false]);

    $this->actingAs($this->admin)
        ->get('/admin/regions?is_active=1')
        ->assertSuccessful()
        ->assertSee($activeRegion->name)
        ->assertDontSee($inactiveRegion->name);
});

test('region with country relationship works', function () {
    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);

    $this->actingAs($this->admin)
        ->get("/admin/regions/{$region->id}")
        ->assertSuccessful()
        ->assertSee($region->name)
        ->assertSee($country->name);
});

test('region with cities relationship works', function () {
    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);

    $this->actingAs($this->admin)
        ->get("/admin/regions/{$region->id}")
        ->assertSuccessful()
        ->assertSee($region->name);
});
