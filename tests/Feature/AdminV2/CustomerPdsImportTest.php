<?php

use App\Livewire\AdminV2\CustomerManagement\Customers\PdsImport;
use App\Models\Customer;
use App\Models\User;
use App\Services\PdsAccountImportService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Kundenverwaltung > Kunden > Aus pds-admin importieren: Hauptaccount anlegen,
 * Unteraccounts anlegen und der Agentur zuordnen.
 */
beforeEach(function () {
    config([
        'services.passolution.internal_api_url' => 'https://pds.test/api/v2',
        'services.passolution.internal_token' => 'token',
        'services.passolution.internal_admin_secret' => 'secret',
    ]);
});

function pdsImportAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function pdsAccount(int $id, array $attributes = []): array
{
    return array_merge([
        'id' => $id,
        'name' => "Agentur {$id}",
        'first_name' => 'Lisa',
        'last_name' => 'Nitsch',
        'email' => "agentur{$id}@solamento.test",
        'phone' => '+49 1',
        'address_line_1' => 'Güterstr. 23b',
        'zip_code' => '45219',
        'city' => 'Essen',
        'state' => null,
        'country_id' => 46,
        'status' => ['active' => true, 'deleted' => false],
    ], $attributes);
}

function fakePds(array $parent, array $children): void
{
    Http::fake([
        "pds.test/api/v2/__internal/admin/accounts/{$parent['id']}/children*" => function (Request $request) use ($children) {
            $page = (int) ($request->data()['page'] ?? 1);
            $chunks = array_chunk($children, 2) ?: [[]];

            return Http::response(['data' => $chunks[$page - 1] ?? [], 'meta' => ['last_page' => count($chunks)]]);
        },
        "pds.test/api/v2/__internal/admin/accounts/{$parent['id']}" => Http::response(['data' => $parent]),
        'pds.test/*' => Http::response(['message' => 'Not found'], 404),
    ]);
}

it('legt den Hauptaccount als Kunden an', function () {
    fakePds(pdsAccount(3145, ['name' => 'solamento Reisen GmbH', 'first_name' => 'Solamento', 'last_name' => 'Zentrale']), []);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->set('pdsAccountId', '3145')
        ->call('fetch')
        ->assertSee('solamento Reisen GmbH')
        ->assertSee('Als Kunde anlegen')
        ->call('importAccount');

    $customer = Customer::where('pds_account_id', 3145)->sole();

    expect($customer)
        ->email->toBe('agentur3145@solamento.test')
        ->company_name->toBe('solamento Reisen GmbH')
        ->company_additional->toBe('Solamento Zentrale')
        ->company_street->toBe('Güterstr.')
        ->company_house_number->toBe('23b')
        ->company_country->toBe('DE')
        ->customer_type->toBe('business')
        ->assign_to->toBeNull()
        ->and(\App\Models\Employee::where('customer_id', $customer->id)->where('email', $customer->email)->exists())->toBeTrue();

    Http::assertSent(fn (Request $request) => $request->hasHeader('X-PDS-Service', 'admin')
        && $request->hasHeader('X-PDS-Secret', 'secret')
        && $request->hasHeader('Authorization', 'Bearer token'));
});

it('erkennt einen vorhandenen Kunden statt ihn doppelt anzulegen', function () {
    $existing = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    fakePds(pdsAccount(3145), []);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->set('pdsAccountId', '3145')
        ->call('fetch')
        ->assertSet('agencyId', $existing->id)
        ->assertDontSee('Als Kunde anlegen')
        ->assertSee('2. Unteraccounts');

    expect(Customer::where('pds_account_id', 3145)->count())->toBe(1);
});

it('meldet einen unbekannten Account', function () {
    fakePds(pdsAccount(3145), []);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->set('pdsAccountId', '999')
        ->call('fetch')
        ->assertSet('account', null)
        ->assertSee('Account 999 gibt es in pds-admin nicht.');
});

it('importiert Unteraccounts über alle Seiten und ordnet sie der Agentur zu', function () {
    $agency = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    $other = Customer::factory()->create(['email' => 'andere@agentur.test']);

    // Schon per Login vorhanden, noch ohne Account-ID und Agentur.
    $known = Customer::factory()->create(['email' => 'Agentur2@solamento.test', 'pds_account_id' => null]);
    // Bereits einer anderen Agentur zugeordnet – wird umgehaengt.
    $foreign = Customer::factory()->create(['email' => 'agentur3@solamento.test', 'pds_account_id' => 3, 'assign_to' => $other->id]);
    $other->accessibleAccounts()->attach($foreign->id);
    // Im Papierkorb – wird trotzdem zugeordnet, bleibt geloescht.
    $trashed = Customer::factory()->create(['email' => 'agentur6@solamento.test', 'pds_account_id' => 6]);
    $trashed->delete();

    fakePds(pdsAccount(3145), [
        pdsAccount(1, ['country_id' => 164]),
        pdsAccount(2),
        pdsAccount(3),
        pdsAccount(4, ['email' => 'keine-mail']),
        pdsAccount(5, ['country_id' => 9999]),
        pdsAccount(6),
    ]);

    $component = Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->set('pdsAccountId', '3145')
        ->call('fetch')
        ->call('loadChildren')
        ->assertSet('childCounts', [
            PdsAccountImportService::STATUS_CREATED => 2,
            PdsAccountImportService::STATUS_UPDATED => 1,
            PdsAccountImportService::STATUS_REASSIGNED => 1,
            PdsAccountImportService::STATUS_INVALID => 1,
            PdsAccountImportService::STATUS_TRASHED => 1,
        ])
        ->assertSee('5 importieren und zuordnen');

    // Die Pruefung schreibt nichts.
    expect(Customer::where('assign_to', $agency->id)->count())->toBe(0);

    $component->call('importChildren')
        ->assertSet('result', [
            PdsAccountImportService::STATUS_CREATED => 2,
            PdsAccountImportService::STATUS_UPDATED => 1,
            PdsAccountImportService::STATUS_REASSIGNED => 1,
            PdsAccountImportService::STATUS_INVALID => 1,
            PdsAccountImportService::STATUS_TRASHED => 1,
        ]);

    $created = Customer::where('pds_account_id', 1)->sole();

    expect($created->assign_to)->toBe($agency->id)
        ->and($created->company_country)->toBe('AT')
        ->and(Customer::where('pds_account_id', 5)->sole()->company_country)->toBeNull()
        ->and($known->fresh())->pds_account_id->toBe(2)->assign_to->toBe($agency->id)
        ->and($foreign->fresh()->assign_to)->toBe($agency->id)
        ->and($other->accessibleAccounts()->whereKey($foreign->id)->exists())->toBeFalse()
        ->and(Customer::withTrashed()->find($trashed->id))->trashed()->toBeTrue()->assign_to->toBe($agency->id)
        ->and($agency->accessibleAccounts()->withTrashed()->pluck('customers.id')->sort()->values()->all())
        ->toBe(collect([$created->id, $known->id, $foreign->id, $trashed->id, Customer::where('pds_account_id', 5)->value('id')])->sort()->values()->all());

    // Ein zweiter Lauf legt nichts doppelt an.
    $component->call('importChildren')
        ->assertSet('result', [
            PdsAccountImportService::STATUS_UNCHANGED => 5,
            PdsAccountImportService::STATUS_INVALID => 1,
        ]);

    expect(Customer::withTrashed()->where('assign_to', $agency->id)->count())->toBe(5);
});

it('gleicht ohne pds-api einen CSV-Export ab und importiert nur die Differenz', function () {
    config(['services.passolution.internal_admin_secret' => null]);
    Http::fake();

    $agency = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    // Schon in der Plattform (per Login), wird nur zugeordnet.
    $known = Customer::factory()->create(['email' => 'bekannt@solamento.test', 'pds_account_id' => null]);

    $csv = implode("\n", [
        "\u{FEFF}id;name;first_name;last_name;email;phone;address_line_1;zip_code;city;state;country_id;parent.id;status.active;status.deleted",
        '11;Reisebüro Augsburg;Doreen;Kalberg;neu@solamento.test;0151;Feuerhausstr. 25;86165;Augsburg;;46;3145;true;false',
        '12;Reisebüro Essen;Max;Muster;Bekannt@solamento.test;;Weg 1;45219;Essen;;46;3145;true;false',
        '13;Zweitaccount;;;neu@solamento.test;;;;;;46;3145;true;false',
        '14;Inaktiv;;;inaktiv@solamento.test;;;;;;46;3145;false;false',
        '15;Fremd;;;fremd@solamento.test;;;;;;46;999;true;false',
    ]);

    $component = Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->assertSee('Import nicht eingerichtet')
        ->set('pdsAccountId', '3145')
        ->call('fetch')
        ->assertSet('agencyId', $agency->id)
        ->assertSee('pds-api nicht angebunden')
        ->set('csv', UploadedFile::fake()->createWithContent('solamento.csv', $csv))
        ->call('loadChildren')
        ->assertSet('csvSkipped', 2)
        ->assertSet('childCounts', [
            PdsAccountImportService::STATUS_CREATED => 1,
            PdsAccountImportService::STATUS_UPDATED => 1,
            PdsAccountImportService::STATUS_DUPLICATE => 1,
        ])
        ->set('childFilter', 'existing')
        ->assertSee('Reisebüro Essen')
        ->assertDontSee('Reisebüro Augsburg');

    $component->call('importChildren')
        ->assertSet('result', [
            PdsAccountImportService::STATUS_CREATED => 1,
            PdsAccountImportService::STATUS_UPDATED => 1,
            PdsAccountImportService::STATUS_DUPLICATE => 1,
        ]);

    expect(Customer::where('pds_account_id', 11)->sole())
        ->assign_to->toBe($agency->id)
        ->company_city->toBe('Augsburg')
        ->and($known->fresh())->pds_account_id->toBe(12)->assign_to->toBe($agency->id)
        ->and(Customer::where('assign_to', $agency->id)->count())->toBe(2)
        ->and(Customer::where('email', 'inaktiv@solamento.test')->exists())->toBeFalse();

    Http::assertNothingSent();
});

it('meldet ohne pds-api einen Hauptaccount, den es noch nicht als Kunden gibt', function () {
    config(['services.passolution.internal_admin_secret' => null]);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->set('pdsAccountId', '3145')
        ->call('fetch')
        ->assertSet('account', null)
        ->assertSee('keiner hat die Account-ID 3145');
});

it('ordnet die Unteraccounts einem frei gewählten Kunden zu und lädt auf Wunsch auch inaktive', function () {
    $main = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    $target = Customer::factory()->create(['email' => 'ziel@agentur.test']);
    $known = Customer::factory()->create(['email' => 'agentur1@solamento.test']);

    fakePds(pdsAccount(3145), [pdsAccount(1), pdsAccount(2, ['status' => ['active' => false, 'deleted' => false]])]);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->assertSet('source', 'api')
        ->set('pdsAccountId', '3145')
        ->call('fetch')
        ->assertSet('targetCustomerId', (string) $main->id)
        ->set('targetCustomerId', '999999')
        ->assertHasErrors('targetCustomerId')
        ->set('targetCustomerId', (string) $target->id)
        ->assertSet('agencyId', $target->id)
        ->set('onlyActive', false)
        ->call('loadChildren')
        ->call('importChildren')
        ->assertSet('result', [
            PdsAccountImportService::STATUS_UPDATED => 1,
            PdsAccountImportService::STATUS_CREATED => 1,
        ]);

    expect($known->fresh())->assign_to->toBe($target->id)->pds_account_id->toBe(1)
        ->and(Customer::where('pds_account_id', 2)->sole()->assign_to)->toBe($target->id)
        ->and(Customer::where('assign_to', $main->id)->exists())->toBeFalse();

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/children') && ! str_contains(urldecode($request->url()), 'filters'));
});

it('nimmt bei Quelle Importdatei die CSV statt der Schnittstelle', function () {
    Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    fakePds(pdsAccount(3145), [pdsAccount(1)]);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->set('pdsAccountId', '3145')
        ->call('fetch')
        ->set('source', 'csv')
        ->call('loadChildren')
        ->assertSee('Bitte zuerst die Importdatei wählen.')
        ->set('csv', UploadedFile::fake()->createWithContent('a.csv', "id,email,name,parent.id\n77,datei@solamento.test,Aus Datei,3145\n"))
        ->call('loadChildren')
        ->assertSet('childCounts', [PdsAccountImportService::STATUS_CREATED => 1])
        ->assertSee('datei@solamento.test')
        ->assertDontSee('agentur1@solamento.test');

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/children'));
});

it('ist nur für Admins erreichbar', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]))
        ->get(route('adminv2.customer-management.customers.import'))
        ->assertForbidden();

    $this->actingAs(pdsImportAdmin())
        ->get(route('adminv2.customer-management.customers.index'))
        ->assertSee('Aus pds-admin importieren');
});
