<?php

use App\Models\Customer;
use App\Models\Push\AppInvite;
use Illuminate\Support\Facades\Queue;

/**
 * Einladungslinks der TripData-App zu Reisen und Gruppen: anlegen, Webseite, ansehen, annehmen, Ablauf.
 */
function inviteToken(): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('App', ['gtm:read'])->plainTextToken;
}

function inviteDevice(object $test, string $token, string $name): array
{
    $data = $test->withToken($token)->postJson('/api/v1/devices', ['platform' => 'ios', 'push_token' => fake()->sha256()])->json('data');
    $test->withToken($token)->withHeader('X-Device-Secret', $data['secret'])->putJson('/api/v1/devices/'.$data['id'], ['display_name' => $name]);

    return $data;
}

function asInviteDevice(object $test, string $token, array $device): object
{
    return $test->withToken($token)->withHeader('X-Device-Secret', $device['secret']);
}

beforeEach(fn () => Queue::fake());

it('laedt per Link zu einer Reise ein – Webseite ohne App, Annahme in der App', function () {
    $token = inviteToken();
    [$anna, $ben] = [inviteDevice($this, $token, 'Anna'), inviteDevice($this, $token, 'Ben')];
    $base = fn ($d) => '/api/v1/devices/'.$d['id'];

    asInviteDevice($this, $token, $anna)->putJson($base($anna).'/trips/t1', ['name' => 'Peru 2027', 'start_date' => '2027-03-01', 'end_date' => '2027-03-20', 'country_codes' => ['PE']]);
    $link = asInviteDevice($this, $token, $anna)->postJson($base($anna).'/trips/t1/invite')->assertOk()->json('data');
    expect($link['url'])->toEndWith('/einladung/'.$link['code'])->and($link['code'])->toMatch('/^[2-9A-HJKMNP-TV-Z]{4}-[2-9A-HJKMNP-TV-Z]{4}$/');

    // Gleicher Link beim zweiten Mal, neuer mit renew
    expect(asInviteDevice($this, $token, $anna)->postJson($base($anna).'/trips/t1/invite')->json('data.code'))->toBe($link['code']);

    // Webseite (ohne App)
    $this->get('/einladung/'.$link['code'])->assertOk()
        ->assertSee('Anna möchte die Reise „Peru 2027“ mit dir teilen', false)
        ->assertSee('Peru')
        ->assertSee($link['code'])
        ->assertSee('apps.apple.com', false)
        ->assertSee('play.google.com', false);
    $this->get('/einladung/2222-2222')->assertOk()->assertSee('abgelaufen oder ungültig');

    // In der App: ansehen und annehmen
    asInviteDevice($this, $token, $ben)->getJson($base($ben).'/invites/'.strtolower(str_replace('-', '', $link['code'])))
        ->assertOk()->assertJsonPath('data.kind', 'trip')->assertJsonPath('data.from', 'Anna')->assertJsonPath('data.already', false);
    asInviteDevice($this, $token, $ben)->postJson($base($ben).'/invites/'.$link['code'].'/accept')->assertOk()
        ->assertJsonPath('data.kind', 'trip')
        ->assertJsonPath('data.received_trips.0.name', 'Peru 2027')
        ->assertJsonCount(0, 'data.trip_invitations');
    asInviteDevice($this, $token, $ben)->getJson($base($ben).'/invites/'.$link['code'])->assertJsonPath('data.already', true);
    asInviteDevice($this, $token, $anna)->postJson($base($anna).'/invites/'.$link['code'].'/accept')->assertUnprocessable();
    expect(AppInvite::sole()->uses)->toBe(1);
});

it('laedt per Link in eine Gruppe ein – nur der Ersteller erzeugt Links, abgelaufene gelten nicht', function () {
    $token = inviteToken();
    [$anna, $ben, $cara] = [inviteDevice($this, $token, 'Anna'), inviteDevice($this, $token, 'Ben'), inviteDevice($this, $token, 'Cara')];
    $base = fn ($d) => '/api/v1/devices/'.$d['id'];

    $group = asInviteDevice($this, $token, $anna)->postJson($base($anna).'/groups', ['name' => 'Familie'])->json('data.id');
    $code = asInviteDevice($this, $token, $anna)->postJson($base($anna)."/groups/{$group}/invite")->assertOk()->json('data.code');
    asInviteDevice($this, $token, $ben)->postJson($base($ben)."/groups/{$group}/invite")->assertForbidden();

    $this->get('/einladung/'.$code)->assertOk()->assertSee('Anna lädt dich in die Gruppe „Familie“ ein', false);

    asInviteDevice($this, $token, $ben)->postJson($base($ben).'/invites/'.$code.'/accept')->assertOk()
        ->assertJsonPath('data.kind', 'group')
        ->assertJsonPath('data.groups.0.status', 'accepted')
        ->assertJsonCount(2, 'data.groups.0.members');

    // Neuer Link macht den alten ungueltig; abgelaufene Links gelten nicht
    $renewed = asInviteDevice($this, $token, $anna)->postJson($base($anna)."/groups/{$group}/invite", ['renew' => true])->json('data.code');
    expect($renewed)->not->toBe($code);
    asInviteDevice($this, $token, $cara)->getJson($base($cara).'/invites/'.$code)->assertNotFound();
    AppInvite::query()->update(['expires_at' => now()->subDay()]);
    asInviteDevice($this, $token, $cara)->postJson($base($cara).'/invites/'.$renewed.'/accept')->assertNotFound();
});

it('erlaubt der App die Einladungslinks', function () {
    $this->getJson('/.well-known/apple-app-site-association')->assertOk()
        ->assertJsonFragment(['/' => '/einladung/*']);
});
