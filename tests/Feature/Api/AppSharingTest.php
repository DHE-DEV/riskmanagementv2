<?php

use App\Jobs\Push\SendPushBatch;
use App\Models\Customer;
use App\Models\Push\AppPushMessage;
use App\Models\Push\AppSharedTrip;
use Illuminate\Support\Facades\Queue;

/**
 * Teilen in der TripData-App: App-ID, Gruppen, geteilte Reisen mit Einladung und Annahme.
 */
function sharingToken(): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('App', ['gtm:read'])->plainTextToken;
}

/** Meldet ein Geraet an und liefert [id, secret, share_code]. */
function sharingDevice(object $test, string $token, ?string $name = null): array
{
    $data = $test->withToken($token)->postJson('/api/v1/devices', ['platform' => 'ios', 'push_token' => fake()->sha256()])->assertCreated()->json('data');
    if ($name) {
        $test->withToken($token)->withHeader('X-Device-Secret', $data['secret'])->putJson('/api/v1/devices/'.$data['id'], ['display_name' => $name])->assertOk();
    }

    return $data;
}

function asDevice(object $test, string $token, array $device): object
{
    return $test->withToken($token)->withHeader('X-Device-Secret', $device['secret']);
}

function tripPayload(array $extra = []): array
{
    return array_merge([
        'name' => 'Peru 2027',
        'start_date' => '2027-03-01',
        'end_date' => '2027-03-20',
        'country_codes' => ['pe', 'BO'],
        'travel_link_code' => 'zipt-hrg1-z6y5',
    ], $extra);
}

beforeEach(fn () => Queue::fake());

it('gibt jedem Geraet eine App-ID, einen Anzeigenamen und findet andere per App-ID', function () {
    $token = sharingToken();
    $anna = sharingDevice($this, $token, 'Anna');
    $ben = sharingDevice($this, $token);

    expect($anna['share_code'])->toMatch('/^[2-9A-HJKMNP-TV-Z]{4}-[2-9A-HJKMNP-TV-Z]{4}$/')
        ->and($anna['share_code'])->not->toBe($ben['share_code']);

    asDevice($this, $token, $anna)->getJson('/api/v1/devices/'.$anna['id'].'/sharing')
        ->assertOk()->assertJsonPath('data.me.share_code', $anna['share_code'])->assertJsonPath('data.me.display_name', 'Anna');

    asDevice($this, $token, $ben)->getJson('/api/v1/devices/'.$ben['id'].'/lookup/'.strtolower(str_replace('-', '', $anna['share_code'])))
        ->assertOk()->assertJsonPath('data.name', 'Anna');
    asDevice($this, $token, $ben)->getJson('/api/v1/devices/'.$ben['id'].'/lookup/2222-2222')->assertNotFound();

    $this->withToken($token)->withHeader('X-Device-Secret', 'falsch')->getJson('/api/v1/devices/'.$anna['id'].'/sharing')->assertForbidden();
});

it('laedt in Gruppen ein – erst nach Annahme Mitglied, nur der Ersteller verwaltet', function () {
    $token = sharingToken();
    [$anna, $ben, $cara] = [sharingDevice($this, $token, 'Anna'), sharingDevice($this, $token, 'Ben'), sharingDevice($this, $token, 'Cara')];
    $base = fn ($d) => '/api/v1/devices/'.$d['id'];

    $group = asDevice($this, $token, $anna)->postJson($base($anna).'/groups', ['name' => 'Familie'])->assertCreated()->json('data.id');

    asDevice($this, $token, $anna)->postJson($base($anna)."/groups/{$group}/members", ['codes' => ['2222-2222']])
        ->assertUnprocessable()->assertJsonValidationErrors('codes');
    asDevice($this, $token, $anna)->postJson($base($anna)."/groups/{$group}/members", ['codes' => [$ben['share_code'], $cara['share_code'], $anna['share_code']]])->assertOk();

    Queue::assertPushed(SendPushBatch::class, fn ($job) => count($job->deviceIds) === 2);
    expect(AppPushMessage::sole())->type->toBe(AppPushMessage::TYPE_SHARE)->source->toBe(AppPushMessage::SOURCE_SHARING);

    // Eingeladen: sieht die Gruppe, aber noch keine Mitglieder
    asDevice($this, $token, $ben)->getJson($base($ben).'/sharing')
        ->assertJsonPath('data.groups.0.status', 'invited')->assertJsonPath('data.groups.0.owner_name', 'Anna')->assertJsonCount(0, 'data.groups.0.members');

    asDevice($this, $token, $ben)->postJson($base($ben)."/groups/{$group}/accept")->assertOk()
        ->assertJsonPath('data.groups.0.status', 'accepted')->assertJsonCount(3, 'data.groups.0.members')
        ->assertJsonPath('data.groups.0.members.0.name', 'Anna')->assertJsonPath('data.groups.0.members.0.is_owner', true);
    asDevice($this, $token, $cara)->postJson($base($cara)."/groups/{$group}/decline")->assertOk()->assertJsonCount(0, 'data.groups');

    // Nur der Ersteller entfernt andere; Ben kann selbst gehen, Anna nicht.
    $members = asDevice($this, $token, $anna)->getJson($base($anna).'/sharing')->json('data.groups.0.members');
    $annaMember = collect($members)->firstWhere('is_me', true)['id'];
    $benMember = collect($members)->firstWhere('name', 'Ben')['id'];
    asDevice($this, $token, $ben)->deleteJson($base($ben)."/groups/{$group}/members/{$annaMember}")->assertForbidden();
    asDevice($this, $token, $anna)->deleteJson($base($anna)."/groups/{$group}/members/{$annaMember}")->assertUnprocessable();
    asDevice($this, $token, $ben)->putJson($base($ben)."/groups/{$group}", ['name' => 'Meins'])->assertForbidden();
    asDevice($this, $token, $ben)->deleteJson($base($ben)."/groups/{$group}/members/{$benMember}")->assertOk()->assertJsonCount(0, 'data.groups');
});

it('teilt eine Reise per App-ID: Einladung, Annahme, Aenderungen bei allen, Freigabe aufheben', function () {
    $token = sharingToken();
    [$anna, $ben] = [sharingDevice($this, $token, 'Anna'), sharingDevice($this, $token, 'Ben')];
    $base = fn ($d) => '/api/v1/devices/'.$d['id'];

    asDevice($this, $token, $anna)->putJson($base($anna).'/trips/trip-1', tripPayload(['codes' => [$ben['share_code']]]))->assertOk()
        ->assertJsonPath('data.owned_trips.0.recipients.0.name', 'Ben')->assertJsonPath('data.owned_trips.0.recipients.0.status', 'invited');
    Queue::assertPushed(SendPushBatch::class);

    $invite = asDevice($this, $token, $ben)->getJson($base($ben).'/sharing')
        ->assertJsonCount(0, 'data.received_trips')
        ->assertJsonPath('data.trip_invitations.0.name', 'Peru 2027')
        ->assertJsonPath('data.trip_invitations.0.owner_name', 'Anna')
        ->assertJsonPath('data.trip_invitations.0.country_codes', ['PE', 'BO'])
        ->assertJsonPath('data.trip_invitations.0.travel_link_code', 'ZIPT-HRG1-Z6Y5')
        ->json('data.trip_invitations.0.id');

    asDevice($this, $token, $ben)->postJson($base($ben)."/shared-trips/{$invite}/accept")->assertOk()
        ->assertJsonCount(0, 'data.trip_invitations')->assertJsonPath('data.received_trips.0.revision', 1);

    // Erneutes Speichern ohne Aenderung: gleiche Revision; Namensaenderung: neue Revision bei Ben, Empfaenger bleiben.
    asDevice($this, $token, $anna)->putJson($base($anna).'/trips/trip-1', tripPayload())->assertOk();
    expect(AppSharedTrip::sole()->revision)->toBe(1);
    asDevice($this, $token, $anna)->putJson($base($anna).'/trips/trip-1', tripPayload(['name' => 'Peru & Bolivien']))->assertOk()
        ->assertJsonPath('data.owned_trips.0.recipients.0.status', 'accepted');
    asDevice($this, $token, $ben)->getJson($base($ben).'/sharing')
        ->assertJsonPath('data.received_trips.0.name', 'Peru & Bolivien')->assertJsonPath('data.received_trips.0.revision', 2);

    // Ben aus der Freigabe nehmen → weg; ganz aufheben → auch beim Ersteller weg.
    asDevice($this, $token, $anna)->putJson($base($anna).'/trips/trip-1', tripPayload(['codes' => []]))->assertOk();
    asDevice($this, $token, $ben)->getJson($base($ben).'/sharing')->assertJsonCount(0, 'data.received_trips')->assertJsonCount(0, 'data.trip_invitations');
    asDevice($this, $token, $anna)->deleteJson($base($anna).'/trips/trip-1')->assertOk()->assertJsonCount(0, 'data.owned_trips');
    expect(AppSharedTrip::count())->toBe(0);
});

it('zeigt Reisen einer Gruppe allen angenommenen Mitgliedern ohne eigene Einladung', function () {
    $token = sharingToken();
    [$anna, $ben, $cara] = [sharingDevice($this, $token, 'Anna'), sharingDevice($this, $token, 'Ben'), sharingDevice($this, $token, 'Cara')];
    $base = fn ($d) => '/api/v1/devices/'.$d['id'];

    $group = asDevice($this, $token, $anna)->postJson($base($anna).'/groups', ['name' => 'Reisegruppe'])->json('data.id');
    asDevice($this, $token, $anna)->postJson($base($anna)."/groups/{$group}/members", ['codes' => [$ben['share_code'], $cara['share_code']]]);
    asDevice($this, $token, $ben)->postJson($base($ben)."/groups/{$group}/accept");

    // Ben (Mitglied) teilt mit der Gruppe; Cara hat noch nicht angenommen.
    asDevice($this, $token, $ben)->putJson($base($ben).'/trips/b-1', tripPayload(['group_ids' => [$group]]))->assertOk()
        ->assertJsonPath('data.owned_trips.0.groups.0.name', 'Reisegruppe');

    $trip = asDevice($this, $token, $anna)->getJson($base($anna).'/sharing')
        ->assertJsonCount(0, 'data.trip_invitations')
        ->assertJsonPath('data.received_trips.0.via_group', 'Reisegruppe')
        ->assertJsonPath('data.received_trips.0.owner_name', 'Ben')
        ->json('data.received_trips.0.id');
    asDevice($this, $token, $cara)->getJson($base($cara).'/sharing')->assertJsonCount(0, 'data.received_trips');
    asDevice($this, $token, $cara)->postJson($base($cara)."/shared-trips/{$trip}/accept")->assertNotFound();

    // Nach Annahme sieht Cara sie auch; Anna blendet sie aus.
    asDevice($this, $token, $cara)->postJson($base($cara)."/groups/{$group}/accept");
    asDevice($this, $token, $cara)->getJson($base($cara).'/sharing')->assertJsonCount(1, 'data.received_trips');
    asDevice($this, $token, $anna)->postJson($base($anna)."/shared-trips/{$trip}/decline")->assertOk()->assertJsonCount(0, 'data.received_trips');

    // Fremde Gruppe beim Teilen: wird ignoriert.
    $other = sharingDevice($this, $token);
    asDevice($this, $token, $other)->putJson($base($other).'/trips/x', tripPayload(['group_ids' => [$group]]))->assertOk()->assertJsonCount(0, 'data.owned_trips.0.groups');
});

it('loescht Gruppen und Freigaben mit dem Geraet', function () {
    $token = sharingToken();
    [$anna, $ben] = [sharingDevice($this, $token, 'Anna'), sharingDevice($this, $token, 'Ben')];
    $base = fn ($d) => '/api/v1/devices/'.$d['id'];

    asDevice($this, $token, $anna)->postJson($base($anna).'/groups', ['name' => 'Familie']);
    asDevice($this, $token, $anna)->putJson($base($anna).'/trips/t', tripPayload(['codes' => [$ben['share_code']]]));
    asDevice($this, $token, $anna)->deleteJson($base($anna))->assertNoContent();

    expect(AppSharedTrip::count())->toBe(0)
        ->and(\App\Models\Push\AppGroup::count())->toBe(0);
    asDevice($this, $token, $ben)->getJson($base($ben).'/sharing')->assertJsonCount(0, 'data.trip_invitations');
});

it('fuehrt gemeinsame Sehenswuerdigkeiten aller Teilnehmer zusammen – besucht bleibt persoenlich', function () {
    $token = sharingToken();
    $anna = sharingDevice($this, $token, 'Anna');
    $ben = sharingDevice($this, $token, 'Ben');
    $url = '/api/v1/devices/'.$anna['id'].'/trips/trip-1';

    asDevice($this, $token, $anna)->putJson($url, tripPayload(['codes' => [$ben['share_code']]]))->assertOk();
    $invitation = asDevice($this, $token, $ben)->getJson('/api/v1/devices/'.$ben['id'].'/sharing')->json('data.trip_invitations.0');
    expect($invitation['sights'])->toBeNull()->and($invitation['has_list'])->toBeFalse();

    asDevice($this, $token, $anna)->putJson($url.'/sights', [
        'sights' => [['key' => 'sight:12', 'updated_at' => 100], ['key' => 'sight:7', 'updated_at' => 100]],
        'visited' => ['sight:12'],
    ])->assertOk()->assertJsonPath('data.owned_trips.0.sights.0.key', 'sight:12');
    $tripId = AppSharedTrip::first()->id;
    asDevice($this, $token, $ben)->postJson('/api/v1/devices/'.$ben['id'].'/shared-trips/'.$tripId.'/accept')->assertOk();

    // Ben fuegt eine hinzu und entfernt eine; ein aelterer Stand von Anna aendert daran nichts.
    $received = asDevice($this, $token, $ben)->putJson('/api/v1/devices/'.$ben['id'].'/shared-trips/'.$tripId.'/sights', [
        'sights' => [['key' => 'sight:3', 'updated_at' => 200], ['key' => 'sight:7', 'updated_at' => 200, 'deleted' => true]],
        'visited' => ['sight:3'],
    ])->assertOk()->json('data.received_trips.0.sights');
    expect(collect($received)->pluck('key')->all())->toBe(['sight:12', 'sight:3'])
        ->and($received[0]['visited_by'])->toBe(['Anna'])
        ->and($received[1]['added_by'])->toBe('Ben')
        ->and($received[1]['visited_by'])->toBe([]);

    $owned = asDevice($this, $token, $anna)->putJson($url.'/sights', ['sights' => [['key' => 'sight:7', 'updated_at' => 150]]])
        ->assertOk()->json('data.owned_trips.0.sights');
    expect(collect($owned)->pluck('key')->all())->toBe(['sight:12', 'sight:3'])
        ->and($owned[1]['visited_by'])->toBe(['Ben'])
        ->and($owned[0]['visited_by'])->toBe([]);

    // Andere bekommen bei Aenderungen einen stillen Push.
    Queue::assertPushed(\App\Jobs\Push\SendSharingSync::class, fn ($job) => $job->deviceIds === [$anna['id']]);

    asDevice($this, $token, $anna)->deleteJson($url.'/sights')->assertOk();
    asDevice($this, $token, $ben)->putJson('/api/v1/devices/'.$ben['id'].'/shared-trips/'.$tripId.'/sights', ['sights' => []])->assertStatus(409);
    asDevice($this, $token, $anna)->putJson($url.'/sights', ['sights' => [['key' => 'city:3', 'updated_at' => 1]]])->assertUnprocessable();
});

it('schickt stille Pushs ohne Anzeige an iOS und Android', function () {
    $sender = Mockery::mock(\App\Services\Push\ApnsClient::class);
    $sender->shouldReceive('send')->once()->withArgs(function (array $messages) {
        $message = array_values($messages)[0];

        return $message['silent'] === true && $message['payload']['aps'] === ['content-available' => 1] && $message['payload']['type'] === 'share_sync';
    })->andReturn([]);
    $fcm = Mockery::mock(\App\Services\Push\FcmClient::class);
    $fcm->shouldReceive('send')->once()->withArgs(fn (array $messages) => array_values($messages)[0]['silent'] === true && ! isset(array_values($messages)[0]['title']))->andReturn([]);

    $token = sharingToken();
    $ios = sharingDevice($this, $token);
    $android = $this->withToken($token)->postJson('/api/v1/devices', ['platform' => 'android', 'push_token' => fake()->sha256()])->assertCreated()->json('data');
    (new \App\Jobs\Push\SendSharingSync('trip', [$ios['id'], $android['id']]))->handle(new \App\Services\Push\PushSender($sender, $fcm));
});

it('plant gemeinsame Sehenswuerdigkeiten: Stimmen, wer dabei ist und ein gemeinsamer Tag', function () {
    $token = sharingToken();
    $anna = sharingDevice($this, $token, 'Anna');
    $ben = sharingDevice($this, $token, 'Ben');
    $url = '/api/v1/devices/'.$anna['id'].'/trips/trip-1';
    asDevice($this, $token, $anna)->putJson($url, tripPayload(['codes' => [$ben['share_code']]]))->assertOk();
    asDevice($this, $token, $anna)->putJson($url.'/sights', ['sights' => [['key' => 'sight:12', 'updated_at' => 100]]])->assertOk();
    $tripId = AppSharedTrip::first()->id;
    asDevice($this, $token, $ben)->postJson('/api/v1/devices/'.$ben['id'].'/shared-trips/'.$tripId.'/accept')->assertOk();

    $plan = fn (array $device, array $body) => asDevice($this, $token, $device)->putJson('/api/v1/devices/'.$device['id'].'/shared-trips/'.$tripId.'/plan', $body);
    $plan($anna, ['key' => 'sight:12', 'vote' => 'must', 'going' => true])->assertOk();
    $sight = $plan($ben, ['key' => 'sight:12', 'vote' => 'no', 'day' => '2027-03-05'])->assertOk()->json('data.received_trips.0.sights.0');

    expect($sight['day'])->toBe('2027-03-05')
        ->and($sight['votes'])->toBe([
            ['name' => 'Anna', 'is_me' => false, 'vote' => 'must', 'going' => true, 'comment' => null],
            ['name' => 'Ben', 'is_me' => true, 'vote' => 'no', 'going' => false, 'comment' => null],
        ]);

    // Nur „dabei“ aendern laesst die Stimme stehen; ein neuer Stand von Anna behaelt den Tag.
    $plan($ben, ['key' => 'sight:12', 'going' => true])->assertOk();
    $owned = asDevice($this, $token, $anna)->putJson($url.'/sights', ['sights' => [['key' => 'sight:12', 'updated_at' => 200]]])->json('data.owned_trips.0.sights.0');
    expect($owned['day'])->toBe('2027-03-05')
        ->and($owned['votes'][1])->toBe(['name' => 'Ben', 'is_me' => false, 'vote' => 'no', 'going' => true, 'comment' => null]);

    // Kommentar: bleibt bei spaeteren Stimmen, leer loescht
    $plan($ben, ['key' => 'sight:12', 'comment' => '  Nur wenn das Wetter passt  '])->assertOk();
    $plan($ben, ['key' => 'sight:12', 'vote' => 'like'])->assertOk();
    $votes = asDevice($this, $token, $anna)->getJson('/api/v1/devices/'.$anna['id'].'/sharing')->json('data.owned_trips.0.sights.0.votes');
    expect($votes[1])->toMatchArray(['name' => 'Ben', 'vote' => 'like', 'going' => true, 'comment' => 'Nur wenn das Wetter passt']);
    $plan($ben, ['key' => 'sight:12', 'comment' => ''])->assertOk();
    $votes = asDevice($this, $token, $anna)->getJson('/api/v1/devices/'.$anna['id'].'/sharing')->json('data.owned_trips.0.sights.0.votes');
    expect($votes[1]['comment'])->toBeNull();
    $plan($ben, ['key' => 'sight:12', 'comment' => str_repeat('x', 501)])->assertUnprocessable();

    $plan($ben, ['key' => 'sight:99', 'vote' => 'like'])->assertNotFound();
    $plan($ben, ['key' => 'sight:12', 'vote' => 'maybe'])->assertUnprocessable();
});

it('fuehrt die gemeinsame Reisekasse aller Teilnehmer zusammen', function () {
    $token = sharingToken();
    $anna = sharingDevice($this, $token, 'Anna');
    $ben = sharingDevice($this, $token, 'Ben');
    asDevice($this, $token, $anna)->putJson('/api/v1/devices/'.$anna['id'].'/trips/trip-1', tripPayload(['codes' => [$ben['share_code']]]))->assertOk();
    $tripId = AppSharedTrip::first()->id;
    asDevice($this, $token, $ben)->postJson('/api/v1/devices/'.$ben['id'].'/shared-trips/'.$tripId.'/accept')->assertOk();
    $send = fn (array $device, array $expenses) => asDevice($this, $token, $device)->putJson('/api/v1/devices/'.$device['id'].'/shared-trips/'.$tripId.'/expenses', ['expenses' => $expenses]);

    $send($anna, [['id' => 'e1', 'title' => 'Abendessen', 'amount' => 8400, 'currency' => 'eur', 'date' => '2027-03-02', 'paid_by' => $anna['share_code'], 'split' => [$anna['share_code'], $ben['share_code']], 'updated_at' => 100]])->assertOk();
    $send($ben, [['id' => 'e2', 'title' => 'Taxi', 'amount' => 3000, 'currency' => 'PEN', 'date' => '2027-03-03', 'paid_by' => $ben['share_code'], 'split' => [$ben['share_code']], 'updated_at' => 100]])->assertOk();
    // Aelterer Stand aendert nichts, neuerer loescht.
    $send($ben, [['id' => 'e1', 'title' => 'Alt', 'amount' => 1, 'currency' => 'EUR', 'paid_by' => $ben['share_code'], 'split' => [$ben['share_code']], 'updated_at' => 50]])->assertOk();
    $expenses = $send($ben, [['id' => 'e2', 'updated_at' => 200, 'deleted' => true]])->assertOk()->json('data.received_trips.0.expenses');

    expect($expenses)->toHaveCount(1)
        ->and($expenses[0])->toMatchArray(['id' => 'e1', 'title' => 'Abendessen', 'amount' => 8400, 'currency' => 'EUR', 'paid_by' => $anna['share_code'], 'split' => [$anna['share_code'], $ben['share_code']]]);
    expect(asDevice($this, $token, $anna)->getJson('/api/v1/devices/'.$anna['id'].'/sharing')->json('data.owned_trips.0.expenses.0.title'))->toBe('Abendessen');

    $send($ben, [['id' => 'e3', 'title' => 'X', 'amount' => 1, 'currency' => 'EUR', 'paid_by' => 'nope', 'split' => [], 'updated_at' => 1]])->assertUnprocessable();
});

it('fuehrt den Reiseverlauf aller Teilnehmer zusammen und meldet geaenderte Orte sichtbar', function () {
    $token = sharingToken();
    $anna = sharingDevice($this, $token, 'Anna');
    $ben = sharingDevice($this, $token, 'Ben');
    $cara = sharingDevice($this, $token, 'Cara');
    asDevice($this, $token, $anna)->putJson('/api/v1/devices/'.$anna['id'].'/trips/trip-1', tripPayload(['start_date' => '2026-11-05', 'end_date' => '2026-11-12', 'codes' => [$ben['share_code'], $cara['share_code']]]))->assertOk();
    $tripId = AppSharedTrip::first()->id;
    asDevice($this, $token, $ben)->postJson('/api/v1/devices/'.$ben['id'].'/shared-trips/'.$tripId.'/accept')->assertOk();
    asDevice($this, $token, $cara)->postJson('/api/v1/devices/'.$cara['id'].'/shared-trips/'.$tripId.'/accept')->assertOk();
    asDevice($this, $token, $cara)->putJson('/api/v1/devices/'.$cara['id'], ['notify_itinerary' => false])->assertOk()->assertJsonPath('data.notify_itinerary', false);
    $send = fn (array $device, array $payload) => asDevice($this, $token, $device)->putJson('/api/v1/devices/'.$device['id'].'/shared-trips/'.$tripId.'/itinerary', $payload);
    AppPushMessage::query()->delete();

    $send($anna, ['stays' => [
        ['id' => 's1', 'kind' => 'place', 'from' => '2026-11-07', 'to' => '2026-11-08', 'name' => 'Funchal, Madeira', 'country_code' => 'pt', 'lat' => 32.65, 'lng' => -16.91, 'arrival' => '07:00', 'departure' => '14:00', 'updated_at' => 100],
        ['id' => 's2', 'kind' => 'sea', 'from' => '2026-11-06', 'updated_at' => 100],
    ]])->assertOk();
    expect(AppPushMessage::sole()->body['de'])->toBe('Anna hat den Reiseverlauf geändert (2 Einträge).');

    // Nur die Notiz: kein sichtbarer Hinweis
    AppPushMessage::query()->delete();
    $send($ben, ['stays' => [['id' => 's1', 'kind' => 'place', 'from' => '2026-11-07', 'to' => '2026-11-08', 'name' => 'Funchal, Madeira', 'country_code' => 'PT', 'lat' => 32.65, 'lng' => -16.91, 'arrival' => '07:00', 'departure' => '14:00', 'note' => 'Liegeplatz Pier 2', 'updated_at' => 200]],
        'day_notes' => [['date' => '2026-11-10', 'note' => 'Ausflug Teide, 08:30 Gangway', 'updated_at' => 200]]])->assertOk();
    expect(AppPushMessage::count())->toBe(0);

    // Neue Zeiten: Hinweis an Anna (Cara hat ihn abgeschaltet)
    $send($ben, ['stays' => [['id' => 's3', 'kind' => 'place', 'from' => '2026-11-09', 'name' => 'Santa Cruz de La Palma', 'arrival' => '09:00', 'departure' => '18:00', 'updated_at' => 300]]])->assertOk();
    $message = AppPushMessage::sole();
    expect($message->body['de'])->toBe('Ben hat Tag 5 geändert: Santa Cruz de La Palma, 09:00–18:00')
        ->and($message->title['de'])->toBe('Peru 2027')
        ->and($message->data)->toMatchArray(['event' => 'itinerary', 'trip' => $tripId]);
    Queue::assertPushed(SendPushBatch::class, fn ($job) => $job->deviceIds === [$anna['id']]);

    // Aelterer Stand aendert nichts, neuerer loescht
    $send($anna, ['stays' => [['id' => 's1', 'kind' => 'place', 'from' => '2026-11-07', 'name' => 'Alt', 'updated_at' => 150]]])->assertOk();
    $itinerary = $send($anna, ['stays' => [['id' => 's2', 'deleted' => true, 'updated_at' => 400]]])->assertOk()->json('data.owned_trips.0.itinerary');
    expect(collect($itinerary['stays'])->pluck('id')->all())->toBe(['s1', 's3'])
        ->and($itinerary['stays'][0])->toMatchArray(['name' => 'Funchal, Madeira', 'country_code' => 'PT', 'to' => '2026-11-08', 'note' => 'Liegeplatz Pier 2', 'changed_by' => 'Ben'])
        ->and($itinerary['stays'][1]['to'])->toBe('2026-11-09')
        ->and($itinerary['day_notes'])->toHaveCount(1)
        ->and($itinerary['day_notes'][0])->toMatchArray(['date' => '2026-11-10', 'note' => 'Ausflug Teide, 08:30 Gangway', 'changed_by' => 'Ben']);
    expect(asDevice($this, $token, $cara)->getJson('/api/v1/devices/'.$cara['id'].'/sharing')->json('data.received_trips.0.itinerary.stays'))->toHaveCount(2);

    // Leere Notiz loescht
    $notes = $send($cara, ['day_notes' => [['date' => '2026-11-10', 'note' => '', 'updated_at' => 500]]])->json('data.received_trips.0.itinerary.day_notes');
    expect($notes)->toBe([]);

    $send($ben, ['stays' => [['id' => 's4', 'kind' => 'place', 'from' => '2026-11-09', 'updated_at' => 1]]])->assertUnprocessable();
    $send($ben, ['stays' => [['id' => 's4', 'kind' => 'place', 'name' => 'X', 'from' => '2026-11-09', 'to' => '2026-11-01', 'updated_at' => 1]]])->assertUnprocessable();
    $send($ben, ['stays' => [['id' => 's4', 'kind' => 'place', 'name' => 'X', 'from' => '2026-11-09', 'arrival' => '25:00', 'updated_at' => 1]]])->assertUnprocessable();
    $stranger = sharingDevice($this, $token, 'Dora');
    $send($stranger, ['stays' => []])->assertNotFound();
});

it('teilt die Beschreibung des Erstellers und benachrichtigt nur auf Wunsch die Gruppe', function () {
    $token = sharingToken();
    $anna = sharingDevice($this, $token, 'Anna');
    $ben = sharingDevice($this, $token, 'Ben');
    $base = fn ($d) => '/api/v1/devices/'.$d['id'];
    $url = $base($anna).'/trips/trip-1';
    asDevice($this, $token, $anna)->putJson($url, tripPayload(['description' => 'Kanaren & Madeira mit Mein Schiff', 'codes' => [$ben['share_code']]]))->assertOk();
    $tripId = AppSharedTrip::first()->id;
    asDevice($this, $token, $ben)->postJson($base($ben).'/shared-trips/'.$tripId.'/accept')->assertOk();
    $received = fn () => asDevice($this, $token, $ben)->getJson($base($ben).'/sharing')->json('data.received_trips.0');
    expect($received()['description'])->toBe('Kanaren & Madeira mit Mein Schiff');
    $revision = $received()['revision'];
    AppPushMessage::query()->delete();

    // Aeltere App ohne Beschreibung: bleibt
    asDevice($this, $token, $anna)->putJson($url, tripPayload())->assertOk();
    expect($received()['description'])->toBe('Kanaren & Madeira mit Mein Schiff');

    // Aendern allein: neuer Stand, aber kein sichtbarer Hinweis
    asDevice($this, $token, $anna)->putJson($url, tripPayload(['description' => "Kanaren & Madeira\nTreffpunkt 14:00 Terminal"]))->assertOk();
    expect($received())->description->toBe("Kanaren & Madeira\nTreffpunkt 14:00 Terminal")->revision->toBe($revision + 1)
        ->and(AppPushMessage::count())->toBe(0);

    // Benachrichtigen nur bei Reisen mit Gruppe
    asDevice($this, $token, $anna)->postJson($url.'/notify', ['changes' => ['description']])->assertStatus(409);
    $group = asDevice($this, $token, $anna)->postJson($base($anna).'/groups', ['name' => 'Familie'])->json('data.id');
    asDevice($this, $token, $anna)->putJson($url, tripPayload(['group_ids' => [$group]]))->assertOk();
    asDevice($this, $token, $anna)->postJson($url.'/notify', ['changes' => ['dates', 'description']])->assertOk()->assertJsonPath('data.owned_trips.0.client_trip_id', 'trip-1');
    expect(AppPushMessage::sole())->body->toMatchArray(['de' => 'Anna hat die Reise geändert: Zeitraum und Beschreibung.'])
        ->data->toMatchArray(['event' => 'trip_changed', 'trip' => $tripId]);

    // Nur der Ersteller, nur bekannte Aenderungen
    asDevice($this, $token, $ben)->postJson($base($ben).'/trips/trip-1/notify', ['changes' => ['dates']])->assertNotFound();
    asDevice($this, $token, $anna)->postJson($url.'/notify', ['changes' => ['wetter']])->assertUnprocessable();
});

it('zeigt spaeter eingetragene Namen in Gruppen und Reisen und laedt die anderen Geraete neu', function () {
    $token = sharingToken();
    $anna = sharingDevice($this, $token, 'Anna');
    $ben = sharingDevice($this, $token);
    $base = fn ($d) => '/api/v1/devices/'.$d['id'];
    $group = asDevice($this, $token, $anna)->postJson($base($anna).'/groups', ['name' => 'Familie'])->json('data.id');
    asDevice($this, $token, $anna)->postJson($base($anna)."/groups/{$group}/members", ['codes' => [$ben['share_code']]])->assertOk();
    asDevice($this, $token, $ben)->postJson($base($ben)."/groups/{$group}/accept")->assertOk();
    asDevice($this, $token, $anna)->putJson($base($anna).'/trips/trip-1', tripPayload(['group_ids' => [$group]]))->assertOk();

    $members = fn () => collect(asDevice($this, $token, $anna)->getJson($base($anna).'/sharing')->json('data.groups.0.members'))->keyBy('is_me');
    expect($members()[0])->toMatchArray(['name' => $ben['share_code'], 'has_name' => false]);
    Queue::fake();

    asDevice($this, $token, $ben)->putJson($base($ben), ['display_name' => 'Ben'])->assertOk();

    expect($members()[0])->toMatchArray(['name' => 'Ben', 'has_name' => true])
        ->and(asDevice($this, $token, $anna)->getJson($base($anna).'/sharing')->json('data.owned_trips.0.participants.1'))->toMatchArray(['name' => 'Ben', 'has_name' => true]);
    Queue::assertPushed(\App\Jobs\Push\SendSharingSync::class, fn ($job) => $job->deviceIds === [$anna['id']]);

    // Gleicher Name: kein Neuladen
    Queue::fake();
    asDevice($this, $token, $ben)->putJson($base($ben), ['display_name' => 'Ben'])->assertOk();
    Queue::assertNotPushed(\App\Jobs\Push\SendSharingSync::class);
});
