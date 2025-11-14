<?php

declare(strict_types=1);

use App\Filament\Resources\AiPrompts\AiPromptResource;
use App\Filament\Resources\AiPrompts\Pages\CreateAiPrompt;
use App\Filament\Resources\AiPrompts\Pages\EditAiPrompt;
use App\Filament\Resources\AiPrompts\Pages\ListAiPrompts;
use App\Models\AiPrompt;
use App\Models\User;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
});

// =====================================================
// AUTHORIZATION TESTS
// =====================================================

describe('Authorization', function () {
    test('admin can access ai prompts list page', function () {
        actingAs($this->admin)
            ->get(AiPromptResource::getUrl('index'))
            ->assertSuccessful();
    });

    test('admin can access create page', function () {
        actingAs($this->admin)
            ->get(AiPromptResource::getUrl('create'))
            ->assertSuccessful();
    });

    test('admin can access edit page', function () {
        $prompt = AiPrompt::factory()->create();

        actingAs($this->admin)
            ->get(AiPromptResource::getUrl('edit', ['record' => $prompt]))
            ->assertSuccessful();
    });

    test('user can access ai prompts list page', function () {
        // No explicit restrictions in AiPromptResource
        actingAs($this->user)
            ->get(AiPromptResource::getUrl('index'))
            ->assertSuccessful();
    });

    test('guest is redirected to login', function () {
        $this->get(AiPromptResource::getUrl('index'))
            ->assertRedirect('/admin/login');
    });
});

// =====================================================
// CREATE TESTS
// =====================================================

describe('Create AI Prompt', function () {
    test('can render create page', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->assertSuccessful();
    });

    test('can create prompt with all required fields', function () {
        $promptData = [
            'name' => 'Country Risk Assessment',
            'description' => 'Analyze country risk factors',
            'model_type' => 'Country',
            'prompt_template' => "Analyze {name} with ISO code {iso_code}",
            'category' => 'Security',
            'sort_order' => 10,
            'is_active' => true,
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'name' => 'Country Risk Assessment',
            'description' => 'Analyze country risk factors',
            'model_type' => 'Country',
            'category' => 'Security',
            'sort_order' => 10,
            'is_active' => true,
        ]);
    });

    test('can create prompt for different model types', function () {
        $modelTypes = [
            'Country',
            'Continent',
            'Region',
            'City',
            'Airport',
            'CustomEvent',
            'PassolutionEvent',
            'TextImprovement_Title',
            'TextImprovement_Description',
        ];

        foreach ($modelTypes as $modelType) {
            $promptData = [
                'name' => "Test Prompt for {$modelType}",
                'model_type' => $modelType,
                'prompt_template' => "Test template for {$modelType}: {name}",
                'is_active' => true,
            ];

            Livewire::actingAs($this->admin)
                ->test(CreateAiPrompt::class)
                ->fillForm($promptData)
                ->call('create')
                ->assertHasNoFormErrors();

            assertDatabaseHas('ai_prompts', [
                'name' => "Test Prompt for {$modelType}",
                'model_type' => $modelType,
            ]);
        }
    });

    test('can create inactive prompt', function () {
        $promptData = [
            'name' => 'Inactive Prompt',
            'model_type' => 'Country',
            'prompt_template' => 'Template for inactive prompt',
            'is_active' => false,
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'name' => 'Inactive Prompt',
            'is_active' => false,
        ]);
    });

    test('can create prompt with optional fields', function () {
        $promptData = [
            'name' => 'Prompt with Optional Fields',
            'description' => 'A detailed description of what this prompt does',
            'model_type' => 'Country',
            'prompt_template' => 'Detailed template with placeholders: {name}, {iso_code}',
            'category' => 'Economic Analysis',
            'sort_order' => 50,
            'is_active' => true,
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'name' => 'Prompt with Optional Fields',
            'description' => 'A detailed description of what this prompt does',
            'category' => 'Economic Analysis',
            'sort_order' => 50,
        ]);
    });

    test('can create prompt without optional fields', function () {
        $promptData = [
            'name' => 'Minimal Prompt',
            'model_type' => 'Country',
            'prompt_template' => 'Minimal template',
            'is_active' => true,
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'name' => 'Minimal Prompt',
        ]);

        $prompt = AiPrompt::where('name', 'Minimal Prompt')->first();
        expect($prompt->description)->toBeNull();
        expect($prompt->category)->toBeNull();
    });

    test('requires name field', function () {
        $promptData = [
            'name' => '',
            'model_type' => 'Country',
            'prompt_template' => 'Template',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    });

    test('requires model_type field', function () {
        $promptData = [
            'name' => 'Test Prompt',
            'model_type' => '',
            'prompt_template' => 'Template',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasFormErrors(['model_type' => 'required']);
    });

    test('requires prompt_template field', function () {
        $promptData = [
            'name' => 'Test Prompt',
            'model_type' => 'Country',
            'prompt_template' => '',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasFormErrors(['prompt_template' => 'required']);
    });

    test('validates name max length', function () {
        $promptData = [
            'name' => str_repeat('a', 256), // Exceeds 255 character limit
            'model_type' => 'Country',
            'prompt_template' => 'Template',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasFormErrors(['name']);
    });

    test('validates description max length', function () {
        $promptData = [
            'name' => 'Test Prompt',
            'description' => str_repeat('a', 501), // Exceeds 500 character limit
            'model_type' => 'Country',
            'prompt_template' => 'Template',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasFormErrors(['description']);
    });

    test('validates sort_order is numeric', function () {
        $promptData = [
            'name' => 'Test Prompt',
            'model_type' => 'Country',
            'prompt_template' => 'Template',
            'sort_order' => 'not-a-number',
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasFormErrors(['sort_order']);
    });

    test('validates sort_order minimum value', function () {
        $promptData = [
            'name' => 'Test Prompt',
            'model_type' => 'Country',
            'prompt_template' => 'Template',
            'sort_order' => -1,
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasFormErrors(['sort_order']);
    });
});

// =====================================================
// READ/LIST TESTS
// =====================================================

describe('List AI Prompts', function () {
    test('can render list page', function () {
        Livewire::actingAs($this->admin)
            ->test(ListAiPrompts::class)
            ->assertSuccessful();
    });

    test('can list all prompts', function () {
        $prompts = AiPrompt::factory()->count(5)->create();

        Livewire::actingAs($this->admin)
            ->test(ListAiPrompts::class)
            ->assertCanSeeTableRecords($prompts);
    });

    test('displays prompt details correctly', function () {
        $prompt = AiPrompt::factory()->create([
            'name' => 'Display Test Prompt',
            'model_type' => 'Country',
            'category' => 'Test Category',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListAiPrompts::class)
            ->assertCanSeeTableRecords([$prompt]);
    });

    test('can search prompts by name', function () {
        $searchablePrompt = AiPrompt::factory()->create(['name' => 'Searchable Risk Analysis']);
        $otherPrompt = AiPrompt::factory()->create(['name' => 'Other Prompt']);

        Livewire::actingAs($this->admin)
            ->test(ListAiPrompts::class)
            ->searchTable('Searchable Risk')
            ->assertCanSeeTableRecords([$searchablePrompt])
            ->assertCanNotSeeTableRecords([$otherPrompt]);
    });

    test('can filter prompts by model type', function () {
        $countryPrompt = AiPrompt::factory()->forCountry()->create();
        $cityPrompt = AiPrompt::factory()->create(['model_type' => 'City']);

        Livewire::actingAs($this->admin)
            ->test(ListAiPrompts::class)
            ->filterTable('model_type', 'Country')
            ->assertCanSeeTableRecords([$countryPrompt])
            ->assertCanNotSeeTableRecords([$cityPrompt]);
    });

    test('can filter prompts by active status', function () {
        $activePrompt = AiPrompt::factory()->create(['is_active' => true]);
        $inactivePrompt = AiPrompt::factory()->inactive()->create();

        Livewire::actingAs($this->admin)
            ->test(ListAiPrompts::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$activePrompt])
            ->assertCanNotSeeTableRecords([$inactivePrompt]);
    });

    test('displays soft deleted prompts when requested', function () {
        $prompt = AiPrompt::factory()->create();
        $prompt->delete(); // Soft delete

        // Verify the route includes withTrashed
        $component = Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id]);

        $component->assertSuccessful();
    });
});

// =====================================================
// UPDATE TESTS
// =====================================================

describe('Update AI Prompt', function () {
    test('can render edit page', function () {
        $prompt = AiPrompt::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->assertSuccessful();
    });

    test('can retrieve existing prompt data', function () {
        $prompt = AiPrompt::factory()->create([
            'name' => 'Existing Prompt',
            'description' => 'Existing Description',
            'model_type' => 'Country',
            'category' => 'Security',
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->assertFormSet([
                'name' => 'Existing Prompt',
                'description' => 'Existing Description',
                'model_type' => 'Country',
                'category' => 'Security',
            ]);
    });

    test('can update prompt name', function () {
        $prompt = AiPrompt::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->fillForm(['name' => 'Updated Prompt Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'id' => $prompt->id,
            'name' => 'Updated Prompt Name',
        ]);
    });

    test('can update prompt description', function () {
        $prompt = AiPrompt::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->fillForm(['description' => 'Updated description text'])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'id' => $prompt->id,
            'description' => 'Updated description text',
        ]);
    });

    test('can update model type', function () {
        $prompt = AiPrompt::factory()->forCountry()->create();

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->fillForm(['model_type' => 'City'])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'id' => $prompt->id,
            'model_type' => 'City',
        ]);
    });

    test('can update prompt template', function () {
        $prompt = AiPrompt::factory()->create();

        $newTemplate = "Updated template with placeholders: {name}, {description}, {iso_code}";

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->fillForm(['prompt_template' => $newTemplate])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'id' => $prompt->id,
            'prompt_template' => $newTemplate,
        ]);
    });

    test('can update category', function () {
        $prompt = AiPrompt::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->fillForm(['category' => 'Updated Category'])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'id' => $prompt->id,
            'category' => 'Updated Category',
        ]);
    });

    test('can update sort order', function () {
        $prompt = AiPrompt::factory()->create(['sort_order' => 10]);

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->fillForm(['sort_order' => 99])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'id' => $prompt->id,
            'sort_order' => 99,
        ]);
    });

    test('can toggle is_active status', function () {
        $prompt = AiPrompt::factory()->create(['is_active' => true]);

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'id' => $prompt->id,
            'is_active' => false,
        ]);
    });

    test('can update all fields at once', function () {
        $prompt = AiPrompt::factory()->create();

        $updateData = [
            'name' => 'Completely Updated Prompt',
            'description' => 'Completely updated description',
            'model_type' => 'CustomEvent',
            'prompt_template' => 'New template: {title}, {description}',
            'category' => 'New Category',
            'sort_order' => 75,
            'is_active' => false,
        ];

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->fillForm($updateData)
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('ai_prompts', [
            'id' => $prompt->id,
            'name' => 'Completely Updated Prompt',
            'description' => 'Completely updated description',
            'model_type' => 'CustomEvent',
            'category' => 'New Category',
            'sort_order' => 75,
            'is_active' => false,
        ]);
    });

    test('validates required fields on update', function () {
        $prompt = AiPrompt::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->fillForm(['name' => ''])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required']);
    });
});

// =====================================================
// DELETE TESTS (SOFT DELETE)
// =====================================================

describe('Delete AI Prompt', function () {
    test('can soft delete prompt', function () {
        $prompt = AiPrompt::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->callAction('delete');

        assertSoftDeleted('ai_prompts', [
            'id' => $prompt->id,
        ]);
    });

    test('soft deleted prompt is not in regular queries', function () {
        $prompt = AiPrompt::factory()->create(['name' => 'To Be Deleted']);
        $prompt->delete();

        expect(AiPrompt::where('name', 'To Be Deleted')->first())->toBeNull();
        expect(AiPrompt::withTrashed()->where('name', 'To Be Deleted')->first())->not->toBeNull();
    });

    test('can restore soft deleted prompt', function () {
        $prompt = AiPrompt::factory()->create();
        $prompt->delete();

        // Restore the prompt
        $prompt->restore();

        assertDatabaseHas('ai_prompts', [
            'id' => $prompt->id,
            'deleted_at' => null,
        ]);

        expect(AiPrompt::find($prompt->id))->not->toBeNull();
    });

    test('can force delete prompt', function () {
        $prompt = AiPrompt::factory()->create();
        $promptId = $prompt->id;

        $prompt->forceDelete();

        assertDatabaseMissing('ai_prompts', [
            'id' => $promptId,
        ]);
    });
});

// =====================================================
// MODEL METHOD TESTS
// =====================================================

describe('Model Methods', function () {
    test('active scope returns only active prompts', function () {
        AiPrompt::factory()->count(3)->create(['is_active' => true]);
        AiPrompt::factory()->count(2)->inactive()->create();

        $activePrompts = AiPrompt::active()->get();

        expect($activePrompts->count())->toBe(3);
        expect($activePrompts->every(fn($prompt) => $prompt->is_active))->toBeTrue();
    });

    test('forModel scope filters by model type', function () {
        AiPrompt::factory()->forCountry()->count(2)->create();
        AiPrompt::factory()->forCustomEvent()->count(3)->create();

        $countryPrompts = AiPrompt::forModel('Country')->get();
        $eventPrompts = AiPrompt::forModel('CustomEvent')->get();

        expect($countryPrompts->count())->toBe(2);
        expect($eventPrompts->count())->toBe(3);
    });

    test('byCategory scope filters by category', function () {
        AiPrompt::factory()->count(2)->create(['category' => 'Security']);
        AiPrompt::factory()->count(3)->create(['category' => 'Economic']);

        $securityPrompts = AiPrompt::byCategory('Security')->get();
        $economicPrompts = AiPrompt::byCategory('Economic')->get();

        expect($securityPrompts->count())->toBe(2);
        expect($economicPrompts->count())->toBe(3);
    });

    test('ordered scope sorts by sort_order then name', function () {
        AiPrompt::factory()->create(['name' => 'B Prompt', 'sort_order' => 10]);
        AiPrompt::factory()->create(['name' => 'A Prompt', 'sort_order' => 10]);
        AiPrompt::factory()->create(['name' => 'C Prompt', 'sort_order' => 5]);

        $orderedPrompts = AiPrompt::ordered()->get();

        expect($orderedPrompts->first()->name)->toBe('C Prompt');
        expect($orderedPrompts->get(1)->name)->toBe('A Prompt');
        expect($orderedPrompts->last()->name)->toBe('B Prompt');
    });

    test('fillPlaceholders replaces placeholders correctly', function () {
        $prompt = AiPrompt::factory()->create([
            'prompt_template' => 'Country: {name}, ISO: {iso_code}, Population: {population}',
        ]);

        $data = [
            'name' => 'Germany',
            'iso_code' => 'DE',
            'population' => 83000000,
        ];

        $result = $prompt->fillPlaceholders($data);

        expect($result)->toBe('Country: Germany, ISO: DE, Population: 83000000');
    });

    test('fillPlaceholders handles missing data gracefully', function () {
        $prompt = AiPrompt::factory()->create([
            'prompt_template' => 'Name: {name}, Code: {code}, Missing: {missing}',
        ]);

        $data = [
            'name' => 'Test',
            'code' => 'TST',
        ];

        $result = $prompt->fillPlaceholders($data);

        expect($result)->toBe('Name: Test, Code: TST, Missing: ');
    });

    test('fillPlaceholders handles array values', function () {
        $prompt = AiPrompt::factory()->create([
            'prompt_template' => 'Countries: {countries}',
        ]);

        $data = [
            'countries' => ['Germany', 'France', 'Italy'],
        ];

        $result = $prompt->fillPlaceholders($data);

        expect($result)->toContain('Germany');
        expect($result)->toContain('France');
        expect($result)->toContain('Italy');
    });

    test('fillPlaceholders handles object values', function () {
        $prompt = AiPrompt::factory()->create([
            'prompt_template' => 'Data: {data}',
        ]);

        $data = [
            'data' => (object) ['key' => 'value', 'number' => 42],
        ];

        $result = $prompt->fillPlaceholders($data);

        expect($result)->toContain('key');
        expect($result)->toContain('value');
    });
});

// =====================================================
// COMBINATION/INTEGRATION TESTS
// =====================================================

describe('Integration Tests', function () {
    test('can create prompts for all model types and filter them', function () {
        $modelTypes = ['Country', 'City', 'CustomEvent', 'TextImprovement_Title'];

        foreach ($modelTypes as $modelType) {
            AiPrompt::factory()->create([
                'name' => "Prompt for {$modelType}",
                'model_type' => $modelType,
            ]);
        }

        foreach ($modelTypes as $modelType) {
            $prompts = AiPrompt::forModel($modelType)->get();
            expect($prompts->count())->toBe(1);
            expect($prompts->first()->model_type)->toBe($modelType);
        }
    });

    test('active and ordered scopes work together', function () {
        AiPrompt::factory()->create(['name' => 'B', 'sort_order' => 20, 'is_active' => true]);
        AiPrompt::factory()->create(['name' => 'A', 'sort_order' => 10, 'is_active' => true]);
        AiPrompt::factory()->create(['name' => 'C', 'sort_order' => 5, 'is_active' => false]);

        $prompts = AiPrompt::active()->ordered()->get();

        expect($prompts->count())->toBe(2);
        expect($prompts->first()->name)->toBe('A');
        expect($prompts->last()->name)->toBe('B');
    });

    test('can create, update, soft delete, and restore prompt lifecycle', function () {
        // Create
        $promptData = [
            'name' => 'Lifecycle Test Prompt',
            'model_type' => 'Country',
            'prompt_template' => 'Initial template',
            'is_active' => true,
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateAiPrompt::class)
            ->fillForm($promptData)
            ->call('create')
            ->assertHasNoFormErrors();

        $prompt = AiPrompt::where('name', 'Lifecycle Test Prompt')->first();
        expect($prompt)->not->toBeNull();

        // Update
        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->fillForm(['name' => 'Updated Lifecycle Prompt'])
            ->call('save')
            ->assertHasNoFormErrors();

        $prompt->refresh();
        expect($prompt->name)->toBe('Updated Lifecycle Prompt');

        // Soft Delete
        Livewire::actingAs($this->admin)
            ->test(EditAiPrompt::class, ['record' => $prompt->id])
            ->callAction('delete');

        assertSoftDeleted('ai_prompts', ['id' => $prompt->id]);

        // Restore
        $prompt->restore();
        expect(AiPrompt::find($prompt->id))->not->toBeNull();
    });
});
