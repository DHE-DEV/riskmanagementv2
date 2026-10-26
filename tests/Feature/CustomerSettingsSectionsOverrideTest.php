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
