<?php

use App\Jobs\Push\FanOutPushMessage;
use App\Jobs\Push\SendPushBatch;
use App\Models\Customer;
use App\Models\Push\AppDevice;
use App\Models\Push\AppDeviceTravelLink;
use App\Models\Push\AppPushMessage;
use App\Models\TravelDetail\PdsGlobalSyncState;
use App\Services\Push\PushDispatcher;
use App\Services\Push\PushSender;
use App\Services\Push\TravelChangeScanner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function appToken(array $abilities = ['gtm:read']): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('App', $abilities)->plainTextToken;
}

function registerDevice(object $test, string $token, array $data = []): array
{
    return $test->withToken($token)->postJson('/api/v1/devices', array_merge([
        'platform' => 'ios',
        'push_token' => str_repeat('ab', 32),
        'push_environment' => 'sandbox',
        'locale' => 'de-DE',
        'app_version' => '1.0 (20261017.1000)',
    ], $data))->assertCreated()->json('data');
}

function apnsKey(): string
{
    $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    openssl_pkey_export($key, $pem);

    return $pem;
}

it('meldet ein Geraet an und gibt das Secret genau einmal heraus', function () {
    $this->postJson('/api/v1/devices', ['platform' => 'ios'])->assertUnauthorized();

    $data = registerDevice($this, appToken());

    expect($data['id'])->toHaveLength(26)
        ->and($data['secret'])->toHaveLength(48)
        ->and($data['locale'])->toBe('de')
        ->and($data['has_push_token'])->toBeTrue();

    $device = AppDevice::find($data['id']);
    expect($device->secret_hash)->not->toBe($data['secret'])
        ->and($device->secretMatches($data['secret']))->toBeTrue()
        ->and($device->push_environment)->toBe('sandbox');
});

it('aendert ein Geraet nur mit dem richtigen Secret', function () {
    $token = appToken();
    $data = registerDevice($this, $token);

    $this->withToken($token)->putJson('/api/v1/devices/'.$data['id'], ['locale' => 'nl'])->assertForbidden();
    $this->withToken($token)->withHeader('X-Device-Secret', 'falsch')->putJson('/api/v1/devices/'.$data['id'], ['locale' => 'nl'])->assertForbidden();
    $this->withToken($token)->withHeader('X-Device-Secret', $data['secret'])->putJson('/api/v1/devices/01JUNBEKANNT0000000000000', [])->assertNotFound();

    $this->withToken($token)->withHeader('X-Device-Secret', $data['secret'])
        ->putJson('/api/v1/devices/'.$data['id'], ['locale' => 'nl', 'notifications_enabled' => false])
        ->assertOk()
        ->assertJsonPath('data.locale', 'nl')
        ->assertJsonPath('data.notifications_enabled', false);
});

it('ersetzt die Reise-Links eines Geraets und normalisiert die Reise-IDs', function () {
    $token = appToken();
    $data = registerDevice($this, $token);
    $put = fn (array $tids) => $this->withToken($token)->withHeader('X-Device-Secret', $data['secret'])
        ->putJson('/api/v1/devices/'.$data['id'].'/travel-links', ['tids' => $tids]);

    $put(['zipt-hrg1-z6y5', 'ABCDEFGHIJKL', 'zu-kurz', 'ABCDEFGHIJKL'])
        ->assertOk()
        ->assertJsonPath('data.tids', ['ZIPTHRG1Z6Y5', 'ABCDEFGHIJKL']);

    $put(['ABCDEFGHIJKL', 'MNOPQRSTUVWX'])->assertOk();
    expect(AppDeviceTravelLink::where('app_device_id', $data['id'])->orderBy('tid')->pluck('tid')->all())
        ->toBe(['ABCDEFGHIJKL', 'MNOPQRSTUVWX']);

    $put([])->assertOk();
    expect(AppDeviceTravelLink::count())->toBe(0);

    $this->withToken($token)->withHeader('X-Device-Secret', $data['secret'])->deleteJson('/api/v1/devices/'.$data['id'])->assertNoContent();
    expect(AppDevice::count())->toBe(0);
});

it('gibt einen Push-Token nur einem Geraet – die Neuanmeldung nimmt ihn dem alten', function () {
    $token = appToken();
    $old = registerDevice($this, $token);
    $new = registerDevice($this, $token);

    expect(AppDevice::find($old['id'])->push_token)->toBeNull()
        ->and(AppDevice::find($new['id'])->push_token)->toBe(str_repeat('ab', 32));
});

it('beginnt beim ersten Lauf ab jetzt und benachrichtigt danach nur bei neuen wichtigen Aenderungen', function () {
    Queue::fake();
    config(['services.passolution.internal_token' => 'test-token', 'services.passolution.internal_api_url' => 'https://pds-internal.test/api/v2']);

    $device = AppDevice::create(['platform' => 'ios', 'push_token' => 'tok-1', 'secret_hash' => 'x', 'locale' => 'en']);
    $muted = AppDevice::create(['platform' => 'ios', 'push_token' => 'tok-2', 'secret_hash' => 'x', 'notifications_enabled' => false]);
    // Seit ueber 180 Tagen nicht gemeldet: kein Push (wie bei eigenen Pushs ueber die API)
    $stale = AppDevice::create(['platform' => 'ios', 'push_token' => 'tok-3', 'secret_hash' => 'x', 'last_seen_at' => now()->subDays(181)]);
    $this->travel(-2)->days();
    $link = AppDeviceTravelLink::create(['app_device_id' => $device->id, 'tid' => 'ZIPTHRG1Z6Y5']);
    AppDeviceTravelLink::create(['app_device_id' => $muted->id, 'tid' => 'ZIPTHRG1Z6Y5']);
    AppDeviceTravelLink::create(['app_device_id' => $stale->id, 'tid' => 'ZIPTHRG1Z6Y5']);
    AppDeviceTravelLink::create(['app_device_id' => $device->id, 'tid' => 'ALTEAENDERUN']);
    $this->travelBack();

    Http::fake(['pds-internal.test/*' => Http::response(['data' => [
        ['tid' => 'ZIPT-HRG1-Z6Y5', 'trip_name' => 'Mallorca 2027', 'last_important_change_at' => now()->subHour()->toIso8601String()],
        // Aenderung vor der Anmeldung des Links: kein Push
        ['tid' => 'ALTE-AEND-ERUN', 'trip_name' => 'Alt', 'last_important_change_at' => now()->subDays(5)->toIso8601String()],
        ['tid' => 'NIEM-ANDH-ATSI', 'trip_name' => 'Fremd', 'last_important_change_at' => now()->toIso8601String()],
    ], 'meta' => ['has_more' => false, 'next_cursor' => ['last_change_at' => now()->format('Y-m-d H:i:s'), 'id' => 99]]])]);

    $scanner = app(TravelChangeScanner::class);
    expect($scanner->run()['initialized'])->toBeTrue();
    Http::assertNothingSent();

    $stats = $scanner->run();
    expect($stats)->toMatchArray(['pages' => 1, 'links' => 3, 'notified' => 1]);

    Queue::assertPushed(SendPushBatch::class, fn (SendPushBatch $job) => $job->deviceIds === [$device->id]
        && $job->tidByDevice === [$device->id => 'ZIPTHRG1Z6Y5']
        && $job->tripNames === ['ZIPTHRG1Z6Y5' => 'Mallorca 2027']);

    $message = AppPushMessage::sole();
    expect($message->type)->toBe(AppPushMessage::TYPE_TRAVEL_IMPORTANT_CHANGE)
        ->and($message->targeted)->toBe(1)
        ->and($message->pending_batches)->toBe(1)
        ->and($link->fresh()->notified_change_at)->not->toBeNull()
        ->and(PdsGlobalSyncState::forKey(TravelChangeScanner::STATE_KEY)->cursor_id)->toBe(99);

    // Derselbe Feed noch einmal: keine doppelte Benachrichtigung
    expect($scanner->run()['notified'])->toBe(0);
    Queue::assertPushed(SendPushBatch::class, 1);
});

it('verschickt ueber APNs, vermerkt ungueltige Tokens und schliesst den Versand ab', function () {
    config(['push.apns.key' => apnsKey(), 'push.apns.key_id' => 'ABC123DEFG', 'push.apns.team_id' => 'PFC8Z32F99']);

    $ok = AppDevice::create(['platform' => 'ios', 'push_token' => 'gut', 'push_environment' => 'sandbox', 'secret_hash' => 'x', 'locale' => 'de']);
    $gone = AppDevice::create(['platform' => 'ios', 'push_token' => 'weg', 'secret_hash' => 'x', 'locale' => 'en']);

    Http::fake([
        'api.sandbox.push.apple.com/3/device/gut' => Http::response('', 200),
        'api.push.apple.com/3/device/weg' => Http::response(['reason' => 'Unregistered'], 410),
    ]);

    $dispatcher = app(PushDispatcher::class);
    $message = $dispatcher->begin([
        'type' => AppPushMessage::TYPE_TRAVEL_IMPORTANT_CHANGE,
        'source' => AppPushMessage::SOURCE_SCANNER,
        'title' => ['de' => 'Wichtige Änderung', 'en' => 'Important update'],
        'body' => ['de' => 'Neu für „{trip}“', 'en' => 'New for “{trip}”'],
    ]);
    $dispatcher->release($message);
    $message->update(['pending_batches' => 1, 'status' => AppPushMessage::STATUS_SENDING, 'finished_at' => null]);

    (new SendPushBatch($message->id, [$ok->id, $gone->id], [$ok->id => 'ZIPTHRG1Z6Y5'], ['ZIPTHRG1Z6Y5' => 'Mallorca 2027']))
        ->handle(app(PushSender::class));

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/3/device/gut')
        && $request->header('apns-topic')[0] === 'de.passolution.tripdata.app'
        && $request->header('apns-collapse-id')[0] === 'trip-ZIPTHRG1Z6Y5'
        && str_starts_with($request->header('authorization')[0], 'bearer ey')
        && $request['aps']['alert']['body'] === 'Neu für „Mallorca 2027“'
        && $request['url'] === 'tripdata://reise/ZIPTHRG1Z6Y5');

    $message->refresh();
    expect($message->only(['sent', 'failed', 'invalid', 'status']))->toBe(['sent' => 1, 'failed' => 0, 'invalid' => 1, 'status' => 'done'])
        ->and($gone->fresh()->token_invalid_at)->not->toBeNull()
        ->and($ok->fresh()->token_invalid_at)->toBeNull();
});

it('verschickt ueber FCM an Android-Geraete', function () {
    $rsa = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
    openssl_pkey_export($rsa, $pem);
    config(['push.fcm.credentials' => json_encode(['project_id' => 'tripdata-test', 'client_email' => 'push@tripdata-test.iam.gserviceaccount.com', 'private_key' => $pem])]);

    $device = AppDevice::create(['platform' => 'android', 'push_token' => 'fcm-token', 'secret_hash' => 'x', 'locale' => 'nl']);
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3600]),
        'fcm.googleapis.com/*' => Http::response(['name' => 'projects/tripdata-test/messages/1']),
    ]);

    $message = app(PushDispatcher::class)->begin([
        'type' => AppPushMessage::TYPE_CUSTOM,
        'source' => AppPushMessage::SOURCE_API,
        'title' => ['de' => 'Hallo', 'nl' => 'Hoi'],
        'body' => ['de' => 'Text'],
    ]);

    $stats = app(PushSender::class)->send($message, collect([$device]));

    expect($stats)->toBe(['sent' => 1, 'failed' => 0, 'invalid' => 0]);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'projects/tripdata-test/messages:send')
        && $request['message']['token'] === 'fcm-token'
        && $request['message']['notification']['title'] === 'Hoi'
        && $request['message']['notification']['body'] === 'Text'
        && $request->header('Authorization')[0] === 'Bearer ya29.test');
});

it('verschickt ueber die API nur mit push:send und verteilt auf Versand-Jobs', function () {
    Queue::fake();
    config(['push.batch_size' => 2]);

    $payload = ['title' => ['de' => 'Hinweis'], 'body' => 'Neue Funktion in TripData', 'target' => ['tids' => ['ZIPT-HRG1-Z6Y5']]];
    $this->withToken(appToken())->postJson('/api/v1/push/messages', $payload)->assertForbidden();
    app('auth')->forgetGuards();

    $token = appToken(['push:send']);
    $this->withToken($token)->postJson('/api/v1/push/messages', ['title' => 'x', 'body' => 'y', 'target' => []])->assertUnprocessable();

    $devices = collect(range(1, 3))->map(fn ($i) => AppDevice::create(['platform' => 'ios', 'push_token' => 'tok-'.$i, 'secret_hash' => 'x']));
    $devices->each(fn ($device) => AppDeviceTravelLink::create(['app_device_id' => $device->id, 'tid' => 'ZIPTHRG1Z6Y5']));
    AppDevice::create(['platform' => 'ios', 'push_token' => 'ohne-link', 'secret_hash' => 'x']);

    $id = $this->withToken($token)->postJson('/api/v1/push/messages', $payload)
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'sending')
        ->json('data.id');

    Queue::assertPushed(FanOutPushMessage::class, fn ($job) => $job->messageId === $id);
    (new FanOutPushMessage($id))->handle(app(PushDispatcher::class));

    Queue::assertPushed(SendPushBatch::class, 2);
    expect(AppPushMessage::find($id)->only(['targeted', 'pending_batches', 'title', 'body']))->toBe([
        'targeted' => 3,
        'pending_batches' => 2,
        'title' => ['de' => 'Hinweis'],
        'body' => ['de' => 'Neue Funktion in TripData'],
    ]);

    $this->withToken($token)->getJson('/api/v1/push/messages/'.$id)->assertOk()->assertJsonPath('data.targeted', 3);
    app('auth')->forgetGuards();
    $this->withToken(appToken(['push:send']))->getJson('/api/v1/push/messages/'.$id)->assertNotFound();
});

it('entfernt lange inaktive Geraete samt ihren Reise-Links', function () {
    $old = AppDevice::create(['platform' => 'ios', 'push_token' => 'alt', 'secret_hash' => 'x', 'last_seen_at' => now()->subDays(181)]);
    $active = AppDevice::create(['platform' => 'ios', 'push_token' => 'neu', 'secret_hash' => 'x', 'last_seen_at' => now()->subDays(5)]);
    AppDeviceTravelLink::create(['app_device_id' => $old->id, 'tid' => 'ZIPTHRG1Z6Y5']);
    AppDeviceTravelLink::create(['app_device_id' => $active->id, 'tid' => 'ZIPTHRG1Z6Y5']);

    $this->artisan('push:prune-devices')->assertSuccessful();

    expect(AppDevice::pluck('id')->all())->toBe([$active->id])
        ->and(AppDeviceTravelLink::count())->toBe(1);
});
