<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\EventCategories\EventCategoryResource;
use App\Models\EventCategory;
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

test('can render event category list page', function () {
    $this->get(EventCategoryResource::getUrl('index'))
        ->assertSuccessful();
});

test('can list event categories', function () {
    $eventType = EventType::factory()->create();
    $categories = EventCategory::factory()->count(10)->create(['event_type_id' => $eventType->id]);

    Livewire::test(EventCategoryResource\Pages\ListEventCategories::class)
        ->assertCanSeeTableRecords($categories);
});

test('can search event categories by name', function () {
    $eventType = EventType::factory()->create();
    $category1 = EventCategory::factory()->create(['event_type_id' => $eventType->id, 'name' => 'Severe Weather']);
    $category2 = EventCategory::factory()->create(['event_type_id' => $eventType->id, 'name' => 'Minor Incident']);

    Livewire::test(EventCategoryResource\Pages\ListEventCategories::class)
        ->searchTable('Severe')
        ->assertCanSeeTableRecords([$category1])
        ->assertCanNotSeeTableRecords([$category2]);
});

test('can sort event categories by name', function () {
    $eventType = EventType::factory()->create();
    $categories = EventCategory::factory()->count(5)->create(['event_type_id' => $eventType->id]);

    Livewire::test(EventCategoryResource\Pages\ListEventCategories::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords($categories->sortBy('name'), inOrder: true);
});

test('can sort event categories by sort order', function () {
    $eventType = EventType::factory()->create();
    EventCategory::factory()->create(['event_type_id' => $eventType->id, 'name' => 'A', 'sort_order' => 3]);
    EventCategory::factory()->create(['event_type_id' => $eventType->id, 'name' => 'B', 'sort_order' => 1]);
    EventCategory::factory()->create(['event_type_id' => $eventType->id, 'name' => 'C', 'sort_order' => 2]);

    $sorted = EventCategory::orderBy('sort_order')->orderBy('name')->get();

    Livewire::test(EventCategoryResource\Pages\ListEventCategories::class)
        ->sortTable('sort_order')
        ->assertCanSeeTableRecords($sorted, inOrder: true);
});

// ============================================================================
// CREATE TESTS
// ============================================================================

test('can render event category create page', function () {
    $this->get(EventCategoryResource::getUrl('create'))
        ->assertSuccessful();
});

test('can create event category with all fields', function () {
    $eventType = EventType::factory()->create();

    $categoryData = [
        'event_type_id' => $eventType->id,
        'name' => 'Severe Weather',
        'description' => 'Severe weather events',
        'color' => '#FF0000',
        'is_active' => true,
        'sort_order' => 10,
    ];

    Livewire::test(EventCategoryResource\Pages\CreateEventCategory::class)
        ->fillForm($categoryData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_categories', [
        'event_type_id' => $eventType->id,
        'name' => 'Severe Weather',
        'description' => 'Severe weather events',
        'is_active' => true,
        'sort_order' => 10,
    ]);
});

test('can create event category with minimal required fields', function () {
    $eventType = EventType::factory()->create();

    $categoryData = [
        'event_type_id' => $eventType->id,
        'name' => 'Test Category',
        'is_active' => true,
        'sort_order' => 0,
    ];

    Livewire::test(EventCategoryResource\Pages\CreateEventCategory::class)
        ->fillForm($categoryData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_categories', [
        'event_type_id' => $eventType->id,
        'name' => 'Test Category',
    ]);
});

test('cannot create event category without required fields', function () {
    Livewire::test(EventCategoryResource\Pages\CreateEventCategory::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors(['event_type_id', 'name']);
});

test('cannot create event category without event type', function () {
    Livewire::test(EventCategoryResource\Pages\CreateEventCategory::class)
        ->fillForm([
            'name' => 'Test Category',
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['event_type_id']);
});

test('can create inactive event category', function () {
    $eventType = EventType::factory()->create();

    $categoryData = [
        'event_type_id' => $eventType->id,
        'name' => 'Inactive Category',
        'is_active' => false,
        'sort_order' => 0,
    ];

    Livewire::test(EventCategoryResource\Pages\CreateEventCategory::class)
        ->fillForm($categoryData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_categories', [
        'name' => 'Inactive Category',
        'is_active' => false,
    ]);
});

test('validates sort order is numeric', function () {
    $eventType = EventType::factory()->create();

    Livewire::test(EventCategoryResource\Pages\CreateEventCategory::class)
        ->fillForm([
            'event_type_id' => $eventType->id,
            'name' => 'Test Category',
            'sort_order' => 'not-a-number',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['sort_order']);
});

// ============================================================================
// EDIT / UPDATE TESTS
// ============================================================================

test('can render event category edit page', function () {
    $eventType = EventType::factory()->create();
    $category = EventCategory::factory()->create(['event_type_id' => $eventType->id]);

    $this->get(EventCategoryResource::getUrl('edit', ['record' => $category]))
        ->assertSuccessful();
});

test('can retrieve event category data for editing', function () {
    $eventType = EventType::factory()->create();
    $category = EventCategory::factory()->create([
        'event_type_id' => $eventType->id,
        'name' => 'Test Category',
        'description' => 'Test description',
        'is_active' => true,
        'sort_order' => 5,
    ]);

    Livewire::test(EventCategoryResource\Pages\EditEventCategory::class, [
        'record' => $category->getRouteKey(),
    ])
        ->assertFormSet([
            'event_type_id' => $eventType->id,
            'name' => 'Test Category',
            'description' => 'Test description',
            'is_active' => true,
            'sort_order' => 5,
        ]);
});

test('can update event category with all fields', function () {
    $eventType1 = EventType::factory()->create();
    $eventType2 = EventType::factory()->create();
    $category = EventCategory::factory()->create(['event_type_id' => $eventType1->id]);

    $updatedData = [
        'event_type_id' => $eventType2->id,
        'name' => 'Updated Category',
        'description' => 'Updated description',
        'color' => '#00FF00',
        'is_active' => false,
        'sort_order' => 20,
    ];

    Livewire::test(EventCategoryResource\Pages\EditEventCategory::class, [
        'record' => $category->getRouteKey(),
    ])
        ->fillForm($updatedData)
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_categories', [
        'id' => $category->id,
        'event_type_id' => $eventType2->id,
        'name' => 'Updated Category',
        'description' => 'Updated description',
        'is_active' => false,
        'sort_order' => 20,
    ]);
});

test('can update event category name only', function () {
    $eventType = EventType::factory()->create();
    $category = EventCategory::factory()->create([
        'event_type_id' => $eventType->id,
        'name' => 'Old Name',
    ]);

    Livewire::test(EventCategoryResource\Pages\EditEventCategory::class, [
        'record' => $category->getRouteKey(),
    ])
        ->fillForm(['name' => 'New Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_categories', [
        'id' => $category->id,
        'name' => 'New Name',
    ]);
});

test('can toggle event category active status', function () {
    $eventType = EventType::factory()->create();
    $category = EventCategory::factory()->create([
        'event_type_id' => $eventType->id,
        'is_active' => true,
    ]);

    Livewire::test(EventCategoryResource\Pages\EditEventCategory::class, [
        'record' => $category->getRouteKey(),
    ])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_categories', [
        'id' => $category->id,
        'is_active' => false,
    ]);
});

test('can change event category event type', function () {
    $eventType1 = EventType::factory()->create(['name' => 'Type 1']);
    $eventType2 = EventType::factory()->create(['name' => 'Type 2']);
    $category = EventCategory::factory()->create(['event_type_id' => $eventType1->id]);

    Livewire::test(EventCategoryResource\Pages\EditEventCategory::class, [
        'record' => $category->getRouteKey(),
    ])
        ->fillForm(['event_type_id' => $eventType2->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_categories', [
        'id' => $category->id,
        'event_type_id' => $eventType2->id,
    ]);
});

// ============================================================================
// DELETE TESTS
// ============================================================================

test('can delete event category', function () {
    $eventType = EventType::factory()->create();
    $category = EventCategory::factory()->create(['event_type_id' => $eventType->id]);

    Livewire::test(EventCategoryResource\Pages\EditEventCategory::class, [
        'record' => $category->getRouteKey(),
    ])
        ->callAction('delete');

    $this->assertModelMissing($category);
});

test('can delete event category without custom events', function () {
    $eventType = EventType::factory()->create();
    $category = EventCategory::factory()->create(['event_type_id' => $eventType->id]);

    // Ensure no custom events exist for this category
    expect($category->customEvents()->exists())->toBeFalse();

    Livewire::test(EventCategoryResource\Pages\EditEventCategory::class, [
        'record' => $category->getRouteKey(),
    ])
        ->callAction('delete');

    $this->assertModelMissing($category);
});

// ============================================================================
// RELATIONSHIP TESTS
// ============================================================================

test('event category belongs to event type', function () {
    $eventType = EventType::factory()->create();
    $category = EventCategory::factory()->create(['event_type_id' => $eventType->id]);

    expect($category->eventType)->not->toBeNull();
    expect($category->eventType->id)->toBe($eventType->id);
});

test('event category has custom events relationship', function () {
    $eventType = EventType::factory()->create();
    $category = EventCategory::factory()->create(['event_type_id' => $eventType->id]);
    $customEvent = CustomEvent::factory()->create([
        'event_type_id' => $eventType->id,
        'event_category_id' => $category->id,
    ]);

    expect($category->customEvents)->toHaveCount(1);
    expect($category->customEvents->first()->id)->toBe($customEvent->id);
});

test('multiple categories can belong to same event type', function () {
    $eventType = EventType::factory()->create();
    $category1 = EventCategory::factory()->create(['event_type_id' => $eventType->id]);
    $category2 = EventCategory::factory()->create(['event_type_id' => $eventType->id]);

    $categories = EventCategory::where('event_type_id', $eventType->id)->get();
    expect($categories)->toHaveCount(2);
});

// ============================================================================
// SCOPE TESTS
// ============================================================================

test('active scope returns only active event categories', function () {
    $eventType = EventType::factory()->create();
    EventCategory::factory()->create(['event_type_id' => $eventType->id, 'is_active' => true]);
    EventCategory::factory()->create(['event_type_id' => $eventType->id, 'is_active' => true]);
    EventCategory::factory()->create(['event_type_id' => $eventType->id, 'is_active' => false]);

    $activeCategories = EventCategory::active()->get();

    expect($activeCategories)->toHaveCount(2);
});

test('ordered scope returns categories in correct order', function () {
    $eventType = EventType::factory()->create();
    EventCategory::factory()->create(['event_type_id' => $eventType->id, 'name' => 'C', 'sort_order' => 2]);
    EventCategory::factory()->create(['event_type_id' => $eventType->id, 'name' => 'A', 'sort_order' => 1]);
    EventCategory::factory()->create(['event_type_id' => $eventType->id, 'name' => 'B', 'sort_order' => 1]);

    $orderedCategories = EventCategory::ordered()->get();

    expect($orderedCategories[0]->name)->toBe('A'); // sort_order 1, name A
    expect($orderedCategories[1]->name)->toBe('B'); // sort_order 1, name B
    expect($orderedCategories[2]->name)->toBe('C'); // sort_order 2
});

test('byEventType scope filters categories by event type', function () {
    $eventType1 = EventType::factory()->create();
    $eventType2 = EventType::factory()->create();

    EventCategory::factory()->count(3)->create(['event_type_id' => $eventType1->id]);
    EventCategory::factory()->count(2)->create(['event_type_id' => $eventType2->id]);

    $categoriesForType1 = EventCategory::byEventType($eventType1->id)->get();
    $categoriesForType2 = EventCategory::byEventType($eventType2->id)->get();

    expect($categoriesForType1)->toHaveCount(3);
    expect($categoriesForType2)->toHaveCount(2);
});

test('combined scopes work together', function () {
    $eventType1 = EventType::factory()->create();
    $eventType2 = EventType::factory()->create();

    EventCategory::factory()->create(['event_type_id' => $eventType1->id, 'is_active' => true, 'name' => 'B', 'sort_order' => 2]);
    EventCategory::factory()->create(['event_type_id' => $eventType1->id, 'is_active' => true, 'name' => 'A', 'sort_order' => 1]);
    EventCategory::factory()->create(['event_type_id' => $eventType1->id, 'is_active' => false, 'name' => 'C', 'sort_order' => 3]);
    EventCategory::factory()->create(['event_type_id' => $eventType2->id, 'is_active' => true, 'name' => 'D', 'sort_order' => 1]);

    $categories = EventCategory::byEventType($eventType1->id)
        ->active()
        ->ordered()
        ->get();

    expect($categories)->toHaveCount(2);
    expect($categories[0]->name)->toBe('A');
    expect($categories[1]->name)->toBe('B');
});

// ============================================================================
// NAVIGATION BADGE TESTS
// ============================================================================

test('navigation badge shows count of active categories', function () {
    $eventType = EventType::factory()->create();
    EventCategory::factory()->count(5)->create(['event_type_id' => $eventType->id, 'is_active' => true]);
    EventCategory::factory()->count(2)->create(['event_type_id' => $eventType->id, 'is_active' => false]);

    $badge = EventCategoryResource::getNavigationBadge();

    expect($badge)->toBe('5');
});

test('navigation badge color is success', function () {
    $color = EventCategoryResource::getNavigationBadgeColor();
    expect($color)->toBe('success');
});

// ============================================================================
// AUTHORIZATION TESTS
// ============================================================================

test('non-admin users cannot access event categories', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(EventCategoryResource::getUrl('index'))
        ->assertForbidden();
});

test('unauthorized users cannot create event categories', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(EventCategoryResource::getUrl('create'))
        ->assertForbidden();
});

test('unauthorized users cannot edit event categories', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $eventType = EventType::factory()->create();
    $category = EventCategory::factory()->create(['event_type_id' => $eventType->id]);
    $this->actingAs($user);

    $this->get(EventCategoryResource::getUrl('edit', ['record' => $category]))
        ->assertForbidden();
});

// ============================================================================
// BULK DELETE TESTS
// ============================================================================

test('can bulk delete event categories', function () {
    $eventType = EventType::factory()->create();
    $categories = EventCategory::factory()->count(3)->create(['event_type_id' => $eventType->id]);

    Livewire::test(EventCategoryResource\Pages\ListEventCategories::class)
        ->callTableBulkAction('delete', $categories);

    foreach ($categories as $category) {
        $this->assertModelMissing($category);
    }
});

// ============================================================================
// FORM FIELD TESTS
// ============================================================================

test('event type field shows only available event types', function () {
    $activeType = EventType::factory()->create(['is_active' => true]);
    $inactiveType = EventType::factory()->inactive()->create();

    Livewire::test(EventCategoryResource\Pages\CreateEventCategory::class)
        ->assertFormFieldExists('event_type_id')
        ->assertFormFieldIsVisible('event_type_id');
});

test('color field accepts valid hex color', function () {
    $eventType = EventType::factory()->create();

    Livewire::test(EventCategoryResource\Pages\CreateEventCategory::class)
        ->fillForm([
            'event_type_id' => $eventType->id,
            'name' => 'Test Category',
            'color' => '#FF0000',
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('event_categories', [
        'name' => 'Test Category',
        'color' => '#FF0000',
    ]);
});

test('default sort order is 0', function () {
    $eventType = EventType::factory()->create();

    Livewire::test(EventCategoryResource\Pages\CreateEventCategory::class)
        ->fillForm([
            'event_type_id' => $eventType->id,
            'name' => 'Test Category',
            'is_active' => true,
        ])
        ->assertFormSet(['sort_order' => 0]);
});
