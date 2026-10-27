<?php

use App\Livewire\AdminV2\CustomerManagement\Customers\Editor;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Admin v2 › Kunde › Abo: Vertraege mit Laufzeit, Bedingungen und
 * individuellen Preisen je Auspraegung, optional an PAX gebunden.
 */
beforeEach(function () {
    Http::fake();
    Carbon::setTestNow('2026-10-15 12:00:00');
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));
});

afterEach(fn () => Carbon::setTestNow());

it('legt einen Vertrag mit Bedingungen und individuellen Preisen samt PAX an', function () {
    $customer = Customer::factory()->create();
    $trs = Product::where('key', 'travel_requirements')->with('versions')->sole();
    $premium = $trs->versions->firstWhere('name', 'Premium');
    $alert = Product::where('key', 'travel_alert')->with('versions')->sole()->versions->first();

    Livewire::test(Editor::class, ['customer' => $customer->id])
        ->call('showTab', 'subscription')
        ->call('newContract')
        ->assertSee('Travel Requirements Service')
        ->assertSee('14,90 € pro Monat')
        ->set('contractForm.name', 'Rahmenvertrag Großkunde')
        ->set('contractForm.starts_on', '2027-01-01')
        ->set('contractForm.ends_on', '2028-12-31')
        ->set('contractForm.conditions.0', 'Mindestens 50 Büros angeschlossen')
        ->call('addContractCondition')
        ->set('contractForm.conditions.1', 'Preise gelten nur bei Jahreszahlung')
        ->set("contractForm.prices.{$premium->id}.price_yearly", '9,9x')
        ->call('saveContract')
        ->assertHasErrors("contractForm.prices.{$premium->id}.price_yearly")
        ->set("contractForm.prices.{$premium->id}.price_yearly", '99.000,00')
        ->set("contractForm.prices.{$premium->id}.pax", '10.000.000')
        ->set("contractForm.prices.{$alert->id}.is_free", true)
        ->call('saveContract')
        ->assertHasNoErrors()
        ->assertSee('Rahmenvertrag Großkunde')
        ->assertSee('Mindestens 50 Büros angeschlossen');

    $contract = CustomerContract::with('prices')->sole();
    expect($contract->conditions)->toBe(['Mindestens 50 Büros angeschlossen', 'Preise gelten nur bei Jahreszahlung'])
        ->and($contract->prices)->toHaveCount(2)
        ->and($contract->priceFor($premium->id)->price_yearly)->toBe('99000.00')
        ->and($contract->priceFor($premium->id)->pax)->toBe(10000000)
        ->and($contract->priceFor($premium->id)->label())->toBe('99.000,00 € pro Jahr · für 10.000.000 PAX')
        ->and($contract->priceFor($alert->id)->label())->toBe('kostenlos')
        ->and($contract->priceFor($trs->versions->firstWhere('name', 'Standard')->id))->toBeNull();
});

it('laesst Vertraege sich nicht ueberschneiden und entfernt geleerte Preise', function () {
    $customer = Customer::factory()->create();
    $standard = Product::where('key', 'travel_requirements')->with('versions')->sole()->versions->firstWhere('name', 'Standard');

    $editor = Livewire::test(Editor::class, ['customer' => $customer->id])->call('showTab', 'subscription');

    $editor->call('newContract')
        ->set('contractForm.starts_on', '2026-01-01')
        ->set('contractForm.ends_on', '2026-12-31')
        ->set("contractForm.prices.{$standard->id}.price_monthly", '8,50')
        ->call('saveContract')
        ->assertHasNoErrors();

    expect(CustomerContract::sole()->priceFor($standard->id)->price_monthly)->toBe('8.50');

    $editor->call('newContract')
        ->set('contractForm.name', 'Folgevertrag')
        ->set('contractForm.starts_on', '2026-12-01')
        ->call('saveContract')
        ->assertHasErrors('contractForm.starts_on');

    $contract = CustomerContract::sole();
    $editor->call('editContract', $contract->id)
        ->assertSet("contractForm.prices.{$standard->id}.price_monthly", '8,50')
        ->set("contractForm.prices.{$standard->id}.price_monthly", '')
        ->call('saveContract')
        ->assertHasNoErrors();

    expect($contract->prices()->count())->toBe(0);
});

it('speichert die jaehrliche Preiserhoehung und erinnert einmal je Vertragsjahr', function () {
    \Illuminate\Support\Facades\Mail::fake();
    config(['app.contract_price_increase_mail' => ['to' => ['dennis.zimon@passolution.de'], 'cc' => ['daniel.henninger@passolution.de']]]);

    $customer = Customer::factory()->create(['company_name' => 'Großkunde AG']);
    $standard = Product::where('key', 'travel_requirements')->with('versions')->sole()->versions->firstWhere('name', 'Standard');

    Livewire::test(Editor::class, ['customer' => $customer->id])
        ->call('showTab', 'subscription')
        ->call('newContract')
        ->set('contractForm.starts_on', '2025-11-01')
        ->set('contractForm.annual_increase_percent', '2,5')
        ->set("contractForm.prices.{$standard->id}.price_monthly", '8,00')
        ->call('saveContract')
        ->assertHasNoErrors()
        ->assertSee('Preiserhöhung 2,5 % pro Jahr (nächste 01.11.2026)');

    $contract = CustomerContract::sole();
    expect($contract->annual_increase_percent)->toBe('2.50');

    // 15.10.2026: Jahrestag 01.11.2026 liegt innerhalb von 30 Tagen
    $this->artisan('contracts:price-increase-reminders')->assertSuccessful();
    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContractPriceIncreaseReminderMail::class, function ($mail) {
        $html = $mail->render();

        return $mail->hasTo('dennis.zimon@passolution.de')
            && $mail->hasCc('daniel.henninger@passolution.de')
            && $mail->anniversary->toDateString() === '2026-11-01'
            && str_contains($html, '8,00 €')
            && str_contains($html, '8,20 €')
            && str_contains($html, 'Es wurde nichts automatisch geändert');
    });
    expect($contract->fresh()->increase_reminded_for->toDateString())->toBe('2026-11-01')
        ->and($contract->prices()->first()->price_monthly)->toBe('8.00');

    // Am naechsten Tag nicht noch einmal
    Carbon::setTestNow('2026-10-16 07:00:00');
    $this->artisan('contracts:price-increase-reminders')->assertSuccessful();
    \Illuminate\Support\Facades\Mail::assertSentCount(1);

    // Im naechsten Jahr wieder
    Carbon::setTestNow('2027-10-05 07:00:00');
    $this->artisan('contracts:price-increase-reminders')->assertSuccessful();
    \Illuminate\Support\Facades\Mail::assertSentCount(2);
});

it('erinnert nicht, wenn der Vertrag vor dem Jahrestag endet oder nichts vereinbart ist', function () {
    \Illuminate\Support\Facades\Mail::fake();
    $customer = Customer::factory()->create();
    CustomerContract::create(['customer_id' => $customer->id, 'name' => 'Endet', 'starts_on' => '2025-11-01', 'ends_on' => '2026-10-31', 'annual_increase_percent' => 3]);
    CustomerContract::create(['customer_id' => Customer::factory()->create()->id, 'name' => 'Ohne', 'starts_on' => '2025-11-01']);

    $this->artisan('contracts:price-increase-reminders')->assertSuccessful();
    \Illuminate\Support\Facades\Mail::assertNothingSent();
});
