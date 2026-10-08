<?php

use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Livewire\AdminV2\System\TaxiApps\Editor;
use App\Livewire\AdminV2\System\TaxiApps\Index;
use App\Models\Continent;
use App\Models\Country;
use App\Models\MasterDataChange;
use App\Models\TaxiApp;
use App\Models\User;
use Database\Seeders\TaxiAppSeeder;
use Livewire\Livewire;

/**
 * System > Taxi Apps: Anbieter pflegen und sie den Laendern zuordnen.
 */
function taxiAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function taxiCountry(string $german, string $iso, string $iso3): Country
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create(['name_translations' => ['de' => $german, 'en' => $german], 'iso_code' => $iso, 'iso3_code' => $iso3, 'continent_id' => $continent->id]);
}

it('bringt Uber, Bolt und FREENOW mit Beschreibungen in drei Sprachen mit', function () {
    $this->seed(TaxiAppSeeder::class);
    $this->seed(TaxiAppSeeder::class);

    expect(TaxiApp::ordered()->pluck('name')->all())->toBe(['Uber', 'Bolt', 'FREENOW']);

    foreach (TaxiApp::all() as $app) {
        expect($app->description_translations)->toHaveKeys(['de', 'en', 'nl'])
            ->and($app->logo_url)->toStartWith('https://')
            ->and($app->website_url)->toStartWith('https://')
            ->and($app->app_store_url)->toContain('apps.apple.com')
            ->and($app->play_store_url)->toContain('play.google.com')
            ->and($app->is_active)->toBeTrue();
    }
});

it('legt eine Taxi App mit Logo, Links und Beschreibung je Sprache an und zeigt sie als Karte', function () {
    $this->actingAs(taxiAdmin());

    Livewire::test(Editor::class)
        ->call('save')
        ->assertHasErrors(['name'])
        ->set('name', 'Cabify')
        ->set('descriptions.de', 'Fahrdienst in Spanien und Lateinamerika.')
        ->set('descriptions.en', 'Ride-hailing in Spain and Latin America.')
        ->set('logoUrl', 'kein-link')
        ->call('save')
        ->assertHasErrors(['logoUrl'])
        ->set('logoUrl', 'https://example.org/cabify.svg')
        ->set('websiteUrl', 'https://cabify.com')
        ->set('appStoreUrl', 'https://apps.apple.com/app/id476087442')
        ->set('playStoreUrl', 'https://play.google.com/store/apps/details?id=com.cabify.rider')
        ->set('sortOrder', '5')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.system.taxi-apps.index'));

    $app = TaxiApp::firstWhere('name', 'Cabify');

    expect($app->description_translations)->toEqual(['de' => 'Fahrdienst in Spanien und Lateinamerika.', 'en' => 'Ride-hailing in Spain and Latin America.'])
        ->and($app->description('nl'))->toBe('Ride-hailing in Spain and Latin America.')
        ->and($app->logo_url)->toBe('https://example.org/cabify.svg')
        ->and($app->sort_order)->toBe(5)
        ->and($app->is_active)->toBeTrue();

    $this->get(route('adminv2.system.taxi-apps.index'))
        ->assertOk()
        ->assertSee('Taxi Apps')
        ->assertSee('Cabify')
        ->assertSee('https://example.org/cabify.svg')
        ->assertSee('Fahrdienst in Spanien und Lateinamerika.')
        ->assertSee(route('adminv2.system.taxi-apps.edit', $app));

    Livewire::test(Editor::class, ['app' => $app->id])
        ->assertSet('name', 'Cabify')
        ->assertSet('descriptions.en', 'Ride-hailing in Spain and Latin America.')
        ->set('name', 'Cabify Rider')
        ->call('save')
        ->assertHasNoErrors();

    expect($app->fresh()->name)->toBe('Cabify Rider');

    Livewire::test(Index::class)
        ->call('toggleActive', $app->id)
        ->assertDispatched('adminv2-toast');

    expect($app->fresh()->is_active)->toBeFalse();

    Livewire::test(Index::class)->call('delete', $app->id);

    expect(TaxiApp::whereKey($app->id)->exists())->toBeFalse();
});

it('ordnet einem Land Taxi Apps zu und zeigt Logo, Name und Website', function () {
    $this->seed(TaxiAppSeeder::class);
    $uber = TaxiApp::firstWhere('name', 'Uber');
    $bolt = TaxiApp::firstWhere('name', 'Bolt');
    $inactive = TaxiApp::create(['name' => 'Altanbieter', 'is_active' => false]);
    $spain = taxiCountry('Spanien', 'ES', 'ESP');

    $this->actingAs($admin = taxiAdmin());

    $editor = Livewire::test(CountryEditor::class, ['country' => $spain->id])
        ->assertSet('taxiAppIds', [])
        ->assertSee('Taxi-Apps')
        ->assertSee('Uber')
        // Inaktive Apps werden nicht angeboten.
        ->assertDontSee('Altanbieter')
        ->set('taxiAppIds', [(string) $bolt->id, (string) $uber->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee($uber->logo_url)
        ->assertSee('uber.com')
        ->assertSee('bolt.eu');

    expect($spain->taxiApps()->pluck('name')->all())->toBe(['Uber', 'Bolt'])
        ->and($uber->countries()->pluck('iso_code')->all())->toBe(['ES'])
        ->and(MasterDataChange::where('model_id', $spain->id)->where('user_id', $admin->id)->latest('id')->value('changes'))->toBe(['taxi_apps']);

    // Entfernen in der Karte, dann speichern.
    $editor
        ->call('removeTaxiApp', $bolt->id)
        ->assertSet('taxiAppIds', [(string) $uber->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($spain->taxiApps()->pluck('name')->all())->toBe(['Uber']);

    // Eine bereits zugeordnete, inzwischen inaktive App bleibt sichtbar und zugeordnet.
    $spain->taxiApps()->attach($inactive->id);

    Livewire::test(CountryEditor::class, ['country' => $spain->id])
        ->assertSee('Altanbieter')
        ->assertSee('Inaktiv')
        ->call('openAiCheck', 'taxi_apps')
        ->assertSet('aiSection', 'taxi_apps');

    // Loeschen eines Anbieters nimmt die Zuordnung mit.
    $inactive->delete();
    expect($spain->taxiApps()->count())->toBe(1);
});
