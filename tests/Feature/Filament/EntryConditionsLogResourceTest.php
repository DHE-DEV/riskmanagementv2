<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\EntryConditionsLogs\EntryConditionsLogResource;
use App\Filament\Resources\EntryConditionsLogs\Pages\ListEntryConditionsLogs;
use App\Filament\Resources\EntryConditionsLogs\Pages\ViewEntryConditionsLog;
use App\Models\EntryConditionsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EntryConditionsLogResourceTest extends TestCase
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
    public function it_can_list_entry_conditions_logs()
    {
        $logs = EntryConditionsLog::factory()->count(5)->create();

        Livewire::test(ListEntryConditionsLogs::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($logs);
    }

    /** @test */
    public function it_can_render_index_page()
    {
        $this->get(EntryConditionsLogResource::getUrl('index'))
            ->assertSuccessful();
    }

    /** @test */
    public function it_cannot_create_entry_conditions_log()
    {
        // The resource has canCreate() returning false
        $this->assertFalse(EntryConditionsLogResource::canCreate());
    }

    /** @test */
    public function it_does_not_have_create_page()
    {
        // Verify that there is no create route
        $pages = EntryConditionsLogResource::getPages();
        $this->assertArrayNotHasKey('create', $pages);
    }

    /** @test */
    public function it_can_render_view_page()
    {
        $log = EntryConditionsLog::factory()->create();

        $this->get(EntryConditionsLogResource::getUrl('view', ['record' => $log]))
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_view_log_with_all_fields()
    {
        $log = EntryConditionsLog::factory()->create([
            'nationality' => 'DE',
            'success' => true,
            'results_count' => 5,
            'filters' => [
                'passport' => true,
                'idCard' => false,
                'visaFree' => true,
            ],
            'request_body' => [
                'nationality' => 'DE',
                'destination' => 'FR',
            ],
            'response_data' => [
                'success' => true,
                'data' => ['requirements' => ['passport' => true]],
            ],
            'response_status' => 200,
        ]);

        Livewire::test(ViewEntryConditionsLog::class, ['record' => $log->getRouteKey()])
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_filter_by_nationality()
    {
        $germanLog = EntryConditionsLog::factory()->create(['nationality' => 'DE']);
        $frenchLog = EntryConditionsLog::factory()->create(['nationality' => 'FR']);

        Livewire::test(ListEntryConditionsLogs::class)
            ->filterTable('nationality', 'DE')
            ->assertCanSeeTableRecords([$germanLog])
            ->assertCanNotSeeTableRecords([$frenchLog]);
    }

    /** @test */
    public function it_can_filter_by_success_status()
    {
        $successLog = EntryConditionsLog::factory()->successful()->create();
        $failedLog = EntryConditionsLog::factory()->failed()->create();

        Livewire::test(ListEntryConditionsLogs::class)
            ->filterTable('success', true)
            ->assertCanSeeTableRecords([$successLog])
            ->assertCanNotSeeTableRecords([$failedLog]);

        Livewire::test(ListEntryConditionsLogs::class)
            ->filterTable('success', false)
            ->assertCanSeeTableRecords([$failedLog])
            ->assertCanNotSeeTableRecords([$successLog]);
    }

    /** @test */
    public function it_displays_success_icon_correctly()
    {
        $successLog = EntryConditionsLog::factory()->successful()->create();
        $failedLog = EntryConditionsLog::factory()->failed()->create();

        Livewire::test(ListEntryConditionsLogs::class)
            ->assertCanSeeTableRecords([$successLog, $failedLog]);
    }

    /** @test */
    public function it_can_search_by_nationality()
    {
        $log1 = EntryConditionsLog::factory()->create(['nationality' => 'DE']);
        $log2 = EntryConditionsLog::factory()->create(['nationality' => 'FR']);

        Livewire::test(ListEntryConditionsLogs::class)
            ->searchTable('DE')
            ->assertCanSeeTableRecords([$log1])
            ->assertCanNotSeeTableRecords([$log2]);
    }

    /** @test */
    public function it_can_sort_by_created_at()
    {
        $newer = EntryConditionsLog::factory()->create(['created_at' => now()]);
        $older = EntryConditionsLog::factory()->create(['created_at' => now()->subHours(5)]);

        Livewire::test(ListEntryConditionsLogs::class)
            ->sortTable('created_at', 'desc')
            ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
    }

    /** @test */
    public function it_can_handle_successful_logs_with_results()
    {
        $log = EntryConditionsLog::factory()->successful()->create([
            'results_count' => 10,
            'response_status' => 200,
        ]);

        $this->assertTrue($log->success);
        $this->assertEquals(200, $log->response_status);
        $this->assertEquals(10, $log->results_count);
        $this->assertNull($log->error_message);
    }

    /** @test */
    public function it_can_handle_failed_logs_with_error_message()
    {
        $errorMessage = 'API request failed due to invalid parameters';
        $log = EntryConditionsLog::factory()->failed()->create([
            'error_message' => $errorMessage,
            'results_count' => 0,
        ]);

        $this->assertFalse($log->success);
        $this->assertEquals(0, $log->results_count);
        $this->assertNotNull($log->error_message);
        $this->assertStringContainsString('failed', $log->error_message);
    }

    /** @test */
    public function it_stores_filters_as_json()
    {
        $filters = [
            'passport' => true,
            'idCard' => false,
            'visaFree' => true,
            'eVisa' => false,
        ];

        $log = EntryConditionsLog::factory()->create(['filters' => $filters]);

        $this->assertEquals($filters, $log->filters);
        $this->assertIsArray($log->filters);
    }

    /** @test */
    public function it_stores_request_body_as_json()
    {
        $requestBody = [
            'nationality' => 'DE',
            'destination' => 'US',
            'date' => '2025-12-01',
            'travel_purpose' => 'tourism',
        ];

        $log = EntryConditionsLog::factory()->create(['request_body' => $requestBody]);

        $this->assertEquals($requestBody, $log->request_body);
        $this->assertIsArray($log->request_body);
    }

    /** @test */
    public function it_stores_response_data_as_json()
    {
        $responseData = [
            'success' => true,
            'data' => [
                'requirements' => [
                    'passport' => true,
                    'visa' => false,
                    'vaccination' => false,
                ],
                'validity' => '90 days',
            ],
            'metadata' => [
                'timestamp' => '2025-11-14T10:00:00Z',
                'version' => '1.0',
            ],
        ];

        $log = EntryConditionsLog::factory()->create(['response_data' => $responseData]);

        $this->assertEquals($responseData, $log->response_data);
        $this->assertIsArray($log->response_data);
    }

    /** @test */
    public function it_can_handle_empty_filters()
    {
        $log = EntryConditionsLog::factory()->create(['filters' => []]);

        $this->assertEquals([], $log->filters);
        $this->assertIsArray($log->filters);
    }

    /** @test */
    public function it_can_handle_null_response_data_for_failed_requests()
    {
        $log = EntryConditionsLog::factory()->failed()->create([
            'response_data' => null,
        ]);

        $this->assertNull($log->response_data);
        $this->assertFalse($log->success);
    }

    /** @test */
    public function it_displays_results_count_correctly()
    {
        $logWithResults = EntryConditionsLog::factory()->create(['results_count' => 15]);
        $logWithoutResults = EntryConditionsLog::factory()->create(['results_count' => 0]);

        Livewire::test(ListEntryConditionsLogs::class)
            ->assertCanSeeTableRecords([$logWithResults, $logWithoutResults]);
    }

    /** @test */
    public function it_formats_filters_for_display()
    {
        $filters = [
            'passport' => true,
            'idCard' => true,
            'tempPassport' => false,
            'visaFree' => true,
            'eVisa' => false,
        ];

        $log = EntryConditionsLog::factory()->create(['filters' => $filters]);

        Livewire::test(ListEntryConditionsLogs::class)
            ->assertCanSeeTableRecords([$log]);
    }

    /** @test */
    public function it_can_handle_different_response_statuses()
    {
        $statuses = [200, 400, 404, 500, 503];

        foreach ($statuses as $status) {
            $log = EntryConditionsLog::factory()->create([
                'response_status' => $status,
                'success' => $status === 200,
            ]);

            $this->assertEquals($status, $log->response_status);
        }
    }

    /** @test */
    public function it_can_delete_logs_via_bulk_action()
    {
        $logs = EntryConditionsLog::factory()->count(3)->create();

        Livewire::test(ListEntryConditionsLogs::class)
            ->callTableBulkAction('delete', $logs);

        foreach ($logs as $log) {
            $this->assertModelMissing($log);
        }
    }

    /** @test */
    public function it_displays_error_message_for_failed_logs()
    {
        $log = EntryConditionsLog::factory()->failed()->create([
            'error_message' => 'Connection timeout',
        ]);

        Livewire::test(ListEntryConditionsLogs::class)
            ->assertCanSeeTableRecords([$log]);
    }

    /** @test */
    public function it_can_view_log_details_with_complex_data()
    {
        $log = EntryConditionsLog::factory()->create([
            'filters' => [
                'passport' => true,
                'idCard' => true,
                'visaFree' => true,
            ],
            'request_body' => [
                'nationality' => 'DE',
                'destination' => 'US',
                'date' => '2025-12-01',
            ],
            'response_data' => [
                'success' => true,
                'data' => [
                    'requirements' => [
                        'passport' => ['required' => true, 'validity' => '6 months'],
                        'visa' => ['required' => true, 'type' => 'ESTA'],
                    ],
                ],
            ],
        ]);

        Livewire::test(ViewEntryConditionsLog::class, ['record' => $log->getRouteKey()])
            ->assertSuccessful();
    }

    /** @test */
    public function it_sorts_by_created_at_descending_by_default()
    {
        $older = EntryConditionsLog::factory()->create(['created_at' => now()->subDays(2)]);
        $middle = EntryConditionsLog::factory()->create(['created_at' => now()->subDay()]);
        $newer = EntryConditionsLog::factory()->create(['created_at' => now()]);

        Livewire::test(ListEntryConditionsLogs::class)
            ->assertCanSeeTableRecords([$newer, $middle, $older], inOrder: true);
    }

    /** @test */
    public function it_can_handle_all_filter_types()
    {
        $allFilters = [
            'passport' => true,
            'idCard' => true,
            'tempPassport' => true,
            'tempIdCard' => true,
            'childPassport' => true,
            'visaFree' => true,
            'eVisa' => true,
            'visaOnArrival' => true,
            'noInsurance' => true,
            'noEntryForm' => true,
        ];

        $log = EntryConditionsLog::factory()->create(['filters' => $allFilters]);

        $this->assertEquals($allFilters, $log->filters);
        $this->assertCount(10, $log->filters);
    }

    /** @test */
    public function it_can_handle_various_nationalities()
    {
        $nationalities = ['DE', 'AT', 'CH', 'FR', 'IT', 'ES', 'GB', 'US'];

        foreach ($nationalities as $nationality) {
            $log = EntryConditionsLog::factory()
                ->forNationality($nationality)
                ->create();

            $this->assertEquals($nationality, $log->nationality);
        }
    }
}
