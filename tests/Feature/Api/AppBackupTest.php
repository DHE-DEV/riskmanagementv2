<?php

use App\Jobs\Push\SendPushBatch;
use App\Models\Customer;
use App\Models\Push\AppDevice;
use App\Models\Push\AppDeviceBackup;
use App\Models\Push\AppDeviceTransfer;
use App\Models\Push\AppPushMessage;
use Illuminate\Support\Facades\Queue;

/**
 * Sicherung, Wiederherstellungscode und Uebertragung auf ein neues Geraet (TripData).
 */
function backupToken(): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('App', ['gtm:read'])->plainTextToken;
}

/** Meldet ein Geraet an und liefert die Antwort (id, secret, share_code, …). */
function backupDevice(object $test, string $token, array $data = []): array
{
    return $test->withToken($token)->postJson('/api/v1/devices', array_merge([
        'platform' => 'ios',
        'push_token' => fake()->sha256(),
        'locale' => 'de',
        'app_version' => '1.4 (100)',
    ], $data))->assertCreated()->json('data');
}

function backupAs(object $test, string $token, array $device): object
{
    return $test->withToken($token)->withHeader('X-Device-Secret', $device['secret']);
}

function backupBody(array $extra = []): array
{
    return array_merge([
        'schema' => 3,
        'platform' => 'ios',
        'app_version' => '1.4 (100)',
        'counts' => ['journeys' => 3, 'visited_countries' => 12],
        'data' => [
            'journeys' => [['id' => 'j1', 'name' => 'Peru', 'price' => 1.0, 'tags' => []]],
            'settings' => new stdClass,
            'note' => 'Grüße – "zitiert" / mit Schrägstrich',
        ],
    ], $extra);
}

beforeEach(fn () => Queue::fake());

it('speichert die Sicherung, liefert sie mit und ohne Inhalt und loescht sie', function () {
    $token = backupToken();
    $anna = backupDevice($this, $token);
    $url = '/api/v1/devices/'.$anna['id'].'/backup';

    backupAs($this, $token, $anna)->getJson($url)->assertOk()
        ->assertJsonPath('data.exists', false)->assertJsonPath('data.has_recovery_code', false)->assertJsonPath('data.data', null);

    // Roh senden (putJson wuerde 1.0 zu 1 machen)
    $saved = $this->call('PUT', $url, [], [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        'HTTP_X_DEVICE_SECRET' => $anna['secret'],
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], json_encode(backupBody(), JSON_PRESERVE_ZERO_FRACTION))->assertOk()
        ->assertJsonPath('success', true)->assertJsonPath('data.schema', 3)->assertJsonPath('data.platform', 'ios')
        ->assertJsonPath('data.counts.journeys', 3)->json('data');
    expect($saved['backed_up_at'])->toMatch('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d[+-]\d\d:\d\d$/')
        ->and($saved['size'])->toBeGreaterThan(50);

    // Inhalt kommt unveraendert zurueck ({} bleibt {}, 1.0 bleibt 1.0)
    $response = backupAs($this, $token, $anna)->getJson($url)->assertOk()
        ->assertJsonPath('data.exists', true)->assertJsonPath('data.app_version', '1.4 (100)')
        ->assertJsonPath('data.data.journeys.0.name', 'Peru')
        ->assertJsonPath('data.data.note', 'Grüße – "zitiert" / mit Schrägstrich');
    expect($response->getContent())->toContain('"settings":{}')->toContain('"price":1.0')->toContain('"tags":[]');

    backupAs($this, $token, $anna)->getJson($url.'?meta=1')->assertOk()
        ->assertJsonPath('data.exists', true)->assertJsonPath('data.size', $saved['size'])->assertJsonMissingPath('data.data');

    // Ueberschreiben: nur eine Sicherung je Geraet
    backupAs($this, $token, $anna)->putJson($url, backupBody(['schema' => 4, 'counts' => null]))->assertOk()->assertJsonPath('data.schema', 4);
    expect(AppDeviceBackup::count())->toBe(1);

    backupAs($this, $token, $anna)->deleteJson($url)->assertNoContent();
    backupAs($this, $token, $anna)->getJson($url.'?meta=1')->assertJsonPath('data.exists', false);
});

it('nimmt die Sicherung auch gzip-komprimiert an', function () {
    $token = backupToken();
    $anna = backupDevice($this, $token);

    $this->call('PUT', '/api/v1/devices/'.$anna['id'].'/backup', [], [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        'HTTP_X_DEVICE_SECRET' => $anna['secret'],
        'HTTP_CONTENT_ENCODING' => 'gzip',
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], gzencode(json_encode(backupBody())))->assertOk()->assertJsonPath('data.schema', 3);

    backupAs($this, $token, $anna)->getJson('/api/v1/devices/'.$anna['id'].'/backup')->assertJsonPath('data.data.journeys.0.id', 'j1');
});

it('prueft Form, Groesse und Secret der Sicherung', function () {
    $token = backupToken();
    $anna = backupDevice($this, $token);
    $ben = backupDevice($this, $token);
    $url = '/api/v1/devices/'.$anna['id'].'/backup';

    backupAs($this, $token, $anna)->putJson($url, backupBody(['data' => null]))->assertUnprocessable()->assertJsonValidationErrors('data');
    backupAs($this, $token, $anna)->putJson($url, backupBody(['data' => [1, 2, 3]]))->assertUnprocessable()->assertJsonValidationErrors('data');
    backupAs($this, $token, $anna)->putJson($url, backupBody(['platform' => 'windows']))->assertUnprocessable()->assertJsonValidationErrors('platform');
    backupAs($this, $token, $anna)->putJson($url, backupBody(['schema' => 'x']))->assertUnprocessable()->assertJsonValidationErrors('schema');

    backupAs($this, $token, $anna)->putJson($url, backupBody(['data' => ['blob' => str_repeat('a', AppDeviceBackup::MAX_BYTES)]]))
        ->assertStatus(413)->assertJsonPath('code', 'backup_too_large');
    expect(AppDeviceBackup::count())->toBe(0);

    // Fremdes Secret: weder lesen noch schreiben noch loeschen
    backupAs($this, $token, $anna)->putJson($url, backupBody())->assertOk();
    backupAs($this, $token, $ben)->getJson($url)->assertForbidden();
    backupAs($this, $token, $ben)->putJson($url, backupBody())->assertForbidden();
    backupAs($this, $token, $ben)->deleteJson($url)->assertForbidden();
    $this->withToken($token)->withHeader('X-Device-Secret', 'falsch')->getJson($url)->assertForbidden();
    expect(AppDeviceBackup::count())->toBe(1);

    // Geraet abmelden: Sicherung geht mit
    backupAs($this, $token, $anna)->deleteJson('/api/v1/devices/'.$anna['id'])->assertNoContent();
    expect(AppDeviceBackup::count())->toBe(0);
});

it('uebernimmt ein Geraet mit App-ID und Wiederherstellungscode', function () {
    $token = backupToken();
    $old = backupDevice($this, $token, ['display_name' => 'Anna']);
    backupAs($this, $token, $old)->putJson('/api/v1/devices/'.$old['id'].'/backup', backupBody())->assertOk();

    $first = backupAs($this, $token, $old)->postJson('/api/v1/devices/'.$old['id'].'/recovery-code')->assertCreated()->json('data.code');
    $code = backupAs($this, $token, $old)->postJson('/api/v1/devices/'.$old['id'].'/recovery-code')->assertCreated()->json('data.code');
    expect($code)->toMatch('/^[2-9A-HJKMNP-TV-Z]{4}-[2-9A-HJKMNP-TV-Z]{4}-[2-9A-HJKMNP-TV-Z]{4}$/')->not->toBe($first)
        ->and(AppDevice::find($old['id'])->recovery_hash)->toBe(hash('sha256', str_replace('-', '', $code)));
    backupAs($this, $token, $old)->getJson('/api/v1/devices/'.$old['id'].'/backup?meta=1')->assertJsonPath('data.has_recovery_code', true);

    $new = backupDevice($this, $token, ['platform' => 'android', 'locale' => 'nl', 'push_token' => 'fcm-token-neu', 'app_version' => '2.0 (200)']);
    $adopt = '/api/v1/devices/'.$new['id'].'/adopt';

    // Alter (ersetzter) Code und falsche App-ID: neutrale 422
    backupAs($this, $token, $new)->postJson($adopt, ['share_code' => $old['share_code'], 'recovery_code' => $first])
        ->assertUnprocessable()->assertJsonPath('code', 'invalid_recovery')->assertJsonPath('message', 'App-ID oder Wiederherstellungscode stimmt nicht.');
    backupAs($this, $token, $new)->postJson($adopt, ['share_code' => '2222-2222', 'recovery_code' => $code])->assertUnprocessable()->assertJsonPath('code', 'invalid_recovery');

    $data = backupAs($this, $token, $new)->postJson($adopt, ['share_code' => strtolower($old['share_code']), 'recovery_code' => strtolower(str_replace('-', ' ', $code))])
        ->assertOk()
        ->assertJsonPath('data.device.id', $old['id'])
        ->assertJsonPath('data.device.share_code', $old['share_code'])
        ->assertJsonPath('data.device.display_name', 'Anna')
        ->assertJsonPath('data.device.platform', 'android')
        ->assertJsonPath('data.device.locale', 'nl')
        ->assertJsonPath('data.backup.exists', true)
        ->assertJsonPath('data.backup.data.journeys.0.name', 'Peru')
        ->json('data');

    expect(AppDevice::find($new['id']))->toBeNull()
        ->and(AppDevice::find($old['id']))->push_token->toBe('fcm-token-neu')->app_version->toBe('2.0 (200)');

    // Alte Installation: 403; neue arbeitet mit der alten ID und dem neuen Secret
    backupAs($this, $token, $old)->getJson('/api/v1/devices/'.$old['id'].'/backup?meta=1')->assertForbidden();
    $this->withToken($token)->withHeader('X-Device-Secret', $data['device']['secret'])->getJson('/api/v1/devices/'.$old['id'].'/backup?meta=1')
        ->assertOk()->assertJsonPath('data.has_recovery_code', true);

    // Der Code bleibt gueltig: eine weitere Neuinstallation kann erneut uebernehmen
    $third = backupDevice($this, $token);
    backupAs($this, $token, $third)->postJson('/api/v1/devices/'.$third['id'].'/adopt', ['share_code' => $old['share_code'], 'recovery_code' => $code])
        ->assertOk()->assertJsonPath('data.device.id', $old['id']);
});

it('begrenzt Versuche mit dem Wiederherstellungscode auf 5 pro Stunde', function () {
    $token = backupToken();
    $old = backupDevice($this, $token);
    $code = backupAs($this, $token, $old)->postJson('/api/v1/devices/'.$old['id'].'/recovery-code')->json('data.code');
    $new = backupDevice($this, $token);
    $adopt = '/api/v1/devices/'.$new['id'].'/adopt';

    foreach (range(1, 5) as $i) {
        backupAs($this, $token, $new)->postJson($adopt, ['share_code' => $old['share_code'], 'recovery_code' => 'AAAA-AAAA-AAAA'])->assertUnprocessable();
    }
    backupAs($this, $token, $new)->postJson($adopt, ['share_code' => $old['share_code'], 'recovery_code' => $code])->assertTooManyRequests();

    // Auch ein anderes anfragendes Geraet scheitert an derselben Ziel-App-ID
    $other = backupDevice($this, $token);
    backupAs($this, $token, $other)->postJson('/api/v1/devices/'.$other['id'].'/adopt', ['share_code' => $old['share_code'], 'recovery_code' => $code])->assertTooManyRequests();
    expect(AppDevice::find($new['id']))->not->toBeNull();
});

it('uebertraegt auf ein neues Geraet nach Bestaetigung am alten', function () {
    $token = backupToken();
    $old = backupDevice($this, $token, ['display_name' => 'Anna']);
    $new = backupDevice($this, $token, ['platform' => 'android', 'push_token' => 'fcm-neu', 'locale' => 'en']);
    $oldBase = '/api/v1/devices/'.$old['id'];
    $newBase = '/api/v1/devices/'.$new['id'];

    backupAs($this, $token, $new)->postJson($newBase.'/transfers', ['share_code' => '2222-2222'])->assertNotFound()->assertJsonPath('message', 'Diese App-ID gibt es nicht.');
    backupAs($this, $token, $new)->postJson($newBase.'/transfers', ['share_code' => $new['share_code']])->assertUnprocessable()->assertJsonPath('code', 'own_share_code');

    $transfer = backupAs($this, $token, $new)->postJson($newBase.'/transfers', ['share_code' => strtolower(str_replace('-', '', $old['share_code'])), 'label' => 'Android · Galaxy S25'])
        ->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.from_share_code', $old['share_code'])->json('data');

    // Sichtbarer Push an das alte Geraet
    $message = AppPushMessage::sole();
    expect($message->type)->toBe(AppPushMessage::TYPE_TRANSFER_REQUEST)
        ->and($message->data)->toMatchArray(['transfer' => $transfer['id']])
        ->and($message->text('body', 'de'))->toContain('(Android · Galaxy S25)');
    Queue::assertPushed(SendPushBatch::class, fn ($job) => $job->deviceIds === [$old['id']]);

    // Anfrage beim alten Geraet; das neue sieht sie dort nicht
    backupAs($this, $token, $old)->getJson($oldBase.'/transfers/incoming')->assertOk()
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $transfer['id'])
        ->assertJsonPath('data.0.to_label', 'Android · Galaxy S25')->assertJsonPath('data.0.to_platform', 'android');
    backupAs($this, $token, $new)->postJson($newBase.'/transfers/'.$transfer['id'].'/approve')->assertNotFound();

    // Ohne Sicherung keine Bestaetigung
    backupAs($this, $token, $old)->postJson($oldBase.'/transfers/'.$transfer['id'].'/approve')->assertStatus(409)->assertJsonPath('code', 'backup_required');
    backupAs($this, $token, $new)->getJson($newBase.'/transfers/'.$transfer['id'])->assertOk()->assertJsonPath('data.status', 'pending')->assertJsonMissingPath('data.device');

    backupAs($this, $token, $old)->putJson($oldBase.'/backup', backupBody())->assertOk();
    backupAs($this, $token, $old)->postJson($oldBase.'/transfers/'.$transfer['id'].'/approve')->assertOk()->assertJsonPath('data.status', 'approved');
    backupAs($this, $token, $old)->postJson($oldBase.'/transfers/'.$transfer['id'].'/decline')->assertStatus(409)->assertJsonPath('code', 'transfer_not_pending');

    // Abruf durch das neue Geraet: Identitaet uebernommen
    $result = backupAs($this, $token, $new)->getJson($newBase.'/transfers/'.$transfer['id'])->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.device.id', $old['id'])
        ->assertJsonPath('data.device.share_code', $old['share_code'])
        ->assertJsonPath('data.device.display_name', 'Anna')
        ->assertJsonPath('data.device.platform', 'android')
        ->assertJsonPath('data.backup.data.journeys.0.name', 'Peru')
        ->json('data');
    $secret = $result['device']['secret'];

    expect(AppDevice::find($new['id']))->toBeNull()
        ->and(AppDevice::find($old['id']))->push_token->toBe('fcm-neu')->locale->toBe('en')
        ->and(AppDevice::count())->toBe(1);

    backupAs($this, $token, $old)->getJson($oldBase.'/backup?meta=1')->assertForbidden();
    $this->withToken($token)->withHeader('X-Device-Secret', $secret)->getJson($oldBase.'/backup?meta=1')->assertOk()->assertJsonPath('data.exists', true);

    // Verlorene Antwort: erneuter Abruf mit alter (neuer) ID und Secret liefert dasselbe, fremdes Secret nicht
    backupAs($this, $token, $new)->getJson($newBase.'/transfers/'.$transfer['id'])->assertOk()
        ->assertJsonPath('data.status', 'completed')->assertJsonPath('data.device.secret', $secret)->assertJsonPath('data.backup.exists', true);
    $this->withToken($token)->withHeader('X-Device-Secret', 'falsch')->getJson($newBase.'/transfers/'.$transfer['id'])->assertForbidden();

    // Nach 10 Minuten nicht mehr
    $this->travel(11)->minutes();
    backupAs($this, $token, $new)->getJson($newBase.'/transfers/'.$transfer['id'])->assertOk()
        ->assertJsonPath('data.status', 'completed')->assertJsonMissingPath('data.device');
    expect(AppDeviceTransfer::sole()->getRawOriginal('secret_encrypted'))->toBeNull();
});

it('lehnt ab, bricht ab und laesst Anfragen ablaufen', function () {
    $token = backupToken();
    $old = backupDevice($this, $token);
    $new = backupDevice($this, $token);
    $oldBase = '/api/v1/devices/'.$old['id'];
    $newBase = '/api/v1/devices/'.$new['id'];
    backupAs($this, $token, $old)->putJson($oldBase.'/backup', backupBody())->assertOk();
    $request = fn () => backupAs($this, $token, $new)->postJson($newBase.'/transfers', ['share_code' => $old['share_code']])->assertCreated()->json('data.id');

    // Ablehnen
    $declined = $request();
    backupAs($this, $token, $old)->postJson($oldBase.'/transfers/'.$declined.'/decline')->assertOk()->assertJsonPath('data.status', 'declined');
    backupAs($this, $token, $new)->getJson($newBase.'/transfers/'.$declined)->assertJsonPath('data.status', 'declined');
    backupAs($this, $token, $old)->getJson($oldBase.'/transfers/incoming')->assertJsonCount(0, 'data');

    // Neue Anfrage ersetzt die offene; Abbrechen durch das neue Geraet
    $first = $request();
    $second = $request();
    expect(AppDeviceTransfer::find($first)->status)->toBe('cancelled');
    backupAs($this, $token, $new)->deleteJson($newBase.'/transfers/'.$second)->assertNoContent();
    backupAs($this, $token, $old)->postJson($oldBase.'/transfers/'.$second.'/approve')->assertStatus(409)->assertJsonPath('data.status', 'cancelled');
    backupAs($this, $token, $old)->getJson($oldBase.'/transfers/incoming')->assertJsonCount(0, 'data');

    // Ablauf nach 15 Minuten
    $expiring = $request();
    $this->travel(16)->minutes();
    backupAs($this, $token, $old)->getJson($oldBase.'/transfers/incoming')->assertJsonCount(0, 'data');
    backupAs($this, $token, $old)->postJson($oldBase.'/transfers/'.$expiring.'/approve')->assertStatus(409)->assertJsonPath('data.status', 'expired');
    backupAs($this, $token, $new)->getJson($newBase.'/transfers/'.$expiring)->assertJsonPath('data.status', 'expired');

    // Limit: 5 Anfragen pro Stunde je anfragendem Geraet (4 bisher in dieser Stunde)
    $request();
    backupAs($this, $token, $new)->postJson($newBase.'/transfers', ['share_code' => $old['share_code']])->assertTooManyRequests();

    // Beide Geraete bleiben, wie sie sind
    expect(AppDevice::count())->toBe(2);
    backupAs($this, $token, $old)->getJson($oldBase.'/backup?meta=1')->assertOk();
});

it('raeumt Uebertragungen auf', function () {
    $token = backupToken();
    $old = backupDevice($this, $token);
    $new = backupDevice($this, $token);
    $from = AppDevice::find($old['id']);
    $base = ['from_device_id' => $from->id, 'to_device_id' => $new['id'], 'to_secret_hash' => str_repeat('a', 64), 'to_platform' => 'ios'];

    $pending = AppDeviceTransfer::create($base + ['status' => 'pending', 'expires_at' => now()->subMinute()]);
    $done = AppDeviceTransfer::create($base + ['status' => 'completed', 'expires_at' => now(), 'completed_at' => now()->subMinutes(11), 'secret_encrypted' => 'geheim']);
    $fresh = AppDeviceTransfer::create($base + ['status' => 'completed', 'expires_at' => now(), 'completed_at' => now()->subMinutes(2), 'secret_encrypted' => 'geheim']);
    $ancient = AppDeviceTransfer::create($base + ['status' => 'declined', 'expires_at' => now()]);
    $ancient->forceFill(['created_at' => now()->subDays(31)])->save();

    $this->artisan('app-devices:prune-transfers')->assertSuccessful();

    expect($pending->fresh()->status)->toBe('expired')
        ->and($done->fresh()->secret_encrypted)->toBeNull()
        ->and($fresh->fresh()->secret_encrypted)->toBe('geheim')
        ->and($ancient->fresh())->toBeNull();
});

it('behaelt inaktive Geraete mit Sicherung bis zu 2 Jahre', function () {
    $create = fn (int $days) => AppDevice::create(['platform' => 'ios', 'secret_hash' => 'x', 'last_seen_at' => now()->subDays($days)]);
    $withBackup = fn (AppDevice $device) => AppDeviceBackup::create([
        'app_device_id' => $device->id, 'schema' => 1, 'platform' => 'ios', 'payload' => gzencode('{}'), 'size' => 2, 'counts' => (object) [], 'backed_up_at' => now(),
    ]);

    $kept = $create(400);
    $withBackup($kept);
    $expired = $create(731);
    $withBackup($expired);
    $noBackup = $create(181);

    $this->artisan('push:prune-devices')->assertSuccessful();

    expect(AppDevice::pluck('id')->all())->toBe([$kept->id])
        ->and(AppDeviceBackup::pluck('app_device_id')->all())->toBe([$kept->id]);
});
