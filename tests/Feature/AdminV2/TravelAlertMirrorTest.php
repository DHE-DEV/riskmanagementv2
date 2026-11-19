<?php

use App\Livewire\AdminV2\System\TravelAlertMirror;
use App\Models\Customer;
use App\Models\TravelAlertPdsAccount;
use App\Models\TravelAlertPdsTrip;
use App\Models\User;
use Livewire\Livewire;

/**
 * Admin v2 > System > Travel-Alert-Spiegel: Übersicht der lokal gespiegelten pds-Konten.
 */
function mirrorAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

it('zeigt die Seite nur Admins', function () {
    $this->get(route('adminv2.system.travel-alert-mirror'))->assertRedirect();

    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]))
        ->get(route('adminv2.system.travel-alert-mirror'))->assertForbidden();

    $this->actingAs(mirrorAdmin())->get(route('adminv2.system.travel-alert-mirror'))
        ->assertOk()
        ->assertSee('Travel-Alert-Spiegel')
        ->assertSee('Noch keine Konten gespiegelt.');
});

it('listet gespiegelte Konten mit Kunden, Reisen und Status', function () {
    Customer::factory()->create(['pds_account_id' => 3181, 'company_name' => 'Reisen mit Freude GmbH']);
    TravelAlertPdsAccount::create(['pds_account_id' => 3181, 'last_requested_at' => now(), 'full_synced_at' => now(), 'synced_from' => now()]);
    TravelAlertPdsAccount::create(['pds_account_id' => 4711, 'last_requested_at' => now()]);
    TravelAlertPdsAccount::create(['pds_account_id' => 5000, 'last_requested_at' => now(), 'full_synced_at' => now()->subDays(3), 'synced_from' => now()->subDays(3)]);
    TravelAlertPdsTrip::create(['pds_account_id' => 3181, 'tid' => 'A1', 'payload' => []]);
    TravelAlertPdsTrip::create(['pds_account_id' => 3181, 'tid' => 'A2', 'payload' => []]);

    $this->actingAs(mirrorAdmin());

    Livewire::test(TravelAlertMirror::class)
        ->assertSeeInOrder(['3181', 'Reisen mit Freude GmbH', '2', 'Aktiv'])
        ->assertSee('Wartet auf Vollabruf')
        ->assertSee('Veraltet')
        ->assertSet('summary.synced', 2)
        ->set('search', 'freude')
        ->assertSee('3181')
        ->assertDontSee('4711');
});
