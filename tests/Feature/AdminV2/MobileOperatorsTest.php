<?php

use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Livewire\AdminV2\System\MobileOperators\Editor;
use App\Livewire\AdminV2\System\MobileOperators\Index;
use App\Models\Continent;
use App\Models\Country;
use App\Models\MasterDataChange;
use App\Models\MobileOperator;
use App\Models\User;
use Livewire\Livewire;

/**
 * System > Mobilfunkanbieter: Anbieter pflegen und sie den Laendern mit
 * Suche und Autovervollstaendigung zuordnen.
 */
function operatorAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function operatorCountry(string $german, string $iso, string $iso3): Country
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create(['name_translations' => ['de' => $german, 'en' => $german], 'iso_code' => $iso, 'iso3_code' => $iso3, 'continent_id' => $continent->id]);
}

it('legt einen Mobilfunkanbieter mit Logo, Links, eSIM und Beschreibung je Sprache an', function () {
    $this->actingAs(operatorAdmin());

    Livewire::test(Editor::class)
        ->call('save')
        ->assertHasErrors(['name'])
        ->set('name', 'Telekom')
        ->set('descriptions.de', 'Größtes Netz in Deutschland.')
        ->set('descriptions.en', 'Largest network in Germany.')
        ->set('logoUrl', 'https://example.org/telekom.svg')
        ->set('websiteUrl', 'https://www.telekom.de')
        ->set('prepaidUrl', 'nicht-gueltig')
        ->call('save')
        ->assertHasErrors(['prepaidUrl'])
        ->set('prepaidUrl', 'https://www.telekom.de/prepaid')
        ->set('offersEsim', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.system.mobile-operators.index'));

    $operator = MobileOperator::firstWhere('name', 'Telekom');

    expect($operator->description('nl'))->toBe('Largest network in Germany.')
        ->and($operator->offers_esim)->toBeTrue()
        ->and($operator->prepaid_url)->toBe('https://www.telekom.de/prepaid');

    $this->get(route('adminv2.system.mobile-operators.index'))
        ->assertOk()
        ->assertSee('Mobilfunkanbieter')
        ->assertSee('Telekom')
        ->assertSee('eSIM')
        ->assertSee('https://example.org/telekom.svg');

    MobileOperator::create(['name' => 'Vodafone']);

    Livewire::test(Index::class)
        ->set('search', 'voda')
        ->assertSee('Vodafone')
        ->assertDontSee('Telekom')
        ->call('toggleActive', $operator->id)
        ->call('delete', $operator->id);

    expect($operator->fresh())->toBeNull();
});

it('ordnet einem Land Anbieter per Suche zu und zeigt die Auswahl mit Logo und Website', function () {
    $telekom = MobileOperator::create(['name' => 'Telekom', 'logo_url' => 'https://example.org/telekom.svg', 'website_url' => 'https://www.telekom.de', 'sort_order' => 1]);
    $vodafone = MobileOperator::create(['name' => 'Vodafone', 'website_url' => 'https://www.vodafone.de', 'sort_order' => 2]);
    $o2 = MobileOperator::create(['name' => 'O2', 'sort_order' => 3]);
    $inactive = MobileOperator::create(['name' => 'Altnetz', 'is_active' => false]);
    $germany = operatorCountry('Deutschland', 'DE', 'DEU');

    $this->actingAs($admin = operatorAdmin());

    $editor = Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->assertSee('Mobilfunkanbieter')
        ->assertSee('Noch kein Anbieter zugeordnet')
        // Inaktive werden nicht angeboten.
        ->assertDontSee('Altnetz')
        ->set('mobileOperatorSearch', 'tele')
        ->assertSee('Telekom')
        ->assertDontSee('Vodafone');

    expect($editor->instance()->mobileOperatorMatches->pluck('name')->all())->toBe(['Telekom']);

    $editor
        ->call('addMobileOperator', $telekom->id)
        ->assertSet('mobileOperatorIds', [(string) $telekom->id])
        ->assertSet('mobileOperatorSearch', '')
        ->call('addMobileOperator', $o2->id)
        ->call('addMobileOperator', $inactive->id)
        ->assertSet('mobileOperatorIds', [(string) $telekom->id, (string) $o2->id])
        ->assertSee('Zugeordnet (2)')
        ->assertSee('https://example.org/telekom.svg')
        ->assertSee('telekom.de')
        ->call('save')
        ->assertHasNoErrors();

    expect($germany->mobileOperators()->pluck('name')->all())->toBe(['Telekom', 'O2'])
        ->and($telekom->countries()->pluck('iso_code')->all())->toBe(['DE'])
        ->and(MasterDataChange::where('model_id', $germany->id)->where('user_id', $admin->id)->latest('id')->value('changes'))->toBe(['mobile_operators']);

    // Zugeordnete erscheinen nicht mehr in den Treffern; Entfernen bringt sie zurueck.
    $fresh = Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->assertSet('mobileOperatorIds', [(string) $telekom->id, (string) $o2->id]);

    expect($fresh->instance()->mobileOperatorMatches->pluck('name')->all())->toBe(['Vodafone']);

    $fresh->call('removeMobileOperator', $telekom->id)
        ->assertSet('mobileOperatorIds', [(string) $o2->id])
        ->call('openAiCheck', 'mobile_operators')
        ->assertSet('aiSection', 'mobile_operators')
        ->call('save')
        ->assertHasNoErrors();

    expect($germany->mobileOperators()->pluck('name')->all())->toBe(['O2']);

    $o2->delete();
    expect($germany->mobileOperators()->count())->toBe(0);
});
