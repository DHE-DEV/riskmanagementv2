<?php

use App\Livewire\AdminV2\Events\Editor;
use App\Livewire\AdminV2\Events\RuleCheck;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use App\Models\TravelDetail\TdTrip;
use App\Models\User;
use App\Services\NotificationRuleService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * "Regeln der Kunden pruefen": Vorschau, welche Benachrichtigungsregeln bei
 * einem Ereignis greifen wuerden – ohne Versand und ohne Protokoll.
 */
function ruleFor(Customer $customer, string $source, string $name, array $attributes = [], ?string $recipient = 'empfaenger@example.com'): NotificationRule
{
    $rule = NotificationRule::create(array_merge([
        'customer_id' => $customer->id,
        'source' => $source,
        'name' => $name,
        'is_active' => true,
    ], $attributes));

    if ($recipient) {
        $rule->recipients()->create(['email' => $recipient, 'recipient_type' => 'to']);
    }

    return $rule;
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));

    $this->italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);
    $this->spain = Country::factory()->create(['iso_code' => 'ES', 'iso3_code' => 'ESP']);
    $strike = EventType::create(['code' => 'strike', 'name' => 'Streik', 'icon' => 'fa-bolt', 'is_active' => true, 'sort_order' => 1]);

    $this->event = CustomEvent::create([
        'title' => 'Streik in Rom', 'popup_content' => 'Text', 'event_type' => 'other', 'priority' => 'high',
        'start_date' => '2026-10-09 00:00:00', 'end_date' => '2026-10-09 23:59:00',
        'is_active' => true, 'archived' => false, 'review_status' => 'approved',
    ]);
    $this->event->eventTypes()->attach($strike->id);
    $this->event->countries()->attach($this->italy->id, ['use_default_coordinates' => true]);

    $this->alpha = Customer::factory()->create(['name' => 'Alpha Reisen', 'company_name' => 'Alpha Reisen', 'pds_account_id' => 1001, 'notifications_enabled' => true]);
    $this->beta = Customer::factory()->create(['name' => 'Beta Tours', 'company_name' => 'Beta Tours', 'pds_account_id' => 1002, 'notifications_enabled' => true]);
});

it('zeigt je Kunde, welche Regeln zutreffen und woran es sonst liegt', function () {
    Mail::fake();

    // Global Travel Monitor
    ruleFor($this->alpha, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Alles Hohe', ['risk_levels' => ['high']]);
    ruleFor($this->alpha, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Nur Spanien', ['country_ids' => [$this->spain->id]]);
    ruleFor($this->beta, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Nur Niedrig', ['risk_levels' => ['low']]);
    ruleFor($this->beta, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Ausgeschaltet', ['is_active' => false]);

    // Travel Alert: Alpha hat eine Reise nach Italien im Zeitraum, Beta nicht.
    ruleFor($this->alpha, NotificationRule::SOURCE_TRAVEL_ALERT, 'Meine Reisenden');
    ruleFor($this->beta, NotificationRule::SOURCE_TRAVEL_ALERT, 'Reisen Beta');

    TdTrip::create([
        'customer_id' => $this->alpha->id, 'external_trip_id' => 'rom-1', 'pds_tid' => 'rom-1', 'provider_id' => 'pds',
        'provider_sent_at' => now(), 'trip_name' => 'Städtereise Rom', 'status' => 'active',
        'computed_start_at' => '2026-10-07 00:00:00', 'computed_end_at' => '2026-10-12 00:00:00', 'countries_visited' => ['IT'],
    ]);

    $page = Livewire::test(RuleCheck::class, ['event' => $this->event->id])
        ->assertSee('Die Regeln werden ausgewertet')
        ->call('evaluate')
        // Standard: nur zutreffende Regeln
        ->assertSee('Alles Hohe')
        ->assertSee('Meine Reisenden')
        ->assertSee('Städtereise Rom')
        ->assertDontSee('Nur Spanien')
        ->assertDontSee('Reisen Beta')
        ->assertSee('1 von 4 Regeln treffen zu')
        ->assertSee('1 von 2 Regeln treffen zu')
        // Alle Regeln: mit Begruendung
        ->set('show', 'all')
        ->assertSee('Land passt nicht')
        ->assertSee('Priorität passt nicht')
        ->assertSee('Regel ist deaktiviert')
        ->assertSee('Keine betroffenen Reisen im Zeitraum');

    $byRule = collect($page->get('results'))->keyBy('rule_name');

    expect($byRule['Alles Hohe']['would_notify'])->toBeTrue()
        ->and($byRule['Nur Spanien']['would_notify'])->toBeFalse()
        ->and($byRule['Meine Reisenden']['trips'])->toHaveCount(1)
        ->and($byRule['Reisen Beta']['reasons'])->toBe(['Keine betroffenen Reisen im Zeitraum']);

    // Reine Vorschau: keine Mail, kein Protokoll.
    Mail::assertNothingSent();
    expect(NotificationLog::count())->toBe(0);
});

it('beschraenkt die Vorschau auf eine Kundennummer', function () {
    ruleFor($this->alpha, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Regel Alpha');
    ruleFor($this->beta, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Regel Beta');

    Livewire::withQueryParams(['customer' => '1002'])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertSee('Regel Beta')
        ->assertDontSee('Regel Alpha')
        ->assertSee('Beta Tours')
        // Wechsel auf einen anderen Kunden bzw. zurueck auf alle
        ->set('customerInput', '1001')
        ->call('applyCustomer')
        ->assertSee('Regel Alpha')
        ->assertDontSee('Regel Beta')
        ->set('customerInput', '999999')
        ->call('applyCustomer')
        ->assertHasErrors('customerInput')
        ->call('showAllCustomers')
        ->assertSee('Regel Alpha')
        ->assertSee('Regel Beta');
});

it('fragt im Ereignis-Formular erst nach allen Kunden oder einer Kundennummer', function () {
    Livewire::test(Editor::class, ['event' => $this->event->id])
        ->assertSee('Regeln der Kunden prüfen')
        ->call('openRuleCheck')
        ->assertRedirect(route('adminv2.events.rules', $this->event->id));

    Livewire::test(Editor::class, ['event' => $this->event->id])
        ->set('ruleCheckScope', 'one')
        ->call('openRuleCheck')
        ->assertHasErrors('ruleCheckCustomer')
        ->set('ruleCheckCustomer', '424242')
        ->call('openRuleCheck')
        ->assertHasErrors('ruleCheckCustomer')
        ->set('ruleCheckCustomer', '1001')
        ->call('openRuleCheck')
        ->assertRedirect(route('adminv2.events.rules', ['event' => $this->event->id, 'customer' => '1001']));

    $this->get(route('adminv2.events.rules', ['event' => $this->event->id, 'customer' => '1001']))
        ->assertOk()
        ->assertSee('Regeln der Kunden')
        ->assertSee('Alpha Reisen');
});

/**
 * Travel-Detail-Links, wie PDS sie liefert. $withPorts = mit Kreuzfahrt-Daten.
 */
function pdsRows(bool $withPorts): array
{
    $cruise = [
        'tid' => 'AAAA-1111', 'trip_name' => 'Kreuzfahrt Adria', 'reference_id' => 'REF-7',
        'start_date' => '2026-10-05', 'end_date' => '2026-10-15', 'destinations' => [],
        'cruise_compass' => ['cruise_id' => '597-1'],
    ];
    $farCruise = [
        'tid' => 'CCCC-3333', 'trip_name' => 'Kreuzfahrt 2027', 'start_date' => '2027-09-10', 'end_date' => '2027-09-19',
        'destinations' => [], 'cruise_compass' => ['cruise_id' => '597-2'],
    ];

    if ($withPorts) {
        $cruise['cruise'] = ['port_calls' => [['port' => ['country' => ['code' => 'IT']]], ['port' => ['country' => ['code' => 'HR']]]]];
        $farCruise['cruise'] = ['port_calls' => [['port' => ['country' => ['code' => 'IT']]]]];
    }

    return [
        $cruise,
        $farCruise,
        ['tid' => 'BBBB-2222', 'trip_name' => 'Spanien im November', 'start_date' => '2026-11-01', 'end_date' => '2026-11-08', 'destinations' => ['es'], 'cruise_compass' => null],
    ];
}

/**
 * PDS, wie es sich mit einer fehlerhaften Kreuzfahrt verhaelt: Jeder Abruf mit
 * Kreuzfahrt-Daten, dessen Zeitraum die Kreuzfahrt 2027 beruehrt, endet mit 500.
 */
function fakePdsWithBrokenCruise(): void
{
    Http::fake(['*/__internal/account/travel-details*' => function (Request $request) {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        $from = $query['end_date']['>='] ?? '0000-00-00';
        $to = $query['start_date']['<='] ?? '9999-12-31';
        $withPorts = isset($query['__with']);

        $rows = array_values(array_filter(pdsRows($withPorts), fn (array $row) => $row['end_date'] >= $from && $row['start_date'] <= $to));

        if ($withPorts && in_array('CCCC-3333', array_column($rows, 'tid'), true)) {
            return Http::response(['error' => ['message' => 'An unexpected error occurred.']], 500);
        }

        return Http::response(['data' => $rows]);
    }]);
}

it('ruft fuer einen einzelnen Kunden die zukuenftigen Reisen ab und zeigt die betroffenen', function () {
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 09:00:00');
    config(['services.passolution.internal_token' => 'test-token']);
    Mail::fake();

    Http::fake(['*/__internal/account/travel-details*' => Http::response(['data' => pdsRows(true)])]);

    // Lokal gespeichert: eine vergangene und eine betroffene Reise.
    foreach ([['alt-1', 'Rom im Frühjahr', '2026-04-01', '2026-04-08'], ['rom-1', 'Städtereise Rom', '2026-10-07', '2026-10-12']] as [$tid, $name, $from, $to]) {
        TdTrip::create([
            'customer_id' => $this->alpha->id, 'external_trip_id' => $tid, 'pds_tid' => $tid, 'provider_id' => 'pds',
            'provider_sent_at' => now(), 'trip_name' => $name, 'status' => 'active',
            'computed_start_at' => $from.' 00:00:00', 'computed_end_at' => $to.' 00:00:00', 'countries_visited' => ['IT'],
        ]);
    }

    $page = Livewire::withQueryParams(['customer' => '1001'])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertSee('Zukünftige Reisen abrufen')
        ->assertDontSee('Kreuzfahrt Adria')
        ->call('loadTrips')
        ->assertSee('2 von 4 Reisen sind von diesem Ereignis betroffen')
        ->assertSee('Städtereise Rom')
        ->assertSee('Kreuzfahrt Adria')
        ->assertSee('Spanien im November')
        ->assertSee('Zeitraum und Land passen nicht')
        ->assertSee('Zeitraum passt nicht')
        ->assertDontSee('Rom im Frühjahr')
        ->assertSee('/de?tid=AAAA-1111&amp;preview', false);

    $trips = collect($page->get('trips'))->keyBy('tid');

    // Betroffene zuerst; die PDS-Kreuzfahrt zaehlt ueber ihre Haefen.
    expect(array_slice(array_column($page->get('trips'), 'affected'), 0, 3))->toBe([true, true, false])
        ->and($trips['AAAA-1111']['affected'])->toBeTrue()
        ->and($trips['AAAA-1111']['url'])->toBe(rtrim(config('services.passolution.travel_details_link'), '/').'/de?tid=AAAA-1111&preview')
        ->and(collect($trips['AAAA-1111']['countries'])->firstWhere('match', true)['name'])->not->toBeEmpty()
        // Der Versand nimmt lokale Reisen im Zeitraum und fragt PDS dann nicht mehr – das faellt auf.
        ->and($trips['AAAA-1111']['counted'])->toBeFalse()
        ->and($trips['rom-1']['counted'])->toBeTrue()
        ->and($trips['BBBB-2222']['affected'])->toBeFalse()
        ->and($trips['CCCC-3333']['in_period'])->toBeFalse();

    $page->assertSee('Der Versand berücksichtigt diese Reise derzeit nicht.')
        ->set('tripsShow', 'affected')
        ->assertDontSee('Spanien im November')
        // Kundenwechsel verwirft die Liste.
        ->set('customerInput', '1002')
        ->call('applyCustomer')
        ->assertSet('tripsLoaded', false)
        ->assertSee('Zukünftige Reisen abrufen');

    Mail::assertNothingSent();
    expect(NotificationLog::count())->toBe(0);

    \Illuminate\Support\Carbon::setTestNow();
});

it('zeigt die Reisen auch dann, wenn PDS an den Kreuzfahrt-Daten scheitert', function () {
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 09:00:00');
    config(['services.passolution.internal_token' => 'test-token']);

    fakePdsWithBrokenCruise();

    $page = Livewire::withQueryParams(['customer' => '1001'])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->call('loadTrips')
        ->assertSet('tripsFailed', false)
        ->assertSee('1 von 3 Reisen sind von diesem Ereignis betroffen')
        ->assertSee('Die Häfen konnten nicht geladen werden');

    $trips = collect($page->get('trips'))->keyBy('tid');

    expect($trips['AAAA-1111']['affected'])->toBeTrue()
        ->and($trips['AAAA-1111']['counted'])->toBeTrue()
        ->and($trips['AAAA-1111']['ports_missing'])->toBeFalse()
        ->and($trips['CCCC-3333']['ports_missing'])->toBeTrue()
        ->and($trips['BBBB-2222']['ports_missing'])->toBeFalse();

    \Illuminate\Support\Carbon::setTestNow();
});

it('bietet den Abruf der Reisen nur fuer einen einzelnen Kunden an', function () {
    Livewire::test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertDontSee('Zukünftige Reisen des Kunden')
        ->call('loadTrips')
        ->assertSet('tripsLoaded', false);
});

it('versendet den Travel Alert auch dann, wenn PDS an einer anderen Kreuzfahrt scheitert', function () {
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 09:00:00');
    config(['services.passolution.internal_token' => 'test-token']);
    fakePdsWithBrokenCruise();

    // Ereignis ohne Ende: Der Abruf reicht bis zur fehlerhaften Kreuzfahrt 2027.
    $this->event->update(['end_date' => null]);

    $service = app(NotificationRuleService::class);
    $method = new ReflectionMethod($service, 'findAffectedTrips');
    $method->setAccessible(true);

    $trips = $method->invoke($service, $this->alpha->id, ['IT'], $this->event->fresh());

    // Die Adria-Kreuzfahrt wird ueber ihre nachgeladenen Haefen erkannt.
    expect($trips->pluck('pds_tid')->all())->toBe(['AAAA-1111'])
        ->and($service->pdsApiFailed())->toBeFalse()
        // Die fehlerhafte Kreuzfahrt bleibt als Luecke sichtbar.
        ->and($service->pdsPortsMissing())->toBe([$this->alpha->id => ['CCCC-3333']]);

    // Auf der Regel-Seite: Regel trifft zu, die Luecke wird gemeldet.
    ruleFor($this->alpha, NotificationRule::SOURCE_TRAVEL_ALERT, 'Meine Reisenden');

    Livewire::withQueryParams(['customer' => '1001'])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertSee('Kreuzfahrt Adria')
        ->assertSet('pdsFailed', false)
        ->assertSee('PDS konnte zu 1 Kreuzfahrt im Zeitraum keine Häfen liefern.');

    \Illuminate\Support\Carbon::setTestNow();
});

it('versucht es bei einem nicht erreichbaren PDS kein zweites Mal', function () {
    config(['services.passolution.internal_token' => 'test-token']);

    $attempts = 0;

    Http::fake(['*/__internal/account/travel-details*' => function () use (&$attempts) {
        $attempts++;

        throw new \Illuminate\Http\Client\ConnectionException('timeout');
    }]);

    $service = app(NotificationRuleService::class);
    $method = new ReflectionMethod($service, 'findAffectedTrips');
    $method->setAccessible(true);

    expect($method->invoke($service, $this->alpha->id, ['IT'], $this->event))->toHaveCount(0)
        ->and($service->pdsApiFailed())->toBeTrue();

    expect($attempts)->toBe(1);
});

it('sendet die Benachrichtigung von Hand nur fuer eine Regel eines einzelnen Kunden', function () {
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 09:00:00');
    Mail::fake();

    \App\Models\NotificationTemplate::create([
        'source' => NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'name' => 'Standard', 'is_system' => true,
        'subject' => 'Neues Ereignis: {event_title}', 'body_html' => '<p>{event_title} in {country_name}</p>',
    ]);

    $alphaRule = ruleFor($this->alpha, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Alles Hohe', ['risk_levels' => ['high']], 'alpha@example.com');
    $otherAlphaRule = ruleFor($this->alpha, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Nur Spanien', ['country_ids' => [$this->spain->id]], 'spanien@example.com');
    $betaRule = ruleFor($this->beta, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Regel Beta', [], 'beta@example.com');

    // Alle Kunden: kein Senden von Hand.
    Livewire::test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertDontSee('Benachrichtigung jetzt senden')
        ->call('confirmSend', $alphaRule->id)
        ->call('sendRule');

    Mail::assertNothingSent();

    $page = Livewire::withQueryParams(['customer' => '1001'])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertSee('Benachrichtigung jetzt senden');

    // Regel eines anderen Kunden und nicht zutreffende Regel: abgelehnt.
    $page->call('confirmSend', $betaRule->id)->call('sendRule');
    $page->call('confirmSend', $otherAlphaRule->id)->call('sendRule');

    Mail::assertNothingSent();

    $page->call('confirmSend', $alphaRule->id)
        ->assertSee('Benachrichtigung jetzt senden?')
        ->call('sendRule')
        ->assertSee('bereits benachrichtigt am 02.10.2026 09:00')
        ->assertSee('Benachrichtigung erneut senden');

    Mail::assertSent(\App\Mail\RiskEventMail::class, 1);
    Mail::assertSent(\App\Mail\RiskEventMail::class, fn ($mail) => $mail->hasTo('alpha@example.com'));

    expect(NotificationLog::count())->toBe(1)
        ->and(NotificationLog::first()->notification_rule_id)->toBe($alphaRule->id);

    // Direkt noch einmal: Schutz vor Doppelversand.
    $page->call('confirmSend', $alphaRule->id)->call('sendRule');
    Mail::assertSent(\App\Mail\RiskEventMail::class, 1);

    // Spaeter erneut senden – die erste Versendung bleibt im Protokoll.
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 09:05:00');
    $page->call('confirmSend', $alphaRule->id)->call('sendRule');

    Mail::assertSent(\App\Mail\RiskEventMail::class, 2);
    expect(NotificationLog::where('status', 'sent')->count())->toBe(2);

    \Illuminate\Support\Carbon::setTestNow();
});

it('sendet nicht von Hand, solange das Ereignis nicht veroeffentlicht ist', function () {
    Mail::fake();

    $this->event->update(['is_active' => false]);
    $rule = ruleFor($this->alpha, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Alles Hohe', ['risk_levels' => ['high']]);

    Livewire::withQueryParams(['customer' => '1001'])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertSee('Alles Hohe')
        ->assertDontSee('Benachrichtigung jetzt senden')
        ->call('confirmSend', $rule->id)
        ->call('sendRule');

    Mail::assertNothingSent();
    expect(NotificationLog::count())->toBe(0);
});

it('zeigt den Versandverlauf und an jeder Reise, wann sie gemeldet wurde', function () {
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 09:00:00');
    config(['services.passolution.internal_token' => 'test-token']);
    Mail::fake();
    Http::fake(['*/__internal/account/travel-details*' => Http::response(['data' => pdsRows(true)])]);

    \App\Models\NotificationTemplate::create([
        'source' => NotificationRule::SOURCE_TRAVEL_ALERT, 'name' => 'Standard', 'is_system' => true,
        'subject' => 'Travel Alert: {event_title}', 'body_html' => '<p>{affected_trips_count} Reisen</p>{affected_trips}',
    ]);

    $rule = ruleFor($this->alpha, NotificationRule::SOURCE_TRAVEL_ALERT, 'Meine Reisenden', [], 'alpha@example.com');

    // Aeltere Mail aus der Zeit, als nur die Anzahl der Reisen gespeichert wurde.
    NotificationLog::create([
        'notification_rule_id' => $rule->id, 'customer_id' => $this->alpha->id, 'event_id' => $this->event->id,
        'event_type' => CustomEvent::class, 'recipient_email' => 'alpha@example.com', 'subject' => 'Travel Alert: Streik in Rom',
        'status' => 'sent', 'affected_trips_count' => 1,
    ]);
    // Mail eines anderen Kunden: gehoert nicht in den Verlauf dieses Kunden.
    NotificationLog::create([
        'customer_id' => $this->beta->id, 'event_id' => $this->event->id, 'event_type' => CustomEvent::class,
        'recipient_email' => 'beta@example.com', 'subject' => 'Andere Mail', 'status' => 'sent',
    ]);

    \Illuminate\Support\Carbon::setTestNow('2026-10-02 10:30:00');

    $page = Livewire::withQueryParams(['customer' => '1001'])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertSee('Versandverlauf')
        ->assertSee('02.10.2026 09:00')
        ->assertSee('Welche, wurde damals noch nicht gespeichert.')
        ->assertDontSee('beta@example.com')
        ->call('confirmSend', $rule->id)
        ->call('sendRule');

    // Die neue Mail haelt fest, welche Reise sie nannte.
    $log = NotificationLog::orderByDesc('id')->first();

    expect($log->affected_trips)->toEqual([['key' => 'AAAA-1111', 'name' => 'Kreuzfahrt Adria', 'start' => '2026-10-05', 'end' => '2026-10-15']])
        ->and($page->instance()->history['rows'])->toHaveCount(2)
        // Neueste zuerst; umgekehrt auf Wunsch.
        ->and(array_column($page->instance()->history['rows'], 'at'))->toBe(['02.10.2026 10:30', '02.10.2026 09:00'])
        ->and(array_column($page->call('toggleHistoryDirection')->instance()->history['rows'], 'at'))->toBe(['02.10.2026 09:00', '02.10.2026 10:30']);

    $page->call('loadTrips')
        ->assertSee('Gemeldet am 02.10.2026 10:30')
        ->assertSee('Bei 1 älteren Mail wurde noch nicht gespeichert');

    expect($page->instance()->tripMails)->toBe(['AAAA-1111' => ['02.10.2026 10:30']]);

    // Alle Kunden: der Verlauf zeigt auch die Mail des anderen Kunden.
    Livewire::withQueryParams([])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertSee('beta@example.com')
        ->assertSee('3 Mails zu diesem Ereignis');

    \Illuminate\Support\Carbon::setTestNow();
});

it('verlinkt im Versandverlauf die angewendete Regel und zeigt sie zum Nachsehen', function () {
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 09:00:00');

    \App\Models\NotificationTemplate::create([
        'source' => NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'name' => 'Standard GTM', 'is_system' => true,
        'subject' => 'Neues Ereignis: {event_title}', 'body_html' => '<p>{event_title}</p>',
    ]);

    $rule = ruleFor($this->alpha, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Alles Hohe', ['risk_levels' => ['high'], 'country_ids' => [$this->italy->id]], 'alpha@example.com');
    $rule->recipients()->create(['email' => 'kopie@example.com', 'recipient_type' => 'cc']);

    NotificationLog::create([
        'notification_rule_id' => $rule->id, 'customer_id' => $this->alpha->id, 'event_id' => $this->event->id,
        'event_type' => CustomEvent::class, 'recipient_email' => 'alpha@example.com', 'subject' => 'Neues Ereignis: Streik in Rom',
        'status' => 'sent',
    ]);

    $url = route('adminv2.rules.show', $rule);

    // Im Verlauf und an der Regel selbst: Link in einem neuen Tab.
    $page = Livewire::withQueryParams(['customer' => '1001'])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertSee('Angewendete Regel')
        ->assertSee('href="'.$url.'" target="_blank"', false);

    expect($page->instance()->history['rows'][0]['rule_id'])->toBe($rule->id);

    $this->get($url)
        ->assertOk()
        ->assertSee('Alles Hohe')
        ->assertSee('Alpha Reisen')
        ->assertSee('Kundennummer 1001')
        ->assertSee('Global Travel Monitor')
        ->assertSee('Hoch')
        ->assertSee($this->italy->getName('de'))
        ->assertSee('alpha@example.com')
        ->assertSee('kopie@example.com')
        ->assertSee('Standard GTM')
        ->assertSee('1 Mail insgesamt')
        ->assertSee('Streik in Rom')
        ->assertSee(route('adminv2.events.edit', $this->event->id));

    // Mehr als zehn Mails: die Regel-Seite blaettert.
    foreach (range(1, 12) as $number) {
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-10-02 09:00:00')->addMinutes($number));
        NotificationLog::create([
            'notification_rule_id' => $rule->id, 'customer_id' => $this->alpha->id, 'event_id' => $this->event->id,
            'event_type' => CustomEvent::class, 'recipient_email' => 'alpha@example.com', 'subject' => 'Weitere Mail '.$number, 'status' => 'sent',
        ]);
    }

    $rulePage = Livewire::test(\App\Livewire\AdminV2\Rules\Show::class, ['rule' => $rule->id])
        ->assertSee('13 Mails insgesamt')
        ->assertSee('1–10 von 13')
        ->assertSee('Weitere Mail 12')
        ->assertDontSee('Weitere Mail 2 ')
        ->call('nextPage')
        ->assertSee('11–13 von 13')
        ->assertSee('Neues Ereignis: Streik in Rom');

    expect($rulePage->instance()->logs->count())->toBe(3);

    // Eine geloeschte Regel bleibt ueber den Verlauf erreichbar.
    $rule->delete();

    $page = Livewire::withQueryParams(['customer' => '1001'])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate');

    expect($page->instance()->history['rows'][0]['rule_deleted'])->toBeTrue();

    $this->get($url)->assertOk()->assertSee('Gelöscht');
    $this->get(route('adminv2.rules.show', 999999))->assertNotFound();

    \Illuminate\Support\Carbon::setTestNow();
});

it('blaettert und filtert im Versandverlauf', function () {
    $gtm = ruleFor($this->alpha, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR, 'Regel GTM', [], 'gtm@example.com');
    $alert = ruleFor($this->alpha, NotificationRule::SOURCE_TRAVEL_ALERT, 'Regel Alert', [], 'alert@example.com');

    // 23 Mails an Alpha ueber mehrere Tage, eine davon fehlgeschlagen; dazu eine an Beta.
    foreach (range(1, 23) as $number) {
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-10-01 08:00:00')->addHours($number * 3));

        NotificationLog::create([
            'notification_rule_id' => $number % 2 === 0 ? $gtm->id : $alert->id, 'customer_id' => $this->alpha->id,
            'event_id' => $this->event->id, 'event_type' => CustomEvent::class,
            'recipient_email' => $number % 2 === 0 ? 'gtm@example.com' : 'alert@example.com',
            'subject' => 'Mail Nummer '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            'status' => $number === 5 ? 'failed' : 'sent', 'error_message' => $number === 5 ? 'Postfach voll' : null,
        ]);
    }

    NotificationLog::create([
        'customer_id' => $this->beta->id, 'event_id' => $this->event->id, 'event_type' => CustomEvent::class,
        'recipient_email' => 'beta@example.com', 'subject' => 'Mail an Beta', 'status' => 'sent',
    ]);

    \Illuminate\Support\Carbon::setTestNow('2026-10-05 09:00:00');

    $subjects = fn ($page) => array_column($page->instance()->history['rows'], 'subject');

    $page = Livewire::withQueryParams(['customer' => '1001'])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertSee('23 Mails zu diesem Ereignis an diesen Kunden')
        ->assertSee('1–10 von 23')
        ->assertSee('Seite 1 von 3');

    // Hoechstens zehn auf einmal, neueste zuerst.
    expect($subjects($page))->toHaveCount(10)
        ->and($subjects($page)[0])->toBe('Mail Nummer 23')
        ->and($subjects($page->call('historyGoTo', 3)))->toBe(['Mail Nummer 03', 'Mail Nummer 02', 'Mail Nummer 01']);

    $page->assertSee('21–23 von 23');

    // Status – ein Filter beginnt wieder auf Seite 1.
    expect($subjects($page->set('historyStatus', 'failed')))->toBe(['Mail Nummer 05'])
        ->and($page->get('historyPage'))->toBe(1);

    $page->assertSee('Postfach voll')->assertSee('gefiltert aus 23');

    // Bereich und Regel
    expect($page->set('historyStatus', '')->set('historySource', NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR)->instance()->history['filtered'])->toBe(11)
        ->and($page->set('historySource', '')->set('historyRule', (string) $alert->id)->instance()->history['filtered'])->toBe(12);

    // Suche in Empfaenger und Betreff
    expect($page->set('historyRule', '')->set('historySearch', 'gtm@')->instance()->history['filtered'])->toBe(11)
        ->and($subjects($page->set('historySearch', 'Nummer 17')))->toBe(['Mail Nummer 17']);

    // Zeitraum: 02.10. = Mails 6 bis 13 (11:00 am 01.10. + 3 h je Mail)
    expect($page->set('historySearch', '')->set('historyFrom', '2026-10-02')->set('historyTo', '2026-10-02')->instance()->history['filtered'])->toBe(8);

    $page->call('resetHistoryFilters')->assertSee('1–10 von 23');

    // Alle Kunden: auch die Mail an Beta, mit Suche nach dem Kunden.
    $all = Livewire::withQueryParams([])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->assertSee('24 Mails zu diesem Ereignis');

    expect($subjects($all->set('historySearch', 'Beta Tours')))->toBe(['Mail an Beta']);

    \Illuminate\Support\Carbon::setTestNow();
});

it('filtert die zukuenftigen Reisen', function () {
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 09:00:00');
    config(['services.passolution.internal_token' => 'test-token']);
    Http::fake(['*/__internal/account/travel-details*' => Http::response(['data' => pdsRows(true)])]);

    // Die Adria-Kreuzfahrt wurde schon einmal gemeldet.
    NotificationLog::create([
        'customer_id' => $this->alpha->id, 'event_id' => $this->event->id, 'event_type' => CustomEvent::class,
        'recipient_email' => 'alpha@example.com', 'subject' => 'Travel Alert', 'status' => 'sent', 'affected_trips_count' => 1,
        'affected_trips' => [['key' => 'AAAA-1111', 'name' => 'Kreuzfahrt Adria', 'start' => '2026-10-05', 'end' => '2026-10-15']],
    ]);

    // Feste Laendernamen – die der Factory sind zufaellig und koennen sich gleichen.
    $this->spain->update(['name_translations' => ['de' => 'Spanien', 'en' => 'Spain']]);
    $this->italy->update(['name_translations' => ['de' => 'Italien', 'en' => 'Italy']]);

    $names = fn ($page) => array_column($page->instance()->visibleTrips, 'name');

    $page = Livewire::withQueryParams(['customer' => '1001'])
        ->test(RuleCheck::class, ['event' => $this->event->id])
        ->call('evaluate')
        ->call('loadTrips')
        ->assertSee('3 von 3');

    expect($names($page))->toBe(['Kreuzfahrt Adria', 'Spanien im November', 'Kreuzfahrt 2027'])
        ->and($names($page->set('tripsShow', 'affected')))->toBe(['Kreuzfahrt Adria'])
        ->and($names($page->set('tripsShow', 'unaffected')))->toBe(['Spanien im November', 'Kreuzfahrt 2027'])
        // Suche nach Name, Referenz und Kennung
        ->and($names($page->set('tripsShow', 'all')->set('tripsSearch', 'spanien')))->toBe(['Spanien im November'])
        ->and($names($page->set('tripsSearch', 'REF-7')))->toBe(['Kreuzfahrt Adria'])
        ->and($names($page->set('tripsSearch', 'cccc')))->toBe(['Kreuzfahrt 2027'])
        // Land
        ->and($page->set('tripsSearch', '')->instance()->tripCountries)->toContain('Spanien', 'Italien')
        ->and($names($page->set('tripsCountry', 'Spanien')))->toBe(['Spanien im November'])
        // Unterwegs im Zeitraum
        ->and($names($page->set('tripsCountry', '')->set('tripsFrom', '2026-10-10')->set('tripsTo', '2026-11-03')))->toBe(['Kreuzfahrt Adria', 'Spanien im November'])
        ->and($names($page->set('tripsFrom', '2027-01-01')->set('tripsTo', '')))->toBe(['Kreuzfahrt 2027'])
        // Gemeldet
        ->and($names($page->set('tripsFrom', '')->set('tripsReported', 'reported')))->toBe(['Kreuzfahrt Adria'])
        ->and($names($page->set('tripsReported', 'unreported')))->toBe(['Spanien im November', 'Kreuzfahrt 2027']);

    $page->assertSee('2 von 3')
        ->set('tripsSearch', 'gibt es nicht')
        ->assertSee('keinen Treffer')
        ->call('resetTripFilters')
        ->assertSee('3 von 3');

    \Illuminate\Support\Carbon::setTestNow();
});
