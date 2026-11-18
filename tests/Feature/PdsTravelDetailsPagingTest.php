<?php

use App\Models\Customer;
use App\Services\PassolutionApiService;
use App\Services\RiskOverviewService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * pds-Reisen über __internal/account/travel-details: alle Seiten statt nur der ersten 1000,
 * und im Travel Alert einmal je Konto und Zeitraum abgefragt statt für jede Ansicht neu.
 */
function fakePdsPages(int $total, int $perPage = 1000): void
{
    config(['services.passolution.internal_token' => 'test-token']);

    Http::fake(['*/__internal/account/travel-details*' => function (Request $request) use ($total, $perPage) {
        $page = (int) ($request['page'] ?? 1);
        $from = ($page - 1) * $perPage;
        $rows = array_map(fn (int $i) => [
            'tid' => 'trip-'.$i,
            'trip_name' => 'Reise '.$i,
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-30',
            'destinations' => ['IT'],
            'destinations_list' => [['code' => 'IT', 'note' => str_repeat('x', 50)]],
            'nationalities' => ['DE'],
            'cover_media' => 'nicht benötigt',
        ], range($from, min($total, $from + $perPage) - 1));

        return Http::response(['data' => $rows, 'meta' => ['total' => $total, 'last_page' => (int) ceil($total / $perPage)]]);
    }]);
}

beforeEach(fn () => Cache::flush());

it('lädt alle Seiten der Reisen statt nur der ersten', function () {
    fakePdsPages(2500);

    $rows = app(PassolutionApiService::class)->fetchTravelDetailsByEmail('kunde@example.com', '2026-11-19', '2026-12-19');

    expect($rows)->toHaveCount(2500)
        ->and(collect($rows)->pluck('tid')->unique())->toHaveCount(2500);
    Http::assertSentCount(3);
});

it('lädt auch für die Benachrichtigungen alle Seiten über die Konto-ID', function () {
    fakePdsPages(1500);

    $rows = app(PassolutionApiService::class)->fetchTravelDetailsByAccountId(4711, '2026-11-19', '2026-12-19');

    expect($rows)->toHaveCount(1500);
});

it('hält sich an die Seitengrenze', function () {
    fakePdsPages((PassolutionApiService::TRAVEL_DETAILS_MAX_PAGES + 5) * 10, 10);

    $rows = app(PassolutionApiService::class)->fetchTravelDetailsByEmail('kunde@example.com', '2026-11-19', '2026-12-19');

    expect($rows)->toHaveCount(PassolutionApiService::TRAVEL_DETAILS_MAX_PAGES * 10);
});

it('fragt im Travel Alert die pds-API je Konto und Zeitraum nur einmal ab', function () {
    fakePdsPages(1200);
    $customer = Customer::factory()->create();
    $service = app(RiskOverviewService::class);
    $fetch = new ReflectionMethod($service, 'fetchTravelAlertTravelDetails');

    $first = $fetch->invoke($service, $customer->email, '2026-11-19', '2026-12-19');
    $second = $fetch->invoke($service, $customer->email, '2026-11-19', '2026-12-19');

    expect($first)->toHaveCount(1200)
        ->and($second)->toBe($first)
        ->and($first[0])->not->toHaveKey('cover_media')
        ->and($first[0]['destinations_list'])->toBe([['code' => 'IT']]);
    Http::assertSentCount(2);
});
