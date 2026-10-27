<?php

use App\Livewire\AdminV2\CustomerManagement\Customers\Editor;
use App\Livewire\AdminV2\System\Cooperations\Editor as CooperationEditor;
use App\Livewire\AdminV2\System\Cooperations\Index as CooperationIndex;
use App\Models\Cooperation;
use App\Models\Customer;
use App\Models\CustomerBillingInterval;
use App\Models\CustomerBillingPeriod;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Admin v2: System › Kooperationen und beim Kunden die Tabs „Abrechnung“
 * (selbststaendig oder ueber eine Kooperation, je Zeitraum) und „Abo“
 * (Abrechnungsintervall fuer alle Dienste oder einzelne Module).
 */
beforeEach(function () {
    Http::fake();
    Carbon::setTestNow('2026-10-15 12:00:00');
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));
});

afterEach(fn () => Carbon::setTestNow());

it('legt eine Kooperation mit Kickback an und prueft die Eingaben', function () {
    $account = Customer::factory()->create(['company_name' => 'solamento Zentrale']);

    Livewire::test(CooperationEditor::class)
        ->set('name', 'solamento')
        ->set('kickbackType', 'percent')
        ->set('kickbackValue', '120')
        ->call('save')
        ->assertHasErrors('kickbackValue');

    Livewire::test(CooperationEditor::class)
        ->set('name', 'solamento')
        ->call('chooseCustomer', $account->id)
        ->set('memberBilling', 'cooperation')
        ->set('billingInterval', 'monthly')
        ->set('kickbackType', 'percent')
        ->set('kickbackValue', '7,5')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.system.cooperations.index'));

    $cooperation = Cooperation::sole();
    expect($cooperation->customer_id)->toBe($account->id)
        ->and($cooperation->kickback_value)->toBe('7.50')
        ->and($cooperation->kickbackLabel())->toBe('7,5 %');

    Livewire::test(CooperationIndex::class)->assertSee('solamento')->assertSee('Kickback 7,5 %');
});

it('ordnet einen Kunden zeitlich Kooperationen zu, ohne Ueberschneidung', function () {
    $customer = Customer::factory()->create();
    $rtk = Cooperation::create(['name' => 'RTK', 'member_billing' => 'cooperation']);
    $tsg = Cooperation::create(['name' => 'TSG', 'member_billing' => 'self_payer']);

    $editor = Livewire::test(Editor::class, ['customer' => $customer->id])->call('showTab', 'billing');

    // Selbststaendig seit 01.01.2026, offen
    $editor->call('newBillingPeriod')
        ->set('billingForm.starts_on', '2026-01-01')
        ->call('saveBillingPeriod')
        ->assertHasNoErrors();

    // Wechsel zu RTK ab 01.11.2026: der offene Zeitraum endet am Vortag
    $editor->call('newBillingPeriod')
        ->set('billingForm.cooperation_id', (string) $rtk->id)
        ->assertSet('billingForm.billing_mode', 'cooperation')
        ->set('billingForm.starts_on', '2026-11-01')
        ->set('billingForm.ends_on', '2027-06-30')
        ->call('saveBillingPeriod')
        ->assertHasNoErrors();

    $periods = CustomerBillingPeriod::where('customer_id', $customer->id)->orderBy('starts_on')->get();
    expect($periods)->toHaveCount(2)
        ->and($periods[0]->ends_on->toDateString())->toBe('2026-10-31')
        ->and($periods[1]->cooperation_id)->toBe($rtk->id);

    // Ueberschneidung wird abgelehnt
    $editor->call('newBillingPeriod')
        ->set('billingForm.cooperation_id', (string) $tsg->id)
        ->assertSet('billingForm.billing_mode', 'self_payer')
        ->set('billingForm.starts_on', '2027-06-01')
        ->set('billingForm.close_previous', false)
        ->call('saveBillingPeriod')
        ->assertHasErrors('billingForm.starts_on');

    // Danach TSG, einzeln abgerechnet
    $editor->set('billingForm.starts_on', '2027-07-01')
        ->call('saveBillingPeriod')
        ->assertHasNoErrors();

    expect($customer->currentBillingPeriod->label())->toBe('Selbstständig · Selbstzahler');
    Carbon::setTestNow('2026-12-01');
    expect($customer->fresh()->currentBillingPeriod->cooperation_id)->toBe($rtk->id);

    // Kooperation mit Zeitraeumen laesst sich nicht loeschen
    Livewire::test(CooperationIndex::class)->call('delete', $rtk->id);
    expect(Cooperation::find($rtk->id))->not->toBeNull();
});

it('ohne Kooperation ist der Kunde immer Selbstzahler', function () {
    $customer = Customer::factory()->create();

    Livewire::test(Editor::class, ['customer' => $customer->id])
        ->call('newBillingPeriod')
        ->set('billingForm.billing_mode', 'cooperation')
        ->set('billingForm.starts_on', '2026-01-01')
        ->call('saveBillingPeriod')
        ->assertHasNoErrors();

    expect(CustomerBillingPeriod::sole()->billing_mode)->toBe('self_payer');
});

it('stellt das Abrechnungsintervall fuer alle Dienste und einzelne Module um', function () {
    $customer = Customer::factory()->create();

    $editor = Livewire::test(Editor::class, ['customer' => $customer->id])->call('showTab', 'subscription');

    $editor->call('newBillingInterval')
        ->set('intervalForm.interval', 'monthly')
        ->set('intervalForm.starts_on', '2026-01-01')
        ->call('saveBillingInterval')
        ->assertHasNoErrors();

    // Umstellen auf jaehrlich ab 01.01.2027 – monatlich endet am 31.12.2026
    $editor->call('newBillingInterval')
        ->set('intervalForm.interval', 'yearly')
        ->set('intervalForm.starts_on', '2027-01-01')
        ->call('saveBillingInterval')
        ->assertHasNoErrors();

    // Travel Alert einzeln halbjaehrlich – ueberschneidet sich nicht mit „alle Dienste“
    $editor->call('newBillingInterval', 'travel_alert')
        ->set('intervalForm.interval', 'half_yearly')
        ->set('intervalForm.starts_on', '2026-07-01')
        ->call('saveBillingInterval')
        ->assertHasNoErrors();

    // Zweiter Travel-Alert-Zeitraum, der sich ueberschneidet
    $editor->call('newBillingInterval', 'travel_alert')
        ->set('intervalForm.interval', 'monthly')
        ->set('intervalForm.starts_on', '2026-08-01')
        ->set('intervalForm.close_previous', false)
        ->call('saveBillingInterval')
        ->assertHasErrors('intervalForm.starts_on');

    $all = CustomerBillingInterval::whereNull('module')->orderBy('starts_on')->get();
    expect($all[0]->ends_on->toDateString())->toBe('2026-12-31')
        ->and(CustomerBillingInterval::intervalFor($customer->id, 'travel_alert', '2026-10-15')->interval)->toBe('half_yearly')
        ->and(CustomerBillingInterval::intervalFor($customer->id, 'global_travel_monitor', '2026-10-15')->interval)->toBe('monthly')
        ->and(CustomerBillingInterval::intervalFor($customer->id, 'global_travel_monitor', '2027-03-01')->interval)->toBe('yearly');

    $editor->assertSee('Travel Alert')->assertSee('Halbjährlich')->assertSee('Alle abonnierten Dienste');
});

it('zeigt die Abrechnung bei den Unteraccounts', function () {
    $agency = Customer::factory()->create();
    $coop = Cooperation::create(['name' => 'Kette Nord']);
    $office = Customer::factory()->create(['assign_to' => $agency->id, 'location_type' => 'travel_advisor']);
    $self = Customer::factory()->create(['assign_to' => $agency->id]);
    CustomerBillingPeriod::create(['customer_id' => $office->id, 'cooperation_id' => $coop->id, 'billing_mode' => 'cooperation', 'starts_on' => '2026-01-01']);
    CustomerBillingPeriod::create(['customer_id' => $self->id, 'billing_mode' => 'self_payer', 'starts_on' => '2026-01-01']);

    Livewire::test(Editor::class, ['customer' => $agency->id])
        ->call('showTab', 'sub-accounts')
        ->assertSee('Über Kette Nord')
        ->assertSee('Selbstzahler')
        ->assertSee('Reiseberater');
});

it('speichert einen Festbetrag pro Buero und zeigt ihn beim Kunden', function () {
    Livewire::test(CooperationEditor::class)
        ->set('name', 'Kette Süd')
        ->set('fixedPricePerOffice', '4,9x')
        ->call('save')
        ->assertHasErrors('fixedPricePerOffice')
        ->set('fixedPricePerOffice', '4,90')
        ->call('save')
        ->assertHasNoErrors();

    $cooperation = Cooperation::sole();
    expect($cooperation->fixed_price_per_office)->toBe('4.90')
        ->and($cooperation->fixedPriceLabel())->toBe('4,90 € pro Büro/Monat');

    Livewire::test(CooperationEditor::class, ['cooperation' => $cooperation->id])
        ->assertSet('fixedPricePerOffice', '4,90')
        ->set('fixedPricePerOffice', '')
        ->call('save');
    expect($cooperation->fresh()->fixed_price_per_office)->toBeNull();

    $cooperation->refresh()->update(['fixed_price_per_office' => '4.90']);
    $customer = Customer::factory()->create();
    $period = CustomerBillingPeriod::create(['customer_id' => $customer->id, 'cooperation_id' => $cooperation->id, 'billing_mode' => 'self_payer', 'starts_on' => '2026-01-01']);

    expect($period->label())->toBe('Kette Süd · einzeln abgerechnet (Selbstzahler) · Festbetrag 4,90 € pro Büro/Monat');

    Livewire::test(Editor::class, ['customer' => $customer->id])
        ->call('showTab', 'billing')
        ->assertSee('Festbetrag 4,90 € pro Büro/Monat');
    Livewire::test(CooperationIndex::class)->assertSee('Festbetrag 4,90 € pro Büro/Monat');
});

it('speichert einen Rabatt bei Jahreszahlung, aber nicht bei monatlicher', function () {
    $customer = Customer::factory()->create();

    $editor = Livewire::test(Editor::class, ['customer' => $customer->id])->call('showTab', 'subscription');

    $editor->call('newBillingInterval')
        ->set('intervalForm.interval', 'yearly')
        ->set('intervalForm.discount_percent', '120')
        ->set('intervalForm.starts_on', '2027-01-01')
        ->call('saveBillingInterval')
        ->assertHasErrors('intervalForm.discount_percent')
        ->set('intervalForm.discount_percent', '7,5')
        ->call('saveBillingInterval')
        ->assertHasNoErrors()
        ->assertSee('7,5 % Rabatt');

    $yearly = CustomerBillingInterval::sole();
    expect($yearly->discount_percent)->toBe('7.50');

    $editor->call('editBillingInterval', $yearly->id)
        ->assertSet('intervalForm.discount_percent', '7,5')
        ->set('intervalForm.interval', 'monthly')
        ->call('saveBillingInterval')
        ->assertHasNoErrors();

    expect($yearly->fresh()->discount_percent)->toBeNull();
});

it('speichert mehrere Rechnungsempfaenger mit CC und BCC', function () {
    Livewire::test(CooperationEditor::class)
        ->set('name', 'Kette West')
        ->set('invoiceEmails.to', 'rechnung@kette.test; chef@kette.test')
        ->set('invoiceEmails.cc', 'falsch@')
        ->call('save')
        ->assertHasErrors('invoiceEmails.cc')
        ->assertSee('Rechnungsempfänger')
        ->set('invoiceEmails.cc', 'steuer@kette.test')
        ->set('invoiceEmails.bcc', 'archiv@kette.test, ARCHIV@kette.test')
        ->call('save')
        ->assertHasNoErrors();

    $cooperation = Cooperation::sole();
    expect($cooperation->invoice_emails)->toEqual([
        'to' => ['rechnung@kette.test', 'chef@kette.test'],
        'cc' => ['steuer@kette.test'],
        'bcc' => ['archiv@kette.test'],
    ]);

    Livewire::test(CooperationEditor::class, ['cooperation' => $cooperation->id])
        ->assertSet('invoiceEmails.to', 'rechnung@kette.test, chef@kette.test');
    Livewire::test(CooperationIndex::class)->set('search', 'steuer@kette')->assertSee('Kette West');
});

it('speichert den Steuersatz an Kooperation und Kunde, Vorschlag 19 %', function () {
    $component = Livewire::test(CooperationEditor::class)
        ->assertSet('taxRate', '19')
        ->set('name', 'Steuerfrei-Kette')
        ->set('taxRate', '7')
        ->call('save')
        ->assertHasErrors('taxRate')
        ->set('taxRate', '0')
        ->call('save')
        ->assertHasNoErrors();

    expect(Cooperation::sole()->tax_rate)->toBe('0.00');
    Livewire::test(CooperationIndex::class)->assertSee('0 % USt.');

    $customer = Customer::factory()->create();
    expect($customer->fresh()->tax_rate)->toBe('19.00');

    Livewire::test(Editor::class, ['customer' => $customer->id])
        ->assertSet('taxRate', '19')
        ->set('taxRate', '0')
        ->call('save')
        ->assertHasNoErrors();

    expect($customer->fresh()->tax_rate)->toBe('0.00');
});
