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
