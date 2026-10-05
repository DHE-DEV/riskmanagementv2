<?php

use App\Livewire\AdminV2\CustomerManagement\PluginClients\Editor;
use App\Livewire\AdminV2\CustomerManagement\PluginClients\Index;
use App\Models\Customer;
use App\Models\PluginClient;
use App\Models\PluginDomain;
use App\Models\PluginKey;
use App\Models\PluginUsageEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/**
 * Kundenverwaltung > Plugin-Kunden: Liste mit Kennzahlen, Filtern und
 * Aktionen sowie die Seite eines Plugin-Kunden mit API-Key, Domains und Aufrufen.
 */
function pluginClientsAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function pluginClient(array $attributes = []): PluginClient
{
    return PluginClient::create(array_merge([
        'company_name' => 'Reisebüro Sonnenschein',
        'contact_name' => 'Petra Sonne',
        'email' => 'petra@sonnenschein.test',
        'status' => 'active',
    ], $attributes));
}

function pluginUsage(PluginClient $client, string $at, array $attributes = []): PluginUsageEvent
{
    return PluginUsageEvent::create(array_merge([
        'plugin_client_id' => $client->id,
        'public_key' => 'pk_live_test',
        'domain' => 'sonnenschein.test',
        'path' => '/reisen',
        'event_type' => 'page_load',
        'created_at' => $at,
    ], $attributes));
}

beforeEach(function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
});

afterEach(fn () => Carbon::setTestNow());

it('zeigt Kennzahlen und Liste und filtert, sucht und sortiert', function () {
    $this->actingAs(pluginClientsAdmin());

    $this->get(route('adminv2.customer-management.plugin-clients.index'))
        ->assertOk()
        ->assertSee('Keine Plugin-Kunden');

    $customer = Customer::factory()->create(['name' => 'Kundin Köln', 'company_city' => 'Köln']);

    $sonne = pluginClient(['customer_id' => $customer->id]);
    $sonne->generateKey();
    $sonne->addDomain('sonnenschein.test');
    $sonne->forceFill(['created_at' => '2026-10-02 08:00:00'])->save();

    $alpen = pluginClient(['company_name' => 'Alpen Tours', 'contact_name' => 'Hans Berg', 'email' => 'hans@alpen.test', 'status' => 'inactive', 'city' => 'Aachen']);
    $alpen->forceFill(['created_at' => '2026-09-10 08:00:00'])->save();

    $nord = pluginClient(['company_name' => 'Nordlicht Reisen', 'contact_name' => '', 'email' => 'info@nordlicht.test', 'status' => 'suspended', 'allow_app_access' => true]);
    $nord->forceFill(['created_at' => '2025-10-05 08:00:00'])->save();

    pluginUsage($sonne, '2026-10-15 09:00:00');
    pluginUsage($sonne, '2026-10-03 09:00:00');
    pluginUsage($sonne, '2026-08-01 09:00:00');

    $this->get(route('adminv2.customer-management.plugin-clients.index'))
        ->assertOk()
        ->assertSee('Reisebüro Sonnenschein')
        ->assertSee('Alpen Tours');

    $list = Livewire::test(Index::class);

    expect($list->get('stats'))->toBe(['clients' => 3, 'active' => 1, 'new' => 1, 'today' => 1, 'month' => 2, 'total' => 3]);

    // Vorgabe: neueste Registrierung zuerst.
    $list->assertSeeInOrder(['Reisebüro Sonnenschein', 'Alpen Tours', 'Nordlicht Reisen'])
        ->assertSee('Gesperrt')
        ->assertSee('App-Zugang')
        ->assertSee('Kundin Köln');

    // Nach einer Aktualisierung prueft assertSeeInOrder die JSON-Antwort – dort ohne Umlaute suchen.
    $list->set('sort', 'company_name')->call('toggleDirection')
        ->assertSeeInOrder(['Alpen Tours', 'Nordlicht Reisen', 'Sonnenschein']);

    // Ort: eigener Ort, ersatzweise der des verknuepften Kunden; ohne Ort am Ende.
    $list->set('sort', 'city')
        ->assertSeeInOrder(['Alpen Tours', 'Sonnenschein', 'Nordlicht Reisen']);

    $list->set('sort', 'usage_events_count')->call('toggleDirection')
        ->assertSeeInOrder(['Sonnenschein', 'Nordlicht Reisen']);

    $list->set('search', 'hans')->assertSee('Alpen Tours')->assertDontSee('Nordlicht Reisen');
    $list->set('search', '')->set('status', 'suspended')->assertSee('Nordlicht Reisen')->assertDontSee('Alpen Tours');
    $list->set('status', '')->set('usage', 'yes')->assertSee('Reisebüro Sonnenschein')->assertDontSee('Alpen Tours');
    // "Diesen Monat" meint Oktober 2026 – nicht den Oktober des Vorjahres.
    $list->set('usage', '')->set('registered', 'month')->assertSee('Reisebüro Sonnenschein')->assertDontSee('Nordlicht Reisen')->assertDontSee('Alpen Tours');

    expect($list->instance()->hasFilters())->toBeTrue();

    $list->call('resetFilters')->assertSet('registered', '')->assertSee('Nordlicht Reisen');

    expect($list->instance()->hasFilters())->toBeFalse();
});

it('wechselt den Status und loescht einzeln oder mehrere samt Domains, Keys und Aufrufen', function () {
    $this->actingAs(pluginClientsAdmin());

    $sonne = pluginClient();
    $sonne->generateKey();
    $sonne->addDomain('sonnenschein.test');
    pluginUsage($sonne, '2026-10-15 09:00:00');

    $alpen = pluginClient(['company_name' => 'Alpen Tours', 'email' => 'hans@alpen.test', 'status' => 'suspended']);
    $nord = pluginClient(['company_name' => 'Nordlicht Reisen', 'email' => 'info@nordlicht.test']);
    $nord->generateKey();

    $list = Livewire::test(Index::class)->call('toggleStatus', $sonne->id);

    expect($sonne->fresh()->status)->toBe('inactive');

    // Auch ein gesperrter Kunde wird ueber die Aktion wieder aktiv.
    $list->call('toggleStatus', $sonne->id)->call('toggleStatus', $alpen->id);

    expect($sonne->fresh()->status)->toBe('active')
        ->and($alpen->fresh()->status)->toBe('active');

    $list->call('delete', $alpen->id)->assertDontSee('Alpen Tours');

    expect(PluginClient::count())->toBe(2);

    $list->call('selectPage')
        ->assertSet('selected', [(string) $nord->id, (string) $sonne->id])
        ->call('clearSelection')
        ->assertSet('selected', [])
        ->set('selected', [(string) $sonne->id, (string) $nord->id])
        ->call('deleteSelected')
        ->assertSet('selected', [])
        ->assertSee('Keine Plugin-Kunden');

    expect(PluginClient::count())->toBe(0)
        ->and(PluginDomain::count())->toBe(0)
        ->and(PluginKey::count())->toBe(0)
        ->and(PluginUsageEvent::count())->toBe(0);
});

it('legt einen Plugin-Kunden mit API-Key an und bearbeitet ihn', function () {
    $this->actingAs(pluginClientsAdmin());

    $customer = Customer::factory()->create(['name' => 'Kundin Köln', 'email' => 'koeln@kunde.test', 'customer_type' => 'business', 'business_type' => ['travel_agency', 'organizer']]);

    $this->get(route('adminv2.customer-management.plugin-clients.create'))
        ->assertOk()
        ->assertSee('Neuer Plugin-Kunde')
        ->assertSee('Nach dem Speichern');

    Livewire::test(Editor::class)
        ->assertSet('status', 'active')
        ->assertSet('allowAppAccess', false)
        ->call('save')
        ->assertHasErrors(['companyName', 'email'])
        ->set('companyName', 'Reisebüro Sonnenschein')
        ->set('email', 'keine-adresse')
        ->set('houseNumber', str_repeat('1', 21))
        ->set('status', 'unbekannt')
        ->set('customerId', '999999')
        ->call('save')
        ->assertHasErrors(['email', 'houseNumber', 'status', 'customerId'])
        ->set('email', 'petra@sonnenschein.test')
        ->set('houseNumber', '12a')
        ->set('status', 'active')
        ->set('customerId', (string) $customer->id)
        ->set('street', 'Hauptstraße')
        ->set('allowAppAccess', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.customer-management.plugin-clients.edit', PluginClient::first()->id));

    $client = PluginClient::first();

    expect($client->company_name)->toBe('Reisebüro Sonnenschein')
        ->and($client->contact_name)->toBe('')
        ->and($client->customer_id)->toBe($customer->id)
        ->and($client->house_number)->toBe('12a')
        ->and($client->city)->toBeNull()
        ->and($client->allow_app_access)->toBeTrue()
        // Der API-Key entsteht beim Anlegen von selbst.
        ->and($client->keys()->count())->toBe(1)
        ->and($client->activeKey->public_key)->toStartWith('pk_live_');

    $key = $client->activeKey->public_key;

    $this->get(route('adminv2.customer-management.plugin-clients.edit', $client->id))
        ->assertOk()
        ->assertSee($key)
        ->assertSee('https://global-travel-monitor.eu/embed/events?key='.$key)
        ->assertSee('https://global-travel-monitor.eu/embed/map?key='.$key)
        ->assertSee('https://global-travel-monitor.eu/embed/dashboard?key='.$key)
        ->assertSee('Kundin Köln')
        ->assertSee(route('adminv2.customer-management.customers.edit', $customer->id))
        ->assertSee('Firmenkunde')
        ->assertSee('Reisebüro')
        ->assertSee('Veranstalter')
        ->assertSee('Keine Domains')
        ->assertSee('Keine Aufrufe');

    $editor = Livewire::test(Editor::class, ['pluginClient' => $client->id])
        ->assertSet('companyName', 'Reisebüro Sonnenschein')
        ->assertSet('customerId', (string) $customer->id)
        ->set('contactName', 'Petra Sonne')
        ->set('status', 'suspended')
        ->set('customerId', '')
        ->set('city', 'Köln')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $client->refresh();

    expect($client->contact_name)->toBe('Petra Sonne')
        ->and($client->status)->toBe('suspended')
        ->and($client->customer_id)->toBeNull()
        ->and($client->city)->toBe('Köln');

    // Neuer API-Key: der alte wird ungueltig.
    $editor->call('regenerateKey')->assertDontSee($key);

    expect($client->keys()->count())->toBe(2)
        ->and($client->keys()->where('is_active', true)->count())->toBe(1)
        ->and($client->fresh()->activeKey->public_key)->not->toBe($key);

    $editor->call('delete')->assertRedirect(route('adminv2.customer-management.plugin-clients.index'));

    expect(PluginClient::count())->toBe(0)->and(PluginKey::count())->toBe(0);

    $this->get(route('adminv2.customer-management.plugin-clients.edit', $client->id))->assertNotFound();
});

it('pflegt die Domains eines Plugin-Kunden', function () {
    $this->actingAs(pluginClientsAdmin());

    $client = pluginClient();
    $other = pluginClient(['company_name' => 'Alpen Tours', 'email' => 'hans@alpen.test']);
    $foreign = $other->addDomain('alpen.test');

    $editor = Livewire::test(Editor::class, ['pluginClient' => $client->id])
        ->call('addDomain')
        ->assertHasErrors(['newDomain'])
        // Protokoll, "www." und Pfad fallen weg.
        ->set('newDomain', 'https://www.Sonnenschein.test/reisen')
        ->call('addDomain')
        ->assertHasNoErrors()
        ->assertSet('newDomain', '')
        ->assertSee('sonnenschein.test')
        // Jede Domain gibt es nur einmal – auch bei einem anderen Kunden.
        ->set('newDomain', 'sonnenschein.test')
        ->call('addDomain')
        ->assertHasErrors(['newDomain'])
        ->set('newDomain', 'alpen.test')
        ->call('addDomain')
        ->assertHasErrors(['newDomain'])
        ->set('newDomain', 'inaktiv.test')
        ->set('newDomainActive', false)
        ->call('addDomain')
        ->assertHasNoErrors()
        ->assertSet('newDomainActive', true);

    $domain = $client->domains()->where('domain', 'sonnenschein.test')->first();

    expect($client->domains()->count())->toBe(2)
        ->and($domain->is_active)->toBeTrue()
        ->and($client->domains()->where('domain', 'inaktiv.test')->first()->is_active)->toBeFalse();

    $editor->call('toggleDomain', $domain->id);

    expect($domain->fresh()->is_active)->toBeFalse();

    $editor->call('toggleDomain', $domain->id)->call('deleteDomain', $domain->id);

    expect($client->domains()->pluck('domain')->all())->toBe(['inaktiv.test']);

    // Domains anderer Kunden lassen sich von hier aus nicht aendern.
    $editor->call('deleteDomain', $foreign->id)->assertNotFound();

    expect($foreign->fresh())->not->toBeNull();
});

it('zeigt die Aufrufe eines Plugin-Kunden mit Statistik, Filtern und Sortierung', function () {
    $this->actingAs(pluginClientsAdmin());

    $client = pluginClient();
    $other = pluginClient(['company_name' => 'Alpen Tours', 'email' => 'hans@alpen.test']);

    // Do 15.10.2026: die Woche beginnt am Mo 12.10.
    pluginUsage($client, '2026-10-15 09:00:00', ['path' => '/heute']);
    pluginUsage($client, '2026-10-13 09:00:00', ['path' => '/woche', 'event_type' => 'click']);
    pluginUsage($client, '2026-10-02 09:00:00', ['path' => '/monat', 'domain' => 'blog.sonnenschein.test', 'event_type' => 'embed_view']);
    pluginUsage($client, '2026-08-01 09:00:00', ['path' => '/alt', 'user_agent' => 'Mozilla/5.0 Testbrowser']);
    pluginUsage($other, '2026-10-15 10:00:00', ['path' => '/fremd', 'domain' => 'alpen.test']);

    $editor = Livewire::test(Editor::class, ['pluginClient' => $client->id]);

    expect($editor->get('usage'))->toBe(['total' => 4, 'days30' => 3, 'today' => 1])
        ->and($editor->get('eventTypeOptions'))->toBe(['page_load' => 'Page Load', 'click' => 'Click', 'embed_view' => 'embed_view'])
        ->and($editor->get('eventDomainOptions'))->toBe(['blog.sonnenschein.test', 'sonnenschein.test']);

    $editor->assertSeeInOrder(['/heute', '/woche', '/monat', '/alt'])
        ->assertSee('Mozilla/5.0 Testbrowser')
        ->assertDontSee('/fremd')
        ->call('toggleEventDirection')
        ->assertSeeInOrder(['/alt', '/monat', '/woche', '/heute'])
        ->call('toggleEventDirection')
        ->set('eventSort', 'domain')
        ->call('toggleEventDirection')
        ->assertSeeInOrder(['/monat', '/heute']);

    $editor->set('eventPeriod', 'today')->assertSee('/heute')->assertDontSee('/woche');
    $editor->set('eventPeriod', 'week')->assertSee('/woche')->assertDontSee('/monat');
    $editor->set('eventPeriod', 'month')->assertSee('/monat')->assertDontSee('/alt');
    $editor->set('eventPeriod', '')->set('eventType', 'click')->assertSee('/woche')->assertDontSee('/heute');
    $editor->set('eventType', '')->set('eventDomain', 'blog.sonnenschein.test')->assertSee('/monat')->assertDontSee('/heute');
    $editor->set('eventDomain', '')->set('eventSearch', '/alt')->assertSee('Mozilla/5.0 Testbrowser')->assertDontSee('/heute');

    expect($editor->instance()->hasEventFilters())->toBeTrue();

    $editor->set('eventSearch', 'gibt-es-nicht')->assertSee('Zu Suche und Filtern passt kein Aufruf.')
        ->call('resetEventFilters')
        ->assertSet('eventSearch', '')
        ->assertSee('/heute');
});

it('blaettert durch die Aufrufe', function () {
    $this->actingAs(pluginClientsAdmin());

    $client = pluginClient();

    foreach (range(1, Editor::EVENTS_PER_PAGE + 1) as $minute) {
        pluginUsage($client, sprintf('2026-10-15 09:%02d:00', $minute), ['path' => '/seite-'.sprintf('%02d', $minute)]);
    }

    Livewire::test(Editor::class, ['pluginClient' => $client->id])
        ->assertSee('/seite-26')
        ->assertDontSee('/seite-01')
        ->call('nextPage')
        ->assertSee('/seite-01')
        ->assertDontSee('/seite-26')
        // Ein Filter fuehrt zurueck auf die erste Seite.
        ->set('eventSearch', 'seite')
        ->assertSee('/seite-26');
});

it('ist nur fuer Administratoren erreichbar', function () {
    $client = pluginClient();

    $this->get(route('adminv2.customer-management.plugin-clients.index'))->assertRedirect();

    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]));

    $this->get(route('adminv2.customer-management.plugin-clients.index'))->assertForbidden();
    $this->get(route('adminv2.customer-management.plugin-clients.create'))->assertForbidden();
    $this->get(route('adminv2.customer-management.plugin-clients.edit', $client->id))->assertForbidden();

    Livewire::test(Index::class)->assertForbidden();
    Livewire::test(Editor::class, ['pluginClient' => $client->id])->assertForbidden();

    expect(PluginClient::count())->toBe(1);
});
