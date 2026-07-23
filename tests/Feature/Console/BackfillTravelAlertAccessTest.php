<?php

use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\TravelAlertOrder;

function travelAlertOrderFor(string $email): TravelAlertOrder
{
    return TravelAlertOrder::create([
        'customer_type' => 'business',
        'company' => 'Reisebuero Muster GmbH',
        'email' => $email,
        'phone' => '0123456789',
        'street' => 'Musterweg 1',
        'postal_code' => '12345',
        'city' => 'Musterstadt',
        'country' => 'Deutschland',
        'existing_billing' => 'nein',
    ]);
}

test('Kunde mit Bestellung wird freigeschaltet', function () {
    $customer = Customer::factory()->create(['email' => 'besteller@example.com']);
    travelAlertOrderFor('besteller@example.com');

    $this->artisan('travel-alert:backfill-access')->assertSuccessful();

    $override = CustomerFeatureOverride::where('customer_id', $customer->id)->first();
    expect($override)->not->toBeNull();
    expect($override->navigation_risk_overview_enabled)->toBeTrue();
});

test('Kunde ohne Bestellung wird nicht angefasst', function () {
    $customer = Customer::factory()->create(['email' => 'ohne-bestellung@example.com']);
    travelAlertOrderFor('jemand-anders@example.com');

    $this->artisan('travel-alert:backfill-access')->assertSuccessful();

    expect(CustomerFeatureOverride::where('customer_id', $customer->id)->exists())->toBeFalse();
});

test('im Admin gesetztes Deaktiviert wird nicht ueberschrieben', function () {
    $customer = Customer::factory()->create(['email' => 'gesperrt@example.com']);
    travelAlertOrderFor('gesperrt@example.com');

    CustomerFeatureOverride::create([
        'customer_id' => $customer->id,
        'navigation_risk_overview_enabled' => false,
    ]);

    $this->artisan('travel-alert:backfill-access')->assertSuccessful();

    $override = CustomerFeatureOverride::where('customer_id', $customer->id)->first();
    expect($override->navigation_risk_overview_enabled)->toBeFalse();
});

test('bestehende Override-Zeile mit NULL wird ergaenzt, andere Flags bleiben', function () {
    $customer = Customer::factory()->create(['email' => 'teiloverride@example.com']);
    travelAlertOrderFor('teiloverride@example.com');

    // Nur ein anderes Feature gesetzt -> Travel Alert steht auf NULL und faellt
    // damit auf die .env zurueck. Genau dieser Fall wuerde beim Umschalten den
    // Zugang verlieren.
    CustomerFeatureOverride::create([
        'customer_id' => $customer->id,
        'navigation_booking_enabled' => true,
    ]);

    $this->artisan('travel-alert:backfill-access')->assertSuccessful();

    $override = CustomerFeatureOverride::where('customer_id', $customer->id)->first();
    expect($override->navigation_risk_overview_enabled)->toBeTrue();
    expect($override->navigation_booking_enabled)->toBeTrue();
    expect(CustomerFeatureOverride::where('customer_id', $customer->id)->count())->toBe(1);
});

test('E-Mail-Abgleich ignoriert Gross- und Kleinschreibung', function () {
    $customer = Customer::factory()->create(['email' => 'misch@example.com']);
    travelAlertOrderFor('Misch@Example.COM');

    $this->artisan('travel-alert:backfill-access')->assertSuccessful();

    $override = CustomerFeatureOverride::where('customer_id', $customer->id)->first();
    expect($override?->navigation_risk_overview_enabled)->toBeTrue();
});

test('dry-run schreibt nichts', function () {
    $customer = Customer::factory()->create(['email' => 'trocken@example.com']);
    travelAlertOrderFor('trocken@example.com');

    $this->artisan('travel-alert:backfill-access', ['--dry-run' => true])->assertSuccessful();

    expect(CustomerFeatureOverride::where('customer_id', $customer->id)->exists())->toBeFalse();
});

test('ohne Bestellungen passiert nichts', function () {
    Customer::factory()->create(['email' => 'egal@example.com']);

    $this->artisan('travel-alert:backfill-access')->assertSuccessful();

    expect(CustomerFeatureOverride::count())->toBe(0);
});
