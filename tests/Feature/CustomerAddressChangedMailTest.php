<?php

use App\Mail\CustomerAddressChangedMail;
use App\Mail\OrganizationChangeMail;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * Aendert ein Kunde unter /customer/settings seine Adresse, geht eine Infomail
 * mit alten und neuen Daten an Passolution.
 */
beforeEach(function () {
    Http::fake();
    Mail::fake();
    config([
        'app.customer_address_change_mail' => [
            'to' => ['info@passolution.de'],
            'cc' => ['daniel.henninger@passolution.de', 'dennis.zimon@passolution.de'],
        ],
        'app.pds_backend_url' => 'https://backend.passolution.eu',
    ]);
});

it('schickt bei geaenderter Firmenanschrift eine Infomail mit alt und neu', function () {
    $customer = Customer::factory()->create([
        'company_name' => 'Reisebüro Alt',
        'company_street' => 'Altstraße',
        'company_house_number' => '1',
        'company_postal_code' => '10115',
        'company_city' => 'Berlin',
        'pds_account_id' => 4711,
        'can_edit_own_address' => true,
    ]);

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.profile.update-company-address'), [
            'company_name' => 'Reisebüro Alt',
            'company_street' => 'Neuweg',
            'company_house_number' => '5',
            'company_postal_code' => '80331',
            'company_city' => 'München',
        ])
        ->assertOk();

    Mail::assertQueued(CustomerAddressChangedMail::class, function (CustomerAddressChangedMail $mail) use ($customer) {
        $html = $mail->render();

        return $mail->hasTo('info@passolution.de')
            && $mail->hasCc('daniel.henninger@passolution.de')
            && $mail->hasCc('dennis.zimon@passolution.de')
            && $mail->type === CustomerAddressChangedMail::TYPE_COMPANY
            && $mail->old['company_city'] === 'Berlin'
            && $mail->new['company_city'] === 'München'
            && str_contains($html, 'Altstraße')
            && str_contains($html, 'Neuweg')
            && str_contains($html, route('adminv2.customer-management.customers.edit', $customer->id))
            && str_contains($html, 'https://backend.passolution.eu/client/accounts/4711/details');
    });
});

it('schickt bei geaenderter Rechnungsadresse eine Infomail', function () {
    $customer = Customer::factory()->create(['billing_city' => 'Berlin', 'can_edit_own_address' => true]);

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.profile.update-billing-address'), ['billing_city' => 'Hamburg'])
        ->assertOk();

    Mail::assertQueued(CustomerAddressChangedMail::class, fn (CustomerAddressChangedMail $mail) => $mail->type === CustomerAddressChangedMail::TYPE_BILLING
        && $mail->new['billing_city'] === 'Hamburg');
});

it('schickt keine Mail, wenn sich nichts geaendert hat', function () {
    $customer = Customer::factory()->create(['company_city' => 'Berlin', 'company_street' => null, 'can_edit_own_address' => true]);

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.profile.update-company-address'), ['company_city' => 'Berlin', 'company_street' => ''])
        ->assertOk();

    Mail::assertNothingQueued();
});

it('laesst den pds-admin-Link ohne PDS-ID weg', function () {
    $customer = Customer::factory()->create(['pds_account_id' => null]);

    $html = (new CustomerAddressChangedMail($customer, CustomerAddressChangedMail::TYPE_COMPANY, ['company_city' => 'A'], ['company_city' => 'B']))->render();

    expect($html)->not->toContain('/client/accounts/')
        ->and($html)->toContain('Keine PDS-ID hinterlegt');
});

it('verweigert ohne Freigabe das Aendern einer vorhandenen Adresse', function () {
    $customer = Customer::factory()->create(['company_city' => 'Berlin']);

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.profile.update-company-address'), ['company_city' => 'Hamburg'])
        ->assertForbidden();

    expect($customer->fresh()->company_city)->toBe('Berlin');
    Mail::assertNothingQueued();
});

it('erlaubt ohne Freigabe die Ersterfassung einer leeren Adresse', function () {
    $customer = Customer::factory()->create(['company_street' => null, 'company_postal_code' => null, 'company_city' => null]);

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.profile.update-company-address'), ['company_city' => 'Hamburg'])
        ->assertOk();

    expect($customer->fresh()->company_city)->toBe('Hamburg');
    Mail::assertQueued(CustomerAddressChangedMail::class);
});

it('vererbt die Freigabe an die Unteraccounts nur mit beiden Schaltern', function () {
    $agency = Customer::factory()->create(['can_edit_own_address' => true]);
    $office = Customer::factory()->create(['assign_to' => $agency->id]);

    expect($office->canEditOwnAddress())->toBeFalse();

    $agency->update(['sub_accounts_can_edit_own_address' => true]);
    expect($office->fresh()->canEditOwnAddress())->toBeTrue();

    $agency->update(['can_edit_own_address' => false]);
    expect($office->fresh()->canEditOwnAddress())->toBeFalse();
});

it('meldet Aenderungen eines Bueros an Buero und Zentrale, wenn eingeschaltet', function () {
    $agency = Customer::factory()->create([
        'email' => 'zentrale@example.test',
        'can_edit_own_address' => true,
        'sub_accounts_can_edit_own_address' => true,
        'notify_address_changes' => true,
    ]);
    $office = Customer::factory()->create(['assign_to' => $agency->id, 'email' => 'buero@example.test', 'company_city' => 'Berlin']);

    $this->actingAs($office, 'customer')
        ->postJson(route('customer.profile.update-company-address'), ['company_city' => 'Köln'])
        ->assertOk();

    Mail::assertQueued(CustomerAddressChangedMail::class, fn ($mail) => $mail->hasTo('info@passolution.de'));
    Mail::assertQueued(OrganizationChangeMail::class, function (OrganizationChangeMail $mail) {
        return $mail->hasTo('zentrale@example.test')
            && $mail->hasTo('buero@example.test')
            && str_contains($mail->mailHtml, 'Köln')
            && ! str_contains($mail->mailHtml, '/adminv2/')
            && ! str_contains($mail->mailHtml, '/client/accounts/');
    });
});

it('meldet ohne Schalter nur an Passolution', function () {
    $agency = Customer::factory()->create(['can_edit_own_address' => true, 'sub_accounts_can_edit_own_address' => true]);
    $office = Customer::factory()->create(['assign_to' => $agency->id, 'company_city' => 'Berlin']);

    $this->actingAs($office, 'customer')
        ->postJson(route('customer.profile.update-company-address'), ['company_city' => 'Köln'])
        ->assertOk();

    Mail::assertQueued(CustomerAddressChangedMail::class, 1);
    Mail::assertNotQueued(OrganizationChangeMail::class);
});

it('laesst die Zentrale mit Freigabe die Adresse eines Bueros aendern', function () {
    $agency = Customer::factory()->create(['can_edit_own_address' => true]);
    $office = Customer::factory()->create(['assign_to' => $agency->id, 'company_city' => 'Berlin']);
    $foreign = Customer::factory()->create(['company_city' => 'Berlin']);

    $this->actingAs($agency, 'customer')
        ->postJson(route('customer.sub-accounts.update-company-address', $office), ['company_city' => 'Bonn'])
        ->assertOk()
        ->assertJsonPath('address.company_city', 'Bonn');

    $this->actingAs($agency, 'customer')
        ->postJson(route('customer.sub-accounts.update-company-address', $foreign), ['company_city' => 'Bonn'])
        ->assertForbidden();

    expect($office->fresh()->company_city)->toBe('Bonn')
        ->and($foreign->fresh()->company_city)->toBe('Berlin');
    Mail::assertQueued(CustomerAddressChangedMail::class, fn ($mail) => $mail->customer->is($office) && str_contains((string) $mail->changedBy, 'Zentrale'));
});

it('verweigert der Zentrale ohne Freigabe das Aendern eines Bueros', function () {
    $agency = Customer::factory()->create();
    $office = Customer::factory()->create(['assign_to' => $agency->id, 'company_city' => 'Berlin']);

    $this->actingAs($agency, 'customer')
        ->postJson(route('customer.sub-accounts.update-company-address', $office), ['company_city' => 'Bonn'])
        ->assertForbidden();
});

it('zeigt der Zentrale mit Freigabe den Stift in den Agentur-Karten', function () {
    config(['customer_settings.sections.organization' => true]);
    $agency = Customer::factory()->create([
        'branch_management_active' => true,
        'customer_type' => 'business',
        'show_sub_accounts_as_branches' => true,
    ]);
    $office = Customer::factory()->create(['assign_to' => $agency->id, 'company_name' => 'Büro Testhausen']);
    \App\Models\CustomerFeatureOverride::create(['customer_id' => $agency->id, 'settings_organization_enabled' => true]);
    $editUrl = route('customer.sub-accounts.update-company-address', $office);

    $this->actingAs($agency, 'customer')
        ->get(route('customer.settings', ['section' => 'organization']))
        ->assertOk()
        ->assertSee('Büro Testhausen')
        ->assertDontSee('Adresse ändern', false);

    $agency->update(['can_edit_own_address' => true]);

    $this->actingAs($agency->fresh(), 'customer')
        ->get(route('customer.settings', ['section' => 'organization']))
        ->assertOk()
        ->assertSee('Adresse ändern', false)
        ->assertSee(str_replace('/', '\/', $editUrl), false);
});

it('zeigt im Dashboard bei der Karte Büro nur mit Freigabe den Link Ändern', function () {
    $customer = Customer::factory()->create(['customer_type' => 'business']);

    $marker = '@click="edit()" class="text-xs font-medium text-blue-600';

    $this->actingAs($customer, 'customer')->get('/customer/dashboard')->assertOk()->assertDontSee($marker, false);

    $customer->update(['can_edit_own_address' => true]);

    $this->actingAs($customer->fresh(), 'customer')->get('/customer/dashboard')->assertOk()->assertSee($marker, false);
});
