<?php

use App\Models\Customer;
use App\Services\PassolutionApiService;

/**
 * Liefert fuer jede in $accounts hinterlegte E-Mail den passenden Account,
 * fuer alle anderen null (= kein Account gefunden).
 */
function fakePassolutionAccounts(array $accounts): void
{
    test()->mock(PassolutionApiService::class, function ($mock) use ($accounts) {
        $mock->shouldReceive('fetchAccountByEmail')
            ->andReturnUsing(fn (string $email) => isset($accounts[$email])
                ? ['id' => $accounts[$email]]
                : null);
    });
}

test('Kunde ohne Kundennummer bekommt die Account-ID nachgetragen', function () {
    $customer = Customer::factory()->create([
        'email' => 'bestand@example.com',
        'pds_account_id' => null,
    ]);

    fakePassolutionAccounts(['bestand@example.com' => 4711]);

    $this->artisan('pds:backfill-account-id')->assertSuccessful();

    expect($customer->fresh()->pds_account_id)->toBe(4711);
});

test('bereits gesetzte Kundennummer wird nicht angefasst', function () {
    $customer = Customer::factory()->create([
        'email' => 'schonda@example.com',
        'pds_account_id' => 1234,
    ]);

    // Wuerde der Kunde trotzdem abgefragt, kaeme hier 9999 zurueck.
    fakePassolutionAccounts(['schonda@example.com' => 9999]);

    $this->artisan('pds:backfill-account-id')->assertSuccessful();

    expect($customer->fresh()->pds_account_id)->toBe(1234);
});

test('--force fragt auch gesetzte Kundennummern neu ab', function () {
    $customer = Customer::factory()->create([
        'email' => 'veraltet@example.com',
        'pds_account_id' => 1234,
    ]);

    fakePassolutionAccounts(['veraltet@example.com' => 9999]);

    $this->artisan('pds:backfill-account-id', ['--force' => true])->assertSuccessful();

    expect($customer->fresh()->pds_account_id)->toBe(9999);
});

test('Kunde ohne Account in der API bleibt auf NULL', function () {
    $customer = Customer::factory()->create([
        'email' => 'unbekannt@example.com',
        'pds_account_id' => null,
    ]);

    fakePassolutionAccounts([]);

    $this->artisan('pds:backfill-account-id')->assertSuccessful();

    expect($customer->fresh()->pds_account_id)->toBeNull();
});

test('faellt auf pds_username zurueck, wenn keine E-Mail gesetzt ist', function () {
    $customer = Customer::factory()->create([
        'email' => null,
        'pds_username' => 'nur-username@example.com',
        'pds_account_id' => null,
    ]);

    fakePassolutionAccounts(['nur-username@example.com' => 555]);

    $this->artisan('pds:backfill-account-id')->assertSuccessful();

    expect($customer->fresh()->pds_account_id)->toBe(555);
});

test('dry-run schreibt nichts', function () {
    $customer = Customer::factory()->create([
        'email' => 'trocken@example.com',
        'pds_account_id' => null,
    ]);

    fakePassolutionAccounts(['trocken@example.com' => 4711]);

    $this->artisan('pds:backfill-account-id', ['--dry-run' => true])->assertSuccessful();

    expect($customer->fresh()->pds_account_id)->toBeNull();
});

test('ohne Kunden ohne Kundennummer passiert nichts', function () {
    Customer::factory()->create([
        'email' => 'fertig@example.com',
        'pds_account_id' => 1234,
    ]);

    fakePassolutionAccounts(['fertig@example.com' => 9999]);

    $this->artisan('pds:backfill-account-id')
        ->expectsOutputToContain('nichts zu tun')
        ->assertSuccessful();
});
