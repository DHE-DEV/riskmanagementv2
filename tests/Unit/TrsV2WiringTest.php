<?php

use App\Models\Customer;
use App\Services\TrsV2DataService;
use App\Services\TrsV2SearchService;
use App\Services\TrsV2SubscriptionService;
use Illuminate\Support\Facades\Http;

/**
 * Wiring tests for the Travel Requirements Service v2: they verify the exact
 * pds-api requests built per tab and the normalization of responses, using
 * Http::fake (no real API / DB). Run under Unit so Pest does not apply
 * RefreshDatabase (the project DB is not reachable from CI/CLI here).
 */
uses(Tests\TestCase::class);

beforeEach(function () {
    // Avoid the database cache store while exercising the lookup service.
    config(['cache.default' => 'array']);
    app()->setLocale('de');
});

function trsCustomer(): Customer
{
    return new Customer([
        'pds_api_token' => 'TESTTOKEN',
        'pds_api_token_expires_at' => now()->addDay(),
    ]);
}

/** Faked public lookup lists used for localizing destination/nationality titles. */
function fakeLookups(): array
{
    return [
        '*/countries*' => Http::response(['result' => ['data' => [
            ['code' => 'FR', 'name' => 'Frankreich', 'name_en' => 'France', 'name_nl' => 'Frankrijk', 'active' => 1],
            ['code' => 'DE', 'name' => 'Deutschland', 'name_en' => 'Germany', 'name_nl' => 'Duitsland', 'active' => 1],
        ], 'last_page' => 1]], 200),
        '*/nationalities*' => Http::response(['result' => ['data' => [
            ['code' => 'DE', 'name' => 'Germany', 'name_de' => 'Deutschland', 'name_en' => 'Germany', 'name_nl' => 'Duitsland', 'active' => 1],
        ], 'last_page' => 1]], 200),
    ];
}

it('builds the ptd content/all request and groups the response', function () {
    Http::fake(array_merge(fakeLookups(), [
        '*/content/all/html*' => Http::response([
            'requestId' => 'req-1',
            'records' => [[
                'destination' => 'FR', 'destination_type' => 'travel', 'nationality' => 'DE',
                'title' => 'FR/DE', 'destination_flag' => null, 'nationality_flag' => null,
                'traveller' => ['type' => null],
                'entry' => ['title' => 'x', 'content' => '<p>Einreise</p>', 'updated_at' => '2026-05-20'],
                'visa' => ['title' => 'x', 'content' => '<p>Visa</p>', 'updated_at' => null],
                'health' => ['title' => 'x', 'content' => ''],
            ]],
        ], 200),
    ]));

    $service = app(TrsV2SearchService::class);
    $result = $service->search(trsCustomer(), 'ptd', [
        'destinations' => ['FR'],
        'transit' => ['DE'],
        'nationalities' => ['DE'],
        'language' => 'de',
        'showCountryInfo' => true,
        'withMinors' => true,
        'modes' => ['air' => true, 'land' => false, 'sea' => true],
        'tourOperators' => ['TUI'],
    ]);

    expect($result['ok'])->toBeTrue();
    expect($result['request_id'])->toBe('req-1');
    expect($result['groups'])->toHaveCount(1);

    $group = $result['groups'][0];
    expect($group['destination'])->toBe('FR');
    expect($group['title'])->toBe('Frankreich');           // localized (de)
    $record = $group['records'][0];
    expect($record['nationality_title'])->toBe('Deutschland');
    // entry + visa have content, health is empty -> excluded; order preserved.
    expect(collect($record['sections'])->pluck('key')->all())->toBe(['entry', 'visa']);

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'content/all/html')) {
            return false;
        }
        $body = $request->data();

        return $request->method() === 'POST'
            && $body['lang'] === 'de'
            && $body['nat'] === 'DE'
            && $body['include_country_info'] === true
            && $body['travel']['modes'] === ['air', 'sea']
            && $body['travel']['with_minors'] === true
            && $body['tour_operators'] === ['TUI']
            && $body['destinations'] === [
                ['destination' => 'FR'],
                ['destination' => 'DE', 'type' => 'transit'],
            ];
    });
});

it('builds the business request with type=business, trip and travellers', function () {
    Http::fake(array_merge(fakeLookups(), [
        '*/content/all/html*' => Http::response(['records' => []], 200),
    ]));

    app(TrsV2SearchService::class)->search(trsCustomer(), 'business', [
        'destinations' => ['FR'],
        'language' => 'en',
        'tripStart' => '2026-06-01',
        'tripEnd' => '2026-06-10',
        'travellers' => [
            ['nationality' => 'DE', 'residence' => 'DE', 'secondary' => '', 'purpose' => 'MEETINGS_WITH_OR_FOR_A_CLIENT'],
        ],
    ]);

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'content/all/html')) {
            return false;
        }
        $body = $request->data();

        return str_contains($request->url(), 'type=business')
            && str_contains($request->url(), 'lang=en')
            && $body['type'] === 'business'
            && $body['nat'] === 'DE'
            && $body['trip'] === ['start_date' => '2026-06-01', 'end_date' => '2026-06-10']
            && $body['travellers'][0] === [
                'nationality' => 'DE',
                'residence_country' => 'DE',
                'purpose' => 'MEETINGS_WITH_OR_FOR_A_CLIENT',
            ]; // empty 'secondary' omitted
    });
});

it('builds the cruise request from the resolved compass id and sends no destinations', function () {
    Http::fake(array_merge(fakeLookups(), [
        '*/content/all/html*' => Http::response(['records' => []], 200),
    ]));

    app(TrsV2SearchService::class)->search(trsCustomer(), 'cruise', [
        'nationalities' => ['DE'],
        'language' => 'de',
        'cruise_compass_cruise_id' => 'CC123',
    ]);

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'content/all/html')) {
            return false;
        }
        $body = $request->data();

        return $body['cruise_compass_cruise_id'] === 'CC123'
            && $body['nat'] === 'DE'
            && ! array_key_exists('destinations', $body);
    });
});

it('fails cleanly when a cruise search has no compass id', function () {
    Http::fake(fakeLookups());

    $result = app(TrsV2SearchService::class)->search(trsCustomer(), 'cruise', [
        'nationalities' => ['DE'], 'language' => 'de',
    ]);

    expect($result['ok'])->toBeFalse();
    expect($result['error'])->toBe('cruise_not_available');
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'content/all'));
});

it('saves an Abo to the __internal endpoint with country codes mapped to ids', function () {
    Http::fake([
        // public /countries has no id -> mapping falls back to /destinations
        '*/destinations*' => Http::response(['result' => ['data' => [
            ['id' => 73, 'code' => 'FR'],
            ['id' => 46, 'code' => 'DE'],
        ]]], 200),
        '*/countries*' => Http::response(['result' => ['data' => []]], 200),
        '*/__internal/account/subscriptions/general-notification/save' => Http::response(['id' => 9], 200),
    ]);

    $result = app(TrsV2SubscriptionService::class)->save(
        trsCustomer(), 'Mein Abo', ['FR', 'DE'], ['kunde@example.com']
    );

    expect($result['ok'])->toBeTrue();

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '__internal/account/subscriptions/general-notification/save')) {
            return false;
        }
        $body = $request->data();

        return $body['name'] === 'Mein Abo'
            && $body['countries'] === [73, 46]
            && $body['emails'] === ['kunde@example.com'];
    });
});

it('rejects an Abo name shorter than 3 chars without calling the API', function () {
    Http::fake(['*' => Http::response([], 200)]);

    $result = app(TrsV2SubscriptionService::class)->save(trsCustomer(), 'ab', ['FR'], ['x@y.de']);

    expect($result['ok'])->toBeFalse();
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'general-notification/save'));
});

it('fetches cruise lookups from the __internal cruise endpoints', function () {
    Http::fake([
        '*/__internal/cruise/lines' => Http::response(['data' => [['id' => 1, 'name' => 'AIDA']]], 200),
        '*/__internal/cruise/ships' => Http::response(['data' => [['id' => 11, 'name' => 'AIDAnova']]], 200),
    ]);

    $data = app(TrsV2DataService::class);
    expect($data->cruiseLines(trsCustomer()))->toBe([['id' => 1, 'name' => 'AIDA']]);
    expect($data->cruiseShips(trsCustomer(), 1))->toBe([['id' => 11, 'name' => 'AIDAnova']]);

    Http::assertSent(fn ($request) => str_contains($request->url(), '__internal/cruise/ships')
        && $request->data()['line_id'] === 1);
});
