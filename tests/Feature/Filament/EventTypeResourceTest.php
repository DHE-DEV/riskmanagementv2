<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\EventTypes\EventTypeResource;
use App\Models\EventType;
use App\Models\CustomEvent;
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
});

// ============================================================================
// LIST / INDEX TESTS
// ============================================================================

test('can render event type list page', function () {
    $this->get(EventTypeResource::getUrl('index'))
        ->assertSuccessful();
});

test('can list event types', function () {
    $eventTypes = EventType::factory()->count(10)->create();

    Livewire::test(EventTypeResource\Pages\ListEventTypes::class)
        ->assertCanSeeTableRecords($eventTypes);
});

test('can search event types by name', function () {
    $eventType1 = EventType::factory()->create(['name' => 'Erdbeben']);
    $eventType2 = EventType::factory()->create(['name' => 'Überschwemmung']);

    Livewire::test(EventTypeResource\Pages\ListEventTypes::class)
        ->searchTable('Erdbeben')
        ->assertCanSeeTableRecords([$eventType1])
        ->assertCanNotSeeTableRecords([$eventType2]);
});

test('can search event types by code', function () {
    $eventType1 = EventType::factory()->create(['code' => 'earthquake']);
    $eventType2 = EventType::factory()->create(['code' => 'flood']);

    Livewire::test(EventTypeResource\Pages\ListEventTypes::class)
        ->searchTable('earthquake')
        ->assertCanSeeTableRecords([$eventType1])
        ->assertCanNotSeeTableRecords([$eventType2]);
});

test('can search event types by description', function () {
    $eventType1 = EventType::factory()->create(['description' => 'Natural disaster earthquake']);
    $eventType2 = EventType::factory()->create(['description' => 'Water flooding event']);

    Livewire::test(EventTypeResource\Pages\ListEventTypes::class)
        ->searchTable('earthquake')
        ->assertCanSeeTableRecords([$eventType1])
        ->assertCanNotSeeTableRecords([$eventType2]);
});

test('can sort event types by name', function () {
    $eventTypes = EventType::factory()->count(5)->create();

    Livewire::test(EventTypeResource\Pages\ListEventTypes::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords($eventTypes->sortBy('name'), inOrder: true);
});

test('can sort event types by sort order', function () {
    EventType::factory()->create(['name' => 'A', 'sort_order' => 3]);
    EventType::factory()->create(['name' => 'B', 'sort_order' => 1]);
    EventType::factory()->create(['name' => 'C', 'sort_order' => 2]);

    $sorted = EventType::orderBy('sort_order')->orderBy('name')->get();

    Livewire::test(EventTypeResource\Pages\ListEventTypes::class)
        ->sortTable('sort_order')
        ->assertCanSeeTableRecords($sorted, inOrder: true);
});

// ============================================================================
// CREATE TESTS
// ============================================================================

test('can render event type create page', function () {
    $this->get(EventTypeResource::getUrl('create'))
        ->assertSuccessful();
});

test('can create event type with all fields', function () {
    $eventTypeData = [
        'code' => 'earthquake',
        'name' => 'Erdbeben',
        'description' => 'Seismische Aktivität',
        'color' => '#FF0000',
        'icon' => 'fa-house-crack',
        'is_active' => true,
        'sort_order' => 10,
    ];

    Livewire::test(EventTypeResource\Pages\CreateEventType::class)
        ->fillForm($eventTypeData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_types', [
        'code' => 'earthquake',
        'name' => 'Erdbeben',
        'description' => 'Seismische Aktivität',
        'is_active' => true,
        'sort_order' => 10,
    ]);
});

test('can create event type with minimal required fields', function () {
    $eventTypeData = [
        'code' => 'test_event',
        'name' => 'Test Event',
        'is_active' => true,
    ];

    Livewire::test(EventTypeResource\Pages\CreateEventType::class)
        ->fillForm($eventTypeData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_types', [
        'code' => 'test_event',
        'name' => 'Test Event',
    ]);
});

test('cannot create event type without required fields', function () {
    Livewire::test(EventTypeResource\Pages\CreateEventType::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors(['code', 'name']);
});

test('cannot create event type with duplicate code', function () {
    EventType::factory()->create(['code' => 'earthquake']);

    Livewire::test(EventTypeResource\Pages\CreateEventType::class)
        ->fillForm([
            'code' => 'earthquake',
            'name' => 'Another Earthquake',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['code']);
});

test('can create inactive event type', function () {
    $eventTypeData = [
        'code' => 'inactive_event',
        'name' => 'Inactive Event',
        'is_active' => false,
        'sort_order' => 0,
    ];

    Livewire::test(EventTypeResource\Pages\CreateEventType::class)
        ->fillForm($eventTypeData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_types', [
        'code' => 'inactive_event',
        'is_active' => false,
    ]);
});

test('validates code max length', function () {
    Livewire::test(EventTypeResource\Pages\CreateEventType::class)
        ->fillForm([
            'code' => str_repeat('a', 51), // Exceeds 50 character limit
            'name' => 'Test Event',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['code']);
});

test('validates name max length', function () {
    Livewire::test(EventTypeResource\Pages\CreateEventType::class)
        ->fillForm([
            'code' => 'test',
            'name' => str_repeat('a', 101), // Exceeds 100 character limit
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['name']);
});

test('validates description max length', function () {
    Livewire::test(EventTypeResource\Pages\CreateEventType::class)
        ->fillForm([
            'code' => 'test',
            'name' => 'Test Event',
            'description' => str_repeat('a', 501), // Exceeds 500 character limit
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['description']);
});

test('validates sort order is numeric', function () {
    Livewire::test(EventTypeResource\Pages\CreateEventType::class)
        ->fillForm([
            'code' => 'test',
            'name' => 'Test Event',
            'sort_order' => 'not-a-number',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['sort_order']);
});

// ============================================================================
// EDIT / UPDATE TESTS
// ============================================================================

test('can render event type edit page', function () {
    $eventType = EventType::factory()->create();

    $this->get(EventTypeResource::getUrl('edit', ['record' => $eventType]))
        ->assertSuccessful();
});

test('can retrieve event type data for editing', function () {
    $eventType = EventType::factory()->create([
        'code' => 'earthquake',
        'name' => 'Erdbeben',
        'description' => 'Test description',
        'is_active' => true,
        'sort_order' => 5,
    ]);

    Livewire::test(EventTypeResource\Pages\EditEventType::class, [
        'record' => $eventType->getRouteKey(),
    ])
        ->assertFormSet([
            'code' => 'earthquake',
            'name' => 'Erdbeben',
            'description' => 'Test description',
            'is_active' => true,
            'sort_order' => 5,
        ]);
});

test('can update event type with all fields', function () {
    $eventType = EventType::factory()->create();

    $updatedData = [
        'code' => 'updated_code',
        'name' => 'Updated Name',
        'description' => 'Updated description',
        'color' => '#00FF00',
        'icon' => 'fa-fire',
        'is_active' => false,
        'sort_order' => 20,
    ];

    Livewire::test(EventTypeResource\Pages\EditEventType::class, [
        'record' => $eventType->getRouteKey(),
    ])
        ->fillForm($updatedData)
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_types', [
        'id' => $eventType->id,
        'code' => 'updated_code',
        'name' => 'Updated Name',
        'description' => 'Updated description',
        'is_active' => false,
        'sort_order' => 20,
    ]);
});

test('can update event type name only', function () {
    $eventType = EventType::factory()->create(['code' => 'earthquake', 'name' => 'Old Name']);

    Livewire::test(EventTypeResource\Pages\EditEventType::class, [
        'record' => $eventType->getRouteKey(),
    ])
        ->fillForm(['name' => 'New Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_types', [
        'id' => $eventType->id,
        'code' => 'earthquake',
        'name' => 'New Name',
    ]);
});

test('can toggle event type active status', function () {
    $eventType = EventType::factory()->create(['is_active' => true]);

    Livewire::test(EventTypeResource\Pages\EditEventType::class, [
        'record' => $eventType->getRouteKey(),
    ])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_types', [
        'id' => $eventType->id,
        'is_active' => false,
    ]);
});

test('cannot update event type with duplicate code', function () {
    $eventType1 = EventType::factory()->create(['code' => 'earthquake']);
    $eventType2 = EventType::factory()->create(['code' => 'flood']);

    Livewire::test(EventTypeResource\Pages\EditEventType::class, [
        'record' => $eventType2->getRouteKey(),
    ])
        ->fillForm(['code' => 'earthquake'])
        ->call('save')
        ->assertHasFormErrors(['code']);
});

test('can update event type with same code', function () {
    $eventType = EventType::factory()->create(['code' => 'earthquake', 'name' => 'Old Name']);

    Livewire::test(EventTypeResource\Pages\EditEventType::class, [
        'record' => $eventType->getRouteKey(),
    ])
        ->fillForm([
            'code' => 'earthquake', // Same code
            'name' => 'New Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_types', [
        'id' => $eventType->id,
        'code' => 'earthquake',
        'name' => 'New Name',
    ]);
});

// ============================================================================
// DELETE TESTS
// ============================================================================

test('can delete event type', function () {
    $eventType = EventType::factory()->create();

    Livewire::test(EventTypeResource\Pages\EditEventType::class, [
        'record' => $eventType->getRouteKey(),
    ])
        ->callAction('delete');

    $this->assertModelMissing($eventType);
});

test('cannot delete event type that has custom events', function () {
    $eventType = EventType::factory()->create();
    CustomEvent::factory()->create(['event_type_id' => $eventType->id]);

    Livewire::test(EventTypeResource\Pages\EditEventType::class, [
        'record' => $eventType->getRouteKey(),
    ])
        ->callAction('delete')
        ->assertNotified();

    $this->assertModelExists($eventType);
});

test('can delete event type without custom events', function () {
    $eventType = EventType::factory()->create();

    // Ensure no custom events exist for this type
    expect($eventType->customEvents()->exists())->toBeFalse();

    Livewire::test(EventTypeResource\Pages\EditEventType::class, [
        'record' => $eventType->getRouteKey(),
    ])
        ->callAction('delete');

    $this->assertModelMissing($eventType);
});

// ============================================================================
// RELATIONSHIP TESTS
// ============================================================================

test('event type has custom events relationship', function () {
    $eventType = EventType::factory()->create();
    $customEvent = CustomEvent::factory()->create(['event_type_id' => $eventType->id]);

    expect($eventType->customEvents)->toHaveCount(1);
    expect($eventType->customEvents->first()->id)->toBe($customEvent->id);
});

test('event type has event categories relationship', function () {
    $eventType = EventType::factory()->create();
    $category = \App\Models\EventCategory::factory()->create(['event_type_id' => $eventType->id]);

    expect($eventType->eventCategories)->toHaveCount(1);
    expect($eventType->eventCategories->first()->id)->toBe($category->id);
});

// ============================================================================
// SCOPE TESTS
// ============================================================================

test('active scope returns only active event types', function () {
    EventType::factory()->create(['is_active' => true]);
    EventType::factory()->create(['is_active' => true]);
    EventType::factory()->create(['is_active' => false]);

    $activeEventTypes = EventType::active()->get();

    expect($activeEventTypes)->toHaveCount(2);
});

test('ordered scope returns event types in correct order', function () {
    EventType::factory()->create(['name' => 'C', 'sort_order' => 2]);
    EventType::factory()->create(['name' => 'A', 'sort_order' => 1]);
    EventType::factory()->create(['name' => 'B', 'sort_order' => 1]);

    $orderedEventTypes = EventType::ordered()->get();

    expect($orderedEventTypes[0]->name)->toBe('A'); // sort_order 1, name A
    expect($orderedEventTypes[1]->name)->toBe('B'); // sort_order 1, name B
    expect($orderedEventTypes[2]->name)->toBe('C'); // sort_order 2
});

// ============================================================================
// GLOBAL SEARCH TESTS
// ============================================================================

test('event types are globally searchable', function () {
    $eventType = EventType::factory()->create([
        'name' => 'Earthquake Event',
        'code' => 'earthquake',
        'description' => 'Natural disaster',
    ]);

    expect(EventTypeResource::getGloballySearchableAttributes())
        ->toContain('name', 'code', 'description');
});

test('global search returns event type details', function () {
    $eventType = EventType::factory()->create(['code' => 'EQ123']);

    $details = EventTypeResource::getGlobalSearchResultDetails($eventType);

    expect($details)->toHaveKey('Code');
    expect($details['Code'])->toBe('EQ123');
});

// ============================================================================
// AUTHORIZATION TESTS
// ============================================================================

test('non-admin users cannot access event types', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(EventTypeResource::getUrl('index'))
        ->assertForbidden();
});

test('unauthorized users cannot create event types', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(EventTypeResource::getUrl('create'))
        ->assertForbidden();
});

test('unauthorized users cannot edit event types', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $eventType = EventType::factory()->create();
    $this->actingAs($user);

    $this->get(EventTypeResource::getUrl('edit', ['record' => $eventType]))
        ->assertForbidden();
});

// ============================================================================
// BULK DELETE TESTS
// ============================================================================

test('can bulk delete event types', function () {
    $eventTypes = EventType::factory()->count(3)->create();

    Livewire::test(EventTypeResource\Pages\ListEventTypes::class)
        ->callTableBulkAction('delete', $eventTypes);

    foreach ($eventTypes as $eventType) {
        $this->assertModelMissing($eventType);
    }
});

test('cannot bulk delete event types that have custom events', function () {
    $eventType1 = EventType::factory()->create();
    $eventType2 = EventType::factory()->create();

    // Create custom event for first event type
    CustomEvent::factory()->create(['event_type_id' => $eventType1->id]);

    Livewire::test(EventTypeResource\Pages\ListEventTypes::class)
        ->callTableBulkAction('delete', [$eventType1, $eventType2]);

    // First should still exist, second should be deleted
    $this->assertModelExists($eventType1);
    $this->assertModelMissing($eventType2);
});

// ============================================================================
// RELATION MANAGER CRUD OPERATIONS - EventCategories
// ============================================================================

describe('EventCategories Relation Manager', function () {
    test('can render event categories relation manager', function () {
        $eventType = EventType::factory()->create();

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->assertSuccessful();
    });

    test('can create event category through relation manager', function () {
        $eventType = EventType::factory()->create();

        $categoryData = [
            'name' => 'Notfall',
            'description' => 'Notfall-Ereignisse',
            'color' => '#FF0000',
            'sort_order' => 10,
            'is_active' => true,
        ];

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->callTableAction('create', data: $categoryData)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('event_categories', [
            'event_type_id' => $eventType->id,
            'name' => 'Notfall',
            'description' => 'Notfall-Ereignisse',
            'color' => '#FF0000',
            'sort_order' => 10,
            'is_active' => true,
        ]);
    });

    test('can create event category with minimal fields', function () {
        $eventType = EventType::factory()->create();

        $categoryData = [
            'name' => 'Minimal Category',
            'is_active' => true,
        ];

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->callTableAction('create', data: $categoryData)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('event_categories', [
            'event_type_id' => $eventType->id,
            'name' => 'Minimal Category',
        ]);
    });

    test('cannot create event category without required fields', function () {
        $eventType = EventType::factory()->create();

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->callTableAction('create', data: [])
            ->assertHasTableActionErrors(['name']);
    });

    test('can edit event category through relation manager', function () {
        $eventType = EventType::factory()->create();
        $category = \App\Models\EventCategory::factory()->create([
            'event_type_id' => $eventType->id,
            'name' => 'Old Name',
            'color' => '#FF0000',
        ]);

        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'color' => '#00FF00',
            'sort_order' => 20,
            'is_active' => false,
        ];

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->callTableAction('edit', $category, data: $updatedData)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('event_categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'color' => '#00FF00',
            'sort_order' => 20,
            'is_active' => false,
        ]);
    });

    test('can delete event category through relation manager', function () {
        $eventType = EventType::factory()->create();
        $category = \App\Models\EventCategory::factory()->create([
            'event_type_id' => $eventType->id,
        ]);

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->callTableAction('delete', $category);

        $this->assertModelMissing($category);
    });

    test('can bulk delete event categories through relation manager', function () {
        $eventType = EventType::factory()->create();
        $categories = \App\Models\EventCategory::factory()->count(3)->create([
            'event_type_id' => $eventType->id,
        ]);

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->callTableBulkAction('delete', $categories);

        foreach ($categories as $category) {
            $this->assertModelMissing($category);
        }
    });

    test('can search event categories in relation manager', function () {
        $eventType = EventType::factory()->create();
        $category1 = \App\Models\EventCategory::factory()->create([
            'event_type_id' => $eventType->id,
            'name' => 'Emergency',
        ]);
        $category2 = \App\Models\EventCategory::factory()->create([
            'event_type_id' => $eventType->id,
            'name' => 'Training',
        ]);

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->searchTableRecords('Emergency')
            ->assertCanSeeTableRecords([$category1])
            ->assertCanNotSeeTableRecords([$category2]);
    });

    test('can sort event categories by name in relation manager', function () {
        $eventType = EventType::factory()->create();
        $categories = \App\Models\EventCategory::factory()->count(5)->create([
            'event_type_id' => $eventType->id,
        ]);

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->sortTableRecords('name')
            ->assertCanSeeTableRecords($categories->sortBy('name'), inOrder: true);
    });

    test('can sort event categories by sort_order in relation manager', function () {
        $eventType = EventType::factory()->create();
        \App\Models\EventCategory::factory()->create([
            'event_type_id' => $eventType->id,
            'name' => 'A',
            'sort_order' => 3,
        ]);
        \App\Models\EventCategory::factory()->create([
            'event_type_id' => $eventType->id,
            'name' => 'B',
            'sort_order' => 1,
        ]);
        \App\Models\EventCategory::factory()->create([
            'event_type_id' => $eventType->id,
            'name' => 'C',
            'sort_order' => 2,
        ]);

        $sorted = \App\Models\EventCategory::where('event_type_id', $eventType->id)
            ->orderBy('sort_order')
            ->get();

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->sortTableRecords('sort_order')
            ->assertCanSeeTableRecords($sorted, inOrder: true);
    });

    test('can sort event categories by is_active in relation manager', function () {
        $eventType = EventType::factory()->create();
        $active = \App\Models\EventCategory::factory()->create([
            'event_type_id' => $eventType->id,
            'is_active' => true,
        ]);
        $inactive = \App\Models\EventCategory::factory()->create([
            'event_type_id' => $eventType->id,
            'is_active' => false,
        ]);

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->sortTableRecords('is_active', 'desc')
            ->assertCanSeeTableRecords([$active, $inactive], inOrder: true);
    });

    test('can view event category table records', function () {
        $eventType = EventType::factory()->create();
        $categories = \App\Models\EventCategory::factory()->count(3)->create([
            'event_type_id' => $eventType->id,
        ]);

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->assertCanSeeTableRecords($categories);
    });

    test('validates name max length when creating category', function () {
        $eventType = EventType::factory()->create();

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->callTableAction('create', data: [
                'name' => str_repeat('a', 256),
                'is_active' => true,
            ])
            ->assertHasTableActionErrors(['name']);
    });

    test('validates sort_order is numeric', function () {
        $eventType = EventType::factory()->create();

        Livewire::test(\App\Filament\Resources\EventTypes\RelationManagers\EventCategoriesRelationManager::class, [
            'ownerRecord' => $eventType,
            'pageClass' => EventTypeResource\Pages\EditEventType::class,
        ])
            ->callTableAction('create', data: [
                'name' => 'Test Category',
                'sort_order' => 'not-a-number',
                'is_active' => true,
            ])
            ->assertHasTableActionErrors(['sort_order']);
    });
});
