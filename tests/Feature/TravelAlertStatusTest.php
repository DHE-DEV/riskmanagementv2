<?php

use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomEvent;
use App\Models\TravelAlertStatus;
use Illuminate\Support\Facades\Cache;

/**
 * Travel Alert: Ereignisse und Reisen als erledigt bzw. offen markieren – je Kunde,
 * einzeln oder mehrere, mit Status-Filter für die Ereignisse.
 */
function statusEvent(Country $country): CustomEvent
{
    return CustomEvent::create([
        'title' => 'Streik',
        'popup_content' => 'Text',
        'event_type' => 'other',
        'priority' => 'high',
        'start_date' => now()->subDay(),
        'is_active' => true,
        'review_status' => 'approved',
        'country_id' => $country->id,
    ]);
}

beforeEach(function () {
    config(['app.event_auto_translate' => false]);
    $this->customer = Customer::factory()->create();
    $this->actingAs($this->customer, 'customer');
});

it('markiert Ereignisse und API-Reisen als erledigt und öffnet sie wieder', function () {
    $italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);
    $event = statusEvent($italy);

    $this->postJson(route('risk-overview.status'), ['done' => true, 'event_ids' => [$event->id, 999999], 'trip_ids' => ['api-AB12-CD34', '../evil']])
        ->assertOk()
        ->assertJsonStructure(['events' => [(string) $event->id], 'trips' => ['api-AB12-CD34']])
        ->assertJsonMissingPath('events.999999')
        ->assertJsonMissingPath('trips.../evil');

    expect(TravelAlertStatus::where('customer_id', $this->customer->id)->pluck('subject_key')->sort()->values()->all())
        ->toBe([(string) $event->id, 'api-AB12-CD34']);

    $this->postJson(route('risk-overview.status'), ['done' => false, 'event_ids' => [$event->id], 'trip_ids' => ['api-AB12-CD34']])
        ->assertOk()
        ->assertJsonPath('trips.api-AB12-CD34', null);

    expect(TravelAlertStatus::where('customer_id', $this->customer->id)->count())->toBe(0);
});

it('verlangt die Angabe, ob erledigt oder offen', function () {
    $this->postJson(route('risk-overview.status'), ['event_ids' => [1]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('done');
});

it('filtert Ereignisse nach Status und berücksichtigt Änderungen sofort', function () {
    $italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);
    $spain = Country::factory()->create(['iso_code' => 'ES', 'iso3_code' => 'ESP']);
    $italian = statusEvent($italy);
    statusEvent($spain);
    Cache::flush();

    $openBefore = $this->getJson(route('risk-overview.data', ['status' => 'open', 'days' => 30]));
    $this->postJson(route('risk-overview.status'), ['done' => true, 'event_ids' => [$italian->id]])->assertOk();
    $openAfter = $this->getJson(route('risk-overview.data', ['status' => 'open', 'days' => 30]));
    $done = $this->getJson(route('risk-overview.data', ['status' => 'done', 'days' => 30]));

    expect(collect($openBefore->json('data.countries'))->pluck('country.code')->sort()->values()->all())->toBe(['ES', 'IT'])
        ->and(collect($openAfter->json('data.countries'))->pluck('country.code')->all())->toBe(['ES'])
        ->and(collect($done->json('data.countries'))->pluck('country.code')->all())->toBe(['IT']);
});

it('liefert den Status an den Ereignissen der Länderdetails – nur den des eigenen Kunden', function () {
    $italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);
    $mine = statusEvent($italy);
    $othersOnly = statusEvent($italy);
    TravelAlertStatus::create(['customer_id' => $this->customer->id, 'subject_type' => 'event', 'subject_key' => (string) $mine->id, 'done_at' => now()]);
    TravelAlertStatus::create(['customer_id' => Customer::factory()->create()->id, 'subject_type' => 'event', 'subject_key' => (string) $othersOnly->id, 'done_at' => now()]);

    $events = collect($this->getJson(route('risk-overview.country', ['countryCode' => 'IT', 'days' => 30]))->assertOk()->json('data.events'))->keyBy('id');

    expect($events[$mine->id]['done_at'])->not->toBeNull()
        ->and($events[$othersOnly->id]['done_at'])->toBeNull();
});
