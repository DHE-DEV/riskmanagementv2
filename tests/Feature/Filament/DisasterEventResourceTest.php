<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\DisasterEvents\DisasterEventResource;
use App\Models\DisasterEvent;
use App\Models\EventType;
use App\Models\Country;
use App\Models\Region;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
        'is_active' => true,
    ]);
    $this->actingAs($this->admin);

    // Create necessary related data
    $this->eventType = EventType::factory()->earthquake()->create();
    $this->country = Country::factory()->create();
    $this->region = Region::factory()->create(['country_id' => $this->country->id]);
    $this->city = City::factory()->create([
        'country_id' => $this->country->id,
        'region_id' => $this->region->id,
    ]);
});

// ============================================================================
// LIST / INDEX TESTS
// ============================================================================

test('can render disaster event list page', function () {
    $this->get(DisasterEventResource::getUrl('index'))
        ->assertSuccessful();
});

test('can list disaster events', function () {
    $events = DisasterEvent::factory()->count(10)->create([
        'event_type_id' => $this->eventType->id,
        'country_id' => $this->country->id,
    ]);

    Livewire::test(DisasterEventResource\Pages\ListDisasterEvents::class)
        ->assertCanSeeTableRecords($events);
});

test('can search disaster events by title', function () {
    $event1 = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'title' => 'Berlin Earthquake',
    ]);
    $event2 = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'title' => 'Munich Flood',
    ]);

    Livewire::test(DisasterEventResource\Pages\ListDisasterEvents::class)
        ->searchTable('Berlin')
        ->assertCanSeeTableRecords([$event1])
        ->assertCanNotSeeTableRecords([$event2]);
});

test('can sort disaster events by title', function () {
    $events = DisasterEvent::factory()->count(5)->create([
        'event_type_id' => $this->eventType->id,
    ]);

    Livewire::test(DisasterEventResource\Pages\ListDisasterEvents::class)
        ->sortTable('title')
        ->assertCanSeeTableRecords($events->sortBy('title'), inOrder: true);
});

test('can sort disaster events by event date', function () {
    $events = DisasterEvent::factory()->count(3)->create([
        'event_type_id' => $this->eventType->id,
    ]);

    Livewire::test(DisasterEventResource\Pages\ListDisasterEvents::class)
        ->sortTable('event_date')
        ->assertCanSeeTableRecords($events->sortBy('event_date'), inOrder: true);
});

test('can filter disaster events by severity', function () {
    $highEvent = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'severity' => 'high',
    ]);
    $lowEvent = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'severity' => 'low',
    ]);

    Livewire::test(DisasterEventResource\Pages\ListDisasterEvents::class)
        ->filterTable('severity', 'high')
        ->assertCanSeeTableRecords([$highEvent])
        ->assertCanNotSeeTableRecords([$lowEvent]);
});

test('can filter disaster events by active status', function () {
    $activeEvent = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => true,
    ]);
    $inactiveEvent = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => false,
    ]);

    Livewire::test(DisasterEventResource\Pages\ListDisasterEvents::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$activeEvent])
        ->assertCanNotSeeTableRecords([$inactiveEvent]);
});

// ============================================================================
// CREATE TESTS
// ============================================================================

test('can render disaster event create page', function () {
    $this->get(DisasterEventResource::getUrl('create'))
        ->assertSuccessful();
});

test('can create disaster event with required fields', function () {
    $eventData = [
        'title' => 'Test Earthquake Event',
        'event_type_id' => $this->eventType->id,
        'severity' => 'high',
        'event_date' => '2024-01-01',
        'is_active' => true,
        'external_sources' => ['source1'],
        'last_updated' => now(),
        'confidence_score' => 0,
        'processing_status' => 'pending',
        'gdacs_temporary' => false,
        'gdacs_is_current' => false,
    ];

    Livewire::test(DisasterEventResource\Pages\CreateDisasterEvent::class)
        ->fillForm($eventData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('disaster_events', [
        'title' => 'Test Earthquake Event',
        'severity' => 'high',
    ]);
});

test('can create disaster event with all fields', function () {
    $eventData = [
        'title' => 'Complete Earthquake Event',
        'description' => 'Detailed description of the earthquake',
        'severity' => 'critical',
        'event_type_id' => $this->eventType->id,
        'lat' => 50.1109,
        'lng' => 8.6821,
        'radius_km' => 15.5,
        'country_id' => $this->country->id,
        'region_id' => $this->region->id,
        'city_id' => $this->city->id,
        'affected_areas' => ['area1', 'area2'],
        'event_date' => '2024-01-01',
        'start_time' => '2024-01-01 10:00:00',
        'end_time' => '2024-01-01 18:00:00',
        'is_active' => true,
        'impact_assessment' => ['infrastructure', 'population'],
        'travel_recommendations' => ['avoid area'],
        'official_sources' => ['government website'],
        'media_coverage' => 'Extensive media coverage',
        'tourism_impact' => ['high impact'],
        'external_sources' => ['external1'],
        'last_updated' => now(),
        'confidence_score' => 85,
        'processing_status' => 'completed',
        'ai_summary' => 'AI generated summary',
        'ai_recommendations' => 'AI recommendations',
        'crisis_communication' => 'Crisis communication plan',
        'keywords' => ['earthquake', 'disaster'],
        'magnitude' => 6.5,
        'casualties' => '10 injured',
        'economic_impact' => 'Significant economic damage',
        'infrastructure_damage' => 'Roads and bridges affected',
        'emergency_response' => 'Emergency services deployed',
        'recovery_status' => 'Recovery in progress',
        'gdacs_temporary' => false,
        'gdacs_is_current' => true,
    ];

    Livewire::test(DisasterEventResource\Pages\CreateDisasterEvent::class)
        ->fillForm($eventData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('disaster_events', [
        'title' => 'Complete Earthquake Event',
        'severity' => 'critical',
        'magnitude' => 6.5,
    ]);
});

test('cannot create disaster event without required fields', function () {
    Livewire::test(DisasterEventResource\Pages\CreateDisasterEvent::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors([
            'title',
            'event_date',
            'external_sources',
            'last_updated',
            'processing_status',
        ]);
});

test('can create disaster event with coordinates', function () {
    $eventData = [
        'title' => 'Event with Coordinates',
        'event_type_id' => $this->eventType->id,
        'severity' => 'medium',
        'lat' => 52.5200,
        'lng' => 13.4050,
        'radius_km' => 10.0,
        'event_date' => '2024-01-01',
        'is_active' => true,
        'external_sources' => ['source1'],
        'last_updated' => now(),
        'confidence_score' => 0,
        'processing_status' => 'none',
        'gdacs_temporary' => false,
        'gdacs_is_current' => false,
    ];

    Livewire::test(DisasterEventResource\Pages\CreateDisasterEvent::class)
        ->fillForm($eventData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('disaster_events', [
        'title' => 'Event with Coordinates',
        'lat' => 52.5200,
        'lng' => 13.4050,
        'radius_km' => 10.0,
    ]);
});

test('can create disaster event with GDACS data', function () {
    $eventData = [
        'title' => 'GDACS Earthquake',
        'event_type_id' => $this->eventType->id,
        'severity' => 'high',
        'event_date' => '2024-01-01',
        'is_active' => true,
        'external_sources' => ['GDACS'],
        'last_updated' => now(),
        'confidence_score' => 95,
        'processing_status' => 'completed',
        'gdacs_event_id' => 'EQ123456',
        'gdacs_episode_id' => 'EP789',
        'gdacs_alert_level' => 'Red',
        'gdacs_alert_score' => 8.5,
        'gdacs_severity_value' => 7.2,
        'gdacs_severity_unit' => 'magnitude',
        'gdacs_population_value' => 1000000,
        'gdacs_population_unit' => 'people',
        'gdacs_vulnerability' => 0.8,
        'gdacs_iso3' => 'DEU',
        'gdacs_country' => 'Germany',
        'gdacs_is_current' => true,
        'gdacs_temporary' => false,
    ];

    Livewire::test(DisasterEventResource\Pages\CreateDisasterEvent::class)
        ->fillForm($eventData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('disaster_events', [
        'title' => 'GDACS Earthquake',
        'gdacs_event_id' => 'EQ123456',
        'gdacs_alert_level' => 'Red',
        'gdacs_is_current' => true,
    ]);
});

test('validates coordinates are numeric', function () {
    Livewire::test(DisasterEventResource\Pages\CreateDisasterEvent::class)
        ->fillForm([
            'title' => 'Test Event',
            'event_type_id' => $this->eventType->id,
            'lat' => 'not-a-number',
            'lng' => 'also-not-a-number',
            'severity' => 'medium',
            'event_date' => '2024-01-01',
            'is_active' => true,
            'external_sources' => ['source1'],
            'last_updated' => now(),
            'confidence_score' => 0,
            'processing_status' => 'none',
            'gdacs_temporary' => false,
            'gdacs_is_current' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['lat', 'lng']);
});

test('validates magnitude is numeric', function () {
    Livewire::test(DisasterEventResource\Pages\CreateDisasterEvent::class)
        ->fillForm([
            'title' => 'Test Event',
            'event_type_id' => $this->eventType->id,
            'magnitude' => 'very strong',
            'severity' => 'medium',
            'event_date' => '2024-01-01',
            'is_active' => true,
            'external_sources' => ['source1'],
            'last_updated' => now(),
            'confidence_score' => 0,
            'processing_status' => 'none',
            'gdacs_temporary' => false,
            'gdacs_is_current' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['magnitude']);
});

// ============================================================================
// EDIT / UPDATE TESTS
// ============================================================================

test('can render disaster event edit page', function () {
    $event = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
    ]);

    $this->get(DisasterEventResource::getUrl('edit', ['record' => $event]))
        ->assertSuccessful();
});

test('can retrieve disaster event data for editing', function () {
    $event = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'title' => 'Test Event',
        'severity' => 'high',
        'lat' => 50.1109,
        'lng' => 8.6821,
    ]);

    Livewire::test(DisasterEventResource\Pages\EditDisasterEvent::class, [
        'record' => $event->getRouteKey(),
    ])
        ->assertFormSet([
            'title' => 'Test Event',
            'severity' => 'high',
            'lat' => '50.110900',
            'lng' => '8.682100',
        ]);
});

test('can update disaster event with all fields', function () {
    $event = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
    ]);

    $updatedData = [
        'title' => 'Updated Event Title',
        'description' => 'Updated description',
        'severity' => 'critical',
        'lat' => 51.5074,
        'lng' => -0.1278,
        'magnitude' => 7.8,
        'is_active' => false,
    ];

    Livewire::test(DisasterEventResource\Pages\EditDisasterEvent::class, [
        'record' => $event->getRouteKey(),
    ])
        ->fillForm($updatedData)
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('disaster_events', [
        'id' => $event->id,
        'title' => 'Updated Event Title',
        'severity' => 'critical',
        'magnitude' => 7.8,
        'is_active' => false,
    ]);
});

test('can toggle disaster event active status', function () {
    $event = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => true,
    ]);

    Livewire::test(DisasterEventResource\Pages\EditDisasterEvent::class, [
        'record' => $event->getRouteKey(),
    ])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('disaster_events', [
        'id' => $event->id,
        'is_active' => false,
    ]);
});

test('can update disaster event relationships', function () {
    $event = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'country_id' => $this->country->id,
    ]);

    $newCountry = Country::factory()->create();
    $newRegion = Region::factory()->create(['country_id' => $newCountry->id]);
    $newCity = City::factory()->create([
        'country_id' => $newCountry->id,
        'region_id' => $newRegion->id,
    ]);

    Livewire::test(DisasterEventResource\Pages\EditDisasterEvent::class, [
        'record' => $event->getRouteKey(),
    ])
        ->fillForm([
            'country_id' => $newCountry->id,
            'region_id' => $newRegion->id,
            'city_id' => $newCity->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('disaster_events', [
        'id' => $event->id,
        'country_id' => $newCountry->id,
        'region_id' => $newRegion->id,
        'city_id' => $newCity->id,
    ]);
});

// ============================================================================
// DELETE TESTS (with soft deletes)
// ============================================================================

test('can soft delete disaster event', function () {
    $event = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
    ]);

    Livewire::test(DisasterEventResource\Pages\EditDisasterEvent::class, [
        'record' => $event->getRouteKey(),
    ])
        ->callAction('delete');

    $this->assertSoftDeleted('disaster_events', ['id' => $event->id]);
});

test('soft deleted events are not shown in list by default', function () {
    $activeEvent = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'title' => 'Active Event',
    ]);
    $deletedEvent = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'title' => 'Deleted Event',
    ]);
    $deletedEvent->delete();

    Livewire::test(DisasterEventResource\Pages\ListDisasterEvents::class)
        ->assertCanSeeTableRecords([$activeEvent])
        ->assertCanNotSeeTableRecords([$deletedEvent]);
});

// ============================================================================
// RELATIONSHIP TESTS
// ============================================================================

test('disaster event belongs to event type', function () {
    $event = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
    ]);

    expect($event->eventType)->not->toBeNull();
    expect($event->eventType->id)->toBe($this->eventType->id);
});

test('disaster event belongs to country', function () {
    $event = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'country_id' => $this->country->id,
    ]);

    expect($event->country)->not->toBeNull();
    expect($event->country->id)->toBe($this->country->id);
});

test('disaster event belongs to region', function () {
    $event = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'region_id' => $this->region->id,
    ]);

    expect($event->region)->not->toBeNull();
    expect($event->region->id)->toBe($this->region->id);
});

test('disaster event belongs to city', function () {
    $event = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'city_id' => $this->city->id,
    ]);

    expect($event->city)->not->toBeNull();
    expect($event->city->id)->toBe($this->city->id);
});

// ============================================================================
// SCOPE TESTS
// ============================================================================

test('active scope returns only active events', function () {
    DisasterEvent::factory()->count(3)->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => true,
    ]);
    DisasterEvent::factory()->count(2)->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => false,
    ]);

    $activeEvents = DisasterEvent::active()->get();
    expect($activeEvents)->toHaveCount(3);
});

test('bySeverity scope filters by severity', function () {
    DisasterEvent::factory()->count(2)->create([
        'event_type_id' => $this->eventType->id,
        'severity' => 'high',
    ]);
    DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'severity' => 'low',
    ]);

    $highSeverityEvents = DisasterEvent::bySeverity('high')->get();
    expect($highSeverityEvents)->toHaveCount(2);
});

test('byCountry scope filters by country', function () {
    $country1 = Country::factory()->create();
    $country2 = Country::factory()->create();

    DisasterEvent::factory()->count(3)->create([
        'event_type_id' => $this->eventType->id,
        'country_id' => $country1->id,
    ]);
    DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'country_id' => $country2->id,
    ]);

    $country1Events = DisasterEvent::byCountry($country1->id)->get();
    expect($country1Events)->toHaveCount(3);
});

test('inDateRange scope filters by date range', function () {
    DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'event_date' => '2024-01-15',
    ]);
    DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'event_date' => '2024-02-15',
    ]);
    DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'event_date' => '2024-03-15',
    ]);

    $events = DisasterEvent::inDateRange('2024-01-01', '2024-02-28')->get();
    expect($events)->toHaveCount(2);
});

test('currentGdacs scope returns only current GDACS events', function () {
    DisasterEvent::factory()->count(2)->create([
        'event_type_id' => $this->eventType->id,
        'gdacs_is_current' => true,
    ]);
    DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'gdacs_is_current' => false,
    ]);

    $currentGdacsEvents = DisasterEvent::currentGdacs()->get();
    expect($currentGdacsEvents)->toHaveCount(2);
});

// ============================================================================
// STATIC METHOD TESTS
// ============================================================================

test('getSeverityOptions returns correct options', function () {
    $options = DisasterEvent::getSeverityOptions();

    expect($options)->toBeArray();
    expect($options)->toHaveKeys(['low', 'medium', 'high']);
});

test('getProcessingStatusOptions returns correct options', function () {
    $options = DisasterEvent::getProcessingStatusOptions();

    expect($options)->toBeArray();
    expect($options)->toHaveKeys(['pending', 'processing', 'completed', 'failed']);
});

test('getGdacsAlertLevelOptions returns correct options', function () {
    $options = DisasterEvent::getGdacsAlertLevelOptions();

    expect($options)->toBeArray();
    expect($options)->toHaveKeys(['green', 'orange', 'red']);
});

// ============================================================================
// NAVIGATION BADGE TESTS
// ============================================================================

test('navigation badge shows count of active events', function () {
    DisasterEvent::factory()->count(5)->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => true,
    ]);
    DisasterEvent::factory()->count(2)->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => false,
    ]);

    $badge = DisasterEventResource::getNavigationBadge();
    expect($badge)->toBe(5);
});

test('navigation badge color is danger', function () {
    $color = DisasterEventResource::getNavigationBadgeColor();
    expect($color)->toBe('danger');
});

// ============================================================================
// AUTHORIZATION TESTS
// ============================================================================

test('non-admin users cannot access disaster events', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(DisasterEventResource::getUrl('index'))
        ->assertForbidden();
});

test('unauthorized users cannot create disaster events', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(DisasterEventResource::getUrl('create'))
        ->assertForbidden();
});

test('unauthorized users cannot edit disaster events', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $event = DisasterEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
    ]);
    $this->actingAs($user);

    $this->get(DisasterEventResource::getUrl('edit', ['record' => $event]))
        ->assertForbidden();
});

// ============================================================================
// BULK DELETE TESTS
// ============================================================================

test('can bulk delete disaster events', function () {
    $events = DisasterEvent::factory()->count(3)->create([
        'event_type_id' => $this->eventType->id,
    ]);

    Livewire::test(DisasterEventResource\Pages\ListDisasterEvents::class)
        ->callTableBulkAction('delete', $events);

    foreach ($events as $event) {
        $this->assertSoftDeleted('disaster_events', ['id' => $event->id]);
    }
});
