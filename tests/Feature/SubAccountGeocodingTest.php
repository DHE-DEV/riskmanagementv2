<?php

use App\Jobs\GeocodeSubAccounts;
use App\Models\Customer;
use App\Services\CustomerGeocoder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * Koordinaten der Unteraccounts fuer die Karte "Filialen & Standorte".
 */
beforeEach(function () {
    config(['services.nominatim.throttle' => 0, 'services.nominatim.url' => 'https://nominatim.test']);
});

function fakeNominatim(): void
{
    Http::fake([
        'nominatim.test/*' => function (Request $request) {
            // Nur Essen ist mit Strasse auffindbar, Bochum nur ueber PLZ/Ort, Nirgendwo gar nicht.
            return match (true) {
                $request['city'] === 'Essen' => Http::response([['lat' => '51.45', 'lon' => '7.01']]),
                $request['city'] === 'Bochum' && ! isset($request['street']) => Http::response([['lat' => '51.48', 'lon' => '7.21']]),
                default => Http::response([]),
            };
        },
        '*' => Http::response([]),
    ]);
}

it('ermittelt Koordinaten mit Fallback und merkt sich auch Fehlschläge', function () {
    fakeNominatim();
    $geocoder = app(CustomerGeocoder::class);

    $essen = Customer::factory()->create(['company_street' => 'Güterstr.', 'company_house_number' => '23b', 'company_postal_code' => '45219', 'company_city' => 'Essen', 'company_country' => 'DE']);
    $bochum = Customer::factory()->create(['company_street' => 'Gibtsnicht', 'company_postal_code' => '44799', 'company_city' => 'Bochum']);
    $nowhere = Customer::factory()->create(['company_postal_code' => '00000', 'company_city' => 'Nirgendwo']);

    expect($geocoder->geocode($essen))->toBeTrue()
        ->and($geocoder->geocode($bochum))->toBeTrue()
        ->and($geocoder->geocode($nowhere))->toBeFalse()
        ->and($essen->fresh())->company_latitude->toBe(51.45)->company_longitude->toBe(7.01)
        ->and($bochum->fresh()->company_latitude)->toBe(51.48)
        ->and($nowhere->fresh())->company_latitude->toBeNull()->company_geocoded_at->not->toBeNull();

    Http::assertSent(fn (Request $request) => ($request->data()['countrycodes'] ?? null) === 'de' && $request->hasHeader('User-Agent'));

    // Neue Adresse: Koordinaten werden zurueckgesetzt.
    $essen->update(['company_city' => 'Bochum']);
    expect($essen->fresh())->company_latitude->toBeNull()->company_geocoded_at->toBeNull();
});

it('stellt den Job ein, wenn ein Unteraccount einer Karten-Zentrale entsteht oder umzieht', function () {
    Queue::fake();

    $agency = Customer::factory()->create(['show_sub_accounts_as_branches' => true]);
    $plain = Customer::factory()->create();

    Customer::factory()->create(['assign_to' => $plain->id, 'company_city' => 'Essen']);
    Queue::assertNothingPushed();

    $office = Customer::factory()->create(['assign_to' => $agency->id, 'company_city' => 'Essen']);
    Queue::assertPushed(GeocodeSubAccounts::class, fn (GeocodeSubAccounts $job) => $job->agencyId === $agency->id);

    // Solange ein Job wartet, kommt kein zweiter dazu (ein Job je Zentrale).
    Customer::factory()->create(['assign_to' => $agency->id, 'company_city' => 'Bochum']);
    Queue::assertPushed(GeocodeSubAccounts::class, 1);

    // Nach dessen Start (Sperre frei) loest ein Umzug einen neuen aus.
    \Illuminate\Support\Facades\Cache::lock('laravel_unique_job:'.GeocodeSubAccounts::class.':'.$agency->id)->forceRelease();
    $office->forceFill(['company_latitude' => 1, 'company_longitude' => 1, 'company_geocoded_at' => now()])->saveQuietly();
    $office->update(['company_city' => 'Bochum']);
    Queue::assertPushed(GeocodeSubAccounts::class, 2);
});

it('arbeitet die Unteraccounts in Portionen ab und stellt sich neu ein', function () {
    fakeNominatim();
    Queue::fake();

    $agency = Customer::factory()->create();
    Customer::factory()->count(3)->create(['assign_to' => $agency->id, 'company_postal_code' => '45219', 'company_city' => 'Essen']);

    GeocodeSubAccounts::$runSeconds = 0;

    try {
        app()->call([new GeocodeSubAccounts($agency->id), 'handle']);
    } finally {
        GeocodeSubAccounts::$runSeconds = 60;
    }

    expect(GeocodeSubAccounts::pending($agency->id)->count())->toBe(2);
    Queue::assertPushed(GeocodeSubAccounts::class, 1);

    app()->call([new GeocodeSubAccounts($agency->id), 'handle']);

    expect(GeocodeSubAccounts::pending($agency->id)->count())->toBe(0)
        ->and(Customer::where('assign_to', $agency->id)->whereNotNull('company_latitude')->count())->toBe(3);
});

it('zeigt die Agenturen mit Koordinaten auf der Karte', function () {
    $agency = Customer::factory()->create(['branch_management_active' => true, 'show_sub_accounts_as_branches' => true]);
    $located = Customer::factory()->create(['assign_to' => $agency->id, 'company_name' => 'Büro Essen']);
    $located->forceFill(['company_latitude' => 51.45, 'company_longitude' => 7.01, 'company_geocoded_at' => now()])->saveQuietly();
    Customer::factory()->create(['assign_to' => $agency->id, 'company_name' => 'Büro ohne Ort'])
        ->forceFill(['company_geocoded_at' => now()])->saveQuietly();

    $this->actingAs($agency, 'customer')
        ->get(route('branches'))
        ->assertOk()
        ->assertSee('Büro ohne Ort')
        ->assertSee('focusAgency('.$located->id.')', false)
        ->assertSee('51.45', false);
});
