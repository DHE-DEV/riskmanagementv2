<?php

/**
 * Ein eingeloggter Kunde soll seine Stammdaten nicht erneut eintippen –
 * das Bestellformular wird mit window.__travelAlertPrefill vorbefuellt.
 */

use App\Models\Customer;

function prefillFromResponse(string $html): ?array
{
    if (! preg_match('/window\.__travelAlertPrefill = (.*?);\n/s', $html, $m)) {
        return null;
    }

    return json_decode($m[1], true);
}

beforeEach(function () {
    // Ohne Feature-Zugang landet der Kunde auf der Promoseite mit dem Formular.
    config(['app.navigation_risk_overview_enabled' => false]);
});

test('Gaeste bekommen keine Prefill-Daten', function () {
    $html = $this->get('/travel-alert')->assertOk()->getContent();

    expect(prefillFromResponse($html))->toBeNull();
});

test('eingeloggter Kunde bekommt seine Stammdaten ins Formular', function () {
    $customer = Customer::factory()->create([
        'name' => 'Erika Muster',
        'email' => 'erika@example.com',
        'phone' => '0123 456',
        'customer_type' => 'business',
        'business_type' => ['travel_agency', 'organizer'],
        'company_name' => 'Reisebuero Muster GmbH',
        'company_street' => 'Musterweg',
        'company_house_number' => '12',
        'company_postal_code' => '12345',
        'company_city' => 'Musterstadt',
        'company_country' => 'Deutschland',
    ]);

    $html = $this->actingAs($customer, 'customer')->get('/travel-alert')->assertOk()->getContent();
    $prefill = prefillFromResponse($html);

    expect($prefill)->not->toBeNull()
        ->and($prefill['company'])->toBe('Reisebuero Muster GmbH')
        ->and($prefill['email'])->toBe('erika@example.com')
        ->and($prefill['phone'])->toBe('0123 456')
        ->and($prefill['street'])->toBe('Musterweg 12')
        ->and($prefill['postal_code'])->toBe('12345')
        ->and($prefill['city'])->toBe('Musterstadt')
        ->and($prefill['country'])->toBe('Deutschland')
        ->and($prefill['customer_type'])->toBe('business')
        ->and($prefill['business_type'])->toBe(['travel_agency', 'organizer']);
});

test('Name wird als Vor- und Nachname aufgeteilt, wenn keine SSO-Namen da sind', function () {
    $customer = Customer::factory()->create([
        'name' => 'Max Mustermann',
        'pds_account_first_name' => null,
        'pds_account_last_name' => null,
    ]);

    $prefill = prefillFromResponse(
        $this->actingAs($customer, 'customer')->get('/travel-alert')->getContent()
    );

    expect($prefill['first_name'])->toBe('Max')
        ->and($prefill['last_name'])->toBe('Mustermann');
});

test('SSO-Vor-/Nachname haben Vorrang vor dem Anzeigenamen', function () {
    $customer = Customer::factory()->create([
        'name' => 'irgendwas',
        'pds_account_first_name' => 'Anna',
        'pds_account_last_name' => 'Beispiel',
    ]);

    $prefill = prefillFromResponse(
        $this->actingAs($customer, 'customer')->get('/travel-alert')->getContent()
    );

    expect($prefill['first_name'])->toBe('Anna')
        ->and($prefill['last_name'])->toBe('Beispiel');
});

test('leeres Land faellt auf Deutschland zurueck', function () {
    $customer = Customer::factory()->create(['company_country' => null]);

    $prefill = prefillFromResponse(
        $this->actingAs($customer, 'customer')->get('/travel-alert')->getContent()
    );

    expect($prefill['country'])->toBe('Deutschland');
});
