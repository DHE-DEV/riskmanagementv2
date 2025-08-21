<?php

declare(strict_types=1);

use App\Models\{User, Continent};

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
        'is_active' => true,
    ]);
});

test('admin can view continents index page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/continents')
        ->assertSuccessful()
        ->assertSee('Continents');
});

test('admin can create a new continent', function () {
    $continentData = [
        'name' => 'Test Continent',
        'code' => 'TC',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/continents', $continentData)
        ->assertRedirect();

    $this->assertDatabaseHas('continents', [
        'name' => 'Test Continent',
        'code' => 'TC',
    ]);
});

test('admin can view a continent', function () {
    $continent = Continent::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/continents/{$continent->id}")
        ->assertSuccessful()
        ->assertSee($continent->name);
});

test('admin can edit a continent', function () {
    $continent = Continent::factory()->create();
    $updatedData = [
        'name' => 'Updated Continent Name',
        'code' => 'UC',
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->put("/admin/continents/{$continent->id}", $updatedData)
        ->assertRedirect();

    $this->assertDatabaseHas('continents', [
        'id' => $continent->id,
        'name' => 'Updated Continent Name',
        'code' => 'UC',
    ]);
});

test('admin can delete a continent', function () {
    $continent = Continent::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/continents/{$continent->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('continents', [
        'id' => $continent->id,
    ]);
});

test('admin can bulk delete continents', function () {
    $continents = Continent::factory()->count(3)->create();
    $continentIds = $continents->pluck('id')->toArray();

    $this->actingAs($this->admin)
        ->post('/admin/continents/bulk-delete', [
            'ids' => $continentIds,
        ])
        ->assertRedirect();

    foreach ($continentIds as $id) {
        $this->assertDatabaseMissing('continents', ['id' => $id]);
    }
});

test('non-admin users cannot access continents', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/continents')
        ->assertForbidden();
});

test('continent form validation works', function () {
    $this->actingAs($this->admin)
        ->post('/admin/continents', [])
        ->assertSessionHasErrors([
            'name',
            'code',
        ]);
});

test('continent with unique code validation works', function () {
    $existingContinent = Continent::factory()->create(['code' => 'TC']);

    $newContinentData = [
        'name' => 'Another Continent',
        'code' => 'TC', // Duplicate
    ];

    $this->actingAs($this->admin)
        ->post('/admin/continents', $newContinentData)
        ->assertSessionHasErrors(['code']);
});

test('continent search works', function () {
    $continent1 = Continent::factory()->create(['name' => 'Europe']);
    $continent2 = Continent::factory()->create(['name' => 'Asia']);

    $this->actingAs($this->admin)
        ->get('/admin/continents?search=Europe')
        ->assertSuccessful()
        ->assertSee($continent1->name)
        ->assertDontSee($continent2->name);
});

test('continent filtering by active status works', function () {
    $activeContinent = Continent::factory()->create(['is_active' => true]);
    $inactiveContinent = Continent::factory()->create(['is_active' => false]);

    $this->actingAs($this->admin)
        ->get('/admin/continents?is_active=1')
        ->assertSuccessful()
        ->assertSee($activeContinent->name)
        ->assertDontSee($inactiveContinent->name);
});

test('continent with relationships works', function () {
    $continent = Continent::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/continents/{$continent->id}")
        ->assertSuccessful()
        ->assertSee($continent->name);
});
