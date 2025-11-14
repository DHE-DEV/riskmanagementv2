<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\InfosystemEntryResource;
use App\Filament\Resources\InfosystemEntryResource\Pages\CreateInfosystemEntry;
use App\Filament\Resources\InfosystemEntryResource\Pages\EditInfosystemEntry;
use App\Filament\Resources\InfosystemEntryResource\Pages\ListInfosystemEntries;
use App\Models\InfosystemEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InfosystemEntryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate a user for all tests
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_infosystem_entries()
    {
        $entries = InfosystemEntry::factory()->count(5)->create();

        Livewire::test(ListInfosystemEntries::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($entries);
    }

    /** @test */
    public function it_can_render_index_page()
    {
        $this->get(InfosystemEntryResource::getUrl('index'))
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_render_create_page()
    {
        $this->get(InfosystemEntryResource::getUrl('create'))
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_create_infosystem_entry_with_all_required_fields()
    {
        $newData = [
            'api_id' => 'test-api-123',
            'header' => 'Test Travel Advisory',
            'content' => 'This is a test travel advisory content.',
            'tagdate' => now()->format('Y-m-d'),
            'active' => true,
            'lang' => 'de',
        ];

        Livewire::test(CreateInfosystemEntry::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('infosystem_entries', [
            'api_id' => 'test-api-123',
            'header' => 'Test Travel Advisory',
        ]);
    }

    /** @test */
    public function it_can_create_infosystem_entry_with_all_fields()
    {
        $newData = [
            'api_id' => 'test-api-456',
            'header' => 'Complete Travel Advisory',
            'content' => 'This is a complete test advisory with all fields.',
            'tagdate' => now()->format('Y-m-d'),
            'active' => true,
            'position' => 5,
            'appearance' => '2',
            'tagtype' => 'warning',
            'tagtext' => 'Important Notice',
            'country_code' => 'DE',
            'country_names' => [
                'de' => 'Deutschland',
                'en' => 'Germany',
            ],
            'lang' => 'de',
            'language_content' => 'German',
            'language_code' => 'de',
            'archive' => false,
            'api_created_at' => now()->format('Y-m-d H:i:s'),
            'request_id' => 'req-789',
            'response_time' => 250,
        ];

        Livewire::test(CreateInfosystemEntry::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('infosystem_entries', [
            'api_id' => 'test-api-456',
            'header' => 'Complete Travel Advisory',
            'country_code' => 'DE',
        ]);
    }

    /** @test */
    public function it_validates_required_api_id_field()
    {
        $newData = [
            'header' => 'Test Advisory',
            'content' => 'Test content',
            // api_id is missing
        ];

        Livewire::test(CreateInfosystemEntry::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasFormErrors(['api_id' => 'required']);
    }

    /** @test */
    public function it_validates_max_length_for_text_fields()
    {
        $newData = [
            'api_id' => str_repeat('a', 256), // Over 255 limit
            'header' => 'Test Advisory',
            'content' => 'Test content',
        ];

        Livewire::test(CreateInfosystemEntry::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasFormErrors(['api_id']);
    }

    /** @test */
    public function it_can_render_edit_page()
    {
        $entry = InfosystemEntry::factory()->create();

        $this->get(InfosystemEntryResource::getUrl('edit', ['record' => $entry]))
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_retrieve_data_in_edit_form()
    {
        $entry = InfosystemEntry::factory()->create([
            'api_id' => 'edit-test-123',
            'header' => 'Original Header',
            'content' => 'Original content',
        ]);

        Livewire::test(EditInfosystemEntry::class, ['record' => $entry->getRouteKey()])
            ->assertFormSet([
                'api_id' => 'edit-test-123',
                'header' => 'Original Header',
                'content' => 'Original content',
            ]);
    }

    /** @test */
    public function it_can_update_infosystem_entry()
    {
        $entry = InfosystemEntry::factory()->create([
            'api_id' => 'update-test-123',
            'header' => 'Original Header',
        ]);

        $updatedData = [
            'header' => 'Updated Header',
            'content' => 'Updated content for the entry.',
        ];

        Livewire::test(EditInfosystemEntry::class, ['record' => $entry->getRouteKey()])
            ->fillForm($updatedData)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('infosystem_entries', [
            'id' => $entry->id,
            'header' => 'Updated Header',
            'content' => 'Updated content for the entry.',
        ]);
    }

    /** @test */
    public function it_can_update_json_field_country_names()
    {
        $entry = InfosystemEntry::factory()->create();

        $updatedData = [
            'country_names' => [
                'de' => 'Frankreich',
                'en' => 'France',
                'fr' => 'France',
            ],
        ];

        Livewire::test(EditInfosystemEntry::class, ['record' => $entry->getRouteKey()])
            ->fillForm($updatedData)
            ->call('save')
            ->assertHasNoFormErrors();

        $entry->refresh();
        $this->assertEquals(['de' => 'Frankreich', 'en' => 'France', 'fr' => 'France'], $entry->country_names);
    }

    /** @test */
    public function it_can_toggle_active_status()
    {
        $entry = InfosystemEntry::factory()->create(['active' => true]);

        Livewire::test(EditInfosystemEntry::class, ['record' => $entry->getRouteKey()])
            ->fillForm(['active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $entry->refresh();
        $this->assertFalse($entry->active);
    }

    /** @test */
    public function it_can_toggle_archive_status()
    {
        $entry = InfosystemEntry::factory()->create(['archive' => false]);

        Livewire::test(EditInfosystemEntry::class, ['record' => $entry->getRouteKey()])
            ->fillForm(['archive' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $entry->refresh();
        $this->assertTrue($entry->archive);
    }

    /** @test */
    public function it_can_filter_by_language()
    {
        InfosystemEntry::factory()->create(['lang' => 'de']);
        InfosystemEntry::factory()->create(['lang' => 'en']);
        InfosystemEntry::factory()->create(['lang' => 'fr']);

        Livewire::test(ListInfosystemEntries::class)
            ->filterTable('lang', 'de')
            ->assertCanSeeTableRecords(InfosystemEntry::where('lang', 'de')->get())
            ->assertCanNotSeeTableRecords(InfosystemEntry::where('lang', '!=', 'de')->get());
    }

    /** @test */
    public function it_can_filter_by_active_status()
    {
        $activeEntry = InfosystemEntry::factory()->create(['active' => true]);
        $inactiveEntry = InfosystemEntry::factory()->create(['active' => false]);

        Livewire::test(ListInfosystemEntries::class)
            ->filterTable('active', true)
            ->assertCanSeeTableRecords([$activeEntry])
            ->assertCanNotSeeTableRecords([$inactiveEntry]);
    }

    /** @test */
    public function it_can_filter_by_archive_status()
    {
        $archived = InfosystemEntry::factory()->create(['archive' => true]);
        $notArchived = InfosystemEntry::factory()->create(['archive' => false]);

        Livewire::test(ListInfosystemEntries::class)
            ->filterTable('archive', true)
            ->assertCanSeeTableRecords([$archived])
            ->assertCanNotSeeTableRecords([$notArchived]);
    }

    /** @test */
    public function it_can_filter_by_published_status()
    {
        $published = InfosystemEntry::factory()->create([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $unpublished = InfosystemEntry::factory()->create([
            'is_published' => false,
            'published_at' => null,
        ]);

        Livewire::test(ListInfosystemEntries::class)
            ->filterTable('is_published', true)
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$unpublished]);
    }

    /** @test */
    public function it_can_search_by_header()
    {
        $entry1 = InfosystemEntry::factory()->create(['header' => 'Important Travel Notice']);
        $entry2 = InfosystemEntry::factory()->create(['header' => 'Weather Update']);

        Livewire::test(ListInfosystemEntries::class)
            ->searchTable('Important')
            ->assertCanSeeTableRecords([$entry1])
            ->assertCanNotSeeTableRecords([$entry2]);
    }

    /** @test */
    public function it_can_search_by_api_id()
    {
        $entry1 = InfosystemEntry::factory()->create(['api_id' => 'ABC123']);
        $entry2 = InfosystemEntry::factory()->create(['api_id' => 'XYZ789']);

        Livewire::test(ListInfosystemEntries::class)
            ->searchTable('ABC123')
            ->assertCanSeeTableRecords([$entry1])
            ->assertCanNotSeeTableRecords([$entry2]);
    }

    /** @test */
    public function it_can_sort_by_tagdate()
    {
        $newer = InfosystemEntry::factory()->create(['tagdate' => now()]);
        $older = InfosystemEntry::factory()->create(['tagdate' => now()->subDays(10)]);

        Livewire::test(ListInfosystemEntries::class)
            ->sortTable('tagdate', 'desc')
            ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
    }

    /** @test */
    public function it_can_validate_language_selection()
    {
        $validLanguages = ['de', 'en', 'fr', 'it'];

        foreach ($validLanguages as $lang) {
            $newData = [
                'api_id' => 'lang-test-' . $lang,
                'header' => 'Test Header',
                'content' => 'Test content',
                'lang' => $lang,
            ];

            Livewire::test(CreateInfosystemEntry::class)
                ->fillForm($newData)
                ->call('create')
                ->assertHasNoFormErrors(['lang']);

            $this->assertDatabaseHas('infosystem_entries', [
                'api_id' => 'lang-test-' . $lang,
                'lang' => $lang,
            ]);
        }
    }

    /** @test */
    public function it_can_validate_numeric_fields()
    {
        $newData = [
            'api_id' => 'numeric-test',
            'header' => 'Test',
            'content' => 'Test',
            'position' => 5,
            'response_time' => 123,
        ];

        Livewire::test(CreateInfosystemEntry::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors(['position', 'response_time']);

        $this->assertDatabaseHas('infosystem_entries', [
            'api_id' => 'numeric-test',
            'position' => 5,
            'response_time' => 123,
        ]);
    }

    /** @test */
    public function it_has_default_values_for_boolean_fields()
    {
        $entry = InfosystemEntry::factory()->create();

        // active defaults to true based on model
        $this->assertTrue($entry->active);
        // archive defaults to false
        $this->assertFalse($entry->archive);
    }

    /** @test */
    public function it_can_render_view_page()
    {
        $entry = InfosystemEntry::factory()->create();

        $this->get(InfosystemEntryResource::getUrl('view', ['record' => $entry]))
            ->assertSuccessful();
    }

    /** @test */
    public function it_displays_published_icon_correctly()
    {
        $published = InfosystemEntry::factory()->create([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $unpublished = InfosystemEntry::factory()->create([
            'is_published' => false,
        ]);

        Livewire::test(ListInfosystemEntries::class)
            ->assertCanSeeTableRecords([$published, $unpublished]);
    }

    /** @test */
    public function it_can_handle_null_country_code()
    {
        $newData = [
            'api_id' => 'null-country-test',
            'header' => 'Test Header',
            'content' => 'Test content',
            'country_code' => null,
        ];

        Livewire::test(CreateInfosystemEntry::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('infosystem_entries', [
            'api_id' => 'null-country-test',
            'country_code' => null,
        ]);
    }

    /** @test */
    public function it_can_update_categories_array()
    {
        $entry = InfosystemEntry::factory()->create([
            'categories' => ['category1', 'category2'],
        ]);

        $entry->refresh();
        $this->assertEquals(['category1', 'category2'], $entry->categories);

        $entry->update(['categories' => ['category3', 'category4', 'category5']]);
        $entry->refresh();
        $this->assertEquals(['category3', 'category4', 'category5'], $entry->categories);
    }
}
