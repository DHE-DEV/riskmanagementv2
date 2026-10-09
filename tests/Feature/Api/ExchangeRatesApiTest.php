<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function ratesToken(): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('Test', ['gtm:read'])->plainTextToken;
}

function fakeRates(): void
{
    Http::fake(['open.er-api.com/*' => Http::response([
        'result' => 'success',
        'time_last_update_unix' => 1791500000,
        'time_next_update_unix' => 1791586400,
        'rates' => ['EUR' => 1, 'USD' => 1.08, 'EGP' => 54.21, 'THB' => 38.5],
    ])]);
}

it('liefert Wechselkurse mit Basis EUR aus dem Cache des Anbieters', function () {
    Cache::flush();
    fakeRates();
    $token = ratesToken();

    $data = $this->withToken($token)->getJson('/api/v1/exchange-rates')->assertOk()->json('data');

    expect($data['base'])->toBe('EUR')
        ->and($data['rates']['EGP'])->toBe(54.21)
        ->and($data['updated_at'])->toStartWith('2026-10-')
        ->and($data['source'])->toContain('ExchangeRate-API');

    // Zweiter Abruf innerhalb der Stunde: kein neuer Anbieter-Request.
    $this->withToken($token)->getJson('/api/v1/exchange-rates?symbols=egp,usd')->assertOk()->assertJsonPath('data.rates', ['EGP' => 54.21, 'USD' => 1.08]);
    Http::assertSentCount(1);
});

it('rechnet auf eine andere Basiswaehrung um und lehnt unbekannte ab', function () {
    Cache::flush();
    fakeRates();
    $token = ratesToken();

    $data = $this->withToken($token)->getJson('/api/v1/exchange-rates?base=USD')->assertOk()->json('data');

    expect($data['base'])->toBe('USD')
        ->and($data['rates']['EUR'])->toBe(round(1 / 1.08, 6))
        ->and($data['rates']['EGP'])->toBe(round(54.21 / 1.08, 6));

    $this->withToken($token)->getJson('/api/v1/exchange-rates?base=XXX')->assertStatus(422);
    $this->withToken($token)->getJson('/api/v1/exchange-rates?base=EURO')->assertStatus(422);
});

it('liefert bei Anbieter-Ausfall die letzten Kurse und sonst 503', function () {
    Cache::flush();
    Http::fake(['open.er-api.com/*' => Http::response('', 500)]);
    $token = ratesToken();

    $this->withToken($token)->getJson('/api/v1/exchange-rates')->assertStatus(503);

    // Alte Kurse im Cache (aelter als eine Stunde, juenger als ein Tag) werden weiter ausgeliefert.
    Cache::put('exchange-rates:EUR', ['fetched_at' => now()->subHours(3)->timestamp, 'data' => ['base' => 'EUR', 'rates' => ['USD' => 1.1], 'updated_at' => null, 'next_update_at' => null, 'source' => 'Test']], now()->addDay());
    $this->withToken($token)->getJson('/api/v1/exchange-rates')->assertOk()->assertJsonPath('data.rates.USD', 1.1);
});
