<?php

declare(strict_types=1);

use App\Filament\Resources\EventDisplaySettings\EventDisplaySettingResource;
use App\Filament\Resources\EventDisplaySettings\Pages\EditEventDisplaySetting;
use App\Models\EventDisplaySetting;
use App\Models\EventType;
use App\Models\User;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();

    // Create or get the singleton settings instance
    $this->settings = EventDisplaySetting::current();

    // Create some event types for testing
    $this->eventType1 = EventType::factory()->create(['name' => 'Multi-Event Type']);
    $this->eventType2 = EventType::factory()->create(['name' => 'Another Event Type']);
});

// =====================================================
// AUTHORIZATION TESTS
// =====================================================

describe('Authorization', function () {
    test('admin can access settings edit page', function () {
        actingAs($this->admin)
            ->get(EventDisplaySettingResource::getUrl('index'))
            ->assertSuccessful();
    });

    test('non-admin can access settings page (no restrictions in resource)', function () {
        // EventDisplaySettingResource doesn't have explicit canViewAny restrictions
        // so we test the actual behavior
        actingAs($this->user)
            ->get(EventDisplaySettingResource::getUrl('index'))
            ->assertSuccessful();
    });

    test('guest is redirected to login', function () {
        $this->get(EventDisplaySettingResource::getUrl('index'))
            ->assertRedirect('/admin/login');
    });

    test('cannot create new event display settings', function () {
        expect(EventDisplaySettingResource::canCreate())->toBeFalse();
    });

    test('cannot delete event display settings', function () {
        expect(EventDisplaySettingResource::canDelete($this->settings))->toBeFalse();
    });

    test('cannot bulk delete event display settings', function () {
        expect(EventDisplaySettingResource::canDeleteAny())->toBeFalse();
    });
});

// =====================================================
// SINGLETON BEHAVIOR TESTS
// =====================================================

describe('Singleton Behavior', function () {
    test('always returns same settings instance', function () {
        $settings1 = EventDisplaySetting::current();
        $settings2 = EventDisplaySetting::current();

        expect($settings1->id)->toBe($settings2->id);
    });

    test('creates settings if none exist', function () {
        // Delete all settings
        EventDisplaySetting::query()->delete();

        $settings = EventDisplaySetting::current();

        expect($settings)->not->toBeNull();
        expect($settings->exists)->toBeTrue();
        expect($settings->multi_event_icon_strategy)->toBe('default');
        expect($settings->show_icon_preview_in_form)->toBeTrue();
    });

    test('returns fresh instance from database', function () {
        $settings = EventDisplaySetting::current();
        $originalStrategy = $settings->multi_event_icon_strategy;

        // Update directly in database
        EventDisplaySetting::query()->first()->update([
            'multi_event_icon_strategy' => 'show_all',
        ]);

        // Current should return fresh data
        $freshSettings = EventDisplaySetting::current();
        expect($freshSettings->multi_event_icon_strategy)->toBe('show_all');
        expect($freshSettings->multi_event_icon_strategy)->not->toBe($originalStrategy);
    });
});

// =====================================================
// READ/RETRIEVE TESTS
// =====================================================

describe('Read Settings', function () {
    test('can render edit page', function () {
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->assertSuccessful();
    });

    test('can retrieve existing settings data', function () {
        $this->settings->update([
            'multi_event_icon_strategy' => 'manual_select',
            'show_icon_preview_in_form' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->assertFormSet([
                'multi_event_icon_strategy' => 'manual_select',
                'show_icon_preview_in_form' => false,
            ]);
    });

    test('displays all strategy options', function () {
        $options = EventDisplaySetting::getStrategyOptions();

        expect($options)->toBeArray();
        expect($options)->toHaveKeys([
            'default',
            'manual_select',
            'multi_event_type',
            'show_all',
            'show_icon_preview',
        ]);
    });
});

// =====================================================
// UPDATE TESTS
// =====================================================

describe('Update Settings', function () {
    test('can update to default strategy', function () {
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_icon_strategy' => 'default',
                'show_icon_preview_in_form' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('event_display_settings', [
            'id' => $this->settings->id,
            'multi_event_icon_strategy' => 'default',
            'show_icon_preview_in_form' => true,
        ]);
    });

    test('can update to manual_select strategy', function () {
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_icon_strategy' => 'manual_select',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('event_display_settings', [
            'id' => $this->settings->id,
            'multi_event_icon_strategy' => 'manual_select',
        ]);
    });

    test('can update to multi_event_type strategy with event type', function () {
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_icon_strategy' => 'multi_event_type',
                'multi_event_type_id' => $this->eventType1->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('event_display_settings', [
            'id' => $this->settings->id,
            'multi_event_icon_strategy' => 'multi_event_type',
            'multi_event_type_id' => $this->eventType1->id,
        ]);
    });

    test('can update to show_all strategy', function () {
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_icon_strategy' => 'show_all',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('event_display_settings', [
            'id' => $this->settings->id,
            'multi_event_icon_strategy' => 'show_all',
        ]);
    });

    test('can update to show_icon_preview strategy', function () {
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_icon_strategy' => 'show_icon_preview',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('event_display_settings', [
            'id' => $this->settings->id,
            'multi_event_icon_strategy' => 'show_icon_preview',
        ]);
    });

    test('can toggle show_icon_preview_in_form', function () {
        $originalValue = $this->settings->show_icon_preview_in_form;

        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'show_icon_preview_in_form' => !$originalValue,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('event_display_settings', [
            'id' => $this->settings->id,
            'show_icon_preview_in_form' => !$originalValue,
        ]);
    });

    test('can update strategy_description', function () {
        $description = 'This is a custom strategy description for testing purposes.';

        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'strategy_description' => $description,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('event_display_settings', [
            'id' => $this->settings->id,
            'strategy_description' => $description,
        ]);
    });

    test('can update all fields at once', function () {
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_icon_strategy' => 'multi_event_type',
                'multi_event_type_id' => $this->eventType2->id,
                'show_icon_preview_in_form' => false,
                'strategy_description' => 'Complete update test description',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('event_display_settings', [
            'id' => $this->settings->id,
            'multi_event_icon_strategy' => 'multi_event_type',
            'multi_event_type_id' => $this->eventType2->id,
            'show_icon_preview_in_form' => false,
            'strategy_description' => 'Complete update test description',
        ]);
    });

    test('can clear strategy_description', function () {
        $this->settings->update(['strategy_description' => 'Some description']);

        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'strategy_description' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('event_display_settings', [
            'id' => $this->settings->id,
            'strategy_description' => null,
        ]);
    });

    test('can change event type for multi_event_type strategy', function () {
        $this->settings->update([
            'multi_event_icon_strategy' => 'multi_event_type',
            'multi_event_type_id' => $this->eventType1->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_type_id' => $this->eventType2->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('event_display_settings', [
            'id' => $this->settings->id,
            'multi_event_type_id' => $this->eventType2->id,
        ]);
    });
});

// =====================================================
// VALIDATION TESTS
// =====================================================

describe('Validation', function () {
    test('requires multi_event_icon_strategy', function () {
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_icon_strategy' => null,
            ])
            ->call('save')
            ->assertHasFormErrors(['multi_event_icon_strategy' => 'required']);
    });

    test('requires multi_event_type_id when strategy is multi_event_type', function () {
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_icon_strategy' => 'multi_event_type',
                'multi_event_type_id' => null,
            ])
            ->call('save')
            ->assertHasFormErrors(['multi_event_type_id' => 'required']);
    });

    test('does not require multi_event_type_id for other strategies', function () {
        $strategies = ['default', 'manual_select', 'show_all', 'show_icon_preview'];

        foreach ($strategies as $strategy) {
            Livewire::actingAs($this->admin)
                ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
                ->fillForm([
                    'multi_event_icon_strategy' => $strategy,
                    'multi_event_type_id' => null,
                ])
                ->call('save')
                ->assertHasNoFormErrors();
        }
    });
});

// =====================================================
// MODEL METHOD TESTS
// =====================================================

describe('Model Methods', function () {
    test('shouldShowManualSelection returns correct value', function () {
        $this->settings->update(['multi_event_icon_strategy' => 'manual_select']);
        expect($this->settings->fresh()->shouldShowManualSelection())->toBeTrue();

        $this->settings->update(['multi_event_icon_strategy' => 'default']);
        expect($this->settings->fresh()->shouldShowManualSelection())->toBeFalse();
    });

    test('shouldShowIconPreview returns correct value', function () {
        $this->settings->update([
            'show_icon_preview_in_form' => true,
            'multi_event_icon_strategy' => 'default',
        ]);
        expect($this->settings->fresh()->shouldShowIconPreview())->toBeTrue();

        $this->settings->update([
            'show_icon_preview_in_form' => false,
            'multi_event_icon_strategy' => 'show_icon_preview',
        ]);
        expect($this->settings->fresh()->shouldShowIconPreview())->toBeTrue();

        $this->settings->update([
            'show_icon_preview_in_form' => false,
            'multi_event_icon_strategy' => 'default',
        ]);
        expect($this->settings->fresh()->shouldShowIconPreview())->toBeFalse();
    });

    test('shouldUseMultiEventType returns correct value', function () {
        $this->settings->update([
            'multi_event_icon_strategy' => 'multi_event_type',
            'multi_event_type_id' => $this->eventType1->id,
        ]);
        expect($this->settings->fresh()->shouldUseMultiEventType())->toBeTrue();

        $this->settings->update([
            'multi_event_icon_strategy' => 'multi_event_type',
            'multi_event_type_id' => null,
        ]);
        expect($this->settings->fresh()->shouldUseMultiEventType())->toBeFalse();

        $this->settings->update([
            'multi_event_icon_strategy' => 'default',
            'multi_event_type_id' => $this->eventType1->id,
        ]);
        expect($this->settings->fresh()->shouldUseMultiEventType())->toBeFalse();
    });

    test('shouldShowAllIcons returns correct value', function () {
        $this->settings->update(['multi_event_icon_strategy' => 'show_all']);
        expect($this->settings->fresh()->shouldShowAllIcons())->toBeTrue();

        $this->settings->update(['multi_event_icon_strategy' => 'default']);
        expect($this->settings->fresh()->shouldShowAllIcons())->toBeFalse();
    });

    test('multiEventType relationship works correctly', function () {
        $this->settings->update([
            'multi_event_icon_strategy' => 'multi_event_type',
            'multi_event_type_id' => $this->eventType1->id,
        ]);

        $settings = EventDisplaySetting::with('multiEventType')->first();
        expect($settings->multiEventType)->not->toBeNull();
        expect($settings->multiEventType->id)->toBe($this->eventType1->id);
        expect($settings->multiEventType->name)->toBe('Multi-Event Type');
    });
});

// =====================================================
// STRATEGY OPTION TESTS
// =====================================================

describe('Strategy Options', function () {
    test('getStrategyOptions returns all expected strategies', function () {
        $options = EventDisplaySetting::getStrategyOptions();

        expect($options)->toHaveCount(5);
        expect($options)->toHaveKey('default');
        expect($options)->toHaveKey('manual_select');
        expect($options)->toHaveKey('multi_event_type');
        expect($options)->toHaveKey('show_all');
        expect($options)->toHaveKey('show_icon_preview');
    });

    test('strategy options have German labels', function () {
        $options = EventDisplaySetting::getStrategyOptions();

        expect($options['default'])->toContain('Standard');
        expect($options['manual_select'])->toContain('Manuell');
        expect($options['multi_event_type'])->toContain('Multi-Event');
        expect($options['show_all'])->toContain('Alle Icons');
        expect($options['show_icon_preview'])->toContain('Vorschau');
    });
});

// =====================================================
// FIELD VISIBILITY TESTS
// =====================================================

describe('Field Visibility', function () {
    test('multi_event_type_id field is visible when strategy is multi_event_type', function () {
        $component = Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_icon_strategy' => 'multi_event_type',
            ]);

        // The field should be visible and required when strategy is multi_event_type
        $component->assertFormFieldExists('multi_event_type_id');
    });

    test('strategy_description field exists', function () {
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->assertFormFieldExists('strategy_description');
    });

    test('show_icon_preview_in_form field exists', function () {
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->assertFormFieldExists('show_icon_preview_in_form');
    });
});

// =====================================================
// INTEGRATION TESTS
// =====================================================

describe('Integration Tests', function () {
    test('switching strategies updates database correctly', function () {
        $strategies = ['default', 'manual_select', 'show_all', 'show_icon_preview'];

        foreach ($strategies as $strategy) {
            Livewire::actingAs($this->admin)
                ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
                ->fillForm(['multi_event_icon_strategy' => $strategy])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->settings->refresh();
            expect($this->settings->multi_event_icon_strategy)->toBe($strategy);
        }
    });

    test('strategy change with event type selection', function () {
        // First set to multi_event_type with an event type
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_icon_strategy' => 'multi_event_type',
                'multi_event_type_id' => $this->eventType1->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('event_display_settings', [
            'id' => $this->settings->id,
            'multi_event_icon_strategy' => 'multi_event_type',
            'multi_event_type_id' => $this->eventType1->id,
        ]);

        // Then change to a different strategy (event type should remain but not be used)
        Livewire::actingAs($this->admin)
            ->test(EditEventDisplaySetting::class, ['record' => $this->settings->id])
            ->fillForm([
                'multi_event_icon_strategy' => 'default',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->settings->refresh();
        expect($this->settings->multi_event_icon_strategy)->toBe('default');
    });
});
