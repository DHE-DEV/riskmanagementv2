<?php

use App\Models\Customer;
use App\Models\Push\AppSharedList;
use Illuminate\Support\Facades\Queue;

/**
 * Geteilte Packliste einer geteilten Reise: gemeinsame Liste, Rechte je Person, Zusammenfuehren von Aenderungen.
 */
function listToken(): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('App', ['gtm:read'])->plainTextToken;
}

function listDevice(object $test, string $token, string $name): array
{
    $data = $test->withToken($token)->postJson('/api/v1/devices', ['platform' => 'ios', 'push_token' => fake()->sha256()])->json('data');
    $test->withToken($token)->withHeader('X-Device-Secret', $data['secret'])->putJson('/api/v1/devices/'.$data['id'], ['display_name' => $name]);

    return $data;
}

function listAs(object $test, string $token, array $device): object
{
    return $test->withToken($token)->withHeader('X-Device-Secret', $device['secret']);
}

function item(string $id, string $name, array $extra = []): array
{
    return array_merge(['id' => $id, 'name' => $name, 'category' => 'clothing', 'quantity' => 1, 'packed' => false, 'packed_at' => 0, 'updated_at' => 1000], $extra);
}

/** Vater teilt die Reise mit Mutter und Kind (beide angenommen) und die Packliste; Vorgabe: abhaken. */
function familyTrip(object $test): array
{
    $token = listToken();
    [$papa, $mama, $kind] = [listDevice($test, $token, 'Papa'), listDevice($test, $token, 'Mama'), listDevice($test, $token, 'Kind')];
    $base = fn ($d) => '/api/v1/devices/'.$d['id'];

    listAs($test, $token, $papa)->putJson($base($papa).'/trips/t1', [
        'name' => 'Sommer', 'country_codes' => ['IT'], 'codes' => [$mama['share_code'], $kind['share_code']],
    ])->assertOk();
    foreach ([$mama, $kind] as $d) {
        $id = listAs($test, $token, $d)->getJson($base($d).'/sharing')->json('data.trip_invitations.0.id');
        listAs($test, $token, $d)->postJson($base($d)."/shared-trips/{$id}/accept")->assertOk();
    }

    listAs($test, $token, $papa)->putJson($base($papa).'/trips/t1/list', [
        'client_list_id' => 'l1', 'name' => 'Packliste Sommer',
        'suitcases' => [['id' => 's1', 'name' => 'Großer Koffer', 'color' => '2563eb', 'assignee' => $mama['share_code'], 'updated_at' => 1000]],
        'items' => [item('i1', 'Sonnencreme'), item('i2', 'Badehose')],
        'default_role' => 'check',
        'roles' => [$mama['share_code'] => 'manage'],
    ])->assertOk()
        ->assertJsonPath('data.owned_trips.0.list.my_role', 'owner')
        ->assertJsonPath('data.owned_trips.0.participants.1.list_role', 'check')
        ->assertJsonPath('data.owned_trips.0.list.suitcases.0.assignee_name', 'Mama');

    return [$token, $papa, $mama, $kind, $base];
}

beforeEach(fn () => Queue::fake());

it('zeigt die Packliste allen Mitreisenden mit ihrer Rolle', function () {
    [$token, $papa, $mama, $kind, $base] = familyTrip($this);

    listAs($this, $token, $mama)->getJson($base($mama).'/sharing')
        ->assertJsonPath('data.received_trips.0.list.name', 'Packliste Sommer')
        ->assertJsonPath('data.received_trips.0.list.my_role', 'manage')
        ->assertJsonPath('data.received_trips.0.list.suitcases.0.assignee_code', $mama['share_code'])
        ->assertJsonCount(3, 'data.received_trips.0.participants');
    listAs($this, $token, $kind)->getJson($base($kind).'/sharing')->assertJsonPath('data.received_trips.0.list.my_role', 'check');
});

it('laesst Kinder nur abhaken, bis die Eltern mehr freigeben', function () {
    [$token, $papa, $mama, $kind, $base] = familyTrip($this);
    $list = AppSharedList::sole();

    // Kind: neuer Gegenstand und Umbenennen werden ignoriert, Abhaken zaehlt.
    listAs($this, $token, $kind)->putJson($base($kind)."/shared-lists/{$list->id}", [
        'items' => [item('i3', 'Spielzeug', ['updated_at' => 2000]), item('i1', 'Umbenannt', ['updated_at' => 2000, 'packed' => true, 'packed_at' => 2000])],
    ])->assertOk();
    $items = collect(AppSharedList::sole()->items)->keyBy('id');
    expect($items)->toHaveCount(2)
        ->and($items['i1']['name'])->toBe('Sonnencreme')
        ->and($items['i1']['packed'])->toBeTrue();

    // Mama (verwalten) gibt dem Kind „bearbeiten“ – jetzt darf es anlegen.
    listAs($this, $token, $mama)->putJson($base($mama)."/shared-lists/{$list->id}/roles", ['roles' => [$kind['share_code'] => 'edit']])->assertOk();
    listAs($this, $token, $kind)->putJson($base($kind)."/shared-lists/{$list->id}", ['items' => [item('i3', 'Spielzeug', ['updated_at' => 3000])]])->assertOk()
        ->assertJsonCount(3, 'data.received_trips.0.list.items');

    // Kind darf keine Rechte vergeben; „nur ansehen“ darf gar nichts aendern.
    listAs($this, $token, $kind)->putJson($base($kind)."/shared-lists/{$list->id}/roles", ['default_role' => 'edit'])->assertForbidden();
    listAs($this, $token, $papa)->putJson($base($papa).'/trips/t1/list', ['client_list_id' => 'l1', 'name' => 'Packliste Sommer', 'roles' => [$kind['share_code'] => 'view']])->assertOk();
    listAs($this, $token, $kind)->putJson($base($kind)."/shared-lists/{$list->id}", ['items' => [item('i2', 'Badehose', ['packed' => true, 'packed_at' => 9000])]])->assertForbidden();
});

it('fuehrt gleichzeitige Aenderungen je Eintrag zusammen', function () {
    [$token, $papa, $mama, $kind, $base] = familyTrip($this);
    $list = AppSharedList::sole();

    // Papa benennt um (aelterer Haken), Kind hakt ab – beides bleibt.
    listAs($this, $token, $kind)->putJson($base($kind)."/shared-lists/{$list->id}", ['items' => [item('i2', 'Badehose', ['packed' => true, 'packed_at' => 5000])]]);
    listAs($this, $token, $papa)->putJson($base($papa).'/trips/t1/list', ['client_list_id' => 'l1', 'name' => 'Packliste Sommer', 'items' => [item('i2', 'Badehose (2x)', ['updated_at' => 6000, 'packed' => false, 'packed_at' => 0])]]);
    $i2 = collect(AppSharedList::sole()->items)->firstWhere('id', 'i2');
    expect($i2['name'])->toBe('Badehose (2x)')->and($i2['packed'])->toBeTrue();

    // Loeschen bleibt als Kennzeichen stehen; ein aelterer Stand holt den Eintrag nicht zurueck.
    listAs($this, $token, $mama)->putJson($base($mama)."/shared-lists/{$list->id}", ['items' => [item('i1', 'Sonnencreme', ['updated_at' => 7000, 'deleted' => true])]]);
    listAs($this, $token, $papa)->putJson($base($papa).'/trips/t1/list', ['client_list_id' => 'l1', 'name' => 'Packliste Sommer', 'items' => [item('i1', 'Sonnencreme', ['updated_at' => 1000])]]);
    expect(collect(AppSharedList::sole()->items)->firstWhere('id', 'i1')['deleted'])->toBeTrue()
        ->and(AppSharedList::sole()->revision)->toBeGreaterThan(1);
});

it('beendet das Teilen der Packliste und verbirgt sie vor Fremden', function () {
    [$token, $papa, $mama, $kind, $base] = familyTrip($this);
    $list = AppSharedList::sole();
    $other = listDevice($this, $token, 'Fremd');

    listAs($this, $token, $other)->putJson($base($other)."/shared-lists/{$list->id}", ['items' => []])->assertNotFound();
    listAs($this, $token, $papa)->deleteJson($base($papa).'/trips/t1/list')->assertOk()->assertJsonPath('data.owned_trips.0.list', null);
    listAs($this, $token, $mama)->getJson($base($mama).'/sharing')->assertJsonPath('data.received_trips.0.list', null);
});
