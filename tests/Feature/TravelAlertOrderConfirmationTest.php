<?php

/**
 * Double-Opt-in fuer Travel Alert-Bestellungen.
 *
 * Der Zugang darf erst nach der Bestaetigung durch den Kunden entstehen,
 * und je nach TRAVEL_ALERT_AUTO_ACTIVATION entweder sofort oder erst nach
 * der Freigabe durch einen Mitarbeiter.
 */

use App\Mail\TravelAlertAccessActivatedMail;
use App\Mail\TravelAlertOrderConfirmationMail;
use App\Mail\TravelAlertOrderMail;
use App\Mail\TravelAlertOrderPendingApprovalMail;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\TravelAlertOrder;
use App\Services\CustomerFeatureService;
use App\Services\TravelAlertOrderService;
use Illuminate\Support\Facades\Mail;

function orderPayload(array $overrides = []): array
{
    return array_merge([
        'customer_type' => 'business',
        'business_type' => ['travel_agency'],
        'company' => 'Reisebuero Muster GmbH',
        'first_name' => 'Erika',
        'last_name' => 'Muster',
        'email' => 'neu@example.com',
        'phone' => '0123456789',
        'street' => 'Musterweg 1',
        'postal_code' => '12345',
        'city' => 'Musterstadt',
        'country' => 'Deutschland',
        'existing_billing' => 'nein',
    ], $overrides);
}

function hasTravelAlert(Customer $customer): bool
{
    app(CustomerFeatureService::class)->clearCache($customer->id);

    return app(CustomerFeatureService::class)
        ->isFeatureEnabled('navigation_risk_overview_enabled', $customer->fresh());
}

beforeEach(function () {
    Mail::fake();
    // Ohne Override entscheidet die .env – auf false, damit der Test wirklich
    // die Freischaltung pro Kunde prueft und nicht den globalen Default.
    config(['app.navigation_risk_overview_enabled' => false]);
});

test('die Bestellung schaltet den Zugang noch nicht frei', function () {
    $this->postJson('/travel-alert/order', orderPayload())
        ->assertOk()
        ->assertJson(['success' => true, 'confirmation_required' => true]);

    $order = TravelAlertOrder::firstOrFail();
    $customer = Customer::where('email', 'neu@example.com')->firstOrFail();

    expect($order->status)->toBe(TravelAlertOrder::STATUS_PENDING_CONFIRMATION)
        ->and($order->confirmation_token)->not->toBeEmpty()
        ->and($order->customer_id)->toBe($customer->id)
        ->and(hasTravelAlert($customer))->toBeFalse();

    Mail::assertSent(TravelAlertOrderConfirmationMail::class);
    Mail::assertSent(TravelAlertOrderMail::class);
    Mail::assertNotSent(TravelAlertAccessActivatedMail::class);
});

test('Bestandskunden bekommen ebenfalls eine Bestaetigungsmail', function () {
    $customer = Customer::factory()->create(['email' => 'bestand@example.com']);

    $this->postJson('/travel-alert/order', orderPayload(['email' => 'bestand@example.com']))
        ->assertOk()
        ->assertJson(['account_created' => false]);

    expect(hasTravelAlert($customer))->toBeFalse();

    Mail::assertSent(TravelAlertOrderConfirmationMail::class);
});

test('mit Auto-Freischaltung ist der Zugang nach der Bestaetigung sofort da', function () {
    config(['app.travel_alert_auto_activation' => true]);

    $this->postJson('/travel-alert/order', orderPayload())->assertOk();

    $order = TravelAlertOrder::firstOrFail();

    $this->get("/travel-alert/order/confirm/{$order->confirmation_token}")
        ->assertOk()
        ->assertSee('Bestellung bestätigt');

    $customer = Customer::where('email', 'neu@example.com')->firstOrFail();

    expect($order->fresh()->status)->toBe(TravelAlertOrder::STATUS_ACTIVE)
        ->and(hasTravelAlert($customer))->toBeTrue();

    Mail::assertSent(TravelAlertAccessActivatedMail::class);
    Mail::assertNotSent(TravelAlertOrderPendingApprovalMail::class);
});

test('ohne Auto-Freischaltung wartet die Bestellung auf einen Mitarbeiter', function () {
    config(['app.travel_alert_auto_activation' => false]);

    $this->postJson('/travel-alert/order', orderPayload())->assertOk();

    $order = TravelAlertOrder::firstOrFail();

    $this->get("/travel-alert/order/confirm/{$order->confirmation_token}")
        ->assertOk()
        ->assertSee('Ein Mitarbeiter prüft Ihre Bestellung', false);

    $customer = Customer::where('email', 'neu@example.com')->firstOrFail();

    expect($order->fresh()->status)->toBe(TravelAlertOrder::STATUS_PENDING_APPROVAL)
        ->and(hasTravelAlert($customer))->toBeFalse();

    Mail::assertSent(TravelAlertOrderPendingApprovalMail::class);
    Mail::assertNotSent(TravelAlertAccessActivatedMail::class);
});

test('der Mitarbeiter schaltet die wartende Bestellung frei', function () {
    config(['app.travel_alert_auto_activation' => false]);

    $this->postJson('/travel-alert/order', orderPayload())->assertOk();
    $order = TravelAlertOrder::firstOrFail();
    $this->get("/travel-alert/order/confirm/{$order->confirmation_token}");

    app(TravelAlertOrderService::class)->approve($order->fresh());

    $customer = Customer::where('email', 'neu@example.com')->firstOrFail();

    expect($order->fresh()->status)->toBe(TravelAlertOrder::STATUS_ACTIVE)
        ->and(hasTravelAlert($customer))->toBeTrue();

    Mail::assertSent(TravelAlertAccessActivatedMail::class);
});

test('die Bestaetigung markiert die E-Mail-Adresse als verifiziert', function () {
    $this->postJson('/travel-alert/order', orderPayload())->assertOk();

    $customer = Customer::where('email', 'neu@example.com')->firstOrFail();
    expect($customer->hasVerifiedEmail())->toBeFalse();

    $order = TravelAlertOrder::firstOrFail();
    $this->get("/travel-alert/order/confirm/{$order->confirmation_token}");

    expect($customer->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('ein zweiter Klick auf den Link aendert nichts', function () {
    $this->postJson('/travel-alert/order', orderPayload())->assertOk();
    $order = TravelAlertOrder::firstOrFail();

    $this->get("/travel-alert/order/confirm/{$order->confirmation_token}");
    $confirmedAt = $order->fresh()->confirmed_at;

    $this->get("/travel-alert/order/confirm/{$order->confirmation_token}")
        ->assertOk()
        ->assertSee('Bereits bestätigt');

    expect($order->fresh()->confirmed_at->equalTo($confirmedAt))->toBeTrue();
    Mail::assertSentCount(4); // Bestaetigung + intern (received) + aktiviert + intern (confirmed)
});

test('ein abgelaufener Link schaltet nichts frei', function () {
    config(['app.travel_alert_confirmation_expire_days' => 7]);

    $this->postJson('/travel-alert/order', orderPayload())->assertOk();

    $order = TravelAlertOrder::firstOrFail();
    $order->forceFill(['created_at' => now()->subDays(8)])->save();

    $this->get("/travel-alert/order/confirm/{$order->confirmation_token}")
        ->assertOk()
        ->assertSee('Link abgelaufen');

    $customer = Customer::where('email', 'neu@example.com')->firstOrFail();

    expect($order->fresh()->isConfirmed())->toBeFalse()
        ->and(hasTravelAlert($customer))->toBeFalse();
});

test('ein unbekannter Token liefert 404', function () {
    $this->get('/travel-alert/order/confirm/gibtesnicht')
        ->assertNotFound()
        ->assertSee('Link unbekannt');
});

test('eine Bestellung nimmt vorhandenen Zugang nicht weg', function () {
    config(['app.travel_alert_auto_activation' => false]);

    $customer = Customer::factory()->create(['email' => 'bestand@example.com']);
    CustomerFeatureOverride::create([
        'customer_id' => $customer->id,
        'navigation_risk_overview_enabled' => true,
    ]);

    $this->postJson('/travel-alert/order', orderPayload(['email' => 'bestand@example.com']))->assertOk();

    expect(hasTravelAlert($customer))->toBeTrue();

    $order = TravelAlertOrder::firstOrFail();
    $this->get("/travel-alert/order/confirm/{$order->confirmation_token}");

    expect(hasTravelAlert($customer))->toBeTrue();
});

test('eine abgelehnte Bestellung schaltet nichts frei', function () {
    $this->postJson('/travel-alert/order', orderPayload())->assertOk();
    $order = TravelAlertOrder::firstOrFail();

    app(TravelAlertOrderService::class)->reject($order, null);

    $this->get("/travel-alert/order/confirm/{$order->confirmation_token}")
        ->assertOk()
        ->assertSee('Bestellung nicht aktiv');

    $customer = Customer::where('email', 'neu@example.com')->firstOrFail();

    expect(hasTravelAlert($customer))->toBeFalse();
});
