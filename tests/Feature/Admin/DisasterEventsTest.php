<?php

declare(strict_types=1);

use App\Models\{User, DisasterEvent, Country, Region, City};

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
        'is_active' => true,
    ]);
});

test('admin can view disaster events index page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/disaster-events')
        ->assertSuccessful()
        ->assertSee('Disaster Events');
});

test('admin can create a new disaster event', function () {
    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['country_id' => $country->id, 'region_id' => $region->id]);

    $eventData = [
        'title' => 'Test Disaster Event',
        'description' => 'This is a test disaster event',
        'severity' => 'high',
        'event_type' => 'earthquake',
        'lat' => 50.1109,
        'lng' => 8.6821,
        'radius_km' => 10.5,
        'country_id' => $country->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'affected_areas' => ['area1', 'area2'],
        'event_date' => '2024-01-01',
        'start_time' => '2024-01-01 10:00:00',
        'end_time' => '2024-01-01 18:00:00',
        'impact_assessment' => ['assessment1', 'assessment2'],
        'travel_recommendations' => ['recommendation1'],
        'official_sources' => ['source1'],
        'media_coverage' => 'Test media coverage',
        'tourism_impact' => ['impact1'],
        'external_sources' => ['external1'],
        'ai_summary' => 'AI generated summary',
        'ai_recommendations' => 'AI recommendations',
        'crisis_communication' => 'Crisis communication info',
        'keywords' => ['earthquake', 'disaster'],
        'magnitude' => 6.5,
        'casualties' => 10,
        'economic_impact' => 'High economic impact',
        'infrastructure_damage' => 'Significant damage',
        'emergency_response' => 'Active response',
        'recovery_status' => 'In progress',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/disaster-events', $eventData)
        ->assertRedirect();

    $this->assertDatabaseHas('disaster_events', [
        'title' => 'Test Disaster Event',
        'event_type' => 'earthquake',
        'severity' => 'high',
    ]);
});

test('admin can view a disaster event', function () {
    $event = DisasterEvent::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/disaster-events/{$event->id}")
        ->assertSuccessful()
        ->assertSee($event->title);
});

test('admin can edit a disaster event', function () {
    $event = DisasterEvent::factory()->create();
    $updatedData = [
        'title' => 'Updated Disaster Event',
        'description' => 'Updated description',
        'event_type' => 'flood',
        'severity' => 'critical',
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->put("/admin/disaster-events/{$event->id}", $updatedData)
        ->assertRedirect();

    $this->assertDatabaseHas('disaster_events', [
        'id' => $event->id,
        'title' => 'Updated Disaster Event',
        'event_type' => 'flood',
        'severity' => 'critical',
    ]);
});

test('admin can delete a disaster event', function () {
    $event = DisasterEvent::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/disaster-events/{$event->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('disaster_events', [
        'id' => $event->id,
    ]);
});

test('admin can bulk delete disaster events', function () {
    $events = DisasterEvent::factory()->count(3)->create();
    $eventIds = $events->pluck('id')->toArray();

    $this->actingAs($this->admin)
        ->post('/admin/disaster-events/bulk-delete', [
            'ids' => $eventIds,
        ])
        ->assertRedirect();

    foreach ($eventIds as $id) {
        $this->assertDatabaseMissing('disaster_events', ['id' => $id]);
    }
});

test('non-admin users cannot access disaster events', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/disaster-events')
        ->assertForbidden();
});

test('disaster event form validation works', function () {
    $this->actingAs($this->admin)
        ->post('/admin/disaster-events', [])
        ->assertSessionHasErrors([
            'title',
            'event_type',
            'severity',
        ]);
});

test('disaster event search works', function () {
    $event1 = DisasterEvent::factory()->create(['title' => 'Berlin Earthquake']);
    $event2 = DisasterEvent::factory()->create(['title' => 'Munich Flood']);

    $this->actingAs($this->admin)
        ->get('/admin/disaster-events?search=Berlin')
        ->assertSuccessful()
        ->assertSee($event1->title)
        ->assertDontSee($event2->title);
});

test('disaster event filtering by event type works', function () {
    $earthquakeEvent = DisasterEvent::factory()->create(['event_type' => 'earthquake']);
    $floodEvent = DisasterEvent::factory()->create(['event_type' => 'flood']);

    $this->actingAs($this->admin)
        ->get('/admin/disaster-events?event_type=earthquake')
        ->assertSuccessful()
        ->assertSee($earthquakeEvent->title)
        ->assertDontSee($floodEvent->title);
});

test('disaster event filtering by severity works', function () {
    $highSeverityEvent = DisasterEvent::factory()->create(['severity' => 'high']);
    $lowSeverityEvent = DisasterEvent::factory()->create(['severity' => 'low']);

    $this->actingAs($this->admin)
        ->get('/admin/disaster-events?severity=high')
        ->assertSuccessful()
        ->assertSee($highSeverityEvent->title)
        ->assertDontSee($lowSeverityEvent->title);
});

test('disaster event filtering by country works', function () {
    $country1 = Country::factory()->create(['name' => 'Germany']);
    $country2 = Country::factory()->create(['name' => 'France']);
    
    $event1 = DisasterEvent::factory()->create(['country_id' => $country1->id]);
    $event2 = DisasterEvent::factory()->create(['country_id' => $country2->id]);

    $this->actingAs($this->admin)
        ->get("/admin/disaster-events?country_id={$country1->id}")
        ->assertSuccessful()
        ->assertSee($event1->title)
        ->assertDontSee($event2->title);
});

test('disaster event filtering by active status works', function () {
    $activeEvent = DisasterEvent::factory()->create(['is_active' => true]);
    $inactiveEvent = DisasterEvent::factory()->create(['is_active' => false]);

    $this->actingAs($this->admin)
        ->get('/admin/disaster-events?is_active=1')
        ->assertSuccessful()
        ->assertSee($activeEvent->title)
        ->assertDontSee($inactiveEvent->title);
});

test('disaster event with coordinates works', function () {
    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['country_id' => $country->id, 'region_id' => $region->id]);

    $eventData = [
        'title' => 'Test Event with Coordinates',
        'description' => 'Test description',
        'event_type' => 'earthquake',
        'lat' => 50.1109,
        'lng' => 8.6821,
        'radius_km' => 15.0,
        'country_id' => $country->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'severity' => 'medium',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/disaster-events', $eventData)
        ->assertRedirect();

    $this->assertDatabaseHas('disaster_events', [
        'title' => 'Test Event with Coordinates',
        'lat' => 50.1109,
        'lng' => 8.6821,
        'radius_km' => 15.0,
    ]);
});

test('disaster event with relationships works', function () {
    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['country_id' => $country->id, 'region_id' => $region->id]);
    
    $event = DisasterEvent::factory()->create([
        'country_id' => $country->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
    ]);

    $this->actingAs($this->admin)
        ->get("/admin/disaster-events/{$event->id}")
        ->assertSuccessful()
        ->assertSee($event->title)
        ->assertSee($country->name)
        ->assertSee($region->name)
        ->assertSee($city->name);
});

test('disaster event with complex data works', function () {
    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['country_id' => $country->id, 'region_id' => $region->id]);

    $eventData = [
        'title' => 'Complex Disaster Event',
        'description' => 'Complex event description',
        'event_type' => 'hurricane',
        'severity' => 'critical',
        'country_id' => $country->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'affected_areas' => ['coastal', 'inland'],
        'impact_assessment' => ['infrastructure', 'population'],
        'travel_recommendations' => ['avoid area', 'evacuate'],
        'keywords' => ['hurricane', 'storm', 'emergency'],
        'magnitude' => 8.5,
        'casualties' => 25,
        'economic_impact' => 'Severe economic impact',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/disaster-events', $eventData)
        ->assertRedirect();

    $this->assertDatabaseHas('disaster_events', [
        'title' => 'Complex Disaster Event',
        'event_type' => 'hurricane',
        'severity' => 'critical',
        'magnitude' => 8.5,
        'casualties' => 25,
    ]);
});

// Model method tests (keeping some of the original tests)
test('disaster event priority options work', function () {
    $options = DisasterEvent::getPriorityOptions();
    
    $this->assertIsArray($options);
    $this->assertArrayHasKey('low', $options);
    $this->assertArrayHasKey('medium', $options);
    $this->assertArrayHasKey('high', $options);
    $this->assertArrayHasKey('critical', $options);
});

test('disaster event severity options work', function () {
    $options = DisasterEvent::getSeverityOptions();
    
    $this->assertIsArray($options);
    $this->assertArrayHasKey('low', $options);
    $this->assertArrayHasKey('medium', $options);
    $this->assertArrayHasKey('high', $options);
    $this->assertArrayHasKey('critical', $options);
});

test('disaster event event type options work', function () {
    $options = DisasterEvent::getEventTypeOptions();
    
    $this->assertIsArray($options);
    $this->assertNotEmpty($options);
    $this->assertArrayHasKey('earthquake', $options);
    $this->assertArrayHasKey('hurricane', $options);
    $this->assertArrayHasKey('flood', $options);
});

test('disaster event processing status options work', function () {
    $options = DisasterEvent::getProcessingStatusOptions();
    
    $this->assertIsArray($options);
    $this->assertArrayHasKey('pending', $options);
    $this->assertArrayHasKey('processing', $options);
    $this->assertArrayHasKey('completed', $options);
    $this->assertArrayHasKey('failed', $options);
});

test('disaster event GDACS alert level options work', function () {
    $options = DisasterEvent::getGdacsAlertLevelOptions();
    
    $this->assertIsArray($options);
    $this->assertArrayHasKey('green', $options);
    $this->assertArrayHasKey('orange', $options);
    $this->assertArrayHasKey('red', $options);
});
