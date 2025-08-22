<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\PassolutionApiService;
use App\Models\InfosystemEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase as LaravelTestCase;

class PassolutionApiServiceTest extends LaravelTestCase
{
    use RefreshDatabase;

    protected PassolutionApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PassolutionApiService();
    }

    public function test_fetch_general_info_success()
    {
        // Mock successful API response
        Http::fake([
            'https://api.passolution.eu/api/v2/infosystem/general*' => Http::response([
                'code' => 0,
                'message' => 'success',
                'result' => [
                    'current_page' => 1,
                    'data' => [
                        [
                            'id' => 6573,
                            'position' => 0,
                            'appearance' => 0,
                            'country' => 'TZ',
                            'country_name' => [
                                'de' => 'Tansania',
                                'en' => 'Tanzania'
                            ],
                            'lang' => 'de',
                            'tagtype' => 4,
                            'tagtext' => null,
                            'tagdate' => '2025-08-22',
                            'header' => 'Test Header',
                            'content' => 'Test Content',
                            'created_at' => '2025-08-22T08:11:49.000000Z',
                            'archive' => 0,
                            'active' => true,
                            'language_content' => 'German',
                            'language_code' => 'de'
                        ]
                    ],
                    'total' => 1
                ],
                'requestid' => 'test-request-id',
                'responsetime' => 100
            ], 200)
        ]);

        $result = $this->service->fetchGeneralInfo('de', 1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('test-request-id', $result['request_id']);
        $this->assertEquals(100, $result['response_time']);
    }

    public function test_fetch_general_info_failure()
    {
        // Mock failed API response
        Http::fake([
            'https://api.passolution.eu/api/v2/infosystem/general*' => Http::response('Server Error', 500)
        ]);

        $result = $this->service->fetchGeneralInfo('de', 1);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('500', $result['error']);
    }

    public function test_store_api_data()
    {
        $apiResponse = [
            'result' => [
                'data' => [
                    [
                        'id' => 6573,
                        'position' => 0,
                        'appearance' => 0,
                        'country' => 'TZ',
                        'country_name' => [
                            'de' => 'Tansania',
                            'en' => 'Tanzania'
                        ],
                        'lang' => 'de',
                        'tagtype' => 4,
                        'tagtext' => null,
                        'tagdate' => '2025-08-22',
                        'header' => 'Test Header',
                        'content' => 'Test Content',
                        'created_at' => '2025-08-22T08:11:49.000000Z',
                        'archive' => 0,
                        'active' => true,
                        'language_content' => 'German',
                        'language_code' => 'de'
                    ]
                ]
            ],
            'requestid' => 'test-request-id',
            'responsetime' => 100
        ];

        $stored = $this->service->storeApiData($apiResponse);

        $this->assertEquals(1, $stored);
        $this->assertDatabaseHas('infosystem_entries', [
            'api_id' => 6573,
            'country_code' => 'TZ',
            'header' => 'Test Header',
            'content' => 'Test Content',
            'request_id' => 'test-request-id'
        ]);
    }

    public function test_fetch_and_store_integration()
    {
        // Mock successful API response
        Http::fake([
            'https://api.passolution.eu/api/v2/infosystem/general*' => Http::response([
                'code' => 0,
                'message' => 'success',
                'result' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'data' => [
                        [
                            'id' => 6574,
                            'position' => 0,
                            'appearance' => 0,
                            'country' => 'FR',
                            'country_name' => [
                                'de' => 'Frankreich',
                                'en' => 'France'
                            ],
                            'lang' => 'de',
                            'tagtype' => 4,
                            'tagtext' => null,
                            'tagdate' => '2025-08-22',
                            'header' => 'France Test',
                            'content' => 'France Content',
                            'created_at' => '2025-08-22T08:11:49.000000Z',
                            'archive' => 0,
                            'active' => true,
                            'language_content' => 'German',
                            'language_code' => 'de'
                        ]
                    ],
                    'total' => 1
                ],
                'requestid' => 'integration-test-id',
                'responsetime' => 150
            ], 200)
        ]);

        $result = $this->service->fetchAndStore('de', 1);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['stored']);
        $this->assertEquals(1, $result['total_available']);
        $this->assertEquals('integration-test-id', $result['request_id']);
        $this->assertEquals(150, $result['response_time']);

        // Verify data was stored
        $this->assertDatabaseHas('infosystem_entries', [
            'api_id' => 6574,
            'country_code' => 'FR',
            'header' => 'France Test'
        ]);
    }

    public function test_get_latest_entries()
    {
        // Create test entries
        InfosystemEntry::factory()->count(5)->create([
            'lang' => 'de',
            'active' => true,
            'archive' => false
        ]);

        InfosystemEntry::factory()->count(3)->create([
            'lang' => 'en',
            'active' => true,
            'archive' => false
        ]);

        $entries = $this->service->getLatestEntries(10, 'de');

        $this->assertCount(5, $entries);
        $entries->each(function ($entry) {
            $this->assertEquals('de', $entry->lang);
            $this->assertTrue($entry->active);
            $this->assertFalse($entry->archive);
        });
    }

    public function test_get_entries_by_country()
    {
        InfosystemEntry::factory()->count(3)->create([
            'country_code' => 'DE',
            'lang' => 'de',
            'active' => true,
            'archive' => false
        ]);

        InfosystemEntry::factory()->count(2)->create([
            'country_code' => 'FR',
            'lang' => 'de',
            'active' => true,
            'archive' => false
        ]);

        $entries = $this->service->getEntriesByCountry('DE', 'de', 10);

        $this->assertCount(3, $entries);
        $entries->each(function ($entry) {
            $this->assertEquals('DE', $entry->country_code);
            $this->assertEquals('de', $entry->lang);
        });
    }

    public function test_get_statistics()
    {
        // Clear any existing entries first
        InfosystemEntry::truncate();
        
        // Create test data with specific tagdates to avoid random factory dates
        InfosystemEntry::factory()->count(10)->create([
            'active' => true,
            'tagdate' => '2025-08-01'  // Old date, not today or this week
        ]);
        InfosystemEntry::factory()->count(2)->create([
            'active' => false,
            'tagdate' => '2025-08-01'
        ]);
        
        // Entries for today
        InfosystemEntry::factory()->count(3)->create([
            'tagdate' => today()->format('Y-m-d'),
            'active' => true
        ]);
        
        // Entries for 3 days ago (this week)
        InfosystemEntry::factory()->count(5)->create([
            'tagdate' => now()->subDays(3)->format('Y-m-d'),
            'active' => true
        ]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(20, $stats['total_entries']); // 10 + 2 + 3 + 5
        $this->assertEquals(18, $stats['active_entries']); // 10 + 3 + 5 (active ones)
        $this->assertEquals(3, $stats['entries_today']);
        $this->assertEquals(8, $stats['entries_this_week']); // 3 today + 5 from 3 days ago
    }
}