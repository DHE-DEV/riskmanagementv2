<?php

use App\Livewire\AdminV2\CustomerManagement\Customers\Editor;
use App\Livewire\AdminV2\CustomerManagement\Customers\Index;
use App\Models\Branch;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\GtmApiRequestLog;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Kundenverwaltung > Kunden: Liste mit Suche, Filtern, Papierkorb und
 * Sammelaktionen sowie die Seite eines Kunden mit Stammdaten, Filialen,
 * Account-Zugriffen, API Tokens und GTM API Logs.
 */
function customersAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function managedCustomer(array $attributes = []): Customer
{
    return Customer::factory()->create(array_merge([
        'name' => 'Bernd Berg',
        'email' => 'bernd@berg.test',
        'customer_type' => 'business',
        'email_verified_at' => '2026-10-01 09:30:45',
    ], $attributes));
}

function customerBranch(Customer $customer, string $name, array $attributes = []): Branch
{
    return Branch::create(array_merge([
        'customer_id' => $customer->id,
        'name' => $name,
        'street' => 'Hauptstrasse',
        'house_number' => '1',
        'postal_code' => '50667',
        'city' => 'Koeln',
        'country' => 'Deutschland',
    ], $attributes));
}

function customerGtmLog(Customer $customer, string $at, array $attributes = []): GtmApiRequestLog
{
    return GtmApiRequestLog::create(array_merge([
        'customer_id' => $customer->id,
        'method' => 'GET',
        'endpoint' => '/gtm-events',
        'response_status' => 200,
        'response_time_ms' => 120,
        'ip_address' => '203.0.113.5',
        'created_at' => $at,
    ], $attributes));
}

beforeEach(function () {
    // Die Observer von Kunde und Filiale fragen sonst Koordinaten im Netz ab.
    Http::fake();
    Carbon::setTestNow('2026-10-15 12:00:00');
});

afterEach(fn () => Carbon::setTestNow());

it('zeigt die Kunden mit Suche, Filtern und Sortierung', function () {
    $this->actingAs(customersAdmin());

    $this->get(route('adminv2.customer-management.customers.index'))
        ->assertOk()
        ->assertSee('Keine Kunden');

    $anna = managedCustomer(['name' => 'Anna Adler', 'email' => 'anna@adler.test', 'customer_type' => 'private', 'email_verified_at' => null, 'created_at' => '2026-10-10 08:00:00']);
    $bernd = managedCustomer(['company_name' => 'Berg Reisen GmbH', 'pds_account_id' => 4711, 'passolution_subscription_type' => 'premium', 'created_at' => '2026-10-12 08:00:00']);
    $clara = managedCustomer(['name' => 'Clara Conrad', 'email' => 'clara@conrad.test', 'provider' => 'google', 'created_at' => '2026-10-14 08:00:00']);
    $clara->delete();

    customerBranch($bernd, 'Filiale Bonn');

    $this->get(route('adminv2.customer-management.customers.index'))
        ->assertOk()
        ->assertSee('Anna Adler')
        ->assertSee('Berg Reisen GmbH')
        ->assertSee('4711')
        ->assertSee('premium')
        ->assertSee('1 Filiale')
        ->assertSee('E-Mail nicht verifiziert')
        ->assertSee(route('adminv2.customer-management.customers.edit', $bernd->id))
        // Geloeschte Kunden stehen nur im Papierkorb.
        ->assertDontSee('Clara Conrad');

    $list = Livewire::test(Index::class);

    // Vorgabe: neueste Registrierung zuerst.
    $list->assertSeeInOrder(['Bernd Berg', 'Anna Adler']);

    $list->set('sort', 'name')->call('toggleDirection')
        ->assertSeeInOrder(['Anna Adler', 'Bernd Berg']);

    $list->set('sort', 'branches_count')->call('toggleDirection')
        ->assertSeeInOrder(['Bernd Berg', 'Anna Adler']);

    // Eine unbekannte Sortierung faellt auf die Vorgabe zurueck.
    $list->set('sort', 'password')->assertSeeInOrder(['Bernd Berg', 'Anna Adler']);

    $list->set('search', 'berg reisen')->assertSee('Bernd Berg')->assertDontSee('Anna Adler');
    $list->set('search', '4711')->assertSee('Bernd Berg')->assertDontSee('Anna Adler');
    $list->set('search', 'adler.test')->assertSee('Anna Adler')->assertDontSee('Bernd Berg');
    // Jedes Wort darf in einer anderen Spalte stehen.
    $list->set('search', 'Bernd Reisen')->assertSee('Bernd Berg')->assertDontSee('Anna Adler');
    $list->set('search', 'Bernd Adler')->assertDontSee('Bernd Berg')->assertDontSee('Anna Adler');
    // Platzhalter des Nutzers gelten als Text.
    $list->set('search', '%')->assertDontSee('Anna Adler')->assertDontSee('Bernd Berg');

    $list->set('search', '')->set('customerType', 'private')->assertSee('Anna Adler')->assertDontSee('Bernd Berg');
    $list->set('customerType', '')->set('emailVerified', 'verified')->assertSee('Bernd Berg')->assertDontSee('Anna Adler');
    $list->set('emailVerified', 'unverified')->assertSee('Anna Adler')->assertDontSee('Bernd Berg');

    $list->set('emailVerified', '')->set('trashed', 'with')
        ->assertSee('Clara Conrad')->assertSee('Gelöscht')->assertSee('Google')->assertSee('Bernd Berg');
    $list->set('trashed', 'only')->assertSee('Clara Conrad')->assertDontSee('Bernd Berg');

    expect($list->instance()->hasFilters())->toBeTrue();

    $list->call('resetFilters')->assertSet('trashed', '')->assertSee('Bernd Berg')->assertDontSee('Clara Conrad');

    expect($list->instance()->hasFilters())->toBeFalse();
});

it('zeigt GTM API Zugang und Anzahl der API Tokens je Kunde und filtert danach', function () {
    $this->actingAs(customersAdmin());

    $withApi = managedCustomer(['company_name' => 'Berg Reisen GmbH', 'gtm_api_enabled' => true, 'gtm_api_rate_limit' => 120]);
    $withApi->createToken('Buchhaltung', ['gtm:read']);
    $withApi->createToken('Reisebüro', ['gtm:read']);
    $withoutApi = managedCustomer(['name' => 'Anna Adler', 'email' => 'anna@adler.test', 'gtm_api_enabled' => false]);
    $withoutApi->createToken('Alt', ['gtm:read']);
    managedCustomer(['name' => 'Clara Conrad', 'email' => 'clara@conrad.test', 'gtm_api_enabled' => false]);

    $this->get(route('adminv2.customer-management.customers.index'))
        ->assertOk()
        ->assertSee('GTM API aktiv')
        ->assertSee('(120/min)')
        ->assertSee('2 API Tokens')
        ->assertSee('1 API Token')
        ->assertSee('0 API Tokens')
        ->assertSee('Tokens vorhanden, aber GTM API Zugang nicht aktiv');

    $list = Livewire::test(Index::class);

    $list->set('gtmApi', 'enabled')
        ->assertSee('Berg Reisen GmbH')
        ->assertDontSee('Anna Adler')
        ->assertDontSee('Clara Conrad');

    $list->set('gtmApi', 'disabled')
        ->assertDontSee('Berg Reisen GmbH')
        ->assertSee('Anna Adler')
        ->assertSee('Clara Conrad');

    $list->set('gtmApi', '')->set('sort', 'tokens_count')->set('direction', 'desc')
        ->assertSeeInOrder(['Berg Reisen GmbH', 'Anna Adler', 'Clara Conrad']);

    $list->set('sort', 'gtm_api_enabled')->set('direction', 'desc')
        ->assertSeeInOrder(['Berg Reisen GmbH', 'Anna Adler']);
});

it('loescht einen Kunden, stellt ihn wieder her und loescht ihn endgueltig', function () {
    $this->actingAs(customersAdmin());

    $anna = managedCustomer(['name' => 'Anna Adler', 'email' => 'anna@adler.test']);
    $bernd = managedCustomer();

    $list = Livewire::test(Index::class);

    // Unbekannte Aktion, unbekannter Kunde, leere Auswahl: keine Rueckfrage.
    $list->call('confirmAction', 'explode', $anna->id)->assertSet('pendingAction', null)
        ->call('confirmAction', 'delete', 999999)->assertSet('pendingAction', null)
        ->call('confirmAction', 'bulk-delete')->assertSet('pendingAction', null);

    $list->call('confirmAction', 'delete', $anna->id)
        ->assertSet('pendingAction', 'delete')
        ->assertSet('pendingId', $anna->id)
        ->assertSee('Möchten Sie „Anna Adler“ wirklich löschen?')
        ->call('runPendingAction')
        ->assertSet('pendingAction', null)
        ->assertDispatched('adminv2-toast')
        ->assertDontSee('anna@adler.test');

    expect($anna->fresh()->trashed())->toBeTrue();

    // Endgueltig loeschen gibt es nur aus dem Papierkorb.
    $list->call('confirmAction', 'force', $bernd->id)->call('runPendingAction');

    expect(Customer::find($bernd->id))->not->toBeNull();

    $list->set('trashed', 'only')
        ->assertSee('anna@adler.test')
        ->call('confirmAction', 'restore', $anna->id)
        ->assertSee('Möchten Sie „Anna Adler“ wiederherstellen?')
        ->call('runPendingAction')
        ->assertDontSee('anna@adler.test');

    expect($anna->fresh()->trashed())->toBeFalse();

    $anna->delete();

    $list->call('confirmAction', 'force', $anna->id)
        ->assertSee('Diese Aktion kann nicht rückgängig gemacht werden!')
        ->call('runPendingAction')
        ->assertDontSee('anna@adler.test');

    expect(Customer::withTrashed()->find($anna->id))->toBeNull()
        ->and(Customer::count())->toBe(1);
});

it('wendet Sammelaktionen auf die ausgewaehlten Kunden an', function () {
    $this->actingAs(customersAdmin());

    $anna = managedCustomer(['name' => 'Anna Adler', 'email' => 'anna@adler.test', 'created_at' => '2026-10-10 08:00:00']);
    $bernd = managedCustomer(['created_at' => '2026-10-12 08:00:00']);
    $clara = managedCustomer(['name' => 'Clara Conrad', 'email' => 'clara@conrad.test', 'created_at' => '2026-10-14 08:00:00']);

    $list = Livewire::test(Index::class)
        ->call('toggleSelectPage')
        ->assertSet('selected', [(string) $clara->id, (string) $bernd->id, (string) $anna->id])
        ->call('toggleSelectPage')
        ->assertSet('selected', [])
        ->set('selected', [(string) $anna->id])
        // Suche und Filter heben die Auswahl auf.
        ->set('search', 'a')
        ->assertSet('selected', [])
        ->set('search', '')
        ->set('selected', [(string) $anna->id, (string) $bernd->id])
        ->assertSee('2 ausgewählt')
        ->call('confirmAction', 'bulk-delete')
        ->assertSet('pendingAction', 'bulk-delete')
        ->assertSee('Möchten Sie die 2 ausgewählten Kunden wirklich löschen?')
        ->call('runPendingAction')
        ->assertSet('selected', [])
        ->assertDispatched('adminv2-toast', message: '2 Kunden gelöscht.')
        ->assertDontSee('anna@adler.test')
        ->assertSee('clara@conrad.test');

    expect(Customer::count())->toBe(1)->and(Customer::onlyTrashed()->count())->toBe(2);

    $list->set('trashed', 'with')
        // Wiederherstellen betrifft nur die geloeschten unter den ausgewaehlten.
        ->set('selected', [(string) $anna->id, (string) $clara->id])
        ->call('confirmAction', 'bulk-restore')
        ->call('runPendingAction')
        ->assertDispatched('adminv2-toast', message: '1 Kunde wiederhergestellt.');

    expect($anna->fresh()->trashed())->toBeFalse()->and($bernd->fresh()->trashed())->toBeTrue();

    $list->set('selected', [(string) $bernd->id])
        ->call('confirmAction', 'bulk-force')
        ->assertSee('Dies löscht die 1 ausgewählten Kunden permanent.')
        ->call('runPendingAction')
        ->assertDispatched('adminv2-toast', message: '1 Kunde endgültig gelöscht.');

    expect(Customer::withTrashed()->find($bernd->id))->toBeNull()
        ->and(Customer::withTrashed()->count())->toBe(2);
});

it('legt keine Kunden an und fuehrt von der Neu-Seite zurueck zur Liste', function () {
    $this->actingAs(customersAdmin());

    $this->get(route('adminv2.customer-management.customers.create'))
        ->assertRedirect(route('adminv2.customer-management.customers.index'));

    $this->get(route('adminv2.customer-management.customers.edit', 999999))->assertNotFound();

    expect(Customer::count())->toBe(0);
});

it('zeigt und speichert die Stammdaten eines Kunden', function () {
    $this->actingAs(customersAdmin());

    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);
    Country::create(['name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'], 'iso_code' => 'DE', 'iso3_code' => 'DEU', 'continent_id' => $continent->id]);

    $customer = managedCustomer([
        // "reisebuero" steht nicht in der Auswahlliste (Altbestand).
        'business_type' => ['reisebuero', 'travel_agency'],
        'pds_account_id' => 4711,
        'company_name' => 'Berg Reisen',
        'company_country' => 'Deutschland',
        'gtm_api_rate_limit' => 60,
    ]);

    $this->get(route('adminv2.customer-management.customers.edit', $customer->id))
        ->assertOk()
        ->assertSee('Bernd Berg')
        ->assertSee($customer->app_code)
        ->assertSee('Allgemeine Informationen')
        ->assertSee('Feature-Überschreibungen')
        ->assertSee('Passolution Integration')
        ->assertSee('Weitere gespeicherte Werte außerhalb dieser Liste bleiben erhalten: reisebuero')
        // Ein gespeichertes Land ohne ISO-Code bleibt waehlbar.
        ->assertSee('Deutschland (gespeicherter Wert)')
        ->assertSee('Keine Filialen');

    $editor = Livewire::test(Editor::class, ['customer' => $customer->id])
        ->assertSet('name', 'Bernd Berg')
        ->assertSet('customerType', 'business')
        ->assertSet('businessTypes', ['travel_agency'])
        ->assertSet('emailVerifiedAt', '2026-10-01T09:30')
        ->assertSet('gtmApiRateLimit', '60')
        ->assertSet('company.name', 'Berg Reisen')
        ->assertSet('company.country', 'Deutschland')
        ->assertSet('featureOverrides.navigation_cruise_enabled', '')
        // Unveraendert speichern: nichts geht verloren.
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('adminv2-toast', message: 'Gespeichert.');

    $customer->refresh();

    expect($customer->business_type)->toBe(['reisebuero', 'travel_agency'])
        // Das Eingabefeld kennt keine Sekunden – der Zeitpunkt bleibt trotzdem genau.
        ->and($customer->email_verified_at->format('Y-m-d H:i:s'))->toBe('2026-10-01 09:30:45')
        ->and($customer->company_country)->toBe('Deutschland')
        // Ohne Ueberschreibung entsteht kein Datensatz.
        ->and(CustomerFeatureOverride::count())->toBe(0);

    $editor->set('name', '')
        ->set('email', 'keine-adresse')
        ->set('customerType', 'unbekannt')
        ->set('businessTypes', ['travel_agency', 'erfunden'])
        ->set('gtmApiRateLimit', '0')
        ->set('company.house_number', str_repeat('1', 21))
        ->set('featureOverrides.navigation_cruise_enabled', 'ja')
        ->call('save')
        ->assertHasErrors(['name', 'email', 'customerType', 'businessTypes.1', 'gtmApiRateLimit', 'company.house_number', 'featureOverrides.navigation_cruise_enabled'])
        ->set('name', '  Bernd Bergmann ')
        ->set('email', 'bernd@bergmann.test')
        ->set('customerType', 'business')
        ->set('businessTypes', ['organizer', 'travel_agency'])
        ->set('emailVerifiedAt', '')
        ->set('branchManagementActive', false)
        ->set('hideProfileCompletion', true)
        ->set('gtmApiEnabled', true)
        ->set('gtmApiRateLimit', '120')
        ->set('featureOverrides.navigation_cruise_enabled', '1')
        ->set('featureOverrides.navigation_booking_enabled', '0')
        ->set('company.house_number', '12a')
        ->set('company.country', 'DE')
        ->set('billing.name', 'Berg Buchhaltung')
        ->set('billing.city', '   ')
        ->call('save')
        ->assertHasNoErrors();

    $customer->refresh();

    expect($customer->name)->toBe('Bernd Bergmann')
        ->and($customer->email)->toBe('bernd@bergmann.test')
        ->and($customer->business_type)->toBe(['reisebuero', 'travel_agency', 'organizer'])
        ->and($customer->email_verified_at)->toBeNull()
        ->and((bool) $customer->branch_management_active)->toBeFalse()
        ->and((bool) $customer->hide_profile_completion)->toBeTrue()
        ->and($customer->gtm_api_enabled)->toBeTrue()
        ->and($customer->gtm_api_rate_limit)->toBe(120)
        ->and($customer->company_house_number)->toBe('12a')
        ->and($customer->company_country)->toBe('DE')
        ->and($customer->billing_company_name)->toBe('Berg Buchhaltung')
        ->and($customer->billing_city)->toBeNull()
        // Nur lesend: die Account-ID kommt aus dem Login.
        ->and($customer->pds_account_id)->toBe(4711);

    $overrides = $customer->featureOverrides()->first();

    expect($overrides->navigation_cruise_enabled)->toBeTrue()
        ->and($overrides->navigation_booking_enabled)->toBeFalse()
        ->and($overrides->navigation_events_enabled)->toBeNull();

    // Zurueck auf die globale Einstellung.
    $editor->set('featureOverrides.navigation_cruise_enabled', '')->call('save')->assertHasNoErrors();

    expect($overrides->fresh()->navigation_cruise_enabled)->toBeNull()
        ->and($overrides->fresh()->navigation_booking_enabled)->toBeFalse();
});

it('loescht den Kunden von seiner Seite aus, stellt ihn wieder her und loescht ihn endgueltig', function () {
    $this->actingAs(customersAdmin());

    $customer = managedCustomer();
    $index = route('adminv2.customer-management.customers.index');

    Livewire::test(Editor::class, ['customer' => $customer->id])
        // Wiederherstellen gibt es nur im Papierkorb.
        ->call('confirmAction', 'restore')
        ->assertSet('pendingAction', null)
        ->call('confirmAction', 'delete')
        ->assertSet('pendingAction', 'delete')
        ->call('runPendingAction')
        ->assertRedirect($index);

    expect($customer->fresh()->trashed())->toBeTrue();

    // Auch ein geloeschter Kunde laesst sich oeffnen.
    $this->get(route('adminv2.customer-management.customers.edit', $customer->id))
        ->assertOk()
        ->assertSee('und kann wiederhergestellt werden')
        ->assertSee('Wiederherstellen')
        ->assertSee('Endgültig löschen');

    Livewire::test(Editor::class, ['customer' => $customer->id])
        ->call('confirmAction', 'delete')
        ->assertSet('pendingAction', null)
        ->call('confirmAction', 'restore')
        ->call('runPendingAction')
        ->assertNoRedirect()
        ->assertDontSee('und kann wiederhergestellt werden');

    expect($customer->fresh()->trashed())->toBeFalse();

    $customer->delete();

    Livewire::test(Editor::class, ['customer' => $customer->id])
        ->call('confirmAction', 'force')
        ->call('runPendingAction')
        ->assertRedirect(route('adminv2.customer-management.customers.index', ['trashed' => 'only']));

    expect(Customer::withTrashed()->find($customer->id))->toBeNull();

    $this->get(route('adminv2.customer-management.customers.edit', $customer->id))->assertNotFound();
});

it('pflegt die Filialen eines Kunden', function () {
    $this->actingAs(customersAdmin());

    $customer = managedCustomer();
    $other = managedCustomer(['name' => 'Anna Adler', 'email' => 'anna@adler.test']);
    $foreign = customerBranch($other, 'Filiale Fremd');

    $editor = Livewire::test(Editor::class, ['customer' => $customer->id])
        ->assertSee('Keine Filialen')
        ->assertDontSee('Filiale Fremd')
        ->call('createBranch')
        ->assertSet('editingBranchId', null)
        ->assertSet('branchForm.country', 'Deutschland')
        ->set('branchForm.country', '')
        ->call('saveBranch')
        ->assertHasErrors(['branchForm.name', 'branchForm.street', 'branchForm.house_number', 'branchForm.postal_code', 'branchForm.city', 'branchForm.country'])
        ->set('branchForm.name', 'Filiale Hamburg')
        ->set('branchForm.street', 'Elbchaussee')
        ->set('branchForm.house_number', '7')
        ->set('branchForm.postal_code', '22763')
        ->set('branchForm.city', 'Hamburg')
        ->set('branchForm.country', 'Deutschland')
        ->set('branchForm.is_headquarters', true)
        ->set('branchForm.latitude', '91')
        ->set('branchForm.longitude', 'ost')
        ->call('saveBranch')
        ->assertHasErrors(['branchForm.latitude', 'branchForm.longitude'])
        ->set('branchForm.latitude', '53.5511')
        ->set('branchForm.longitude', '9.9937')
        ->call('saveBranch')
        ->assertHasNoErrors()
        ->assertDispatched('adminv2-toast', message: 'Filiale „Filiale Hamburg“ angelegt.')
        ->assertSee('Filiale Hamburg');

    $hamburg = $customer->branches()->first();

    // Der App-Code entsteht beim Anlegen von selbst.
    expect($hamburg->app_code)->toHaveLength(4)
        ->and($hamburg->is_headquarters)->toBeTrue()
        ->and((float) $hamburg->latitude)->toBe(53.5511)
        ->and($hamburg->city)->toBe('Hamburg');

    $editor->call('editBranch', $hamburg->id)
        ->assertSet('editingBranchId', $hamburg->id)
        ->assertSet('branchAppCode', $hamburg->app_code)
        ->assertSet('branchForm.name', 'Filiale Hamburg')
        ->assertSet('branchForm.latitude', '53.5511')
        ->set('branchForm.additional', 'Altona')
        ->set('branchForm.latitude', '')
        ->set('branchForm.longitude', '')
        ->call('saveBranch')
        ->assertHasNoErrors()
        ->assertSet('editingBranchId', null)
        ->assertSee('Altona');

    $hamburg->refresh();

    expect($hamburg->additional)->toBe('Altona')
        ->and($hamburg->latitude)->toBeNull()
        ->and($hamburg->app_code)->toBe($editor->get('branchAppCode'))
        ->and($customer->branches()->count())->toBe(1);

    $aachen = customerBranch($customer, 'Filiale Aachen', ['city' => 'Aachen', 'postal_code' => '52062']);
    $bonn = customerBranch($customer, 'Filiale Bonn', ['city' => 'Bonn', 'postal_code' => '53111']);

    // Vorgabe: nach Filialname.
    $editor->call('showTab', 'branches')
        ->assertSeeInOrder(['Filiale Aachen', 'Filiale Bonn', 'Filiale Hamburg'])
        ->call('sortBranches', 'name')
        ->assertSet('branchDirection', 'desc')
        ->assertSeeInOrder(['Filiale Hamburg', 'Filiale Bonn', 'Filiale Aachen'])
        ->call('sortBranches', 'postal_code')
        ->assertSet('branchDirection', 'asc')
        ->assertSeeInOrder(['Filiale Hamburg', 'Filiale Aachen', 'Filiale Bonn'])
        ->call('sortBranches', 'password')
        ->assertSet('branchSort', 'postal_code');

    $editor->set('branchSearch', '52062')->assertSee('Filiale Aachen')->assertDontSee('Filiale Bonn');
    // Strasse und Hausnummer stehen in getrennten Spalten.
    $editor->set('branchSearch', 'Elbchaussee 7')->assertSee('Filiale Hamburg')->assertDontSee('Filiale Bonn');
    $editor->set('branchSearch', $hamburg->app_code)->assertSee('Filiale Hamburg')->assertDontSee('Filiale Bonn');
    $editor->set('branchSearch', '')->set('branchHeadquarters', 'yes')->assertSee('Filiale Hamburg')->assertDontSee('Filiale Bonn');
    $editor->set('branchHeadquarters', 'no')->assertSee('Filiale Bonn')->assertDontSee('Filiale Hamburg');
    $editor->set('branchSearch', 'gibt-es-nicht')->assertSee('Zu Suche und Filter passt keine Filiale.');

    $editor->set('branchSearch', '')->set('branchHeadquarters', '')
        ->call('deleteBranch', $bonn->id)
        ->assertDontSee('Filiale Bonn')
        // Filialen anderer Kunden bleiben bei der Sammelaktion unberuehrt.
        ->set('selectedBranches', [(string) $aachen->id, (string) $hamburg->id, (string) $foreign->id])
        ->call('deleteSelectedBranches')
        ->assertSet('selectedBranches', [])
        ->assertDispatched('adminv2-toast', message: '2 Filialen gelöscht.')
        ->assertSee('Keine Filialen');

    expect($customer->branches()->count())->toBe(0)
        ->and($foreign->fresh())->not->toBeNull();

    // Filialen anderer Kunden lassen sich von hier aus weder oeffnen noch loeschen.
    expect(fn () => $editor->call('editBranch', $foreign->id))->toThrow(ModelNotFoundException::class)
        ->and(fn () => $editor->call('deleteBranch', $foreign->id))->toThrow(ModelNotFoundException::class)
        ->and($foreign->fresh())->not->toBeNull();
});

it('zeigt nach dem Loeschen der letzten Filiale einer Seite die Seite davor', function () {
    $this->actingAs(customersAdmin());

    $customer = managedCustomer();

    foreach (range(1, Editor::RELATION_PER_PAGE + 1) as $number) {
        customerBranch($customer, sprintf('Filiale %02d', $number));
    }

    $last = $customer->branches()->where('name', 'Filiale 11')->first();

    Livewire::test(Editor::class, ['customer' => $customer->id])
        ->call('nextPage', 'branchesPage')
        ->assertSee('Filiale 11')
        ->assertDontSee('Filiale 01')
        ->call('deleteBranch', $last->id)
        // Nicht die leere Seite 2 mit "Keine Filialen", sondern Seite 1.
        ->assertSee('Filiale 01')
        ->assertDontSee('Keine Filialen');
});

it('pflegt den Zugriff auf andere Accounts', function () {
    $this->actingAs(customersAdmin());

    $customer = managedCustomer(['company_name' => 'Berg Reisen']);
    $alpen = managedCustomer(['name' => 'Hans Alm', 'email' => 'hans@alpen.test', 'company_name' => 'Alpen Tours', 'company_city' => 'Aachen']);
    $nord = managedCustomer(['name' => 'Nora Nord', 'email' => 'info@nordlicht.test', 'company_name' => 'Nordlicht Reisen']);

    $editor = Livewire::test(Editor::class, ['customer' => $customer->id])
        ->call('showTab', 'unbekannt')
        ->assertSet('tab', 'branches')
        ->call('showTab', 'access')
        ->assertSet('tab', 'access')
        ->assertSee('Keine Account-Zugriffe')
        // Gesucht wird erst ab zwei Zeichen.
        ->set('accessCandidateSearch', 'a')
        ->assertDontSee('Alpen Tours')
        ->set('accessCandidateSearch', 'alpen')
        ->assertSee('Alpen Tours (hans@alpen.test)')
        ->assertDontSee('Nordlicht Reisen')
        // Der Kunde selbst steht nicht zur Wahl.
        ->set('accessCandidateSearch', 'bernd@berg.test')
        ->assertSee('Kein weiterer Account gefunden.')
        ->call('attachAccess', $alpen->id)
        ->assertSet('accessCandidateSearch', '')
        ->assertDispatched('adminv2-toast', message: 'Zugriff auf „Alpen Tours (hans@alpen.test)“ hinzugefügt.')
        ->assertSee('Alpen Tours')
        ->assertSee('Aachen')
        ->assertSee(route('adminv2.customer-management.customers.edit', $alpen->id))
        // Wer schon berechtigt ist, wird nicht noch einmal angeboten.
        ->set('accessCandidateSearch', 'alpen')
        ->assertSee('Kein weiterer Account gefunden.')
        ->set('accessCandidateSearch', $nord->app_code)
        ->call('attachAccess', $nord->id)
        // Doppelt hinzufuegen aendert nichts.
        ->call('attachAccess', $nord->id);

    expect($customer->accessibleAccounts()->pluck('customers.id')->sort()->values()->all())->toBe([$alpen->id, $nord->id]);

    $editor->set('accessSearch', 'nordlicht')->assertSee('Nordlicht Reisen')->assertDontSee('Alpen Tours');
    $editor->set('accessSearch', 'gibt-es-nicht')->assertSee('Zur Suche passt kein Account-Zugriff.');

    $editor->set('accessSearch', '')
        ->call('detachAccess', $alpen->id)
        ->assertDontSee('Alpen Tours')
        ->set('selectedAccess', [(string) $nord->id])
        ->call('detachSelectedAccess')
        ->assertSet('selectedAccess', [])
        ->assertDispatched('adminv2-toast', message: '1 Account-Zugriff entfernt.')
        ->assertSee('Keine Account-Zugriffe');

    expect($customer->accessibleAccounts()->count())->toBe(0)
        // Die Accounts selbst bleiben bestehen.
        ->and(Customer::count())->toBe(3);

    // Der Kunde kann nicht auf sich selbst berechtigt werden.
    expect(fn () => $editor->call('attachAccess', $customer->id))->toThrow(ModelNotFoundException::class)
        ->and($customer->accessibleAccounts()->count())->toBe(0);
});

it('erstellt API Tokens, zeigt sie einmalig und widerruft sie', function () {
    $this->actingAs(customersAdmin());

    $customer = managedCustomer();
    $other = managedCustomer(['name' => 'Anna Adler', 'email' => 'anna@adler.test']);
    $foreign = $other->createToken('Fremd', ['gtm:read'])->accessToken;

    $editor = Livewire::test(Editor::class, ['customer' => $customer->id])
        ->call('showTab', 'tokens')
        ->assertSee('Keine API Tokens')
        ->call('createToken')
        ->call('saveToken')
        ->assertHasErrors(['tokenForm.name', 'tokenForm.abilities'])
        ->set('tokenForm.name', 'Buchhaltung')
        ->set('tokenForm.abilities', ['gtm:read', 'alles'])
        ->set('tokenForm.expires_at', '2020-01-01T10:00')
        ->call('saveToken')
        ->assertHasErrors(['tokenForm.abilities.1', 'tokenForm.expires_at'])
        ->set('tokenForm.abilities', ['gtm:read', 'folder:read'])
        ->set('tokenForm.expires_at', '2030-12-31T10:00')
        ->call('saveToken')
        ->assertHasNoErrors()
        ->assertSet('tokenForm', ['name' => '', 'abilities' => [], 'expires_at' => ''])
        ->assertSee('Buchhaltung')
        ->assertSee('Vom Admin erstellt')
        ->assertSee('31.12.2030 10:00')
        ->assertSee('Nie verwendet');

    $token = $customer->tokens()->first();
    $plain = $editor->get('plainTextToken');

    expect($token->name)->toBe('admin:Buchhaltung')
        ->and($token->abilities)->toBe(['gtm:read', 'folder:read'])
        ->and($token->expires_at->format('Y-m-d H:i'))->toBe('2030-12-31 10:00')
        ->and($plain)->toStartWith($token->id.'|');

    // Der Klartext steht nur bis zum Schliessen des Dialogs da.
    $editor->assertSee($plain)
        ->call('dismissToken')
        ->assertSet('plainTextToken', null)
        ->assertDontSee($plain);

    $customer->createToken('Eigenes Tool', ['folder:import']);

    $editor->call('$refresh')
        ->assertSee('Eigenes Tool')
        ->assertSee('Vom Kunden erstellt')
        ->assertSee('Unbegrenzt')
        ->assertDontSee('Fremd')
        ->call('sortTokens', 'name')
        ->assertSet('tokenSort', 'name')
        ->assertSet('tokenDirection', 'asc')
        ->call('sortTokens', 'name')
        ->assertSet('tokenDirection', 'desc')
        ->call('sortTokens', 'token')
        ->assertSet('tokenSort', 'name');

    $editor->set('tokenSearch', 'eigenes')->assertSee('Eigenes Tool')->assertDontSee('Buchhaltung');
    $editor->set('tokenSearch', 'gibt-es-nicht')->assertSee('Zur Suche passt kein Token.');

    $editor->set('tokenSearch', '')
        ->call('revokeToken', $token->id)
        ->assertDispatched('adminv2-toast', message: 'Token widerrufen – der API-Zugriff ist gesperrt.')
        ->assertDontSee('Buchhaltung')
        ->assertSee('Eigenes Tool');

    expect($customer->tokens()->count())->toBe(1);

    // Tokens anderer Kunden lassen sich von hier aus nicht widerrufen.
    expect(fn () => $editor->call('revokeToken', $foreign->id))->toThrow(ModelNotFoundException::class)
        ->and($other->tokens()->count())->toBe(1);
});

it('zeigt die GTM API Logs eines Kunden mit Suche, Statusfilter und Sortierung', function () {
    $this->actingAs(customersAdmin());

    $customer = managedCustomer();
    $other = managedCustomer(['name' => 'Anna Adler', 'email' => 'anna@adler.test']);

    // Die Liste laesst sich ueber die Adresse direkt oeffnen.
    $this->get(route('adminv2.customer-management.customers.edit', ['customer' => $customer->id, 'tab' => 'gtm-logs']))
        ->assertOk()
        ->assertSee('Keine API Requests');

    // Endpunkte ohne innere Schraegstriche: nach einer Aktualisierung prueft assertSeeInOrder die JSON-Antwort.
    customerGtmLog($customer, '2026-10-15 09:00:00', ['endpoint' => '/gtm-events', 'response_time_ms' => 300]);
    customerGtmLog($customer, '2026-10-14 09:00:00', ['endpoint' => '/gtm-countries', 'response_time_ms' => 80, 'user_agent' => 'Testbrowser/1.0']);
    customerGtmLog($customer, '2026-10-13 09:00:00', ['endpoint' => '/gtm-limits', 'response_status' => 429, 'response_time_ms' => null]);
    customerGtmLog($other, '2026-10-15 10:00:00', ['endpoint' => '/gtm-fremd']);

    $editor = Livewire::test(Editor::class, ['customer' => $customer->id])
        ->call('showTab', 'gtm-logs')
        // Vorgabe: neueste Anfrage zuerst.
        ->assertSeeInOrder(['/gtm-events', '/gtm-countries', '/gtm-limits'])
        ->assertSee('15.10.2026 09:00:00')
        ->assertSee('300 ms')
        ->assertSee('203.0.113.5')
        ->assertSee('Testbrowser/1.0')
        ->assertDontSee('/gtm-fremd')
        ->call('sortLogs', 'created_at')
        ->assertSet('logDirection', 'asc')
        ->assertSeeInOrder(['/gtm-limits', '/gtm-countries', '/gtm-events'])
        ->call('sortLogs', 'response_time_ms')
        ->call('sortLogs', 'response_time_ms')
        ->assertSet('logDirection', 'desc')
        ->assertSeeInOrder(['/gtm-events', '/gtm-countries']);

    $editor->set('logSearch', 'countries')->assertSee('/gtm-countries')->assertDontSee('/gtm-events');
    $editor->set('logSearch', '')->set('logStatus', '429')->assertSee('/gtm-limits')->assertDontSee('/gtm-events');
    $editor->set('logStatus', '500')->assertSee('Zu Suche und Filter passt keine Anfrage.');
});

it('blaettert durch die GTM API Logs', function () {
    $this->actingAs(customersAdmin());

    $customer = managedCustomer();

    foreach (range(1, Editor::RELATION_PER_PAGE + 1) as $minute) {
        customerGtmLog($customer, sprintf('2026-10-15 09:%02d:00', $minute), ['endpoint' => '/seite-'.sprintf('%02d', $minute)]);
    }

    Livewire::test(Editor::class, ['customer' => $customer->id])
        ->call('showTab', 'gtm-logs')
        ->assertSee('Seite 1 von 2')
        ->assertSee('/seite-11')
        ->assertDontSee('/seite-01')
        ->call('nextPage', 'logsPage')
        ->assertSee('/seite-01')
        ->assertDontSee('/seite-11')
        // Ein Filter fuehrt zurueck auf die erste Seite.
        ->set('logSearch', 'seite')
        ->assertSee('/seite-11');
});

it('laesst nur Admins auf die Kunden', function () {
    $customer = managedCustomer();

    $this->get(route('adminv2.customer-management.customers.index'))->assertRedirect();

    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]));

    $this->get(route('adminv2.customer-management.customers.index'))->assertForbidden();
    $this->get(route('adminv2.customer-management.customers.edit', $customer->id))->assertForbidden();

    Livewire::test(Index::class)->assertForbidden();
    Livewire::test(Editor::class, ['customer' => $customer->id])->assertForbidden();

    expect(Customer::count())->toBe(1);
});
