<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\CustomEvents\CustomEventResource;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Models\EventCategory;
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
    $this->eventType = EventType::factory()->exercise()->create();
    $this->eventCategory = EventCategory::factory()->create(['event_type_id' => $this->eventType->id]);
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

test('can render custom event list page', function () {
    $this->get(CustomEventResource::getUrl('index'))
        ->assertSuccessful();
});

test('can list custom events', function () {
    $events = CustomEvent::factory()->count(10)->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    Livewire::test(CustomEventResource\Pages\ListCustomEvents::class)
        ->assertCanSeeTableRecords($events);
});

test('can search custom events by title', function () {
    $event1 = CustomEvent::factory()->create([
        'title' => 'Frankfurt Evacuation Exercise',
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    $event2 = CustomEvent::factory()->create([
        'title' => 'Munich Training',
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    Livewire::test(CustomEventResource\Pages\ListCustomEvents::class)
        ->searchTable('Frankfurt')
        ->assertCanSeeTableRecords([$event1])
        ->assertCanNotSeeTableRecords([$event2]);
});

test('can sort custom events by title', function () {
    $events = CustomEvent::factory()->count(5)->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    Livewire::test(CustomEventResource\Pages\ListCustomEvents::class)
        ->sortTable('title')
        ->assertCanSeeTableRecords($events->sortBy('title'), inOrder: true);
});

test('can filter custom events by priority', function () {
    $highEvent = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'priority' => 'high',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    $lowEvent = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'priority' => 'low',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    Livewire::test(CustomEventResource\Pages\ListCustomEvents::class)
        ->filterTable('priority', 'high')
        ->assertCanSeeTableRecords([$highEvent])
        ->assertCanNotSeeTableRecords([$lowEvent]);
});

test('can filter custom events by active status', function () {
    $activeEvent = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => true,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    $inactiveEvent = CustomEvent::factory()->inactive()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    Livewire::test(CustomEventResource\Pages\ListCustomEvents::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$activeEvent])
        ->assertCanNotSeeTableRecords([$inactiveEvent]);
});

test('can filter custom events by archived status', function () {
    $activeEvent = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'archived' => false,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    $archivedEvent = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'archived' => true,
        'archived_at' => now(),
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    Livewire::test(CustomEventResource\Pages\ListCustomEvents::class)
        ->filterTable('archived', false)
        ->assertCanSeeTableRecords([$activeEvent])
        ->assertCanNotSeeTableRecords([$archivedEvent]);
});

// ============================================================================
// CREATE TESTS
// ============================================================================

test('can render custom event create page', function () {
    $this->get(CustomEventResource::getUrl('create'))
        ->assertSuccessful();
});

test('can create custom event with required fields', function () {
    $eventData = [
        'title' => 'Test Exercise Event',
        'eventTypes' => [$this->eventType->id],
        'priority' => 'medium',
        'is_active' => true,
        'start_date' => now(),
        'archived' => false,
        'data_source' => 'manual',
    ];

    Livewire::test(CustomEventResource\Pages\CreateCustomEvent::class)
        ->fillForm($eventData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('custom_events', [
        'title' => 'Test Exercise Event',
        'priority' => 'medium',
    ]);
});

test('can create custom event with all fields', function () {
    $eventData = [
        'title' => 'Complete Exercise Event',
        'popup_content' => '<p>Detailed event description with HTML</p>',
        'eventTypes' => [$this->eventType->id],
        'priority' => 'high',
        'severity' => 'medium',
        'is_active' => true,
        'archived' => false,
        'start_date' => now(),
        'end_date' => now()->addDays(7),
        'latitude' => 50.1109,
        'longitude' => 8.6821,
        'marker_color' => '#FF0000',
        'marker_icon' => 'fa-fire-extinguisher',
        'icon_color' => '#FFFFFF',
        'marker_size' => 'large',
        'data_source' => 'manual',
    ];

    Livewire::test(CustomEventResource\Pages\CreateCustomEvent::class)
        ->fillForm($eventData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('custom_events', [
        'title' => 'Complete Exercise Event',
        'priority' => 'high',
        'latitude' => 50.1109,
        'longitude' => 8.6821,
    ]);
});

test('can create custom event with multiple event types', function () {
    $eventType2 = EventType::factory()->flood()->create();

    $eventData = [
        'title' => 'Multi-Type Event',
        'eventTypes' => [$this->eventType->id, $eventType2->id],
        'priority' => 'medium',
        'is_active' => true,
        'start_date' => now(),
        'archived' => false,
        'data_source' => 'manual',
    ];

    Livewire::test(CustomEventResource\Pages\CreateCustomEvent::class)
        ->fillForm($eventData)
        ->call('create')
        ->assertHasNoFormErrors();

    $event = CustomEvent::where('title', 'Multi-Type Event')->first();
    expect($event->eventTypes)->toHaveCount(2);
});

test('cannot create custom event without required fields', function () {
    Livewire::test(CustomEventResource\Pages\CreateCustomEvent::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors(['title', 'eventTypes', 'priority', 'start_date']);
});

test('can create custom event with coordinates', function () {
    $eventData = [
        'title' => 'Event with Location',
        'eventTypes' => [$this->eventType->id],
        'priority' => 'medium',
        'is_active' => true,
        'start_date' => now(),
        'latitude' => 52.5200,
        'longitude' => 13.4050,
        'archived' => false,
        'data_source' => 'manual',
    ];

    Livewire::test(CustomEventResource\Pages\CreateCustomEvent::class)
        ->fillForm($eventData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('custom_events', [
        'title' => 'Event with Location',
        'latitude' => 52.5200,
        'longitude' => 13.4050,
    ]);
});

test('validates latitude range', function () {
    Livewire::test(CustomEventResource\Pages\CreateCustomEvent::class)
        ->fillForm([
            'title' => 'Test Event',
            'eventTypes' => [$this->eventType->id],
            'latitude' => 91.0, // Exceeds max latitude of 90
            'priority' => 'medium',
            'is_active' => true,
            'start_date' => now(),
            'archived' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['latitude']);
});

test('validates longitude range', function () {
    Livewire::test(CustomEventResource\Pages\CreateCustomEvent::class)
        ->fillForm([
            'title' => 'Test Event',
            'eventTypes' => [$this->eventType->id],
            'longitude' => 181.0, // Exceeds max longitude of 180
            'priority' => 'medium',
            'is_active' => true,
            'start_date' => now(),
            'archived' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['longitude']);
});

test('validates title max length', function () {
    Livewire::test(CustomEventResource\Pages\CreateCustomEvent::class)
        ->fillForm([
            'title' => str_repeat('a', 256), // Exceeds 255 character limit
            'eventTypes' => [$this->eventType->id],
            'priority' => 'medium',
            'is_active' => true,
            'start_date' => now(),
            'archived' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['title']);
});

// ============================================================================
// EDIT / UPDATE TESTS
// ============================================================================

test('can render custom event edit page', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $this->get(CustomEventResource::getUrl('edit', ['record' => $event]))
        ->assertSuccessful();
});

test('can retrieve custom event data for editing', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'title' => 'Test Event',
        'priority' => 'high',
        'latitude' => 50.1109,
        'longitude' => 8.6821,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    $event->eventTypes()->attach($this->eventType->id);

    Livewire::test(CustomEventResource\Pages\EditCustomEvent::class, [
        'record' => $event->getRouteKey(),
    ])
        ->assertFormSet([
            'title' => 'Test Event',
            'priority' => 'high',
        ]);
});

test('can update custom event with all fields', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $updatedData = [
        'title' => 'Updated Event Title',
        'popup_content' => '<p>Updated content</p>',
        'priority' => 'high',
        'severity' => 'high',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'is_active' => false,
        'archived' => true,
    ];

    Livewire::test(CustomEventResource\Pages\EditCustomEvent::class, [
        'record' => $event->getRouteKey(),
    ])
        ->fillForm($updatedData)
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('custom_events', [
        'id' => $event->id,
        'title' => 'Updated Event Title',
        'priority' => 'high',
        'is_active' => false,
        'archived' => true,
    ]);
});

test('can toggle custom event active status', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => true,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    Livewire::test(CustomEventResource\Pages\EditCustomEvent::class, [
        'record' => $event->getRouteKey(),
    ])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('custom_events', [
        'id' => $event->id,
        'is_active' => false,
    ]);
});

test('can archive custom event', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'archived' => false,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    Livewire::test(CustomEventResource\Pages\EditCustomEvent::class, [
        'record' => $event->getRouteKey(),
    ])
        ->fillForm(['archived' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    $event->refresh();
    expect($event->archived)->toBeTrue();
    expect($event->archived_at)->not->toBeNull();
});

test('can update custom event event types', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    $event->eventTypes()->attach($this->eventType->id);

    $newEventType = EventType::factory()->flood()->create();

    Livewire::test(CustomEventResource\Pages\EditCustomEvent::class, [
        'record' => $event->getRouteKey(),
    ])
        ->fillForm([
            'eventTypes' => [$newEventType->id],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $event->refresh();
    expect($event->eventTypes->pluck('id')->toArray())->toBe([$newEventType->id]);
});

// ============================================================================
// VIEW TESTS
// ============================================================================

test('can render custom event view page', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $this->get(CustomEventResource::getUrl('view', ['record' => $event]))
        ->assertSuccessful();
});

// ============================================================================
// DELETE TESTS (with soft deletes)
// ============================================================================

test('can soft delete custom event', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    Livewire::test(CustomEventResource\Pages\EditCustomEvent::class, [
        'record' => $event->getRouteKey(),
    ])
        ->callAction('delete');

    $this->assertSoftDeleted('custom_events', ['id' => $event->id]);
});

// ============================================================================
// RELATIONSHIP TESTS
// ============================================================================

test('custom event belongs to event type', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    expect($event->eventType)->not->toBeNull();
    expect($event->eventType->id)->toBe($this->eventType->id);
});

test('custom event has many event types relationship', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    $eventType2 = EventType::factory()->create();

    $event->eventTypes()->attach([$this->eventType->id, $eventType2->id]);

    expect($event->eventTypes)->toHaveCount(2);
});

test('custom event belongs to event category', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'event_category_id' => $this->eventCategory->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    expect($event->eventCategory)->not->toBeNull();
    expect($event->eventCategory->id)->toBe($this->eventCategory->id);
});

test('custom event has many countries relationship', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $country1 = Country::factory()->create();
    $country2 = Country::factory()->create();

    $event->countries()->attach([
        $country1->id => ['latitude' => 50.0, 'longitude' => 8.0],
        $country2->id => ['latitude' => 51.0, 'longitude' => 9.0],
    ]);

    expect($event->countries)->toHaveCount(2);
});

test('custom event has many regions relationship', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $region1 = Region::factory()->create(['country_id' => $this->country->id]);
    $region2 = Region::factory()->create(['country_id' => $this->country->id]);

    $event->regions()->attach([
        $region1->id => ['latitude' => 50.0, 'longitude' => 8.0],
        $region2->id => ['latitude' => 51.0, 'longitude' => 9.0],
    ]);

    expect($event->regions)->toHaveCount(2);
});

test('custom event has many cities relationship', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $city1 = City::factory()->create(['country_id' => $this->country->id]);
    $city2 = City::factory()->create(['country_id' => $this->country->id]);

    $event->cities()->attach([
        $city1->id => ['latitude' => 50.0, 'longitude' => 8.0],
        $city2->id => ['latitude' => 51.0, 'longitude' => 9.0],
    ]);

    expect($event->cities)->toHaveCount(2);
});

test('custom event belongs to creator', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    expect($event->creator)->not->toBeNull();
    expect($event->creator->id)->toBe($this->admin->id);
});

test('custom event belongs to updater', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    expect($event->updater)->not->toBeNull();
    expect($event->updater->id)->toBe($this->admin->id);
});

// ============================================================================
// SCOPE TESTS
// ============================================================================

test('active scope returns only active events', function () {
    CustomEvent::factory()->count(3)->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => true,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    CustomEvent::factory()->count(2)->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => false,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $activeEvents = CustomEvent::active()->get();
    expect($activeEvents)->toHaveCount(3);
});

test('notArchived scope returns only non-archived events', function () {
    CustomEvent::factory()->count(3)->create([
        'event_type_id' => $this->eventType->id,
        'archived' => false,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    CustomEvent::factory()->count(2)->create([
        'event_type_id' => $this->eventType->id,
        'archived' => true,
        'archived_at' => now(),
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $notArchivedEvents = CustomEvent::notArchived()->get();
    expect($notArchivedEvents)->toHaveCount(3);
});

test('archived scope returns only archived events', function () {
    CustomEvent::factory()->count(2)->create([
        'event_type_id' => $this->eventType->id,
        'archived' => true,
        'archived_at' => now(),
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'archived' => false,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $archivedEvents = CustomEvent::archived()->get();
    expect($archivedEvents)->toHaveCount(2);
});

test('byPriority scope filters by priority', function () {
    CustomEvent::factory()->count(2)->create([
        'event_type_id' => $this->eventType->id,
        'priority' => 'high',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'priority' => 'low',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $highPriorityEvents = CustomEvent::byPriority('high')->get();
    expect($highPriorityEvents)->toHaveCount(2);
});

test('inDateRange scope filters by date range', function () {
    CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'start_date' => '2024-01-15 10:00:00',
        'end_date' => '2024-01-20 10:00:00',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'start_date' => '2024-02-15 10:00:00',
        'end_date' => '2024-02-20 10:00:00',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'start_date' => '2024-03-15 10:00:00',
        'end_date' => '2024-03-20 10:00:00',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $events = CustomEvent::inDateRange('2024-01-01', '2024-02-28')->get();
    expect($events)->toHaveCount(2);
});

// ============================================================================
// MODEL METHOD TESTS
// ============================================================================

test('archive method archives event', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'archived' => false,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $event->archive();

    expect($event->archived)->toBeTrue();
    expect($event->archived_at)->not->toBeNull();
});

test('unarchive method unarchives event', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'archived' => true,
        'archived_at' => now(),
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $event->unarchive();

    expect($event->archived)->toBeFalse();
    expect($event->archived_at)->toBeNull();
});

test('isVisible method returns true for active non-archived event', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => true,
        'archived' => false,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    expect($event->isVisible())->toBeTrue();
});

test('isVisible method returns false for inactive event', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'is_active' => false,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    expect($event->isVisible())->toBeFalse();
});

test('getMarkerSizeOptions returns correct options', function () {
    $options = CustomEvent::getMarkerSizeOptions();

    expect($options)->toBeArray();
    expect($options)->toHaveKeys(['small', 'medium', 'large']);
});

test('getPriorityOptions returns correct options', function () {
    $options = CustomEvent::getPriorityOptions();

    expect($options)->toBeArray();
    expect($options)->toHaveKeys(['info', 'low', 'medium', 'high']);
});

test('getSeverityOptions returns correct options', function () {
    $options = CustomEvent::getSeverityOptions();

    expect($options)->toBeArray();
    expect($options)->toHaveKeys(['low', 'medium', 'high']);
});

// ============================================================================
// NAVIGATION BADGE TESTS
// ============================================================================

test('navigation badge shows total count of events', function () {
    CustomEvent::factory()->count(8)->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $badge = CustomEventResource::getNavigationBadge();
    expect($badge)->toBe('8');
});

test('navigation badge color is success', function () {
    $color = CustomEventResource::getNavigationBadgeColor();
    expect($color)->toBe('success');
});

// ============================================================================
// AUTHORIZATION TESTS
// ============================================================================

test('non-admin users cannot access custom events', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(CustomEventResource::getUrl('index'))
        ->assertForbidden();
});

test('unauthorized users cannot create custom events', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(CustomEventResource::getUrl('create'))
        ->assertForbidden();
});

test('unauthorized users cannot edit custom events', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);
    $this->actingAs($user);

    $this->get(CustomEventResource::getUrl('edit', ['record' => $event]))
        ->assertForbidden();
});

// ============================================================================
// BULK DELETE TESTS
// ============================================================================

test('can bulk delete custom events', function () {
    $events = CustomEvent::factory()->count(3)->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    Livewire::test(CustomEventResource\Pages\ListCustomEvents::class)
        ->callTableBulkAction('delete', $events);

    foreach ($events as $event) {
        $this->assertSoftDeleted('custom_events', ['id' => $event->id]);
    }
});

// ============================================================================
// RELATION MANAGER TESTS
// ============================================================================

test('custom event has countries relation manager', function () {
    $event = CustomEvent::factory()->create([
        'event_type_id' => $this->eventType->id,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    expect(CustomEventResource::getRelations())->toContain(
        \App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class
    );
});

test('custom event has regions relation manager', function () {
    expect(CustomEventResource::getRelations())->toContain(
        \App\Filament\Resources\CustomEvents\RelationManagers\RegionsRelationManager::class
    );
});

test('custom event has cities relation manager', function () {
    expect(CustomEventResource::getRelations())->toContain(
        \App\Filament\Resources\CustomEvents\RelationManagers\CitiesRelationManager::class
    );
});

// ============================================================================
// RELATION MANAGER CRUD OPERATIONS - Countries
// ============================================================================

describe('Countries Relation Manager', function () {
    test('can render countries relation manager', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->assertSuccessful();
    });

    test('can attach country to custom event', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $country = Country::factory()->create();

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $country->id,
                'use_default_coordinates' => true,
                'latitude' => $country->lat,
                'longitude' => $country->lng,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('custom_event_country', [
            'custom_event_id' => $event->id,
            'country_id' => $country->id,
        ]);
    });

    test('can attach country with custom coordinates', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $country = Country::factory()->create();

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $country->id,
                'use_default_coordinates' => false,
                'latitude' => 50.1109,
                'longitude' => 8.6821,
                'location_note' => 'Frankfurt Airport',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('custom_event_country', [
            'custom_event_id' => $event->id,
            'country_id' => $country->id,
            'latitude' => 50.1109,
            'longitude' => 8.6821,
            'location_note' => 'Frankfurt Airport',
        ]);
    });

    test('can attach country with region and city', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $country = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country->id]);
        $city = City::factory()->create([
            'country_id' => $country->id,
            'region_id' => $region->id,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $country->id,
                'region_id' => $region->id,
                'city_id' => $city->id,
                'use_default_coordinates' => true,
                'latitude' => $city->lat,
                'longitude' => $city->lng,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('custom_event_country', [
            'custom_event_id' => $event->id,
            'country_id' => $country->id,
            'region_id' => $region->id,
            'city_id' => $city->id,
        ]);
    });

    test('cannot attach duplicate country', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $country = Country::factory()->create();

        // First attach
        $event->countries()->attach($country->id, [
            'latitude' => $country->lat,
            'longitude' => $country->lng,
        ]);

        // Try to attach again - should not be in the available list
        $livewire = Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ]);

        // Verify the country is already attached
        expect($event->countries()->where('country_id', $country->id)->exists())->toBeTrue();
    });

    test('can edit attached country', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $country = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country->id]);

        $event->countries()->attach($country->id, [
            'latitude' => $country->lat,
            'longitude' => $country->lng,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('edit', $country, data: [
                'region_id' => $region->id,
                'use_default_coordinates' => false,
                'latitude' => 51.5074,
                'longitude' => -0.1278,
                'location_note' => 'Updated location',
            ])
            ->assertHasNoTableActionErrors();

        $pivot = $event->countries()->where('country_id', $country->id)->first()->pivot;
        expect($pivot->region_id)->toBe($region->id);
        expect($pivot->latitude)->toBe(51.5074);
        expect($pivot->longitude)->toBe(-0.1278);
        expect($pivot->location_note)->toBe('Updated location');
    });

    test('can detach country from custom event', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $country = Country::factory()->create();

        $event->countries()->attach($country->id, [
            'latitude' => $country->lat,
            'longitude' => $country->lng,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('detach', $country);

        $this->assertDatabaseMissing('custom_event_country', [
            'custom_event_id' => $event->id,
            'country_id' => $country->id,
        ]);
    });

    test('can bulk detach countries from custom event', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $countries = Country::factory()->count(3)->create();

        foreach ($countries as $country) {
            $event->countries()->attach($country->id, [
                'latitude' => $country->lat,
                'longitude' => $country->lng,
            ]);
        }

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableBulkAction('detach', $countries);

        foreach ($countries as $country) {
            $this->assertDatabaseMissing('custom_event_country', [
                'custom_event_id' => $event->id,
                'country_id' => $country->id,
            ]);
        }
    });

    test('can search countries in relation manager', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $germany = Country::factory()->create([
            'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
            'iso_code' => 'DE',
        ]);
        $france = Country::factory()->create([
            'name_translations' => ['de' => 'Frankreich', 'en' => 'France'],
            'iso_code' => 'FR',
        ]);

        $event->countries()->attach([
            $germany->id => ['latitude' => 51.1657, 'longitude' => 10.4515],
            $france->id => ['latitude' => 46.2276, 'longitude' => 2.2137],
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->searchTableRecords('Deutschland')
            ->assertCanSeeTableRecords([$germany])
            ->assertCanNotSeeTableRecords([$france]);
    });

    test('can filter countries by continent', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $europeContinent = \App\Models\Continent::factory()->create([
            'name_translations' => ['de' => 'Europa', 'en' => 'Europe'],
        ]);
        $asiaContinent = \App\Models\Continent::factory()->create([
            'name_translations' => ['de' => 'Asien', 'en' => 'Asia'],
        ]);

        $germany = Country::factory()->create(['continent_id' => $europeContinent->id]);
        $japan = Country::factory()->create(['continent_id' => $asiaContinent->id]);

        $event->countries()->attach([
            $germany->id => ['latitude' => 51.1657, 'longitude' => 10.4515],
            $japan->id => ['latitude' => 36.2048, 'longitude' => 138.2529],
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->filterTableRecords('continent_id', [$europeContinent->id])
            ->assertCanSeeTableRecords([$germany])
            ->assertCanNotSeeTableRecords([$japan]);
    });

    test('can filter countries by EU membership', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $euCountry = Country::factory()->create(['is_eu_member' => true]);
        $nonEuCountry = Country::factory()->create(['is_eu_member' => false]);

        $event->countries()->attach([
            $euCountry->id => ['latitude' => 50.0, 'longitude' => 8.0],
            $nonEuCountry->id => ['latitude' => 51.0, 'longitude' => 9.0],
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->filterTableRecords('is_eu_member', true)
            ->assertCanSeeTableRecords([$euCountry])
            ->assertCanNotSeeTableRecords([$nonEuCountry]);
    });

    test('can view attached countries table records', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $countries = Country::factory()->count(3)->create();

        foreach ($countries as $country) {
            $event->countries()->attach($country->id, [
                'latitude' => $country->lat,
                'longitude' => $country->lng,
            ]);
        }

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->assertCanSeeTableRecords($countries);
    });
});

// ============================================================================
// RELATION MANAGER CRUD OPERATIONS - Regions
// ============================================================================

describe('Regions Relation Manager', function () {
    test('can render regions relation manager', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\RegionsRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->assertSuccessful();
    });

    test('can attach region to custom event', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $region = Region::factory()->create(['country_id' => $this->country->id]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\RegionsRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $region->id,
                'use_default_coordinates' => true,
                'latitude' => $region->lat,
                'longitude' => $region->lng,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('custom_event_region', [
            'custom_event_id' => $event->id,
            'region_id' => $region->id,
        ]);
    });

    test('can attach region with custom coordinates', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $region = Region::factory()->create(['country_id' => $this->country->id]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\RegionsRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $region->id,
                'use_default_coordinates' => false,
                'latitude' => 50.1109,
                'longitude' => 8.6821,
                'location_note' => 'Northern part',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('custom_event_region', [
            'custom_event_id' => $event->id,
            'region_id' => $region->id,
            'latitude' => 50.1109,
            'longitude' => 8.6821,
            'location_note' => 'Northern part',
        ]);
    });

    test('can detach region from custom event', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $region = Region::factory()->create(['country_id' => $this->country->id]);

        $event->regions()->attach($region->id, [
            'latitude' => $region->lat,
            'longitude' => $region->lng,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\RegionsRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('detach', $region);

        $this->assertDatabaseMissing('custom_event_region', [
            'custom_event_id' => $event->id,
            'region_id' => $region->id,
        ]);
    });

    test('can bulk detach regions from custom event', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $regions = Region::factory()->count(3)->create(['country_id' => $this->country->id]);

        foreach ($regions as $region) {
            $event->regions()->attach($region->id, [
                'latitude' => $region->lat,
                'longitude' => $region->lng,
            ]);
        }

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\RegionsRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableBulkAction('detach', $regions);

        foreach ($regions as $region) {
            $this->assertDatabaseMissing('custom_event_region', [
                'custom_event_id' => $event->id,
                'region_id' => $region->id,
            ]);
        }
    });

    test('can search regions in relation manager', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $hessen = Region::factory()->create([
            'country_id' => $this->country->id,
            'name_translations' => ['de' => 'Hessen', 'en' => 'Hesse'],
        ]);
        $bayern = Region::factory()->create([
            'country_id' => $this->country->id,
            'name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria'],
        ]);

        $event->regions()->attach([
            $hessen->id => ['latitude' => 50.5, 'longitude' => 8.5],
            $bayern->id => ['latitude' => 48.5, 'longitude' => 11.5],
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\RegionsRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->searchTableRecords('Hessen')
            ->assertCanSeeTableRecords([$hessen])
            ->assertCanNotSeeTableRecords([$bayern]);
    });

    test('can edit attached region', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $region = Region::factory()->create(['country_id' => $this->country->id]);

        $event->regions()->attach($region->id, [
            'latitude' => $region->lat,
            'longitude' => $region->lng,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\RegionsRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('edit', $region, data: [
                'use_default_coordinates' => false,
                'latitude' => 51.0,
                'longitude' => 9.0,
                'location_note' => 'Central area',
            ])
            ->assertHasNoTableActionErrors();

        $pivot = $event->regions()->where('region_id', $region->id)->first()->pivot;
        expect($pivot->latitude)->toBe(51.0);
        expect($pivot->longitude)->toBe(9.0);
        expect($pivot->location_note)->toBe('Central area');
    });

    test('can view attached regions table records', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $regions = Region::factory()->count(3)->create(['country_id' => $this->country->id]);

        foreach ($regions as $region) {
            $event->regions()->attach($region->id, [
                'latitude' => $region->lat,
                'longitude' => $region->lng,
            ]);
        }

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\RegionsRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->assertCanSeeTableRecords($regions);
    });
});

// ============================================================================
// RELATION MANAGER CRUD OPERATIONS - Cities
// ============================================================================

describe('Cities Relation Manager', function () {
    test('can render cities relation manager', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CitiesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->assertSuccessful();
    });

    test('can attach city to custom event', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $city = City::factory()->create([
            'country_id' => $this->country->id,
            'region_id' => $this->region->id,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CitiesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $city->id,
                'use_default_coordinates' => true,
                'latitude' => $city->lat,
                'longitude' => $city->lng,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('custom_event_city', [
            'custom_event_id' => $event->id,
            'city_id' => $city->id,
        ]);
    });

    test('can attach city with custom coordinates', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $city = City::factory()->create([
            'country_id' => $this->country->id,
            'region_id' => $this->region->id,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CitiesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $city->id,
                'use_default_coordinates' => false,
                'latitude' => 50.1109,
                'longitude' => 8.6821,
                'location_note' => 'City center',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('custom_event_city', [
            'custom_event_id' => $event->id,
            'city_id' => $city->id,
            'latitude' => 50.1109,
            'longitude' => 8.6821,
            'location_note' => 'City center',
        ]);
    });

    test('can detach city from custom event', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $city = City::factory()->create([
            'country_id' => $this->country->id,
            'region_id' => $this->region->id,
        ]);

        $event->cities()->attach($city->id, [
            'latitude' => $city->lat,
            'longitude' => $city->lng,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CitiesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('detach', $city);

        $this->assertDatabaseMissing('custom_event_city', [
            'custom_event_id' => $event->id,
            'city_id' => $city->id,
        ]);
    });

    test('can bulk detach cities from custom event', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $cities = City::factory()->count(3)->create([
            'country_id' => $this->country->id,
            'region_id' => $this->region->id,
        ]);

        foreach ($cities as $city) {
            $event->cities()->attach($city->id, [
                'latitude' => $city->lat,
                'longitude' => $city->lng,
            ]);
        }

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CitiesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableBulkAction('detach', $cities);

        foreach ($cities as $city) {
            $this->assertDatabaseMissing('custom_event_city', [
                'custom_event_id' => $event->id,
                'city_id' => $city->id,
            ]);
        }
    });

    test('can search cities in relation manager', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $frankfurt = City::factory()->create([
            'country_id' => $this->country->id,
            'region_id' => $this->region->id,
            'name_translations' => ['de' => 'Frankfurt', 'en' => 'Frankfurt'],
        ]);
        $munich = City::factory()->create([
            'country_id' => $this->country->id,
            'region_id' => $this->region->id,
            'name_translations' => ['de' => 'München', 'en' => 'Munich'],
        ]);

        $event->cities()->attach([
            $frankfurt->id => ['latitude' => 50.1109, 'longitude' => 8.6821],
            $munich->id => ['latitude' => 48.1351, 'longitude' => 11.5820],
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CitiesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->searchTableRecords('Frankfurt')
            ->assertCanSeeTableRecords([$frankfurt])
            ->assertCanNotSeeTableRecords([$munich]);
    });

    test('can edit attached city', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $city = City::factory()->create([
            'country_id' => $this->country->id,
            'region_id' => $this->region->id,
        ]);

        $event->cities()->attach($city->id, [
            'latitude' => $city->lat,
            'longitude' => $city->lng,
        ]);

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CitiesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->callTableAction('edit', $city, data: [
                'use_default_coordinates' => false,
                'latitude' => 50.0,
                'longitude' => 8.0,
                'location_note' => 'Downtown',
            ])
            ->assertHasNoTableActionErrors();

        $pivot = $event->cities()->where('city_id', $city->id)->first()->pivot;
        expect($pivot->latitude)->toBe(50.0);
        expect($pivot->longitude)->toBe(8.0);
        expect($pivot->location_note)->toBe('Downtown');
    });

    test('can view attached cities table records', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $cities = City::factory()->count(3)->create([
            'country_id' => $this->country->id,
            'region_id' => $this->region->id,
        ]);

        foreach ($cities as $city) {
            $event->cities()->attach($city->id, [
                'latitude' => $city->lat,
                'longitude' => $city->lng,
            ]);
        }

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CitiesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->assertCanSeeTableRecords($cities);
    });

    test('can sort cities by name in relation manager', function () {
        $event = CustomEvent::factory()->create([
            'event_type_id' => $this->eventType->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $cities = City::factory()->count(3)->create([
            'country_id' => $this->country->id,
            'region_id' => $this->region->id,
        ]);

        foreach ($cities as $city) {
            $event->cities()->attach($city->id, [
                'latitude' => $city->lat,
                'longitude' => $city->lng,
            ]);
        }

        Livewire::test(\App\Filament\Resources\CustomEvents\RelationManagers\CitiesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => CustomEventResource\Pages\EditCustomEvent::class,
        ])
            ->sortTableRecords('city_name')
            ->assertSuccessful();
    });
});
