<?php

declare(strict_types=1);

use App\Models\{User, CustomEvent};

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
        'is_active' => true,
    ]);
});

test('admin can view custom events index page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/custom-events')
        ->assertSuccessful()
        ->assertSee('Custom Events');
});

test('admin can create a new custom event', function () {
    $eventData = [
        'title' => 'Test Custom Event',
        'description' => 'This is a test custom event',
        'event_type' => 'exercise',
        'latitude' => 50.1109,
        'longitude' => 8.6821,
        'marker_color' => '#FF0000',
        'marker_icon' => 'fa-map-marker',
        'icon_color' => '#FFFFFF',
        'marker_size' => 'medium',
        'popup_content' => 'Test popup content',
        'start_date' => '2024-01-01 10:00:00',
        'end_date' => '2024-01-01 18:00:00',
        'priority' => 'medium',
        'severity' => 'medium',
        'category' => 'test',
        'tags' => ['test', 'exercise'],
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/custom-events', $eventData)
        ->assertRedirect();

    $this->assertDatabaseHas('custom_events', [
        'title' => 'Test Custom Event',
        'event_type' => 'exercise',
        'priority' => 'medium',
        'severity' => 'medium',
    ]);
});

test('admin can view a custom event', function () {
    $event = CustomEvent::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/custom-events/{$event->id}")
        ->assertSuccessful()
        ->assertSee($event->title);
});

test('admin can edit a custom event', function () {
    $event = CustomEvent::factory()->create();
    $updatedData = [
        'title' => 'Updated Custom Event',
        'description' => 'Updated description',
        'event_type' => 'other',
        'priority' => 'high',
        'severity' => 'high',
        'is_active' => false,
    ];

    $this->actingAs($this->admin)
        ->put("/admin/custom-events/{$event->id}", $updatedData)
        ->assertRedirect();

    $this->assertDatabaseHas('custom_events', [
        'id' => $event->id,
        'title' => 'Updated Custom Event',
        'event_type' => 'other',
        'priority' => 'high',
        'severity' => 'high',
    ]);
});

test('admin can delete a custom event', function () {
    $event = CustomEvent::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/custom-events/{$event->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('custom_events', [
        'id' => $event->id,
    ]);
});

test('admin can bulk delete custom events', function () {
    $events = CustomEvent::factory()->count(3)->create();
    $eventIds = $events->pluck('id')->toArray();

    $this->actingAs($this->admin)
        ->post('/admin/custom-events/bulk-delete', [
            'ids' => $eventIds,
        ])
        ->assertRedirect();

    foreach ($eventIds as $id) {
        $this->assertDatabaseMissing('custom_events', ['id' => $id]);
    }
});

test('non-admin users cannot access custom events', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/custom-events')
        ->assertForbidden();
});

test('custom event form validation works', function () {
    $this->actingAs($this->admin)
        ->post('/admin/custom-events', [])
        ->assertSessionHasErrors([
            'title',
            'event_type',
            'priority',
            'severity',
        ]);
});

test('custom event search works', function () {
    $event1 = CustomEvent::factory()->create(['title' => 'Berlin Exercise']);
    $event2 = CustomEvent::factory()->create(['title' => 'Munich Training']);

    $this->actingAs($this->admin)
        ->get('/admin/custom-events?search=Berlin')
        ->assertSuccessful()
        ->assertSee($event1->title)
        ->assertDontSee($event2->title);
});

test('custom event filtering by event type works', function () {
    $exerciseEvent = CustomEvent::factory()->create(['event_type' => 'exercise']);
    $otherEvent = CustomEvent::factory()->create(['event_type' => 'other']);

    $this->actingAs($this->admin)
        ->get('/admin/custom-events?event_type=exercise')
        ->assertSuccessful()
        ->assertSee($exerciseEvent->title)
        ->assertDontSee($otherEvent->title);
});

test('custom event filtering by priority works', function () {
    $highPriorityEvent = CustomEvent::factory()->create(['priority' => 'high']);
    $lowPriorityEvent = CustomEvent::factory()->create(['priority' => 'low']);

    $this->actingAs($this->admin)
        ->get('/admin/custom-events?priority=high')
        ->assertSuccessful()
        ->assertSee($highPriorityEvent->title)
        ->assertDontSee($lowPriorityEvent->title);
});

test('custom event filtering by severity works', function () {
    $highSeverityEvent = CustomEvent::factory()->create(['severity' => 'high']);
    $lowSeverityEvent = CustomEvent::factory()->create(['severity' => 'low']);

    $this->actingAs($this->admin)
        ->get('/admin/custom-events?severity=high')
        ->assertSuccessful()
        ->assertSee($highSeverityEvent->title)
        ->assertDontSee($lowSeverityEvent->title);
});

test('custom event filtering by active status works', function () {
    $activeEvent = CustomEvent::factory()->create(['is_active' => true]);
    $inactiveEvent = CustomEvent::factory()->create(['is_active' => false]);

    $this->actingAs($this->admin)
        ->get('/admin/custom-events?is_active=1')
        ->assertSuccessful()
        ->assertSee($activeEvent->title)
        ->assertDontSee($inactiveEvent->title);
});

test('custom event with coordinates works', function () {
    $eventData = [
        'title' => 'Test Event with Coordinates',
        'description' => 'Test description',
        'event_type' => 'exercise',
        'latitude' => 50.1109,
        'longitude' => 8.6821,
        'priority' => 'medium',
        'severity' => 'medium',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/custom-events', $eventData)
        ->assertRedirect();

    $this->assertDatabaseHas('custom_events', [
        'title' => 'Test Event with Coordinates',
        'latitude' => 50.1109,
        'longitude' => 8.6821,
    ]);
});

test('custom event with date range works', function () {
    $eventData = [
        'title' => 'Test Event with Date Range',
        'description' => 'Test description',
        'event_type' => 'exercise',
        'start_date' => '2024-01-01 10:00:00',
        'end_date' => '2024-01-01 18:00:00',
        'priority' => 'medium',
        'severity' => 'medium',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/custom-events', $eventData)
        ->assertRedirect();

    $this->assertDatabaseHas('custom_events', [
        'title' => 'Test Event with Date Range',
    ]);
});

test('custom event with tags works', function () {
    $eventData = [
        'title' => 'Test Event with Tags',
        'description' => 'Test description',
        'event_type' => 'exercise',
        'tags' => ['test', 'exercise', 'training'],
        'priority' => 'medium',
        'severity' => 'medium',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post('/admin/custom-events', $eventData)
        ->assertRedirect();

    $this->assertDatabaseHas('custom_events', [
        'title' => 'Test Event with Tags',
    ]);
});

// Model method tests (keeping some of the original tests)
test('custom event priority options work', function () {
    $options = CustomEvent::getPriorityOptions();
    
    $this->assertIsArray($options);
    $this->assertArrayHasKey('low', $options);
    $this->assertArrayHasKey('medium', $options);
    $this->assertArrayHasKey('high', $options);
    $this->assertArrayHasKey('critical', $options);
});

test('custom event severity options work', function () {
    $options = CustomEvent::getSeverityOptions();
    
    $this->assertIsArray($options);
    $this->assertArrayHasKey('low', $options);
    $this->assertArrayHasKey('medium', $options);
    $this->assertArrayHasKey('high', $options);
    $this->assertArrayHasKey('critical', $options);
});

test('custom event event type options work', function () {
    $options = CustomEvent::getEventTypeOptions();
    
    $this->assertIsArray($options);
    $this->assertNotEmpty($options);
    $this->assertArrayHasKey('exercise', $options);
    $this->assertArrayHasKey('other', $options);
});

test('custom event marker size options work', function () {
    $options = CustomEvent::getMarkerSizeOptions();
    
    $this->assertIsArray($options);
    $this->assertArrayHasKey('small', $options);
    $this->assertArrayHasKey('medium', $options);
    $this->assertArrayHasKey('large', $options);
});
