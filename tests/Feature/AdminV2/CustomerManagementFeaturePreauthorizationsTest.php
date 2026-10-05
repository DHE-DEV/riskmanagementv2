<?php

use App\Livewire\AdminV2\CustomerManagement\FeaturePreauthorizations\Editor;
use App\Livewire\AdminV2\CustomerManagement\FeaturePreauthorizations\Index;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\CustomerFeaturePreauthorization;
use App\Models\User;
use Livewire\Livewire;

/**
 * Kundenverwaltung > Feature-Vormerkungen: Liste mit Filtern, Import,
 * Anwenden auf bestehende Konten, Loeschen und das Formular.
 */
function preauthAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function preauthorization(int $accountId, array $attributes = []): CustomerFeaturePreauthorization
{
    return CustomerFeaturePreauthorization::create(array_merge([
        'pds_account_id' => $accountId,
        'feature_key' => 'navigation_risk_overview_enabled',
        'enabled' => true,
    ], $attributes));
}

function preauthorizedCustomer(int $accountId, array $attributes = []): Customer
{
    return Customer::factory()->create(array_merge(['pds_account_id' => $accountId], $attributes));
}

// Eine Migration merkt bereits Travel-Alert-Accounts vor – die Tests beginnen mit leerer Tabelle.
beforeEach(fn () => CustomerFeaturePreauthorization::query()->delete());

it('zeigt die Liste mit Suche, Filtern und Sortierung', function () {
    $this->actingAs(preauthAdmin());

    $this->get(route('adminv2.customer-management.feature-preauthorizations.index'))
        ->assertOk()
        ->assertSee('Keine Vormerkungen')
        ->assertSee('IDs importieren');

    preauthorizedCustomer(11111, ['company_name' => 'Reisebüro Sonnenschein']);

    preauthorization(11111, ['note' => 'Liste August']);
    preauthorization(22222, ['feature_key' => 'navigation_cruise_enabled', 'enabled' => false]);
    preauthorization(33333, ['applied_at' => now(), 'applied_customer_id' => 1]);

    $this->get(route('adminv2.customer-management.feature-preauthorizations.index'))
        ->assertOk()
        ->assertSee('11111')
        ->assertSee('Travel Alert')
        ->assertSee('Kreuzfahrten')
        ->assertSee('Liste August')
        ->assertSee('Reisebüro Sonnenschein')
        ->assertSee('2 offen');

    Livewire::test(Index::class)
        // Vorgabe: nur offene – eingeloeste stehen beim Oeffnen nicht in der Liste.
        ->assertSet('status', 'open')
        ->assertSee(['11111', '22222'])->assertDontSee('33333')
        // Suche nach der Account-ID – sie findet auch eingeloeste.
        ->set('search', '222')
        ->assertSee('22222')->assertDontSee('11111')
        ->set('search', '333')
        ->assertSee('33333')->assertDontSee('11111')
        ->call('resetFilters')
        ->assertDontSee('33333')
        // Feature
        ->set('feature', 'navigation_cruise_enabled')
        ->assertSee('22222')->assertDontSee('33333')
        ->set('feature', '')
        // Freischaltungen bzw. Sperren
        ->set('enabled', 'no')
        ->assertSee('22222')->assertDontSee('11111')
        ->set('enabled', 'yes')
        ->assertSee('11111')->assertDontSee('22222')
        ->set('enabled', '')
        // Eingeloeste bzw. alle
        ->set('status', 'applied')
        ->assertSee('33333')->assertDontSee('11111')
        ->set('status', 'all')
        ->assertSee(['11111', '33333'])
        ->set('status', 'open')
        // Ohne Kundenkonto
        ->set('withoutAccount', true)
        ->assertSee('22222')->assertDontSee('11111')
        ->assertSet('selected', [])
        ->set('status', 'all')
        ->call('resetFilters')
        ->assertSet('withoutAccount', false)
        ->assertSet('status', 'open')
        // Sortierung nach Account-ID
        ->set('status', 'all')
        ->set('sort', 'pds_account_id')
        ->set('direction', 'asc')
        ->assertSeeInOrder(['11111', '22222', '33333'])
        ->call('toggleDirection')
        ->assertSeeInOrder(['33333', '22222', '11111'])
        // Unbekannte Sortierspalte faellt auf die Vorgabe zurueck.
        ->set('sort', 'password')
        ->assertOk();
});

it('importiert eine ID-Liste, aktualisiert vorhandene und wendet sofort an', function () {
    $this->actingAs(preauthAdmin());

    $customer = preauthorizedCustomer(25893);
    preauthorization(25427, ['enabled' => false, 'note' => 'alt']);

    Livewire::test(Index::class)
        ->call('import')
        ->assertHasErrors('importIds')
        ->set('importIds', 'abc, -5; 0')
        ->call('import')
        ->assertHasErrors('importIds')
        ->set('importIds', "25893\n25427, 23854;25893 abc")
        ->set('importFeatureKey', 'unbekannt')
        ->call('import')
        ->assertHasErrors('importFeatureKey')
        ->set('importFeatureKey', 'navigation_risk_overview_enabled')
        ->set('importNote', 'Travel-Alert-Liste')
        ->call('import')
        ->assertHasNoErrors()
        ->assertDispatched('adminv2-toast', message: 'Import abgeschlossen: 3 Account-IDs vorgemerkt, davon 1 mit bestehendem Konto. 1 Konten sofort angepasst.')
        ->assertSet('importIds', '');

    expect(CustomerFeaturePreauthorization::count())->toBe(3)
        // Bereits vorgemerkt: aktualisiert statt doppelt angelegt.
        ->and(CustomerFeaturePreauthorization::where('pds_account_id', 25427)->first()->enabled)->toBeTrue()
        ->and(CustomerFeaturePreauthorization::where('pds_account_id', 25427)->first()->note)->toBe('Travel-Alert-Liste')
        // Bestehendes Konto: sofort freigeschaltet und als eingeloest vermerkt.
        ->and(CustomerFeatureOverride::where('customer_id', $customer->id)->first()->navigation_risk_overview_enabled)->toBeTrue()
        ->and(CustomerFeaturePreauthorization::where('pds_account_id', 25893)->first()->applied_at)->not->toBeNull()
        ->and(CustomerFeaturePreauthorization::where('pds_account_id', 23854)->first()->applied_at)->toBeNull();

    // Ohne "sofort anwenden" bleibt das Konto bis zum Login unberuehrt.
    $later = preauthorizedCustomer(40000);

    Livewire::test(Index::class)
        ->set('importIds', '40000')
        ->set('importFeatureKey', 'navigation_cruise_enabled')
        ->set('importEnabled', false)
        ->set('importApplyNow', false)
        ->call('import')
        ->assertHasNoErrors();

    expect(CustomerFeatureOverride::where('customer_id', $later->id)->exists())->toBeFalse()
        ->and(CustomerFeaturePreauthorization::where('pds_account_id', 40000)->first()->enabled)->toBeFalse();
});

it('wendet einzelne, ausgewaehlte und alle offenen Vormerkungen an', function () {
    $this->actingAs(preauthAdmin());

    $first = preauthorizedCustomer(100);
    $second = preauthorizedCustomer(200);
    $third = preauthorizedCustomer(300);

    $a = preauthorization(100);
    $b = preauthorization(200, ['feature_key' => 'navigation_cruise_enabled']);
    $c = preauthorization(300, ['enabled' => false]);
    $none = preauthorization(999);

    $list = Livewire::test(Index::class)
        // Einzeln
        ->call('apply', $a->id)
        ->assertDispatched('adminv2-toast', message: 'Angewendet: 1 Konten angepasst (1 Freischaltungen).');

    expect(CustomerFeatureOverride::where('customer_id', $first->id)->first()->navigation_risk_overview_enabled)->toBeTrue()
        ->and(CustomerFeatureOverride::where('customer_id', $second->id)->exists())->toBeFalse();

    // Ohne Konto gibt es nichts anzuwenden.
    $list->call('apply', $none->id)
        ->assertDispatched('adminv2-toast', message: 'Nichts zu tun: Alle betroffenen Konten haben bereits einen gesetzten Wert oder es gibt noch keine Konten.');

    // Auswahl – ueber verschiedene Features hinweg
    $list->set('selected', [(string) $b->id, (string) $none->id])
        ->call('applySelected')
        ->assertSet('selected', [])
        ->assertDispatched('adminv2-toast', message: 'Angewendet: 1 Konten angepasst (1 Freischaltungen).');

    expect(CustomerFeatureOverride::where('customer_id', $second->id)->first()->navigation_cruise_enabled)->toBeTrue()
        ->and(CustomerFeatureOverride::where('customer_id', $third->id)->exists())->toBeFalse();

    // Alle offenen: uebrig ist nur noch die Sperre fuer Account 300.
    $list->call('applyPending')
        ->assertDispatched('adminv2-toast', message: 'Angewendet: 1 Konten angepasst (1 Freischaltungen).');

    expect(CustomerFeatureOverride::where('customer_id', $third->id)->first()->navigation_risk_overview_enabled)->toBeFalse()
        ->and($c->fresh()->applied_customer_id)->toBe($third->id)
        ->and($none->fresh()->applied_at)->toBeNull();

    $list->call('applyPending')
        ->assertDispatched('adminv2-toast', message: 'Nichts zu tun: 0 Konten angepasst (0 Freischaltungen).');
});

it('waehlt die Seite aus und loescht nur die Vormerkungen der Auswahl', function () {
    $this->actingAs(preauthAdmin());

    $customer = preauthorizedCustomer(100);
    $a = preauthorization(100);
    $b = preauthorization(200);
    $c = preauthorization(300);

    // Die eingeloeste Vormerkung verschwindet aus der Vorgabe "nur offene".
    $list = Livewire::test(Index::class)->call('apply', $a->id)->call('togglePage');

    expect($list->get('selected'))->toBe([(string) $c->id, (string) $b->id]);

    $list->call('togglePage')->set('status', 'all')->call('togglePage');

    expect($list->get('selected'))->toHaveCount(3);

    $list->call('togglePage')
        ->assertSet('selected', [])
        ->set('selected', [(string) $a->id, (string) $b->id])
        ->call('deleteSelected')
        ->assertSet('selected', [])
        ->assertDispatched('adminv2-toast', message: '2 Vormerkungen gelöscht. Bereits erteilte Freischaltungen bleiben bestehen.');

    expect(CustomerFeaturePreauthorization::pluck('id')->all())->toBe([$c->id])
        // Die bereits erteilte Freischaltung bleibt.
        ->and(CustomerFeatureOverride::where('customer_id', $customer->id)->first()->navigation_risk_overview_enabled)->toBeTrue();
});

it('weist auf eingeloeste Vormerkungen hin, wenn nichts mehr offen ist', function () {
    $this->actingAs(preauthAdmin());

    preauthorization(100, ['applied_at' => now(), 'applied_customer_id' => 1]);
    preauthorization(200, ['applied_at' => now(), 'applied_customer_id' => 1]);

    $this->get(route('adminv2.customer-management.feature-preauthorizations.index'))
        ->assertOk()
        ->assertSee('Keine offenen Vormerkungen')
        ->assertSee('Alle 2 Vormerkungen sind bereits eingelöst.')
        ->assertSee('Eingelöste anzeigen')
        ->assertDontSee('lassen sich ganze Account-Listen auf einmal vormerken');

    Livewire::test(Index::class)
        ->set('status', 'applied')
        ->assertSee(['100', '200'])
        ->assertDontSee('Keine offenen Vormerkungen');

    // Die Auswahl laesst sich ueber die Adresse vorgeben.
    $this->get(route('adminv2.customer-management.feature-preauthorizations.index', ['status' => 'all']))
        ->assertOk()
        ->assertDontSee('Keine offenen Vormerkungen');
});

it('merkt einzeln vor, prueft die Eingaben und wendet bei bestehendem Konto sofort an', function () {
    $this->actingAs(preauthAdmin());

    $this->get(route('adminv2.customer-management.feature-preauthorizations.create'))
        ->assertOk()
        ->assertSee('Feature einzeln vormerken');

    $customer = preauthorizedCustomer(555, ['company_name' => 'Reisewelt GmbH']);

    Livewire::test(Editor::class)
        ->assertSet('featureKey', 'navigation_risk_overview_enabled')
        ->assertSet('enabled', true)
        ->call('save')
        ->assertHasErrors('pdsAccountId')
        ->set('pdsAccountId', '0')
        ->call('save')
        ->assertHasErrors('pdsAccountId')
        ->set('pdsAccountId', '777')
        ->assertSee('Noch kein Konto – greift beim ersten Login')
        ->set('pdsAccountId', '555')
        ->assertSee('1 Konto vorhanden')
        ->assertSee('Reisewelt GmbH')
        ->set('featureKey', 'unbekannt')
        ->set('note', str_repeat('x', 256))
        ->call('save')
        ->assertHasErrors(['featureKey', 'note'])
        ->set('featureKey', 'navigation_cruise_enabled')
        ->set('note', 'Messe-Kontakt')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.customer-management.feature-preauthorizations.index'));

    $record = CustomerFeaturePreauthorization::first();

    expect($record->pds_account_id)->toBe(555)
        ->and($record->feature_key)->toBe('navigation_cruise_enabled')
        ->and($record->note)->toBe('Messe-Kontakt')
        ->and($record->applied_at)->not->toBeNull()
        ->and(CustomerFeatureOverride::where('customer_id', $customer->id)->first()->navigation_cruise_enabled)->toBeTrue()
        ->and(session('adminv2-toast'))->toBe('Sofort angewendet: 1 bestehende Konten angepasst.');

    // Dieselbe Account-ID mit demselben Feature gibt es nur einmal.
    Livewire::test(Editor::class)
        ->set('pdsAccountId', '555')
        ->set('featureKey', 'navigation_cruise_enabled')
        ->call('save')
        ->assertHasErrors('pdsAccountId')
        // Ohne Konto: nur vorgemerkt.
        ->set('pdsAccountId', '888')
        ->set('enabled', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(session('adminv2-toast'))->toBe('Vorgemerkt. Greift beim ersten Login dieses Accounts.')
        ->and(CustomerFeaturePreauthorization::where('pds_account_id', 888)->first()->enabled)->toBeFalse();
});

it('bearbeitet und loescht eine Vormerkung', function () {
    $this->actingAs(preauthAdmin());

    $customer = preauthorizedCustomer(100);
    $record = preauthorization(100, ['note' => 'alt']);

    $this->get(route('adminv2.customer-management.feature-preauthorizations.edit', $record->id))
        ->assertOk()
        ->assertSee('Vormerkung für Account 100')
        ->assertSee('offen');

    $this->get(route('adminv2.customer-management.feature-preauthorizations.edit', 999999))->assertNotFound();

    Livewire::test(Editor::class, ['preauthorization' => $record->id])
        ->assertSet('pdsAccountId', '100')
        ->assertSet('note', 'alt')
        ->set('note', '')
        ->set('enabled', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.customer-management.feature-preauthorizations.index'));

    // Bearbeiten wendet nichts an – das bleibt dem Login oder "Jetzt anwenden" ueberlassen.
    expect($record->fresh()->enabled)->toBeFalse()
        ->and($record->fresh()->note)->toBeNull()
        ->and(CustomerFeatureOverride::where('customer_id', $customer->id)->exists())->toBeFalse();

    Livewire::test(Editor::class, ['preauthorization' => $record->id])
        ->call('delete')
        ->assertRedirect(route('adminv2.customer-management.feature-preauthorizations.index'));

    expect(CustomerFeaturePreauthorization::count())->toBe(0);
});

it('ist nur fuer Administratoren erreichbar', function () {
    $record = preauthorization(100);

    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]));

    $this->get(route('adminv2.customer-management.feature-preauthorizations.index'))->assertForbidden();
    $this->get(route('adminv2.customer-management.feature-preauthorizations.create'))->assertForbidden();
    $this->get(route('adminv2.customer-management.feature-preauthorizations.edit', $record->id))->assertForbidden();

    Livewire::test(Index::class)->assertForbidden();
});
