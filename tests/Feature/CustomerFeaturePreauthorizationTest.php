<?php

use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\CustomerFeaturePreauthorization;
use App\Services\CustomerFeaturePreauthorizationService;

/**
 * Die Vormerkung haengt an der pds_account_id und wird beim Login in ein
 * Override uebersetzt. Entscheidend ist dabei die Vorrangregel: ein im Admin
 * gesetzter Wert darf nie ueberschrieben werden.
 */
const PREAUTH_TRAVEL_ALERT = 'navigation_risk_overview_enabled';

function preauthService(): CustomerFeaturePreauthorizationService
{
    return app(CustomerFeaturePreauthorizationService::class);
}

function preauthCustomer(?int $pdsAccountId): Customer
{
    return Customer::factory()->create(['pds_account_id' => $pdsAccountId]);
}

function preauthOverrideValue(Customer $customer, string $key = PREAUTH_TRAVEL_ALERT): ?bool
{
    return CustomerFeatureOverride::where('customer_id', $customer->id)->value($key);
}

it('schaltet ein Feature beim ersten Login frei', function () {
    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20028]);

    $customer = preauthCustomer(20028);

    expect(CustomerFeatureOverride::where('customer_id', $customer->id)->exists())->toBeFalse();

    $applied = preauthService()->applyForCustomer($customer);

    expect($applied)->toBe([PREAUTH_TRAVEL_ALERT => true]);
    expect(preauthOverrideValue($customer))->toBeTrue();
});

it('ueberschreibt eine im Admin gesetzte Sperre nicht', function () {
    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20029]);

    $customer = preauthCustomer(20029);
    CustomerFeatureOverride::create([
        'customer_id' => $customer->id,
        PREAUTH_TRAVEL_ALERT => false,
    ]);

    expect(preauthService()->applyForCustomer($customer))->toBe([]);
    expect(preauthOverrideValue($customer))->toBeFalse();
});

it('laesst eine bestehende Freischaltung unangetastet', function () {
    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20030], enabled: false);

    $customer = preauthCustomer(20030);
    CustomerFeatureOverride::create([
        'customer_id' => $customer->id,
        PREAUTH_TRAVEL_ALERT => true,
    ]);

    expect(preauthService()->applyForCustomer($customer))->toBe([]);
    expect(preauthOverrideValue($customer))->toBeTrue();
});

it('fasst andere Features im selben Override nicht an', function () {
    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20031]);

    $customer = preauthCustomer(20031);
    CustomerFeatureOverride::create([
        'customer_id' => $customer->id,
        'navigation_cruise_enabled' => false,
    ]);

    preauthService()->applyForCustomer($customer);

    expect(preauthOverrideValue($customer))->toBeTrue();
    expect(preauthOverrideValue($customer, 'navigation_cruise_enabled'))->toBeFalse();
});

it('tut nichts fuer Kunden ohne Vormerkung', function () {
    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20032]);

    $customer = preauthCustomer(99999);

    expect(preauthService()->applyForCustomer($customer))->toBe([]);
    expect(CustomerFeatureOverride::where('customer_id', $customer->id)->exists())->toBeFalse();
});

it('tut nichts fuer Kunden ohne pds_account_id', function () {
    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20033]);

    expect(preauthService()->applyForCustomer(preauthCustomer(null)))->toBe([]);
});

it('nutzt die Account-ID aus dem Token, wenn sie am Kunden fehlt', function () {
    // Mitarbeiter-Login: pds_account_id wird am Firmendatensatz nicht nachgefuehrt.
    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20041]);

    $customer = preauthCustomer(null);

    expect(preauthService()->applyForCustomer($customer, 20041))->toBe([PREAUTH_TRAVEL_ALERT => true]);
    expect(preauthOverrideValue($customer))->toBeTrue();
});

it('vermerkt wann eine Vormerkung eingeloest wurde', function () {
    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20034]);

    $customer = preauthCustomer(20034);
    preauthService()->applyForCustomer($customer);

    $preauthorization = CustomerFeaturePreauthorization::where('pds_account_id', 20034)->first();

    expect($preauthorization->applied_at)->not->toBeNull();
    expect($preauthorization->applied_customer_id)->toBe($customer->id);
});

it('wendet Vormerkungen auf bereits bestehende Kunden an', function () {
    $first = preauthCustomer(20035);
    $second = preauthCustomer(20036);
    $untouched = preauthCustomer(77777);

    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20035, 20036]);

    $result = preauthService()->applyToExistingCustomers(PREAUTH_TRAVEL_ALERT);

    expect($result['customers'])->toBe(2);
    expect(preauthOverrideValue($first))->toBeTrue();
    expect(preauthOverrideValue($second))->toBeTrue();
    expect(CustomerFeatureOverride::where('customer_id', $untouched->id)->exists())->toBeFalse();
});

it('bleibt bei wiederholtem Import stabil', function () {
    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20037, 20037, 20038]);
    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20037, 20038]);

    expect(CustomerFeaturePreauthorization::forFeature(PREAUTH_TRAVEL_ALERT)->count())->toBe(2);
});

it('weist unbekannte Feature-Keys zurueck', function () {
    preauthService()->record('navigation_gibt_es_nicht', [20039]);
})->throws(InvalidArgumentException::class);

it('schaltet alle Kunden eines Accounts frei', function () {
    // Zu einer pds_account_id koennen mehrere Kundendatensaetze gehoeren
    // (z. B. Firma und Mitarbeiterzugang).
    $company = preauthCustomer(20040);
    $employee = preauthCustomer(20040);

    preauthService()->record(PREAUTH_TRAVEL_ALERT, [20040]);
    preauthService()->applyToExistingCustomers(PREAUTH_TRAVEL_ALERT);

    expect(preauthOverrideValue($company))->toBeTrue();
    expect(preauthOverrideValue($employee))->toBeTrue();
});
