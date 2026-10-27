<?php

use App\Mail\BranchChangedMail;
use App\Mail\OrganizationChangeMail;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\EmailAddress;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * Einstellungen › Organisationsstruktur: Filialen anlegen, aendern und
 * loeschen (Infomail an Passolution, Start-/Enddatum), Mail-Einstellungen der
 * Organisation und Tab „Reiseberater“.
 */
beforeEach(function () {
    // Geokodierung (Nominatim) ohne Treffer
    Http::fake(['*' => Http::response([], 200)]);
    Mail::fake();
    config([
        'app.customer_address_change_mail' => [
            'to' => ['info@passolution.de'],
            'cc' => ['daniel.henninger@passolution.de', 'dennis.zimon@passolution.de'],
        ],
    ]);
});

function branchPayload(array $overrides = []): array
{
    return [
        'name' => 'Büro Nord',
        'street' => 'Hafenstraße',
        'house_number' => '3',
        'postal_code' => '20457',
        'city' => 'Hamburg',
        'country' => 'Deutschland',
        ...$overrides,
    ];
}

it('meldet eine neue Filiale mit Startdatum und Art an Passolution', function () {
    $customer = Customer::factory()->create(['pds_account_id' => 815]);

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.branches.store'), branchPayload(['location_type' => 'travel_advisor', 'start_date' => '2026-12-01']))
        ->assertOk();

    $branch = $customer->branches()->sole();
    expect($branch->location_type)->toBe('travel_advisor')
        ->and($branch->start_date->format('Y-m-d'))->toBe('2026-12-01');

    Mail::assertQueued(BranchChangedMail::class, function (BranchChangedMail $mail) {
        $html = $mail->render();

        return $mail->hasTo('info@passolution.de')
            && $mail->hasCc('dennis.zimon@passolution.de')
            && $mail->kind === 'Neue Filiale'
            && str_contains($html, '01.12.2026')
            && str_contains($html, 'Reiseberater')
            && str_contains($html, 'https://backend.passolution.eu/client/accounts/815/details');
    });
});

it('meldet geaenderte Adressen, aber kein Speichern ohne Aenderung', function () {
    $customer = Customer::factory()->create();
    $branch = $customer->branches()->create(branchPayload());

    $this->actingAs($customer, 'customer')->putJson(route('customer.branches.update', $branch), branchPayload())->assertOk();
    Mail::assertNothingQueued();

    $this->actingAs($customer, 'customer')->putJson(route('customer.branches.update', $branch), branchPayload(['city' => 'Lübeck']))->assertOk();
    Mail::assertQueued(BranchChangedMail::class, fn ($mail) => collect($mail->rows)->contains(fn ($row) => $row['label'] === 'Ort' && $row['old'] === 'Hamburg' && $row['new'] === 'Lübeck'));
});

it('verlangt beim Loeschen ein Enddatum in der Zukunft', function () {
    $customer = Customer::factory()->create();
    $branch = $customer->branches()->create(branchPayload());

    $this->actingAs($customer, 'customer')
        ->deleteJson(route('customer.branches.destroy', $branch), [])
        ->assertJsonValidationErrors('end_date');

    $this->actingAs($customer, 'customer')
        ->deleteJson(route('customer.branches.destroy', $branch), ['end_date' => now()->toDateString()])
        ->assertJsonValidationErrors('end_date');

    expect($branch->fresh()->trashed())->toBeFalse();
    Mail::assertNothingQueued();
});

it('loescht sofort weich mit Grund und Enddatum', function () {
    $customer = Customer::factory()->create();
    $branch = $customer->branches()->create(branchPayload());
    $endDate = now()->addMonth()->toDateString();

    $this->actingAs($customer, 'customer')
        ->deleteJson(route('customer.branches.destroy', $branch), ['end_date' => $endDate, 'deletion_reason' => 'Büro geschlossen'])
        ->assertOk();

    $trashed = Branch::withTrashed()->find($branch->id);
    expect($trashed->trashed())->toBeTrue()
        ->and($trashed->deletion_reason)->toBe('Büro geschlossen')
        ->and($trashed->end_date->toDateString())->toBe($endDate)
        ->and($trashed->deletion_requested_by)->toContain('Kunde selbst');

    Mail::assertQueued(BranchChangedMail::class, function (BranchChangedMail $mail) use ($endDate) {
        $html = $mail->render();

        return $mail->kind === 'Filiale gelöscht'
            && str_contains($html, 'Büro geschlossen')
            && str_contains($html, \Carbon\Carbon::parse($endDate)->format('d.m.Y'));
    });
});

it('plant die Loeschung, laesst sie abbrechen und fuehrt sie per Befehl weich aus', function () {
    $customer = Customer::factory()->create();
    $branch = $customer->branches()->create(branchPayload());
    $date = now()->addWeek()->toDateString();

    $this->actingAs($customer, 'customer')
        ->deleteJson(route('customer.branches.destroy', $branch), ['end_date' => $date, 'scheduled_deletion_at' => $date])
        ->assertOk();

    expect($branch->fresh()->trashed())->toBeFalse()
        ->and($branch->fresh()->scheduled_deletion_at->toDateString())->toBe($date);
    Mail::assertQueued(BranchChangedMail::class, fn ($mail) => $mail->kind === 'Löschung geplant');

    $this->actingAs($customer, 'customer')->postJson(route('customer.branches.cancel-deletion', $branch))->assertOk();
    expect($branch->fresh()->scheduled_deletion_at)->toBeNull()
        ->and($branch->fresh()->end_date)->toBeNull();
    Mail::assertQueued(BranchChangedMail::class, fn ($mail) => $mail->kind === 'Löschung abgebrochen');

    $branch->update(['scheduled_deletion_at' => now()->subMinute()]);
    $this->artisan('branches:delete-scheduled')->assertSuccessful();
    expect(Branch::withTrashed()->find($branch->id)->trashed())->toBeTrue();
});

it('speichert die Mail-Einstellungen der Organisation', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->putJson(route('customer.organization.mail-settings.update'), [
            'enabled' => true,
            'to' => ['a@example.test', 'B@example.test'],
            'cc' => ['c@example.test'],
            'bcc' => ['d@example.test'],
            'notify_actor' => true,
            'notify_agency' => false,
            'agency_email' => 'all',
            'subject' => 'Hinweis {agentur}',
            'format' => 'html',
            'body' => '<h1>{art}</h1>',
        ])
        ->assertOk();

    $customer->refresh();
    expect($customer->notify_address_changes)->toBeTrue()
        ->and($customer->org_mail_settings['to'])->toBe(['a@example.test', 'b@example.test'])
        ->and($customer->org_mail_settings['format'])->toBe('html');

    $this->actingAs($customer, 'customer')
        ->putJson(route('customer.organization.mail-settings.update'), ['to' => ['kein-mail'], 'agency_email' => 'account', 'format' => 'text'])
        ->assertJsonValidationErrors('to.0');
});

it('verschickt die Mail der Organisation mit eigenem Betreff, HTML und Empfaengern', function () {
    $customer = Customer::factory()->create([
        'email' => 'org@example.test',
        'company_name' => 'Musterkette',
        'notify_address_changes' => true,
        'org_mail_settings' => [
            'to' => ['zentrale@example.test'],
            'cc' => ['cc@example.test'],
            'bcc' => ['bcc@example.test'],
            'notify_actor' => true,
            'notify_agency' => true,
            'agency_email' => 'primary',
            'subject' => '{art} bei {agentur}',
            'format' => 'html',
            'body' => '<div style="color:red">{agentur} – Start {startdatum}</div><script>alert(1)</script>{aenderungen}',
        ],
    ]);

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.branches.store'), branchPayload(['name' => 'Büro <Süd>', 'start_date' => '2027-01-15']))
        ->assertOk();
    $branch = $customer->branches()->sole();
    EmailAddress::create(['customer_id' => $customer->id, 'branch_id' => $branch->id, 'label' => 'Büro', 'email' => 'sued@example.test', 'is_primary' => true, 'sort_order' => 0]);

    $this->actingAs($customer, 'customer')
        ->putJson(route('customer.branches.update', $branch), branchPayload(['name' => 'Büro <Süd>', 'start_date' => '2027-01-15', 'city' => 'Bremen']))
        ->assertOk();

    Mail::assertQueued(OrganizationChangeMail::class, function (OrganizationChangeMail $mail) {
        return $mail->mailSubject === 'Adressänderung bei Büro <Süd>'
            && $mail->hasTo('zentrale@example.test')
            && $mail->hasTo('sued@example.test')
            && $mail->hasTo('org@example.test')
            && $mail->hasCc('cc@example.test')
            && $mail->hasBcc('bcc@example.test')
            && str_contains($mail->mailHtml, 'Büro &lt;Süd&gt; – Start 15.01.2027')
            && str_contains($mail->mailHtml, 'Bremen')
            && ! str_contains($mail->mailHtml, '<script>');
    });
});

it('schickt ohne eingeschalteten Schalter keine Mail der Organisation', function () {
    $customer = Customer::factory()->create(['org_mail_settings' => ['to' => ['zentrale@example.test']]]);

    $this->actingAs($customer, 'customer')->postJson(route('customer.branches.store'), branchPayload())->assertOk();

    Mail::assertQueued(BranchChangedMail::class);
    Mail::assertNotQueued(OrganizationChangeMail::class);
});

it('zeigt den Tab Reiseberater nur, wenn es Reiseberater gibt, und filtert die Agenturen', function () {
    config(['customer_settings.sections.organization' => true]);
    $customer = Customer::factory()->create(['branch_management_active' => true, 'customer_type' => 'business']);
    CustomerFeatureOverride::create(['customer_id' => $customer->id, 'settings_organization_enabled' => true]);
    $agency = Customer::factory()->create(['assign_to' => $customer->id]);
    $advisor = Customer::factory()->create(['assign_to' => $customer->id, 'location_type' => 'travel_advisor']);

    $this->actingAs($customer, 'customer')
        ->getJson('/customer/assigned-agencies?location_type=agency')
        ->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $agency->id);
    $this->actingAs($customer, 'customer')
        ->getJson('/customer/assigned-agencies?location_type=travel_advisor')
        ->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $advisor->id);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.settings', ['section' => 'organization']))
        ->assertOk()
        ->assertSee("orgTab = 'reiseberater'", false)
        ->assertSee("orgTab = 'einstellungen'", false);

    $advisor->update(['location_type' => 'agency']);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.settings', ['section' => 'organization']))
        ->assertOk()
        ->assertDontSee("orgTab = 'reiseberater'", false);
});
