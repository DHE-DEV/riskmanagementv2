<?php

use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomEvent;
use App\Models\TravelDetail\TdTrip;
use App\Services\NotificationRuleService;
use App\Services\RiskOverviewService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Eine Reise gilt nur dann als betroffen, wenn sich ihr gesamter Reisezeitraum
 * mit dem Ereignis ueberschneidet – das Land allein reicht nicht. Das gilt
 * auch fuer Kreuzfahrten, deren Laender aus den Hafenanlaeufen stammen.
 */
function makePeriodEvent(Country $country, string $start, string $end): CustomEvent
{
    return CustomEvent::create([
        'title' => 'Unwetter',
        'popup_content' => 'Testereignis',
        'event_type' => 'other',
        'priority' => 'high',
        'country_id' => $country->id,
        'start_date' => $start,
        'end_date' => $end,
        'is_active' => true,
        'archived' => false,
        'review_status' => 'approved',
    ]);
}

function affectedTripsFor(NotificationRuleService $service, Customer $customer, CustomEvent $event)
{
    $method = new ReflectionMethod($service, 'findAffectedTrips');
    $method->setAccessible(true);

    return $method->invoke($service, $customer->id, ['IT'], $event);
}

it('meldet eine PDS-Kreuzfahrt nur fuer Ereignisse in ihrem Reisezeitraum', function () {
    config(['services.passolution.internal_token' => 'test-token']);

    // PDS liefert die Kreuzfahrt unabhaengig vom angefragten Zeitraum.
    Http::fake([
        '*/__internal/account/travel-details*' => Http::response(['data' => [[
            'tid' => 'cruise-1',
            'trip_name' => 'AIDAblu Adria',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-12',
            'destinations' => [],
            'cruise_compass' => true,
            'cruise' => ['port_calls' => [
                ['day' => 3, 'port' => ['country' => ['code' => 'IT']]],
                ['day' => 5, 'port' => ['country' => ['code' => 'GR']]],
            ]],
        ]]]),
    ]);

    $italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);
    $customer = Customer::factory()->create(['pds_account_id' => 4711]);

    $duringCruise = makePeriodEvent($italy, '2026-10-06 00:00:00', '2026-10-08 23:59:00');
    $afterCruise = makePeriodEvent($italy, '2026-10-20 00:00:00', '2026-10-22 23:59:00');

    // Ein Lauf verarbeitet mehrere Ereignisse mit derselben Service-Instanz.
    $service = app(NotificationRuleService::class);

    expect(affectedTripsFor($service, $customer, $duringCruise))->toHaveCount(1)
        ->and(affectedTripsFor($service, $customer, $afterCruise))->toHaveCount(0);
});

it('zeigt in der Laenderansicht nur Reisen, die sich mit einem Ereignis ueberschneiden', function () {
    Cache::flush();

    $italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);
    $customer = Customer::factory()->create();

    makePeriodEvent($italy, now()->addDays(2)->toDateTimeString(), now()->addDays(4)->toDateTimeString());

    $cruise = fn (string $tid, int $startInDays, int $endInDays) => TdTrip::create([
        'customer_id' => $customer->id,
        'external_trip_id' => $tid,
        'pds_tid' => $tid,
        'provider_id' => 'pds',
        'provider_sent_at' => now(),
        'is_cruise' => true,
        'status' => 'active',
        'computed_start_at' => now()->addDays($startInDays)->startOfDay(),
        'computed_end_at' => now()->addDays($endInDays)->startOfDay(),
        'countries_visited' => ['IT'],
    ]);

    $cruise('waehrend-ereignis', 1, 8);
    $cruise('nach-ereignis', 15, 22);

    $details = app(RiskOverviewService::class)->getCountryRiskDetails($customer->id, 'IT', 30);

    expect(array_column($details['travelers'], 'trip_id'))->toBe(['waehrend-ereignis'])
        ->and($details['summary']['total_travelers'])->toBe(1);
});
