<?php

use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Services\CustomerFeatureService;
use Illuminate\Support\Facades\Http;

/**
 * Einstellungsbereiche unter "Organisation" (Stammdaten, Organisationsstruktur,
 * Benutzerverwaltung) je Kunde schaltbar; Unteraccounts erben von der Zentrale.
 */
beforeEach(function () {
    Http::fake();
    config([
        'customer_settings.sections.master-data' => false,
        'customer_settings.sections.organization' => false,
        'customer_settings.sections.users' => false,
    ]);
});

it('schaltet die Bereiche je Kunde und vererbt sie an Unteraccounts', function () {
    $service = app(CustomerFeatureService::class);

    $agency = Customer::factory()->create();
    $office = Customer::factory()->create(['assign_to' => $agency->id]);
    $ownOffice = Customer::factory()->create(['assign_to' => $agency->id]);
    $standalone = Customer::factory()->create();

    // Global aus (customer_settings.sections).
    expect($service->isFeatureEnabled('settings_organization_enabled', $agency))->toBeFalse();

    CustomerFeatureOverride::create(['customer_id' => $agency->id, 'settings_organization_enabled' => true, 'settings_users_enabled' => true, 'navigation_cruise_enabled' => false]);
    CustomerFeatureOverride::create(['customer_id' => $ownOffice->id, 'settings_users_enabled' => false]);

    expect($service->isFeatureEnabled('settings_organization_enabled', $agency))->toBeTrue()
        // Unteraccount ohne eigene Einstellung: Wert der Zentrale.
        ->and($service->isFeatureEnabled('settings_organization_enabled', $office))->toBeTrue()
        ->and($service->isFeatureEnabled('settings_users_enabled', $office))->toBeTrue()
        ->and($service->isFeatureEnabled('settings_master_data_enabled', $office))->toBeFalse()
        // Eigene Einstellung geht vor.
        ->and($service->isFeatureEnabled('settings_users_enabled', $ownOffice))->toBeFalse()
        ->and($service->isFeatureEnabled('settings_organization_enabled', $ownOffice))->toBeTrue()
        // Ohne Zentrale: globaler Standard.
        ->and($service->isFeatureEnabled('settings_organization_enabled', $standalone))->toBeFalse()
        // Navigations-Schalter werden nicht vererbt.
        ->and($service->isFeatureEnabled('navigation_cruise_enabled', $agency))->toBeFalse()
        ->and($service->isFeatureEnabled('navigation_cruise_enabled', $office))->toBeTrue();
});

it('zeigt unter Organisation nur freigeschaltete Bereiche und sonst keine leere Überschrift', function () {
    $agency = Customer::factory()->create(['branch_management_active' => true, 'customer_type' => 'business']);
    $office = Customer::factory()->create(['branch_management_active' => true, 'customer_type' => 'business', 'assign_to' => $agency->id]);

    $this->actingAs($office, 'customer')
        ->get(route('customer.settings', ['section' => 'travel-requirements']))
        ->assertOk()
        ->assertDontSee('settings-nav-org', false)
        ->assertDontSee('section=organization', false);

    $this->actingAs($office, 'customer')
        ->get(route('customer.settings', ['section' => 'organization']))
        ->assertNotFound();

    CustomerFeatureOverride::create(['customer_id' => $agency->id, 'settings_organization_enabled' => true]);

    $this->actingAs($office, 'customer')
        ->get(route('customer.settings', ['section' => 'travel-requirements']))
        ->assertOk()
        ->assertSee('settings-nav-org', false)
        ->assertSee('section=organization', false)
        ->assertDontSee('section=users', false);

    $this->actingAs($office, 'customer')
        ->get(route('customer.settings', ['section' => 'organization']))
        ->assertOk();
});

it('zählt am Tab Agenturen alle zugeordneten Agenturen', function () {
    $agency = Customer::factory()->create(['branch_management_active' => true, 'customer_type' => 'business']);
    Customer::factory()->count(3)->create(['assign_to' => $agency->id]);
    CustomerFeatureOverride::create(['customer_id' => $agency->id, 'settings_organization_enabled' => true]);

    $html = $this->actingAs($agency, 'customer')
        ->get(route('customer.settings', ['section' => 'organization']))
        ->assertOk()
        ->getContent();

    expect(preg_replace('/\s+/', ' ', strip_tags($html)))->toContain('Agenturen 3');
});

it('liefert bei den Agenturen nur die angezeigten Alt-Optionen', function () {
    $agency = Customer::factory()->create();
    $office = Customer::factory()->create(['assign_to' => $agency->id]);
    \Illuminate\Support\Facades\DB::table('customers_legacy_options')->insert([
        'account_id' => $office->id,
        'account_type' => 4,
        'live_from' => '2020-01-01',
        'end_of_use' => '2099-12-31',
        'show_visa_service' => 1,
        'visa_places' => 'Berlin',
        'show_travel_warning' => 0,
        'zoho_crm_id' => 'geheim',
        'note' => 'interne Notiz',
        'providers' => '[]',
    ]);

    $options = $this->actingAs($agency, 'customer')
        ->getJson('/customer/assigned-agencies')
        ->assertOk()
        ->json('data.0.legacy_options');

    expect(array_keys($options))->toEqualCanonicalizing(['account_id', 'account_type', 'live_from', 'end_of_use', 'show_visa_service', 'visa_places', 'show_travel_warning', 'travel_warning_country', 'myjack_agency_id'])
        ->and($options['visa_places'])->toBe('Berlin');
});

it('zeigt Agenturen als Kachel und – je Kunde schaltbar – als Adressen in Übersicht und Filialen', function () {
    $agency = Customer::factory()->create(['branch_management_active' => true, 'customer_type' => 'business']);
    Customer::factory()->create(['assign_to' => $agency->id, 'company_name' => 'Reisebüro Augsburg', 'company_city' => 'Augsburg']);
    Customer::factory()->create(['assign_to' => $agency->id, 'company_name' => 'Reisebüro Essen', 'company_city' => 'Essen']);
    CustomerFeatureOverride::create(['customer_id' => $agency->id, 'settings_organization_enabled' => true]);

    $overview = fn () => preg_replace('/\s+/', ' ', strip_tags($this->actingAs($agency, 'customer')
        ->get(route('customer.settings', ['section' => 'organization']))->assertOk()->getContent()));

    // Kachel immer, Adressen nur mit Schalter.
    expect($overview())->toContain('2 Agenturen')->not->toContain('Agenturen (2)');
    $this->actingAs($agency, 'customer')->get(route('branches'))->assertOk()->assertDontSee('Reisebüro Essen');

    $agency->forceFill(['show_sub_accounts_as_branches' => true])->save();

    expect($overview())->toContain('Agenturen (2)')->toContain('Reisebüro Augsburg');
    $this->actingAs($agency->fresh(), 'customer')->get(route('branches'))
        ->assertOk()
        ->assertSee('Agenturen (2)')
        ->assertSee('Reisebüro Essen')
        ->assertDontSee('Keine weiteren Filialen');
});

it('vererbt die ausgewählten Features der Zentrale an die Unteraccounts – freischalten und entziehen', function () {
    config(['app.navigation_cruise_enabled' => false, 'app.navigation_events_enabled' => true, 'app.navigation_airports_enabled' => true]);
    $service = app(CustomerFeatureService::class);

    $agency = Customer::factory()->create();
    $office = Customer::factory()->create(['assign_to' => $agency->id]);
    $ownOffice = Customer::factory()->create(['assign_to' => $agency->id]);
    $agencyOverride = CustomerFeatureOverride::create(['customer_id' => $agency->id, 'navigation_cruise_enabled' => true, 'navigation_events_enabled' => false, 'navigation_airports_enabled' => false]);
    CustomerFeatureOverride::create(['customer_id' => $ownOffice->id, 'navigation_cruise_enabled' => false]);

    // Ohne Schalter: nichts vererbt.
    expect($service->isFeatureEnabled('navigation_cruise_enabled', $office))->toBeFalse()
        ->and($service->isFeatureEnabled('navigation_events_enabled', $office))->toBeTrue();

    // Vererbt werden nur Kreuzfahrt und Ereignisse, nicht Flughaefen.
    $agency->forceFill(['inherited_features' => ['navigation_cruise_enabled', 'navigation_events_enabled']])->save();
    $service->clearCache($agency->id);

    // Freigeschaltet und entzogen wie bei der Zentrale; eigener Wert geht vor.
    expect($service->isFeatureEnabled('navigation_cruise_enabled', $office))->toBeTrue()
        ->and($service->isFeatureEnabled('navigation_events_enabled', $office))->toBeFalse()
        ->and($service->isFeatureEnabled('navigation_cruise_enabled', $ownOffice))->toBeFalse()
        ->and($service->isFeatureEnabled('navigation_events_enabled', $ownOffice))->toBeFalse()
        ->and($service->isFeatureEnabled('navigation_airports_enabled', $office))->toBeTrue();

    // Zentrale wieder auf Standard: bei den Bueros gilt die .env.
    $agencyOverride->update(['navigation_cruise_enabled' => null]);

    expect($service->isFeatureEnabled('navigation_cruise_enabled', $office))->toBeFalse();
});

it('liefert im Konten-Umschalter E-Mail und sortiert Konten ohne Firmenname ans Ende', function () {
    $agency = Customer::factory()->create();
    $named = Customer::factory()->create(['company_name' => 'Zeta Reisen']);
    $unnamed = Customer::factory()->create(['company_name' => null, 'name' => 'Max Muster', 'email' => 'max@muster.test', 'company_postal_code' => null, 'company_city' => null]);
    $agency->accessibleAccounts()->attach([$named->id, $unnamed->id]);

    $data = $this->actingAs($agency, 'customer')
        ->getJson('/customer/account/accessible')
        ->assertOk()
        ->json('data');

    expect(array_column($data, 'id'))->toBe([$named->id, $unnamed->id])
        ->and($data[1]['email'])->toBe('max@muster.test');

    $this->actingAs($agency, 'customer')
        ->getJson('/customer/account/accessible?search=muster.test')
        ->assertOk()
        ->assertJsonPath('data.0.id', $unnamed->id);
});

it('zeigt die Agenturen in der Übersicht als Karten mit Suche, Status-Filter und Inaktiv-Markierung', function () {
    $agency = Customer::factory()->create(['branch_management_active' => true, 'customer_type' => 'business', 'show_sub_accounts_as_branches' => true]);
    $active = Customer::factory()->create(['assign_to' => $agency->id, 'company_name' => 'Aktives Büro', 'email' => 'aktiv@buero.test']);
    $inactive = Customer::factory()->create(['assign_to' => $agency->id, 'company_name' => 'Altes Büro']);
    CustomerFeatureOverride::create(['customer_id' => $agency->id, 'settings_organization_enabled' => true]);
    \Illuminate\Support\Facades\DB::table('customers_legacy_options')->insert([
        ['account_id' => $inactive->id, 'end_of_use' => '2020-12-31', 'providers' => '[]'],
        ['account_id' => $active->id, 'end_of_use' => '2099-12-31', 'providers' => '[]'],
    ]);

    $this->actingAs($agency, 'customer')
        ->get(route('customer.settings', ['section' => 'organization']))
        ->assertOk()
        ->assertSee('x-data="{ q: \'\', status: \'active\' }"', false)
        ->assertSee("status === 'inactive'", false)
        ->assertSee('Inaktiv')
        ->assertSee('mailto:aktiv@buero.test', false);
});
