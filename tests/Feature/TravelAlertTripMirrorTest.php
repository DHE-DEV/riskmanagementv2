<?php

use App\Models\Customer;
use App\Models\TravelAlertPdsAccount;
use App\Models\TravelAlertPdsTrip;
use App\Models\TravelDetail\PdsGlobalSyncState;
use App\Services\RiskOverviewService;
use App\Services\TravelDetail\TravelAlertTripMirror;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Lokaler Spiegel der pds-Reisen für den Travel Alert: Vollabruf, Delta-Feed, Lesepfad, Aufräumen.
 */
function pdsTrip(string $tid, array $overrides = []): array
{
    return $overrides + [
        'tid' => $tid,
        'trip_name' => 'Reise '.$tid,
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(9)->toDateString(),
        'destinations' => ['IT'],
        'nationalities' => ['DE'],
        'cover_media' => 'wird nicht gespiegelt',
    ];
}

/**
 * @param  array<int, array<int, array<string, mixed>>>  $pages  Seiten des Kontoabrufs (Index 1 = Seite 1); null = Seite scheitert
 */
function fakeAccountPages(array $pages): void
{
    Http::fake(['*/__internal/account/travel-details*' => function (Request $request) use ($pages) {
        $page = (int) ($request['page'] ?? 1);

        return $pages[$page] === null
            ? Http::response(['message' => 'Fehler'], 500)
            : Http::response(['data' => $pages[$page], 'meta' => ['last_page' => count($pages)]]);
    }]);
}

beforeEach(function () {
    Cache::flush();
    config(['services.passolution.internal_token' => 'test-token']);
});

it('lädt alle Reisen eines Kontos schlank in den Spiegel und entfernt nicht mehr gelieferte', function () {
    TravelAlertPdsTrip::create(['pds_account_id' => 77, 'tid' => 'GELOESCHT', 'payload' => [], 'end_date' => now()->addDay()]);
    fakeAccountPages([1 => [pdsTrip('A1'), pdsTrip('A2')], 2 => [pdsTrip('A3')]]);

    $synced = app(TravelAlertTripMirror::class)->syncAccount(77);

    expect($synced)->toBeTrue()
        ->and(TravelAlertPdsTrip::where('pds_account_id', 77)->pluck('tid')->sort()->values()->all())->toBe(['A1', 'A2', 'A3'])
        ->and(TravelAlertPdsTrip::where('tid', 'A1')->first()->payload)->not->toHaveKey('cover_media')
        ->and(TravelAlertPdsAccount::find(77))->full_synced_at->not->toBeNull()->trips_count->toBe(3);
});

it('behält den Spiegel, wenn beim Vollabruf eine Seite fehlt', function () {
    TravelAlertPdsTrip::create(['pds_account_id' => 77, 'tid' => 'ALT', 'payload' => [], 'end_date' => now()->addDay()]);
    fakeAccountPages([1 => [pdsTrip('A1')], 2 => null]);

    $synced = app(TravelAlertTripMirror::class)->syncAccount(77);

    expect($synced)->toBeFalse()
        ->and(TravelAlertPdsTrip::pluck('tid')->all())->toBe(['ALT']);
});

it('übernimmt aus dem Delta-Feed nur Änderungen gespiegelter Konten', function () {
    TravelAlertPdsAccount::create(['pds_account_id' => 77, 'last_requested_at' => now(), 'full_synced_at' => now(), 'synced_from' => now()]);
    TravelAlertPdsTrip::create(['pds_account_id' => 77, 'tid' => 'A1', 'payload' => ['trip_name' => 'alt'], 'end_date' => now()->addDay()]);
    Http::fake(['*/__internal/travel-details/changes*' => Http::response([
        'data' => [
            pdsTrip('A1', ['trip_name' => 'neu', 'account_id' => 77, 'last_change_at' => '2026-11-19 10:00:00']),
            pdsTrip('FREMD', ['account_id' => 99]),
        ],
        'meta' => ['has_more' => false, 'next_cursor' => ['last_change_at' => '2026-11-19 10:00:00', 'id' => 4711]],
    ])]);

    app(TravelAlertTripMirror::class)->syncChanges();

    expect(TravelAlertPdsTrip::where('tid', 'A1')->first()->payload['trip_name'])->toBe('neu')
        ->and(TravelAlertPdsTrip::where('tid', 'FREMD')->exists())->toBeFalse()
        ->and(PdsGlobalSyncState::forKey(TravelAlertTripMirror::SYNC_STATE_KEY)->cursor_id)->toBe(4711);
    Http::assertSent(fn (Request $request) => $request['account_ids'] === [77]);
});

it('liest im Travel Alert gespiegelte Konten lokal statt bei pds', function () {
    $customer = Customer::factory()->create(['pds_account_id' => 77]);
    TravelAlertPdsAccount::create(['pds_account_id' => 77, 'last_requested_at' => now(), 'full_synced_at' => now(), 'synced_from' => now()]);
    TravelAlertPdsTrip::create([
        'pds_account_id' => 77, 'tid' => 'A1', 'payload' => TravelAlertTripMirror::slim(pdsTrip('A1')),
        'start_date' => now()->addDays(2), 'end_date' => now()->addDays(9),
    ]);
    Http::fake();
    $service = app(RiskOverviewService::class);
    $read = new ReflectionMethod($service, 'mirroredTravelDetails');

    $inWindow = $read->invoke($service, $customer, now()->toDateString(), now()->addDays(30)->toDateString());
    $beforeMirror = $read->invoke($service, $customer, now()->subDay()->toDateString(), now()->addDays(30)->toDateString());

    expect(collect($inWindow)->pluck('tid')->all())->toBe(['A1'])
        ->and($beforeMirror)->toBeNull();
    Http::assertNothingSent();
});

it('merkt Konten beim Aufruf vor und entfernt sie nach längerer Nichtnutzung', function () {
    $customer = Customer::factory()->create(['pds_account_id' => 88]);
    $service = app(RiskOverviewService::class);
    (new ReflectionMethod($service, 'mirroredTravelDetails'))->invoke($service, $customer, now()->toDateString(), now()->addDays(30)->toDateString());
    TravelAlertPdsAccount::create(['pds_account_id' => 77, 'last_requested_at' => now()->subDays(TravelAlertTripMirror::INACTIVE_AFTER_DAYS + 1)]);
    TravelAlertPdsTrip::create(['pds_account_id' => 77, 'tid' => 'A1', 'payload' => []]);

    app(TravelAlertTripMirror::class)->pruneInactive();

    expect(TravelAlertPdsAccount::pluck('pds_account_id')->all())->toBe([88])
        ->and(TravelAlertPdsTrip::count())->toBe(0);
});
