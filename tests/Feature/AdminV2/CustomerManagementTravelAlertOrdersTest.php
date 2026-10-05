<?php

use App\Livewire\AdminV2\CustomerManagement\TravelAlertOrders\Index;
use App\Livewire\AdminV2\CustomerManagement\TravelAlertOrders\Show;
use App\Mail\TravelAlertAccessActivatedMail;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\TravelAlertOrder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * Kundenverwaltung > TravelAlert Bestellungen: Liste mit Suche, Statusfilter
 * und Sortierung, Einzelansicht, freischalten, ablehnen, Testversion, loeschen.
 */
function travelAlertOrderAdmin(): User
{
    return User::factory()->create(['name' => 'Anna Admin', 'is_admin' => true, 'is_active' => true]);
}

/**
 * Bestellung anlegen; Zeitstempel wie confirmed_at sind nicht "fillable".
 */
function travelAlertOrder(array $attributes = []): TravelAlertOrder
{
    $order = new TravelAlertOrder;

    $order->forceFill(array_merge([
        'customer_type' => 'business',
        'company' => 'Reisebüro Muster GmbH',
        'first_name' => 'Erika',
        'last_name' => 'Muster',
        'email' => 'erika@example.com',
        'phone' => '0123456789',
        'street' => 'Musterweg 1',
        'postal_code' => '12345',
        'city' => 'Musterstadt',
        'country' => 'Deutschland',
        'existing_billing' => 'nein',
    ], $attributes))->save();

    return $order;
}

beforeEach(function () {
    Mail::fake();
    Carbon::setTestNow('2026-10-01 09:00:00');
});

afterEach(fn () => Carbon::setTestNow());

it('zeigt die Bestellungen mit Suche, Statusfilter und Sortierung', function () {
    $this->actingAs(travelAlertOrderAdmin());

    travelAlertOrder(['company' => 'Alpha Reisen', 'email' => 'alpha@example.com', 'city' => 'Aachen', 'created_at' => '2026-09-01 10:00:00']);
    travelAlertOrder(['company' => 'Beta Touristik', 'first_name' => 'Bernd', 'last_name' => 'Bauer', 'email' => 'beta@example.com', 'city' => 'Berlin', 'confirmed_at' => now(), 'created_at' => '2026-09-02 10:00:00', 'existing_billing' => 'ja']);
    travelAlertOrder(['company' => 'Gamma Tours', 'email' => 'gamma@example.com', 'city' => 'Gera', 'confirmed_at' => now(), 'approved_at' => now(), 'created_at' => '2026-09-03 10:00:00', 'trial_expires_at' => '2026-09-15']);
    travelAlertOrder(['company' => 'Delta Travel', 'email' => 'delta@example.com', 'city' => 'Dresden', 'confirmed_at' => now(), 'rejected_at' => now(), 'created_at' => '2026-09-04 10:00:00']);

    $this->get(route('adminv2.customer-management.travel-alert-orders.index'))
        ->assertOk()
        ->assertSee('TravelAlert Bestellungen')
        ->assertSee('4 Bestellungen')
        // Vorgabe: neueste zuerst.
        ->assertSeeInOrder(['Delta Travel', 'Gamma Tours', 'Beta Touristik', 'Alpha Reisen'])
        ->assertSee('Wartet auf Freischaltung')
        ->assertSee('Bestehend')
        ->assertSee('15.09.2026');

    Livewire::test(Index::class)
        // Suche ueber Firma, Ansprechpartner, E-Mail und Stadt.
        ->set('search', 'Bauer')->assertSee('Beta Touristik')->assertDontSee('Alpha Reisen')
        ->set('search', 'gamma@')->assertSee('Gamma Tours')->assertDontSee('Beta Touristik')
        ->set('search', 'Dresden')->assertSee('Delta Travel')->assertDontSee('Gamma Tours')
        ->set('search', 'Alpha')->assertSee('Alpha Reisen')->assertDontSee('Delta Travel')
        // Jedes Wort darf in einer anderen Spalte stehen: Vor- und Nachname, Name und Stadt.
        ->set('search', 'Bernd Bauer')->assertSee('Beta Touristik')->assertDontSee('Alpha Reisen')
        ->set('search', 'Bauer Berlin')->assertSee('Beta Touristik')->assertDontSee('Alpha Reisen')
        ->set('search', 'Bauer Dresden')->assertDontSee('Beta Touristik')->assertDontSee('Delta Travel')
        ->set('search', '')
        // Statusfilter
        ->set('status', TravelAlertOrder::STATUS_PENDING_CONFIRMATION)->assertSee('Alpha Reisen')->assertDontSee('Beta Touristik')->assertDontSee('Gamma Tours')->assertDontSee('Delta Travel')
        ->set('status', TravelAlertOrder::STATUS_PENDING_APPROVAL)->assertSee('Beta Touristik')->assertDontSee('Alpha Reisen')->assertDontSee('Gamma Tours')->assertDontSee('Delta Travel')
        ->set('status', TravelAlertOrder::STATUS_ACTIVE)->assertSee('Gamma Tours')->assertDontSee('Alpha Reisen')->assertDontSee('Beta Touristik')->assertDontSee('Delta Travel')
        ->set('status', TravelAlertOrder::STATUS_REJECTED)->assertSee('Delta Travel')->assertDontSee('Alpha Reisen')->assertDontSee('Beta Touristik')->assertDontSee('Gamma Tours')
        ->assertSet('search', '')
        ->call('resetFilters')
        ->assertSet('status', '')
        // Sortierung
        ->set('sort', 'company')->set('direction', 'asc')
        ->assertSeeInOrder(['Alpha Reisen', 'Beta Touristik', 'Delta Travel', 'Gamma Tours'])
        ->call('toggleDirection')
        ->assertSeeInOrder(['Gamma Tours', 'Delta Travel', 'Beta Touristik', 'Alpha Reisen'])
        // Unbekannte Sortierspalten fallen auf die Vorgabe zurueck.
        ->set('sort', 'confirmation_token')
        ->assertSeeInOrder(['Delta Travel', 'Gamma Tours', 'Beta Touristik', 'Alpha Reisen']);
});

it('blaettert mit 25 Bestellungen je Seite', function () {
    $this->actingAs(travelAlertOrderAdmin());

    foreach (range(1, 27) as $number) {
        travelAlertOrder(['company' => 'Firma '.str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'created_at' => now()->subMinutes($number)]);
    }

    Livewire::test(Index::class)
        ->assertSee('Firma 01')->assertSee('Firma 25')->assertDontSee('Firma 26')
        ->assertSee('Seite 1 von 2')
        ->call('nextPage')
        ->assertSee('Firma 26')->assertSee('Firma 27')->assertDontSee('Firma 25');
});

it('schaltet eine bestaetigte Bestellung aus der Liste frei und lehnt ab', function () {
    $admin = travelAlertOrderAdmin();
    $this->actingAs($admin);

    $customer = Customer::factory()->create();
    $confirmed = travelAlertOrder(['customer_id' => $customer->id, 'confirmed_at' => now()]);
    $unconfirmed = travelAlertOrder(['company' => 'Unbestätigt AG', 'email' => 'offen@example.com']);

    $list = Livewire::test(Index::class)
        // Ohne Bestaetigung des Kunden keine Freischaltung.
        ->call('approve', $unconfirmed->id);

    expect($unconfirmed->fresh()->isApproved())->toBeFalse();
    Mail::assertNothingSent();

    $list->call('approve', $confirmed->id)->assertSee('Freigeschaltet');

    expect($confirmed->fresh()->status)->toBe(TravelAlertOrder::STATUS_ACTIVE)
        ->and($confirmed->fresh()->approved_by)->toBe($admin->id)
        ->and(CustomerFeatureOverride::where('customer_id', $customer->id)->value('navigation_risk_overview_enabled'))->toBeTruthy();
    Mail::assertSent(TravelAlertAccessActivatedMail::class, 1);

    // Schon freigeschaltet: ein zweiter Aufruf verschickt nichts noch einmal.
    $list->call('approve', $confirmed->id);
    Mail::assertSent(TravelAlertAccessActivatedMail::class, 1);

    // Ablehnen geht auch nach der Freischaltung und ohne Bestaetigung.
    $list->call('reject', $confirmed->id)->call('reject', $unconfirmed->id);

    expect($confirmed->fresh()->status)->toBe(TravelAlertOrder::STATUS_REJECTED)
        ->and($confirmed->fresh()->approved_at)->toBeNull()
        ->and($confirmed->fresh()->rejected_by)->toBe($admin->id)
        ->and($unconfirmed->fresh()->status)->toBe(TravelAlertOrder::STATUS_REJECTED);

    // Abgelehnte lassen sich nicht mehr freischalten.
    $list->call('approve', $confirmed->id);
    expect($confirmed->fresh()->isApproved())->toBeFalse();
});

it('meldet eine Freigabe ohne Kundenkonto nicht als Freischaltung', function () {
    $this->actingAs(travelAlertOrderAdmin());

    // Bestaetigt, aber das Kundenkonto gibt es nicht (mehr).
    $order = travelAlertOrder(['confirmed_at' => now()]);

    Livewire::test(Index::class)
        ->call('approve', $order->id)
        ->assertDispatched('adminv2-toast', message: 'Bestellung freigegeben – zu ihr gehört aber kein Kundenkonto mehr: Es wurde nichts freigeschaltet und keine E-Mail verschickt.', variant: 'danger');

    expect($order->fresh()->isApproved())->toBeTrue()
        ->and(CustomerFeatureOverride::count())->toBe(0);
    Mail::assertNothingSent();
});

it('zeigt nach dem Loeschen der letzten Seite die Seite davor', function () {
    $this->actingAs(travelAlertOrderAdmin());

    foreach (range(1, Index::PER_PAGE + 1) as $number) {
        travelAlertOrder(['company' => sprintf('Firma %02d', $number), 'email' => "firma{$number}@example.com", 'created_at' => now()->subMinutes($number)]);
    }

    $last = TravelAlertOrder::where('company', 'Firma 26')->first();

    Livewire::test(Index::class)
        ->call('nextPage')
        ->assertSee('Firma 26')
        ->set('selected', [(string) $last->id])
        ->call('deleteSelected')
        // Nicht die leere Seite 2 mit "Keine Bestellungen", sondern Seite 1.
        ->assertSee('Firma 01')
        ->assertSee('25 Bestellungen');
});

it('loescht die ausgewaehlten Bestellungen gemeinsam', function () {
    $this->actingAs(travelAlertOrderAdmin());

    $first = travelAlertOrder(['company' => 'Erste GmbH']);
    $second = travelAlertOrder(['company' => 'Zweite GmbH']);
    $third = travelAlertOrder(['company' => 'Dritte GmbH']);

    Livewire::test(Index::class)
        ->call('deleteSelected')
        ->assertSee('Erste GmbH')
        // Ganze Seite an- und wieder abwaehlen.
        ->call('togglePage')
        ->assertCount('selected', 3)
        ->call('togglePage')
        ->assertCount('selected', 0)
        ->set('selected', [(string) $first->id, (string) $third->id])
        ->assertSee('2 ausgewählt')
        ->call('deleteSelected')
        ->assertSet('selected', [])
        ->assertDontSee('Erste GmbH')
        ->assertDontSee('Dritte GmbH')
        ->assertSee('Zweite GmbH');

    expect(TravelAlertOrder::pluck('id')->all())->toBe([$second->id])
        // Die Tabelle hat einen Papierkorb – geloescht heisst dort abgelegt.
        ->and(TravelAlertOrder::onlyTrashed()->count())->toBe(2);
});

it('zeigt eine Bestellung mit allen Angaben und dem Kundenkonto', function () {
    $admin = travelAlertOrderAdmin();
    $this->actingAs($admin);

    $customer = Customer::factory()->create();
    $order = travelAlertOrder([
        'customer_id' => $customer->id,
        'remarks' => 'Bitte Rückruf am Vormittag.',
        'existing_billing' => 'ja',
        'confirmed_at' => '2026-09-20 08:15:00',
        'approved_at' => '2026-09-21 11:30:00',
        'approved_by' => $admin->id,
        'trial_expires_at' => '2026-10-11',
        'created_at' => '2026-09-19 17:45:10',
    ]);

    $this->get(route('adminv2.customer-management.travel-alert-orders.show', $order->id))
        ->assertOk()
        ->assertSee('Bestellung '.$order->id)
        ->assertSee('Reisebüro Muster GmbH')
        ->assertSee('Erika Muster')
        ->assertSee('erika@example.com')
        ->assertSee('0123456789')
        ->assertSee('Musterweg 1')
        ->assertSee('12345 Musterstadt')
        ->assertSee('Deutschland')
        ->assertSee('Freigeschaltet')
        ->assertSee('20.09.2026 08:15')
        ->assertSee('21.09.2026 11:30')
        ->assertSee('durch Anna Admin')
        ->assertSee('11.10.2026 (noch 10 Tage)')
        ->assertSee('Bitte Rückruf am Vormittag.')
        ->assertSee('19.09.2026 17:45:10')
        ->assertSee(route('adminv2.customer-management.customers.edit', $customer->id))
        // Freigeschaltet: nur noch ablehnen, nicht erneut freischalten.
        ->assertDontSee('wire:click="approve"', false)
        ->assertSee('wire:click="reject"', false);

    $this->get(route('adminv2.customer-management.travel-alert-orders.show', 999999))->assertNotFound();
});

it('schaltet in der Einzelansicht frei, lehnt ab, setzt die Testversion und loescht', function () {
    $admin = travelAlertOrderAdmin();
    $this->actingAs($admin);

    $customer = Customer::factory()->create();
    $order = travelAlertOrder(['customer_id' => $customer->id]);

    $page = Livewire::test(Show::class, ['order' => $order->id])
        ->assertSee('Wartet auf Bestätigung')
        ->assertSee('Nicht gesetzt')
        ->assertSee('Keine Bemerkung')
        // Noch nicht bestaetigt: die Schaltflaeche ist gesperrt, der Aufruf wirkungslos.
        ->assertDontSee('wire:click="approve"', false)
        ->call('approve');

    expect($order->fresh()->isApproved())->toBeFalse();

    $order->forceFill(['confirmed_at' => now()])->save();

    $page->call('approve')->assertSee('Freigeschaltet')->assertSee('durch Anna Admin');

    expect($order->fresh()->status)->toBe(TravelAlertOrder::STATUS_ACTIVE);
    Mail::assertSent(TravelAlertAccessActivatedMail::class, 1);

    // Ablauf der Testversion setzen, pruefen und wieder entfernen.
    $page->call('openTrialExpiry')
        ->assertSet('trialExpiresAt', '')
        ->set('trialExpiresAt', 'morgen')
        ->call('saveTrialExpiry')
        ->assertHasErrors('trialExpiresAt')
        ->set('trialExpiresAt', '2026-09-30')
        ->call('saveTrialExpiry')
        ->assertHasNoErrors()
        ->assertSee('30.09.2026 (abgelaufen)');

    expect($order->fresh()->trial_expires_at->format('Y-m-d'))->toBe('2026-09-30');

    $page->call('openTrialExpiry')
        ->assertSet('trialExpiresAt', '2026-09-30')
        ->set('trialExpiresAt', '')
        ->call('saveTrialExpiry')
        ->assertSee('Nicht gesetzt');

    expect($order->fresh()->trial_expires_at)->toBeNull();

    $page->call('reject')->assertSee('Abgelehnt')->assertDontSee('wire:click="reject"', false);

    expect($order->fresh()->status)->toBe(TravelAlertOrder::STATUS_REJECTED)
        ->and($order->fresh()->rejected_by)->toBe($admin->id);

    $page->call('delete')->assertRedirect(route('adminv2.customer-management.travel-alert-orders.index'));

    expect(TravelAlertOrder::count())->toBe(0)
        ->and(TravelAlertOrder::withTrashed()->count())->toBe(1);

    $this->get(route('adminv2.customer-management.travel-alert-orders.show', $order->id))->assertNotFound();
});

it('ist nur fuer Administratoren erreichbar', function () {
    $order = travelAlertOrder();

    $this->get(route('adminv2.customer-management.travel-alert-orders.index'))->assertRedirect();

    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]));

    $this->get(route('adminv2.customer-management.travel-alert-orders.index'))->assertForbidden();
    $this->get(route('adminv2.customer-management.travel-alert-orders.show', $order->id))->assertForbidden();

    Livewire::test(Index::class)->assertForbidden();
    Livewire::test(Show::class, ['order' => $order->id])->assertForbidden();

    expect($order->fresh()->trashed())->toBeFalse();
});
