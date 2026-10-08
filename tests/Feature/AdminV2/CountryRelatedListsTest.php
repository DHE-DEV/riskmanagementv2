<?php

use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Models\Airport;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\MasterDataChange;
use App\Models\Region;
use App\Models\User;
use Livewire\Livewire;

/**
 * Seitenspalte des Landes: Regionen, Staedte und Flughaefen mit Suche,
 * Seiten und der Markierung als Major Region bzw. Major City.
 */
function relatedAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function relatedCountry(): Country
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create(['name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'], 'iso_code' => 'DE', 'iso3_code' => 'DEU', 'continent_id' => $continent->id]);
}

it('blaettert durch die Staedte eines Landes mit 15, 30, 100 oder allen je Seite', function () {
    $germany = relatedCountry();

    foreach (range(1, 40) as $i) {
        City::create(['name_translations' => ['de' => sprintf('Stadt %02d', $i)], 'country_id' => $germany->id, 'population' => 1000 * (41 - $i)]);
    }

    $this->actingAs(relatedAdmin());

    $editor = Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->assertSet('relatedLimit.cities', '15')
        ->assertSee('Städte (40)')
        ->assertSee('Stadt 01')
        ->assertSee('Stadt 15')
        ->assertDontSee('Stadt 16')
        ->assertSee('1–15 von 40');

    expect($editor->instance()->related['cities']['last_page'])->toBe(3);

    $editor
        ->call('relatedGoto', 'cities', 3)
        ->assertSee('Stadt 31')
        ->assertSee('Stadt 40')
        ->assertDontSee('Stadt 30')
        ->assertSee('31–40 von 40')
        // Eine Seite hinter dem Ende faellt auf die letzte zurueck.
        ->call('relatedGoto', 'cities', 9)
        ->assertSee('Stadt 40')
        ->set('relatedLimit.cities', '30')
        ->assertSet('relatedPage.cities', 1)
        ->assertSee('Stadt 30')
        ->assertDontSee('Stadt 31')
        ->set('relatedLimit.cities', 'all')
        ->assertSee('Stadt 01')
        ->assertSee('Stadt 40')
        ->assertSee('1–40 von 40');

    expect($editor->instance()->related['cities']['last_page'])->toBe(1);
});

it('sucht in Regionen, Staedten und Flughaefen und schlaegt Treffer zur Autovervollstaendigung vor', function () {
    $germany = relatedCountry();
    $bavaria = Region::create(['name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria'], 'code' => 'BY', 'country_id' => $germany->id]);
    Region::create(['name_translations' => ['de' => 'Hessen', 'en' => 'Hesse'], 'code' => 'HE', 'country_id' => $germany->id]);
    $munich = City::create(['name_translations' => ['de' => 'München', 'en' => 'Munich'], 'country_id' => $germany->id, 'region_id' => $bavaria->id]);
    $hamburg = City::create(['name_translations' => ['de' => 'Hamburg'], 'country_id' => $germany->id]);
    Airport::create(['name' => 'Franz Josef Strauß', 'iata_code' => 'MUC', 'icao_code' => 'EDDM', 'country_id' => $germany->id, 'city_id' => $munich->id, 'is_active' => true]);
    Airport::create(['name' => 'Frankfurt am Main', 'iata_code' => 'FRA', 'icao_code' => 'EDDF', 'country_id' => $germany->id, 'city_id' => $hamburg->id, 'is_active' => true]);

    $this->actingAs(relatedAdmin());

    $editor = Livewire::test(CountryEditor::class, ['country' => $germany->id])
        // Regionen: nach Name in beiden Sprachen und nach Code
        ->set('relatedSearch.regions', 'bavar')
        ->assertSee('Bayern')
        ->assertDontSee('Hessen')
        ->set('relatedSearch.regions', 'HE')
        ->assertSee('Hessen')
        ->assertDontSee('Bayern')
        // Staedte
        ->set('relatedSearch.cities', 'münch')
        ->assertSee('München')
        ->assertDontSee('Hamburg')
        // Flughaefen: Name oder Code
        ->set('relatedSearch.airports', 'eddf')
        ->assertSee('Frankfurt am Main')
        ->assertDontSee('Franz Josef');

    expect($editor->instance()->related['regions']['suggestions'])->toBe(['Hessen'])
        ->and($editor->instance()->related['cities']['suggestions'])->toBe(['München'])
        ->and($editor->instance()->related['airports']['suggestions'])->toBe(['Frankfurt am Main (FRA)'])
        ->and($editor->instance()->related['airports']['found'])->toBe(1)
        ->and($editor->instance()->related['airports']['count'])->toBe(2);

    $editor
        ->set('relatedSearch.cities', 'gibtesnicht')
        ->assertSee('Nichts gefunden')
        ->set('relatedSearch.cities', '')
        ->assertSee('München')
        ->assertSee('Hamburg');
});

it('markiert Staedte als Major City und Regionen als Major Region und stellt sie nach vorn', function () {
    $germany = relatedCountry();
    $bavaria = Region::create(['name_translations' => ['de' => 'Bayern'], 'code' => 'BY', 'country_id' => $germany->id]);
    $hesse = Region::create(['name_translations' => ['de' => 'Hessen'], 'code' => 'HE', 'country_id' => $germany->id]);
    $berlin = City::create(['name_translations' => ['de' => 'Berlin'], 'country_id' => $germany->id, 'is_capital' => true, 'population' => 3600000]);
    $hamburg = City::create(['name_translations' => ['de' => 'Hamburg'], 'country_id' => $germany->id, 'population' => 1800000]);
    $munich = City::create(['name_translations' => ['de' => 'Muenchen'], 'country_id' => $germany->id, 'population' => 1500000]);

    // Eine Stadt eines anderen Landes laesst sich hier nicht markieren.
    $other = Country::create(['name_translations' => ['de' => 'Österreich'], 'iso_code' => 'AT', 'iso3_code' => 'AUT', 'continent_id' => $germany->continent_id]);
    $vienna = City::create(['name_translations' => ['de' => 'Wien'], 'country_id' => $other->id]);

    $this->actingAs($admin = relatedAdmin());

    $editor = Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->assertSeeInOrder(['Berlin', 'Hamburg', 'Muenchen'])
        ->call('toggleMajorCity', $munich->id)
        ->assertDispatched('adminv2-toast')
        ->assertSeeInOrder(['Berlin', 'Muenchen', 'Hamburg'])
        ->call('toggleMajorRegion', $hesse->id)
        ->assertSeeInOrder(['Hessen', 'Bayern'])
        ->call('toggleMajorCity', $vienna->id);

    expect($munich->fresh()->is_major)->toBeTrue()
        ->and($hamburg->fresh()->is_major)->toBeFalse()
        ->and($hesse->fresh()->is_major)->toBeTrue()
        ->and($bavaria->fresh()->is_major)->toBeFalse()
        ->and($vienna->fresh()->is_major)->toBeFalse()
        ->and(MasterDataChange::where('model_type', $munich->getMorphClass())->where('model_id', $munich->id)->where('user_id', $admin->id)->value('changes'))->toBe(['is_major']);

    // Noch einmal klicken hebt die Markierung auf.
    $editor->call('toggleMajorCity', $munich->id)
        ->assertSeeInOrder(['Berlin', 'Hamburg', 'Muenchen']);

    expect($munich->fresh()->is_major)->toBeFalse()
        ->and($berlin->fresh()->is_major)->toBeFalse();
});
