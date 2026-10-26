<?php

use App\Jobs\ImportPdsChildren;
use App\Livewire\AdminV2\CustomerManagement\Customers\PdsChildrenImport;
use App\Livewire\AdminV2\CustomerManagement\Customers\PdsImport;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\PdsChildrenImportCompleted;
use App\Services\PdsAccountImportService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * Kundenverwaltung > Kunden > Account aus pds-admin importieren (einzeln) und
 * Kunde > Unteraccounts > aus pds-admin importieren.
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

it('legt einen einzelnen Account als Kunden an', function () {
    fakePds(pdsAccount(3145, ['name' => 'solamento Reisen GmbH', 'first_name' => 'Solamento', 'last_name' => 'Zentrale']), []);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->set('pdsAccountId', '3145')
        ->call('fetch')
        ->assertSee('solamento Reisen GmbH')
        ->assertSee('Noch nicht in der Plattform')
        ->call('import')
        ->assertSee('Schon in der Plattform');

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

it('erkennt einen schon angelegten Account statt ihn doppelt anzulegen', function () {
    $existing = Customer::factory()->create(['email' => 'AGENTUR3145@solamento.test', 'pds_account_id' => null]);
    fakePds(pdsAccount(3145), []);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->set('pdsAccountId', '3145')
        ->call('fetch')
        ->assertSet('customerId', $existing->id)
        ->assertSee('Schon in der Plattform')
        ->assertSee('Fehlt noch: Account-ID')
        ->assertDontSee('Als Kunde anlegen')
        ->call('import')
        ->assertDontSee('Am Kunden ergänzen');

    expect(Customer::where('email', 'agentur3145@solamento.test')->count())->toBe(1)
        ->and($existing->fresh()->pds_account_id)->toBe(3145);
});

it('ordnet einen Unteraccount beim Einzelimport seinem Hauptaccount zu', function () {
    $agency = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    fakePds(pdsAccount(26054, ['parent' => ['id' => 3145, 'name' => 'solamento Reisen GmbH']]), []);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->set('pdsAccountId', '26054')
        ->call('fetch')
        ->assertSee('Wird dem Hauptaccount zugeordnet')
        ->call('import');

    $customer = Customer::where('pds_account_id', 26054)->sole();

    expect($customer->assign_to)->toBe($agency->id)
        ->and($agency->accessibleAccounts()->whereKey($customer->id)->exists())->toBeTrue();
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

it('zeigt beim Einzelimport einen Hinweis, wenn das Admin-Secret fehlt', function () {
    config(['services.passolution.internal_admin_secret' => null]);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsImport::class)
        ->assertSee('Import nicht eingerichtet')
        ->set('pdsAccountId', '3145')
        ->call('fetch')
        ->assertSee('PDS-Import ist nicht eingerichtet');
});

it('importiert Unteraccounts über alle Seiten und ordnet alle dem Kunden zu', function () {
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
        ->test(PdsChildrenImport::class, ['customer' => $agency->id])
        ->assertSet('source', 'api')
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
        ->call('refreshRun')
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
        ->call('refreshRun')
        ->assertSet('result', [
            PdsAccountImportService::STATUS_UNCHANGED => 5,
            PdsAccountImportService::STATUS_INVALID => 1,
        ]);

    expect(Customer::withTrashed()->where('assign_to', $agency->id)->count())->toBe(5);
});

it('lädt auf Wunsch auch inaktive Unteraccounts', function () {
    $agency = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    fakePds(pdsAccount(3145), [pdsAccount(1)]);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsChildrenImport::class, ['customer' => $agency->id])
        ->set('onlyActive', false)
        ->call('loadChildren')
        ->assertSet('childCounts', [PdsAccountImportService::STATUS_CREATED => 1]);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/children') && ! str_contains(urldecode($request->url()), 'filters'));
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
        ->test(PdsChildrenImport::class, ['customer' => $agency->id])
        ->assertSet('source', 'csv')
        ->call('loadChildren')
        ->assertSee('Bitte zuerst die Importdatei wählen.')
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
        ->call('refreshRun')
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

it('fragt die fehlende PDS Account-ID des Kunden ab und speichert sie', function () {
    $agency = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => null]);
    fakePds(pdsAccount(3145), [pdsAccount(1)]);

    Livewire::actingAs(pdsImportAdmin())
        ->test(PdsChildrenImport::class, ['customer' => $agency->id])
        ->assertSee('PDS Account-ID des Kunden')
        ->assertDontSee('2. Unteraccounts')
        ->set('pdsAccountId', '3145')
        ->call('savePdsAccountId')
        ->assertSee('2. Unteraccounts')
        ->call('loadChildren')
        ->assertSet('childCounts', [PdsAccountImportService::STATUS_CREATED => 1]);

    expect($agency->fresh()->pds_account_id)->toBe(3145);
});

it('ist nur für Admins erreichbar und aus Liste und Kunde verlinkt', function () {
    $customer = Customer::factory()->create(['pds_account_id' => 3145]);

    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]))
        ->get(route('adminv2.customer-management.customers.import'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]))
        ->get(route('adminv2.customer-management.customers.import-children', $customer))
        ->assertForbidden();

    $admin = pdsImportAdmin();

    $this->actingAs($admin)
        ->get(route('adminv2.customer-management.customers.index'))
        ->assertSee('Account aus pds-admin importieren');

    $this->actingAs($admin)
        ->get(route('adminv2.customer-management.customers.edit', ['customer' => $customer->id, 'tab' => 'sub-accounts']))
        ->assertSee('Unteraccounts aus pds-admin importieren')
        ->assertSee(route('adminv2.customer-management.customers.import-children', $customer->id));
});

it('listet im Tab Unteraccounts die zugeordneten Kunden mit Suche', function () {
    $agency = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    Customer::factory()->create(['company_name' => 'Reisebüro Augsburg', 'email' => 'augsburg@solamento.test', 'pds_account_id' => 26054, 'assign_to' => $agency->id]);
    Customer::factory()->create(['company_name' => 'Reisebüro Essen', 'email' => 'essen@solamento.test', 'assign_to' => $agency->id])->delete();
    Customer::factory()->create(['company_name' => 'Fremdes Büro', 'email' => 'fremd@agentur.test']);

    Livewire::actingAs(pdsImportAdmin())
        ->test(\App\Livewire\AdminV2\CustomerManagement\Customers\Editor::class, ['customer' => $agency->id])
        ->call('showTab', 'sub-accounts')
        ->assertSee('Reisebüro Augsburg')
        ->assertSee('26054')
        ->assertSee('Reisebüro Essen')
        ->assertSee('Papierkorb')
        ->assertDontSee('Fremdes Büro')
        ->assertSee('Unteraccounts aus pds-admin importieren')
        ->set('subAccountSearch', 'Augsburg')
        ->assertSee('Reisebüro Augsburg')
        ->assertDontSee('Reisebüro Essen');
});

it('pflegt einen Hinweis zu den Unteraccounts und zeigt ihn beim Unteraccount', function () {
    $agency = Customer::factory()->create(['company_name' => 'solamento Reisen GmbH', 'email' => 'zentrale@login.test']);
    $office = Customer::factory()->create(['company_name' => 'Reisebüro Augsburg', 'email' => 'augsburg@solamento.test', 'assign_to' => $agency->id]);
    $note = 'Reisebürokette – eigenständige Büros mit eigenen Logins.';

    Livewire::actingAs(pdsImportAdmin())
        ->test(\App\Livewire\AdminV2\CustomerManagement\Customers\Editor::class, ['customer' => $agency->id])
        ->call('showTab', 'sub-accounts')
        ->assertSee('Hinweis zu den Unteraccounts')
        ->set('subAccountsNote', "  {$note}  ")
        ->call('saveSubAccountsNote')
        ->assertHasNoErrors()
        ->set('subAccountsNote', str_repeat('x', 2001))
        ->call('saveSubAccountsNote')
        ->assertHasErrors('subAccountsNote');

    expect($agency->fresh()->sub_accounts_note)->toBe($note);

    Livewire::actingAs(pdsImportAdmin())
        ->test(\App\Livewire\AdminV2\CustomerManagement\Customers\Editor::class, ['customer' => $office->id])
        ->assertSee('Unteraccount von')
        ->assertSee('solamento Reisen GmbH')
        ->assertSee($note);

    Livewire::actingAs(pdsImportAdmin())
        ->test(\App\Livewire\AdminV2\CustomerManagement\Customers\Editor::class, ['customer' => $agency->id])
        ->assertSet('subAccountsNote', $note)
        ->assertDontSee('Unteraccount von');
});

it('importiert im Hintergrund per Queue, zeigt den Fortschritt und benachrichtigt am Ende', function () {
    Queue::fake();
    Notification::fake();

    $admin = pdsImportAdmin();
    $agency = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    $children = array_map(fn (int $id) => pdsAccount($id), range(1, 25));
    fakePds(pdsAccount(3145), $children);

    // Ein zweiter Tab hat die Vorschau schon geladen, bevor der Import startet.
    $otherTab = Livewire::actingAs($admin)
        ->test(PdsChildrenImport::class, ['customer' => $agency->id])
        ->call('loadChildren')
        ->assertSee('25 importieren und zuordnen');

    $component = Livewire::actingAs($admin)
        ->test(PdsChildrenImport::class, ['customer' => $agency->id])
        ->call('loadChildren')
        ->call('importChildren')
        ->assertSet('runTotal', 25)
        ->assertSee('Import läuft im Hintergrund')
        ->assertSee('am Ende kommt eine E-Mail an '.$admin->email)
        ->assertSee('0 von 25')
        ->assertDontSee('25 importieren und zuordnen');

    $runKey = $component->get('runKey');
    Queue::assertPushed(ImportPdsChildren::class, fn (ImportPdsChildren $job) => $job->agencyId === $agency->id && $job->userId === $admin->id && $job->runKey === $runKey);

    // Kein zweiter Start: der andere Tab haengt sich an statt neu zu starten,
    // eine neu geladene Seite ebenso.
    $otherTab->call('importChildren')
        ->assertDispatched('adminv2-toast', message: 'Für diesen Kunden läuft schon ein Import.')
        ->assertSet('runKey', $runKey)
        ->assertSee('0 von 25')
        ->assertDontSee('25 importieren und zuordnen');
    Queue::assertPushed(ImportPdsChildren::class, 1);

    Livewire::actingAs($admin)->test(PdsChildrenImport::class, ['customer' => $agency->id])
        ->assertSet('runKey', $runKey)
        ->assertSee('0 von 25')
        ->call('loadChildren')
        ->assertSet('childrenLoaded', false);

    // Der Worker arbeitet den Job ab.
    app()->call([new ImportPdsChildren($agency->id, $admin->id, $runKey), 'handle']);

    expect(Customer::where('assign_to', $agency->id)->count())->toBe(25);

    $component->call('refreshRun')
        ->assertSet('runKey', null)
        ->assertSet('result', [PdsAccountImportService::STATUS_CREATED => 25])
        ->assertSee('Import abgeschlossen')
        ->assertDispatched('pds-import-finished');

    Notification::assertSentTo($admin, PdsChildrenImportCompleted::class, fn (PdsChildrenImportCompleted $notification) => $notification->total === 25
        && $notification->error === null
        && str_contains($notification->toMail($admin)->subject, 'Unteraccounts von'));

    // Danach laesst sich wieder importieren.
    Livewire::actingAs($admin)->test(PdsChildrenImport::class, ['customer' => $agency->id])
        ->assertSet('runKey', null);
});

it('stellt sich nach der Arbeitszeit für den Rest neu ein', function () {
    Queue::fake();
    Notification::fake();

    $admin = pdsImportAdmin();
    $agency = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    fakePds(pdsAccount(3145), array_map(fn (int $id) => pdsAccount($id), range(1, 3)));

    $component = Livewire::actingAs($admin)
        ->test(PdsChildrenImport::class, ['customer' => $agency->id])
        ->call('importChildren');
    $runKey = $component->get('runKey');

    // Ohne Arbeitszeit schafft jeder Job genau einen Unteraccount.
    ImportPdsChildren::$runSeconds = 0;

    try {
        app()->call([new ImportPdsChildren($agency->id, $admin->id, $runKey), 'handle']);
        $component->call('refreshRun')->assertSet('runDone', 1)->assertSee('1 von 3');
        Queue::assertPushed(ImportPdsChildren::class, 2);

        app()->call([new ImportPdsChildren($agency->id, $admin->id, $runKey), 'handle']);
        app()->call([new ImportPdsChildren($agency->id, $admin->id, $runKey), 'handle']);
    } finally {
        ImportPdsChildren::$runSeconds = 60;
    }

    $component->call('refreshRun')
        ->assertSet('runKey', null)
        ->assertSet('result', [PdsAccountImportService::STATUS_CREATED => 3]);

    expect(Customer::where('assign_to', $agency->id)->count())->toBe(3);
    Notification::assertSentToTimes($admin, PdsChildrenImportCompleted::class, 1);
});

it('meldet einen abgebrochenen Hintergrund-Import', function () {
    Queue::fake();
    Notification::fake();

    $admin = pdsImportAdmin();
    $agency = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    fakePds(pdsAccount(3145), [pdsAccount(1)]);

    $component = Livewire::actingAs($admin)
        ->test(PdsChildrenImport::class, ['customer' => $agency->id])
        ->call('importChildren');

    (new ImportPdsChildren($agency->id, $admin->id, $component->get('runKey')))->failed(new RuntimeException('Zeitlimit'));

    $component->call('refreshRun')
        ->assertSet('runKey', null)
        ->assertSee('Der Import ist abgebrochen: Zeitlimit');

    Notification::assertSentTo($admin, PdsChildrenImportCompleted::class, fn (PdsChildrenImportCompleted $notification) => $notification->error !== null);
});

it('lässt einen laufenden Import abbrechen und danach neu starten', function () {
    Queue::fake();
    Notification::fake();

    $admin = pdsImportAdmin();
    $agency = Customer::factory()->create(['email' => 'zentrale@login.test', 'pds_account_id' => 3145]);
    fakePds(pdsAccount(3145), [pdsAccount(1), pdsAccount(2)]);

    $component = Livewire::actingAs($admin)
        ->test(PdsChildrenImport::class, ['customer' => $agency->id])
        ->call('importChildren');
    $runKey = $component->get('runKey');

    $component->call('cancelImport')
        ->assertSet('runKey', null)
        ->assertSee('Der Import wurde abgebrochen.');

    // Der wartende Job tut nichts mehr und schickt keine Mail.
    app()->call([new ImportPdsChildren($agency->id, $admin->id, $runKey), 'handle']);
    expect(Customer::where('assign_to', $agency->id)->count())->toBe(0);
    Notification::assertNothingSent();

    $component->call('importChildren')->assertNotSet('runKey', null);
    Queue::assertPushed(ImportPdsChildren::class, 2);
});
