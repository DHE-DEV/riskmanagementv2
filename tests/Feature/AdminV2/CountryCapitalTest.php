<?php

use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Hauptstadt eines Landes: oben in den Informationen angezeigt und Quelle des
 * Punkts auf der Karte – wie bei landesweiten Ereignissen.
 */
function capitalTestCountry(array $attributes = []): Country
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create(array_merge([
        'name_translations' => ['de' => 'Spanien', 'en' => 'Spain'],
        'iso_code' => 'ES',
        'iso3_code' => 'ESP',
        'continent_id' => $continent->id,
        // Bisher: Mittelpunkt des Landes
        'lat' => 40.4637,
        'lng' => -3.7492,
    ], $attributes));
}

function capitalTestCity(Country $country, string $name, float $lat, float $lng, array $attributes = []): City
{
    return City::create(array_merge([
        'country_id' => $country->id,
        'name_translations' => ['de' => $name, 'en' => $name],
        'lat' => $lat,
        'lng' => $lng,
        'is_capital' => true,
    ], $attributes));
}

/** Rechteck als Landesgrenze, wie der Import aus Natural Earth es ablegt. */
function capitalTestBoundary(Country $country, float $minLat, float $maxLat, float $minLng, float $maxLng): void
{
    $ring = [[$minLng, $minLat], [$maxLng, $minLat], [$maxLng, $maxLat], [$minLng, $maxLat], [$minLng, $minLat]];

    DB::statement(
        'insert into country_boundaries (country_id, iso_a2, iso_a3, name, source, source_features, boundary, min_lat, max_lat, min_lng, max_lng, created_at, updated_at)
         values (?, ?, ?, ?, ?, 1, ST_GeomFromGeoJSON(?, 1, 4326), ?, ?, ?, ?, now(), now())',
        [$country->id, $country->iso_code, $country->iso3_code, 'Test', 'Test', json_encode(['type' => 'MultiPolygon', 'coordinates' => [[$ring]]]), $minLat, $maxLat, $minLng, $maxLng],
    );
}

function capitalTestAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

it('setzt den Punkt des Landes auf die Hauptstadt und zieht bei deren Aenderung mit', function () {
    $spain = capitalTestCountry();

    $madrid = capitalTestCity($spain, 'Madrid', 40.416548, -3.703096);

    expect((float) $spain->fresh()->lat)->toBe(40.416548)
        ->and((float) $spain->fresh()->lng)->toBe(-3.703096);

    $madrid->update(['lat' => 40.4168, 'lng' => -3.7038]);

    expect((float) $spain->fresh()->lat)->toBe(40.4168)
        ->and((float) $spain->fresh()->lng)->toBe(-3.7038);

    // Eine gewoehnliche Stadt aendert den Punkt nicht.
    capitalTestCity($spain, 'Sevilla', 37.3891, -5.9845, ['is_capital' => false])->update(['lat' => 37.39]);

    expect((float) $spain->fresh()->lat)->toBe(40.4168);
});

it('nimmt bei mehreren markierten Hauptstaedten die mit Region und ignoriert eine ausserhalb der Grenze', function () {
    $germany = capitalTestCountry(['name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'], 'iso_code' => 'DE', 'iso3_code' => 'DEU', 'lat' => 51.1657, 'lng' => 10.4515]);
    capitalTestBoundary($germany, 47.27, 55.06, 5.87, 15.04);

    // Altdaten: Prag ohne Region als Hauptstadt von Deutschland markiert, vor Berlin angelegt.
    capitalTestCity($germany, 'Prag', 50.0755, 14.4378);
    $brandenburg = Region::create(['country_id' => $germany->id, 'name_translations' => ['de' => 'Berlin', 'en' => 'Berlin'], 'code' => 'DE-BE']);
    $berlin = capitalTestCity($germany, 'Berlin', 52.52, 13.405, ['region_id' => $brandenburg->id, 'is_regional_capital' => true]);

    expect($germany->fresh()->capital->is($berlin))->toBeTrue()
        ->and((float) $germany->fresh()->lat)->toBe(52.52);

    // Eine Hauptstadt ausserhalb der Landesgrenze (falsch zugeordnet) setzt den Punkt nicht.
    $andorra = capitalTestCountry(['name_translations' => ['de' => 'Andorra', 'en' => 'Andorra'], 'iso_code' => 'AD', 'iso3_code' => 'AND', 'lat' => 42.5462, 'lng' => 1.6016]);
    capitalTestBoundary($andorra, 42.43, 42.66, 1.41, 1.79);
    capitalTestCity($andorra, 'Madrid', 40.416548, -3.703096);

    expect((float) $andorra->fresh()->lat)->toBe(42.5462)
        ->and($andorra->fresh()->capitalCoordinates())->toBeNull();
});

it('zeigt die Hauptstadt oben an, warnt bei mehreren und uebernimmt beim Speichern ihre Koordinaten', function () {
    $spain = capitalTestCountry();
    $region = Region::create(['country_id' => $spain->id, 'name_translations' => ['de' => 'Madrid', 'en' => 'Madrid'], 'code' => 'ES-M']);
    capitalTestCity($spain, 'Madrid', 40.416548, -3.703096, ['region_id' => $region->id, 'is_regional_capital' => true]);
    $wrong = capitalTestCity($spain, 'Lissabon', 38.7223, -9.1393);

    $this->actingAs(capitalTestAdmin());

    Livewire::test(CountryEditor::class, ['country' => $spain->id])
        ->assertSee('Hauptstadt')
        ->assertSee('Madrid')
        ->assertSee('2 Städte sind als Hauptstadt markiert')
        ->assertSee('Lissabon (#'.$wrong->id.')')
        ->assertSee('Übernommen von')
        ->assertSet('lat', '40.416548')
        // Ein von Hand gesetzter Punkt weicht beim Speichern der Hauptstadt.
        ->set('lat', '41')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $spain->fresh()->lat)->toBe(40.416548);
});

it('laesst den Punkt von Hand setzen, solange keine Hauptstadt hinterlegt ist', function () {
    $spain = capitalTestCountry();

    $this->actingAs(capitalTestAdmin());

    Livewire::test(CountryEditor::class, ['country' => $spain->id])
        ->assertSee('Noch keine Stadt als Hauptstadt markiert.')
        ->assertDontSee('Übernommen von')
        ->assertSee('Aus Google Maps übernehmen')
        ->set('lat', '40.5')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $spain->fresh()->lat)->toBe(40.5);
});
