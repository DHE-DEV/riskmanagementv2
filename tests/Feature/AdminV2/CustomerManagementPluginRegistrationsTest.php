<?php

use App\Livewire\AdminV2\CustomerManagement\PluginRegistrations\Index;
use App\Livewire\AdminV2\CustomerManagement\PluginRegistrations\Show;
use App\Models\PluginEmailVerification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Kundenverwaltung > Ausstehende Registrierungen: Liste mit Status-Filter,
 * Suche, Loeschen und Bereinigen sowie die Detailseite.
 */
function pluginRegistrationsAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function pluginRegistration(string $email, array $attributes = [], array $form = []): PluginEmailVerification
{
    $created = $attributes['created_at'] ?? now();
    unset($attributes['created_at']);

    $registration = PluginEmailVerification::create(array_merge([
        'token' => Str::random(64),
        'email' => $email,
        'code' => '123456',
        'form_data' => array_merge([
            'company_name' => 'Firma '.$email,
            'contact_name' => 'Kontakt '.$email,
            'domain' => Str::after($email, '@'),
        ], $form),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(10),
    ], $attributes));

    $registration->forceFill(['created_at' => $created])->save();

    return $registration;
}

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    Carbon::setTestNow('2026-10-15 12:00:00');
});

afterEach(fn () => Carbon::setTestNow());

it('zeigt die Registrierungen mit Status und filtert, sucht und sortiert', function () {
    $this->actingAs(pluginRegistrationsAdmin());

    $this->get(route('adminv2.customer-management.plugin-registrations.index'))
        ->assertOk()
        ->assertSee('Keine ausstehenden Registrierungen')
        ->assertSee('Alte Einträge bereinigen');

    pluginRegistration('offen@a.test', ['created_at' => '2026-10-15 11:55:00'], ['company_name' => 'Sonnenschein GmbH']);
    pluginRegistration('abgelaufen@b.test', ['expires_at' => '2026-10-15 10:00:00', 'attempts' => 2, 'created_at' => '2026-10-15 09:45:00']);
    pluginRegistration('gesperrt@c.test', ['attempts' => 5, 'created_at' => '2026-10-14 11:50:00']);
    pluginRegistration('fertig@d.test', ['verified_at' => '2026-10-14 09:00:00', 'expires_at' => '2026-10-14 09:10:00', 'created_at' => '2026-10-14 08:55:00']);

    $list = Livewire::test(Index::class);

    expect($list->get('pendingCount'))->toBe(1);

    // Vorgabe: alles, was noch nicht verifiziert ist – neueste zuerst.
    $list->assertSeeInOrder(['offen@a.test', 'abgelaufen@b.test', 'gesperrt@c.test'])
        ->assertDontSee('fertig@d.test')
        ->assertSee('Sonnenschein GmbH')
        ->assertSee('Noch 10 Minuten')
        ->assertSee('Zu viele Versuche')
        ->assertSee('2 Fehlversuche');

    expect($list->instance()->hasFilters())->toBeFalse();

    $list->set('status', 'pending')->assertSee('offen@a.test')->assertDontSee('abgelaufen@b.test')->assertDontSee('gesperrt@c.test');
    $list->set('status', 'expired')->assertSee('abgelaufen@b.test')->assertDontSee('offen@a.test');
    $list->set('status', 'exceeded')->assertSee('gesperrt@c.test')->assertDontSee('offen@a.test');
    $list->set('status', 'verified')->assertSee('fertig@d.test')->assertDontSee('offen@a.test');
    $list->set('status', 'all')->assertSee('fertig@d.test')->assertSee('offen@a.test');

    $list->set('created', 'today')->assertSee('offen@a.test')->assertSee('abgelaufen@b.test')->assertDontSee('gesperrt@c.test')->assertDontSee('fertig@d.test');

    expect($list->instance()->hasFilters())->toBeTrue();

    $list->call('resetFilters')->assertSet('status', 'open')->assertSet('created', '');

    // Suche: E-Mail und die (verschluesselt gespeicherten) Angaben des Formulars.
    $list->set('search', 'gesperrt')->assertSee('gesperrt@c.test')->assertDontSee('offen@a.test');
    $list->set('search', 'sonnenschein')->assertSee('offen@a.test')->assertDontSee('gesperrt@c.test');
    $list->set('search', 'b.test')->assertSee('abgelaufen@b.test')->assertDontSee('offen@a.test');

    $list->set('search', '')->set('sort', 'email')->call('toggleDirection')
        ->assertSeeInOrder(['abgelaufen@b.test', 'gesperrt@c.test', 'offen@a.test']);

    $list->set('sort', 'expires_at')
        ->assertSeeInOrder(['abgelaufen@b.test', 'offen@a.test']);
});

it('loescht einzeln und mehrere und bereinigt alte Eintraege', function () {
    $this->actingAs(pluginRegistrationsAdmin());

    $open = pluginRegistration('offen@a.test');
    // Seit ueber 24 Stunden abgelaufen bzw. verifiziert: wird bereinigt.
    pluginRegistration('alt@b.test', ['expires_at' => '2026-10-14 11:00:00']);
    pluginRegistration('alt-fertig@c.test', ['verified_at' => '2026-10-13 09:00:00', 'expires_at' => '2026-10-13 09:10:00']);
    // Erst vor kurzem abgelaufen bzw. verifiziert: bleibt.
    $recent = pluginRegistration('frisch@d.test', ['expires_at' => '2026-10-15 09:00:00']);
    $verified = pluginRegistration('frisch-fertig@e.test', ['verified_at' => '2026-10-15 09:00:00', 'expires_at' => '2026-10-15 09:10:00']);

    $list = Livewire::test(Index::class)->call('cleanup');

    expect(PluginEmailVerification::orderBy('id')->pluck('email')->all())
        ->toBe(['offen@a.test', 'frisch@d.test', 'frisch-fertig@e.test']);

    $list->call('delete', $recent->id)->assertDontSee('frisch@d.test');

    expect(PluginEmailVerification::count())->toBe(2);

    $list->set('status', 'all')
        ->call('selectPage')
        ->assertSet('selected', [(string) $verified->id, (string) $open->id])
        ->call('clearSelection')
        ->set('selected', [(string) $open->id, (string) $verified->id])
        ->call('deleteSelected')
        ->assertSet('selected', []);

    expect(PluginEmailVerification::count())->toBe(0);
});

it('zeigt eine Registrierung im Detail und loescht sie', function () {
    $this->actingAs(pluginRegistrationsAdmin());

    $registration = pluginRegistration('offen@a.test', ['attempts' => 3, 'created_at' => '2026-10-15 11:55:00'], [
        'company_name' => 'Sonnenschein GmbH',
        'contact_name' => 'Petra Sonne',
        'domain' => 'sonnenschein.test',
        'company_street' => 'Hauptstraße',
        'company_house_number' => '12a',
        'company_postal_code' => '50667',
        'company_city' => 'Köln',
        'company_country' => 'Deutschland',
    ]);

    $this->get(route('adminv2.customer-management.plugin-registrations.show', $registration->id))
        ->assertOk()
        ->assertSee('offen@a.test')
        ->assertSee('Ausstehend')
        ->assertSee('Sonnenschein GmbH')
        ->assertSee('Petra Sonne')
        ->assertSee('sonnenschein.test')
        ->assertSee('Hauptstraße')
        ->assertSee('50667')
        ->assertSee('Köln')
        ->assertSee('Deutschland')
        ->assertSee('15.10.2026 11:55')
        ->assertSee('15.10.2026 12:10')
        ->assertSee('Noch nicht verifiziert')
        // Der Bestaetigungscode gehoert nicht auf die Seite.
        ->assertDontSee('123456');

    Livewire::test(Show::class, ['registration' => $registration->id])
        ->call('delete')
        ->assertRedirect(route('adminv2.customer-management.plugin-registrations.index'));

    expect(PluginEmailVerification::count())->toBe(0);

    $this->get(route('adminv2.customer-management.plugin-registrations.show', $registration->id))->assertNotFound();
});

it('zeigt Liste und Detail auch dann, wenn sich die Angaben eines Eintrags nicht entschluesseln lassen', function () {
    $this->actingAs(pluginRegistrationsAdmin());

    pluginRegistration('lesbar@a.test', [], ['company_name' => 'Sonnenschein GmbH']);
    $broken = pluginRegistration('kaputt@b.test');

    // Wie nach einem Wechsel des App-Schluessels: der gespeicherte Wert passt nicht mehr.
    DB::table('plugin_email_verifications')->where('id', $broken->id)->update(['form_data' => 'mit-anderem-schluessel-gespeichert']);

    $this->get(route('adminv2.customer-management.plugin-registrations.index'))
        ->assertOk()
        ->assertSee('kaputt@b.test')
        ->assertSee('Angaben des Formulars nicht lesbar')
        ->assertSee('Sonnenschein GmbH');

    // Auch die Suche in den Angaben uebergeht den Eintrag, statt abzubrechen.
    Livewire::test(Index::class)
        ->set('search', 'sonnenschein')
        ->assertSee('lesbar@a.test')
        ->assertDontSee('kaputt@b.test')
        ->set('search', 'kaputt')
        ->assertSee('kaputt@b.test');

    $this->get(route('adminv2.customer-management.plugin-registrations.show', $broken->id))
        ->assertOk()
        ->assertSee('kaputt@b.test')
        ->assertSee('lassen sich nicht entschlüsseln');

    // Loeschen geht weiterhin.
    Livewire::test(Show::class, ['registration' => $broken->id])->call('delete');

    expect(PluginEmailVerification::count())->toBe(1);
});

it('ist nur fuer Administratoren erreichbar', function () {
    $registration = pluginRegistration('offen@a.test');

    $this->get(route('adminv2.customer-management.plugin-registrations.index'))->assertRedirect();

    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]));

    $this->get(route('adminv2.customer-management.plugin-registrations.index'))->assertForbidden();
    $this->get(route('adminv2.customer-management.plugin-registrations.show', $registration->id))->assertForbidden();

    Livewire::test(Index::class)->assertForbidden();
    Livewire::test(Show::class, ['registration' => $registration->id])->assertForbidden();

    expect(PluginEmailVerification::count())->toBe(1);
});
