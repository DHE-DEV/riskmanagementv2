<?php

use App\Livewire\AdminV2\System\Products\Compare;
use App\Livewire\AdminV2\System\Products\Editor;
use App\Livewire\AdminV2\System\Products\Index;
use App\Models\CustomerBillingInterval;
use App\Models\Product;
use App\Models\ProductFeatureValue;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/**
 * Admin v2 › System › Produkte: Produkte mit Auspraegungen, Preisen ab
 * Stichtag, kostenlos, Leistungsumfang und Gegenueberstellung.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));
});

afterEach(fn () => Carbon::setTestNow());

it('legt die Module des Travel Information Service mit den bekannten Preisen an', function () {
    $trs = Product::where('key', 'travel_requirements')->with('versions.prices')->sole();

    expect($trs->versions->pluck('name')->all())->toBe(['Standard', 'Premium'])
        ->and($trs->versions[0]->priceOn()->price_monthly)->toBe('9.90')
        ->and($trs->versions[1]->priceOn()->price_monthly)->toBe('14.90')
        ->and(Product::where('key', 'travel_alert')->sole()->requiredProduct->key)->toBe('travel_requirements')
        ->and(Product::moduleOptions())->toHaveCount(13);

    Livewire::test(Index::class)
        ->assertSee('Travel Requirements Service')
        ->assertSee('9,90 € pro Monat')
        ->assertSee('nur mit Travel Requirements Service');
});

it('teilt ein Produkt in Standard und Premium mit eigenem Preis, kostenlos und Leistungsumfang', function () {
    $gtm = Product::where('key', 'global_travel_monitor')->sole();

    $editor = Livewire::test(Editor::class, ['product' => $gtm->id])
        ->assertSet('versions.0.name', 'Allgemein')
        ->call('addVersion')
        ->assertSet('versions.0.name', 'Standard')
        ->assertSet('versions.1.name', 'Premium')
        ->call('addPrice', 0)
        ->set('versions.0.prices.0.valid_from', '2026-01-01')
        ->set('versions.0.prices.0.is_free', true)
        ->call('addPrice', 1)
        ->set('versions.1.prices.0.valid_from', '2026-01-01')
        ->set('versions.1.prices.0.price_monthly', '4,9x')
        ->call('save')
        ->assertHasErrors('versions.1.prices.0.price_monthly')
        ->set('versions.1.prices.0.price_monthly', '4,90')
        ->call('addFeature')
        ->set('features.0.name', 'Ereignisse weltweit')
        ->call('addFeature')
        ->set('features.1.name', 'Eigene Ereignisse anlegen');

    $premiumUid = $editor->get('versions.1.uid');
    $standardUid = $editor->get('versions.0.uid');
    $editor->set("features.1.values.$standardUid.included", false)
        ->set("features.1.values.$premiumUid.note", 'bis 50 je Monat')
        ->call('save')
        ->assertHasNoErrors();

    $gtm->load('versions.prices', 'features.values');
    expect($gtm->versions->pluck('name')->all())->toBe(['Standard', 'Premium'])
        ->and($gtm->versions[0]->priceOn()->label())->toBe('kostenlos')
        ->and($gtm->versions[1]->priceOn()->label())->toBe('4,90 € pro Monat')
        ->and($gtm->features->pluck('name')->all())->toBe(['Ereignisse weltweit', 'Eigene Ereignisse anlegen'])
        ->and(ProductFeatureValue::where('product_feature_id', $gtm->features[1]->id)->where('product_version_id', $gtm->versions[0]->id)->value('included'))->toBeFalse()
        ->and(ProductFeatureValue::where('product_feature_id', $gtm->features[1]->id)->where('product_version_id', $gtm->versions[1]->id)->value('note'))->toBe('bis 50 je Monat');

    Livewire::test(Compare::class)
        ->set('product', (string) $gtm->id)
        ->assertSee('Global Travel Monitor')
        ->assertSee('kostenlos')
        ->assertSee('4,90 € pro Monat')
        ->assertSee('Eigene Ereignisse anlegen')
        ->assertSee('bis 50 je Monat')
        ->assertDontSee('14,90');
});

it('zeigt je Stichtag den passenden Preis', function () {
    $alert = Product::where('key', 'travel_alert')->sole();

    Livewire::test(Editor::class, ['product' => $alert->id])
        ->call('addPrice', 0)
        ->set('versions.0.prices.0.valid_from', '2027-01-01')
        ->set('versions.0.prices.0.price_monthly', '8,00')
        ->call('save')
        ->assertHasNoErrors();

    $version = $alert->versions()->with('prices')->first();
    expect($version->priceOn('2026-12-31')->price_monthly)->toBe('7.00')
        ->and($version->priceOn('2027-01-01')->price_monthly)->toBe('8.00');

    // Zwei Preise am selben Tag sind nicht erlaubt
    Livewire::test(Editor::class, ['product' => $alert->id])
        ->call('addPrice', 0)
        ->set('versions.0.prices.0.valid_from', '2027-01-01')
        ->call('save')
        ->assertHasErrors();
});

it('legt ein neues Produkt an und verlangt eindeutige Kuerzel', function () {
    Livewire::test(Editor::class)
        ->set('name', 'Doctors Network')
        ->set('key', 'wallet')
        ->call('save')
        ->assertHasErrors('key')
        ->set('key', 'doctors_network')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('key', 'doctors_network')->sole();
    expect($product->versions()->pluck('name')->all())->toBe(['Allgemein'])
        ->and(Product::moduleOptions())->toHaveKey('doctors_network');
});

it('loescht kein Produkt, das in Abo-Zeitraeumen steckt', function () {
    $wallet = Product::where('key', 'wallet')->sole();
    $customer = \App\Models\Customer::factory()->create();
    CustomerBillingInterval::create(['customer_id' => $customer->id, 'module' => 'wallet', 'interval' => 'yearly', 'starts_on' => '2026-01-01']);

    Livewire::test(Index::class)->call('delete', $wallet->id);
    expect(Product::find($wallet->id))->not->toBeNull();

    expect(CustomerBillingInterval::labelForModule('wallet'))->toBe('Wallet');
});

it('bindet die Voraussetzung an eine bestimmte Auspraegung', function () {
    $alert = Product::where('key', 'travel_alert')->sole();
    $trs = Product::where('key', 'travel_requirements')->with('versions')->sole();
    $premium = $trs->versions->firstWhere('name', 'Premium');
    $wallet = Product::where('key', 'wallet')->with('versions')->sole();

    // Auspraegung eines anderen Produkts wird abgelehnt
    Livewire::test(Editor::class, ['product' => $alert->id])
        ->assertSet('requiresProductId', (string) $trs->id)
        ->assertSet('requiresVersionId', '')
        ->set('requiresVersionId', (string) $wallet->versions->first()->id)
        ->call('save')
        ->assertHasErrors('requiresVersionId')
        ->set('requiresVersionId', (string) $premium->id)
        ->call('save')
        ->assertHasNoErrors();

    $alert->refresh();
    expect($alert->requires_product_version_id)->toBe($premium->id)
        ->and($alert->requirementLabel())->toBe('Travel Requirements Service (Premium)');

    Livewire::test(Index::class)->assertSee('nur mit Travel Requirements Service (Premium)');

    // Anderes Produkt gewaehlt: Auspraegung wird zurueckgesetzt
    Livewire::test(Editor::class, ['product' => $alert->id])
        ->set('requiresProductId', (string) $wallet->id)
        ->assertSet('requiresVersionId', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($alert->fresh()->requires_product_version_id)->toBeNull();
});
