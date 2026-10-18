<?php

use App\Jobs\Push\FanOutPushMessage;
use App\Jobs\Push\SendPushBatch;
use App\Livewire\AdminV2\System\PushMessages;
use App\Models\Push\AppDevice;
use App\Models\Push\AppDeviceTravelLink;
use App\Models\Push\AppPushMessage;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * System > Push senden: eigene Push-Benachrichtigung an Geraete mit bestimmten Reisen oder an alle.
 */
function pushAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function pushDevice(array $tids = [], array $attributes = []): AppDevice
{
    $device = AppDevice::create(array_merge(['platform' => 'ios', 'push_token' => fake()->sha256(), 'secret_hash' => 'x'], $attributes));
    foreach ($tids as $tid) {
        AppDeviceTravelLink::create(['app_device_id' => $device->id, 'tid' => $tid]);
    }

    return $device;
}

it('zeigt die Seite nur Admins und steht im Menue unter System', function () {
    $this->get(route('adminv2.system.push'))->assertRedirect();

    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]))
        ->get(route('adminv2.system.push'))->assertForbidden();

    $this->actingAs(pushAdmin())->get(route('adminv2.system.push'))
        ->assertOk()
        ->assertSee('Push senden')
        ->assertSee('Noch keine Push-Benachrichtigungen verschickt.');
});

it('liest Reise-IDs auch aus Links und zaehlt nur erreichbare Geraete', function () {
    pushDevice(['ZIPTHRG1Z6Y5']);
    pushDevice(['ZIPTHRG1Z6Y5', 'ABCDEFGHJKLM']);
    pushDevice(['ABCDEFGHJKLM']);
    pushDevice(['ZIPTHRG1Z6Y5'], ['notifications_enabled' => false]);
    pushDevice(['ZIPTHRG1Z6Y5'], ['token_invalid_at' => now()]);
    pushDevice(['ZIPTHRG1Z6Y5'], ['last_seen_at' => now()->subDays(200)]);

    $this->actingAs(pushAdmin());

    Livewire::test(PushMessages::class)
        ->set('tidsInput', "zipt-hrg1-z6y5\nhttps://travel-details.eu/de?tid=ZIPT-HRG1-Z6Y5, kaputt")
        ->assertSet('parsedTids.valid', ['ZIPTHRG1Z6Y5'])
        ->assertSet('parsedTids.invalid', ['kaputt'])
        ->assertSet('reach', 2)
        ->set('tidsInput', 'https://platform.passolution.de/reise/ABCDEFGHJKLM ZIPTHRG1Z6Y5')
        ->assertSet('reach', 3)
        ->set('target', PushMessages::TARGET_ALL)
        ->assertSet('reach', 3);
});

it('sendet an die Geraete der Reisen – jeder Push traegt die passende Reise', function () {
    Queue::fake();
    $a = pushDevice(['ZIPTHRG1Z6Y5'], ['locale' => 'en']);
    $b = pushDevice(['ABCDEFGHJKLM']);
    pushDevice(['MNOPQRSTUVWX']);
    $admin = pushAdmin();
    $this->actingAs($admin);

    Livewire::test(PushMessages::class)
        ->set('tidsInput', 'ZIPT-HRG1-Z6Y5, ABCD-EFGH-JKLM')
        ->set('title.de', 'Hinweis zu deiner Reise')
        ->set('body.de', 'Der Transfer startet 30 Minuten später.')
        ->set('title.en', 'Note about your trip')
        ->set('body.en', 'The transfer starts 30 minutes later.')
        ->set('title.nl', 'Nur Titel ohne Text')
        ->call('send')
        ->assertHasNoErrors()
        ->assertDispatched('adminv2-toast')
        ->assertSet('tidsInput', '')
        ->assertSet('title.de', '');

    $message = AppPushMessage::sole();
    expect($message->source)->toBe(AppPushMessage::SOURCE_ADMIN)
        ->and($message->user_id)->toBe($admin->id)
        ->and($message->title)->toBe(['de' => 'Hinweis zu deiner Reise', 'en' => 'Note about your trip'])
        ->and($message->target)->toBe(['tids' => ['ZIPTHRG1Z6Y5', 'ABCDEFGHJKLM']]);

    Queue::assertPushed(FanOutPushMessage::class, fn ($job) => $job->messageId === $message->id);

    // Verteilen: nur die beiden Geraete, jedes mit seiner Reise
    (new FanOutPushMessage($message->id))->handle(app(\App\Services\Push\PushDispatcher::class));
    Queue::assertPushed(SendPushBatch::class, fn (SendPushBatch $job) => collect($job->deviceIds)->sort()->values()->all() === collect([$a->id, $b->id])->sort()->values()->all()
        && $job->tidByDevice[$a->id] === 'ZIPTHRG1Z6Y5'
        && $job->tidByDevice[$b->id] === 'ABCDEFGHJKLM');
    expect($message->fresh()->targeted)->toBe(2);
});

it('sendet nichts ohne deutschen Text, ohne gueltige Reise oder ohne Bestaetigung bei allen Geraeten', function () {
    Queue::fake();
    pushDevice(['ZIPTHRG1Z6Y5']);
    $this->actingAs(pushAdmin());

    Livewire::test(PushMessages::class)
        ->set('tidsInput', 'ZIPTHRG1Z6Y5')
        ->call('send')
        ->assertHasErrors(['title.de', 'body.de'])
        ->set('title.de', 'Titel')
        ->set('body.de', 'Text')
        ->set('tidsInput', 'NIEMANDHATDI')
        ->call('send')
        ->assertHasErrors('target')
        ->set('tidsInput', 'kaputt')
        ->call('send')
        ->assertHasErrors('tidsInput')
        ->set('target', PushMessages::TARGET_ALL)
        ->call('send')
        ->assertHasErrors('confirmAll')
        ->set('confirmAll', true)
        ->call('send')
        ->assertHasNoErrors();

    expect(AppPushMessage::sole()->target)->toBe(['all' => true]);
    Queue::assertPushed(FanOutPushMessage::class, 1);
});
