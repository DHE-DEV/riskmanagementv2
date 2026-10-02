<?php

use App\Livewire\AdminV2\Auth\Login;
use App\Livewire\AdminV2\Events\Editor;
use App\Livewire\AdminV2\Events\Index;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Models\User;
use App\Support\AdminV2\EventState;
use Livewire\Livewire;

/**
 * Neuer Admin-Bereich (/adminv2): Zugang, Liste und der Weg eines
 * Ereignisses vom Entwurf zur veroeffentlichten Version.
 */
function adminUser(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function eventType(string $code): EventType
{
    return EventType::create([
        'code' => $code,
        'name' => ucfirst($code),
        'icon' => 'fa-shield-alt',
        'color' => '#3B82F6',
        'is_active' => true,
        'sort_order' => 1,
    ]);
}

function storedEvent(array $attributes = []): CustomEvent
{
    return CustomEvent::create(array_merge([
        'title' => 'Streik in Rom',
        'popup_content' => '<p>Der Nahverkehr steht still.</p>',
        'event_type' => 'other',
        'priority' => 'medium',
        'start_date' => now()->subDay(),
        'is_active' => true,
        'archived' => false,
        'review_status' => 'approved',
    ], $attributes));
}

it('leitet ohne Anmeldung zum Login und sperrt Nicht-Administratoren aus', function () {
    $this->get('/adminv2')->assertRedirect(route('adminv2.login'));
    $this->get('/adminv2/events')->assertRedirect(route('adminv2.login'));

    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->get('/adminv2')
        ->assertForbidden();
});

it('zeigt fuer jeden Stammdaten-Bereich den Hinweis, dass daran gearbeitet wird', function () {
    $this->actingAs(adminUser());

    foreach (\App\Support\AdminV2\MasterData::sections() as $key => $section) {
        $this->get("/adminv2/master-data/{$key}")
            ->assertOk()
            ->assertSee($section['label'])
            ->assertSee('An dieser Seite wird aktuell gearbeitet');
    }

    $this->get('/adminv2/master-data/gibt-es-nicht')->assertNotFound();
});

it('meldet nur aktive Administratoren an', function () {
    $admin = adminUser();
    User::factory()->create(['email' => 'kunde@example.com', 'is_admin' => false]);

    Livewire::test(Login::class)
        ->set('email', 'kunde@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth('web')->check())->toBeFalse();

    Livewire::test(Login::class)
        ->set('email', $admin->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('adminv2.dashboard', absolute: false));

    expect(auth('web')->id())->toBe($admin->id);
});

it('zeigt Willkommensseite, Uebersicht, Liste und Formular fuer Administratoren', function () {
    $event = storedEvent();

    $this->actingAs(adminUser());

    $this->get('/adminv2')->assertOk()->assertSee('Willkommen im Admin-Bereich');
    $this->get('/adminv2/events/overview')->assertOk()->assertSee('Zuletzt bearbeitet')->assertSee('Streik in Rom');
    $this->get('/adminv2/events')->assertOk()->assertSee('Streik in Rom');
    $this->get('/adminv2/events/create')->assertOk()->assertSee('Neues Ereignis');
    $this->get("/adminv2/events/{$event->id}")->assertOk()->assertSee('Streik in Rom');
});

it('ordnet Ereignisse in der Liste ihrem Zustand zu', function () {
    storedEvent(['title' => 'Laeuft gerade']);
    storedEvent(['title' => 'Noch Entwurf', 'is_active' => false]);
    storedEvent(['title' => 'Schon vorbei', 'start_date' => now()->subDays(10), 'end_date' => now()->subDays(3)]);
    storedEvent(['title' => 'Kommt noch', 'start_date' => now()->addDays(5)]);

    $this->actingAs(adminUser());

    Livewire::test(Index::class)
        ->assertSee('Laeuft gerade')
        ->assertDontSee('Noch Entwurf')
        ->assertDontSee('Schon vorbei')
        // "Aktiv" fasst Live und Geplant zusammen.
        ->set('tab', 'active')
        ->assertSee('Laeuft gerade')
        ->assertSee('Kommt noch')
        ->assertDontSee('Noch Entwurf')
        ->assertDontSee('Schon vorbei')
        ->set('tab', EventState::Draft->value)
        ->assertSee('Noch Entwurf')
        ->assertDontSee('Laeuft gerade')
        ->set('tab', EventState::Expired->value)
        ->assertSee('Schon vorbei')
        ->set('tab', EventState::Scheduled->value)
        ->assertSee('Kommt noch')
        ->set('tab', 'all')
        ->set('search', 'entwurf')
        ->assertSee('Noch Entwurf')
        ->assertDontSee('Kommt noch');
});

it('filtert nach mehreren Prioritaeten, Typen und Laendern zugleich', function () {
    $strike = eventType('strike');
    $health = eventType('health');
    $safety = eventType('safety');
    $italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);
    $spain = Country::factory()->create(['iso_code' => 'ES', 'iso3_code' => 'ESP']);
    $japan = Country::factory()->create(['iso_code' => 'JP', 'iso3_code' => 'JPN']);

    $make = function (string $title, string $priority, $type, $country) {
        $event = storedEvent(['title' => $title, 'priority' => $priority]);
        $event->eventTypes()->attach($type->id);
        $event->countries()->attach($country->id, ['use_default_coordinates' => true]);
    };

    $make('Streik Italien hoch', 'high', $strike, $italy);
    $make('Gesundheit Spanien mittel', 'medium', $health, $spain);
    $make('Sicherheit Japan hoch', 'high', $safety, $japan);
    $make('Streik Spanien niedrig', 'low', $strike, $spain);

    $this->actingAs(adminUser());

    Livewire::test(Index::class)
        // Mehrere Werte eines Filters: einer davon genuegt.
        ->set('priorities', ['high', 'medium'])
        ->assertSee('Streik Italien hoch')
        ->assertSee('Gesundheit Spanien mittel')
        ->assertSee('Sicherheit Japan hoch')
        ->assertDontSee('Streik Spanien niedrig')
        // Weitere Filter grenzen zusaetzlich ein.
        ->set('countryIds', [(string) $italy->id, (string) $spain->id])
        ->assertDontSee('Sicherheit Japan hoch')
        ->set('types', [(string) $strike->id])
        ->assertSee('Streik Italien hoch')
        ->assertDontSee('Gesundheit Spanien mittel')
        // Jeder aktive Filter steht als Badge da und laesst sich einzeln entfernen.
        ->assertSee('Filter Priorität Hoch entfernen')
        ->assertSee('Filter Priorität Mittel entfernen')
        ->assertSee('Filter Typ Strike entfernen')
        ->assertSee('Filter Land '.$italy->getName('de').' entfernen')
        ->call('removeFilter', 'types', (string) $strike->id)
        ->assertSet('types', [])
        ->assertSee('Gesundheit Spanien mittel')
        ->call('removeFilter', 'priorities', 'medium')
        ->assertSet('priorities', ['high'])
        ->assertDontSee('Gesundheit Spanien mittel')
        ->call('resetFilters')
        ->assertSee('Streik Spanien niedrig')
        ->assertSee('Sicherheit Japan hoch');
});

it('findet ueber das Zeitfenster alle Ereignisse, die sich damit ueberschneiden', function () {
    storedEvent(['title' => 'Vor dem Fenster', 'start_date' => '2026-10-01 00:00:00', 'end_date' => '2026-10-04 23:59:00']);
    storedEvent(['title' => 'Ragt hinein', 'start_date' => '2026-10-03 00:00:00', 'end_date' => '2026-10-05 12:00:00']);
    storedEvent(['title' => 'Liegt mittendrin', 'start_date' => '2026-10-07 00:00:00', 'end_date' => '2026-10-08 00:01:00']);
    storedEvent(['title' => 'Beginnt am letzten Tag', 'start_date' => '2026-10-10 18:00:00', 'end_date' => '2026-10-20 00:01:00']);
    storedEvent(['title' => 'Ohne Ende', 'start_date' => '2026-09-01 00:00:00', 'end_date' => null]);
    storedEvent(['title' => 'Nach dem Fenster', 'start_date' => '2026-10-11 00:00:00', 'end_date' => '2026-10-12 00:01:00']);

    $this->actingAs(adminUser());

    $list = Livewire::test(Index::class)
        ->set('tab', 'all')
        ->set('periodFrom', '2026-10-05')
        ->set('periodTo', '2026-10-10')
        ->assertSee('Ragt hinein')
        ->assertSee('Liegt mittendrin')
        ->assertSee('Beginnt am letzten Tag')
        ->assertSee('Ohne Ende')
        ->assertDontSee('Vor dem Fenster')
        ->assertDontSee('Nach dem Fenster');

    // Die Zahlen an den Reitern folgen dem Filter.
    expect($list->instance()->tabCounts['all'])->toBe(4);
});

it('speichert ein neues Ereignis als Entwurf und veroeffentlicht es erst mit Standort', function () {
    $type = eventType('safety');
    $italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);

    $this->actingAs($admin = adminUser());

    $editor = Livewire::test(Editor::class)
        // Die erste Quellen-Zeile steht bei einem neuen Ereignis schon bereit.
        ->assertCount('sources', 1)
        ->set('titles.de', 'Unwetter in Norditalien')
        ->set('contents.de', '<p>Starkregen <script>alert(1)</script></p>')
        ->set('eventTypeIds', [(string) $type->id])
        ->set('priority', 'high')
        ->set('startDate', '2026-10-09T08:00')
        ->set('endDate', '2026-10-12T00:00')
        ->call('save');

    $event = CustomEvent::firstWhere('title', 'Unwetter in Norditalien');

    // Entwurf: gespeichert, aber nicht ausgeliefert.
    expect($event)->not->toBeNull()
        ->and($event->is_active)->toBeFalse()
        ->and($event->activated_at)->toBeNull()
        ->and(EventState::of($event))->toBe(EventState::Draft)
        ->and($event->created_by)->toBe($admin->id)
        ->and($event->getPopupContent('de'))->not->toContain('script')
        ->and($event->end_date->format('H:i'))->toBe('00:01')
        // Die leer gebliebene Quellen-Zeile wird nicht gespeichert.
        ->and($event->normalizedSourceLinks())->toBe([])
        ->and($event->eventTypes()->pluck('event_types.id')->all())->toBe([$type->id]);

    // Ohne Standort laesst sich nicht veroeffentlichen.
    Livewire::test(Editor::class, ['event' => $event->id])
        ->call('publish')
        ->assertHasErrors('locations');

    expect($event->fresh()->is_active)->toBeFalse();

    Livewire::test(Editor::class, ['event' => $event->id])
        ->call('addLocation', 'country', $italy->id)
        ->assertSet('locations.0.country_id', $italy->id)
        ->call('publish')
        ->assertHasNoErrors();

    $event = $event->fresh();

    expect($event->is_active)->toBeTrue()
        ->and($event->activated_at)->not->toBeNull()
        ->and(EventState::of($event))->toBe(EventState::Scheduled)
        ->and($event->countries()->pluck('countries.id')->all())->toBe([$italy->id]);
});

it('aendert ein veroeffentlichtes Ereignis still und loest es erst ueber eine neue Version ab', function () {
    $type = eventType('travel');
    $italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);

    $event = storedEvent();
    $event->eventTypes()->attach($type->id);
    $event->countries()->attach($italy->id, ['use_default_coordinates' => true]);
    $activatedAt = $event->fresh()->activated_at;

    $this->actingAs(adminUser());

    // Korrektur: bleibt dieselbe Version und wird nicht erneut "aktiviert".
    Livewire::test(Editor::class, ['event' => $event->id])
        ->assertSet('titles.de', 'Streik in Rom')
        ->set('titles.de', 'Streik in Rom und Mailand')
        ->call('save')
        ->assertHasNoErrors();

    $event = $event->fresh();

    expect($event->getTitle('de'))->toBe('Streik in Rom und Mailand')
        ->and($event->version)->toBe(1)
        ->and($event->is_active)->toBeTrue()
        ->and($event->activated_at->equalTo($activatedAt))->toBeTrue()
        // Der unveraenderte Text wird nicht umgeschrieben.
        ->and($event->getPopupContent('de'))->toBe('<p>Der Nahverkehr steht still.</p>');

    // Inhaltliche Aenderung: neue Version als Entwurf, das Original bleibt live.
    Livewire::test(Editor::class, ['event' => $event->id])
        ->set('newVersionNote', 'Mailand ergänzt')
        ->set('newVersionInternalNote', 'Hinweis kam telefonisch vom Kunden')
        ->call('createVersion');

    $draft = CustomEvent::where('version_group_uuid', $event->version_group_uuid)->where('version', 2)->first();

    expect($draft)->not->toBeNull()
        ->and($draft->is_active)->toBeFalse()
        ->and($draft->version_note)->toBe('Mailand ergänzt')
        ->and($draft->version_internal_note)->toBe('Hinweis kam telefonisch vom Kunden')
        // Die interne Notiz geht in keiner Ausgabe nach aussen.
        ->and($draft->toArray())->not->toHaveKey('version_internal_note')
        ->and($event->fresh()->is_active)->toBeTrue();

    Livewire::test(Editor::class, ['event' => $draft->id])
        ->call('publish')
        ->assertHasNoErrors();

    expect($draft->fresh()->is_active)->toBeTrue()
        ->and(EventState::of($event->fresh()))->toBe(EventState::Superseded);

    // Die interne Notiz laesst sich je Version nachtragen und steht in der Historie.
    Livewire::test(Editor::class, ['event' => $draft->id])
        ->assertSet('versionInternalNote', 'Hinweis kam telefonisch vom Kunden')
        ->assertSee('Intern:')
        ->set('versionInternalNote', 'Mit dem Kunden abgestimmt')
        ->call('save')
        ->assertHasNoErrors();

    expect($draft->fresh()->version_internal_note)->toBe('Mit dem Kunden abgestimmt')
        ->and($draft->fresh()->version_note)->toBe('Mailand ergänzt');

    // Eine weitere Version beginnt ohne die interne Notiz der vorigen.
    $third = app(\App\Services\CustomEventVersionService::class)->createNewVersion($draft->fresh());

    expect($third->version_internal_note)->toBeNull();
});

it('verlangt eigene Koordinaten in lesbarer Form', function () {
    $type = eventType('health');
    $italy = Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);

    $this->actingAs(adminUser());

    $editor = Livewire::test(Editor::class)
        ->set('titles.de', 'Hitzewelle')
        ->set('eventTypeIds', [(string) $type->id])
        ->set('startDate', '2026-10-09T08:00')
        ->call('addLocation', 'country', $italy->id)
        ->set('locations.0.use_default_coordinates', false)
        ->set('locations.0.coordinates', 'irgendwo')
        ->call('save')
        ->assertHasErrors('locations.0.coordinates');

    $editor->set('locations.0.coordinates', '45.4642, 9.1900')
        ->call('save')
        ->assertHasNoErrors();

    $pivot = CustomEvent::firstWhere('title', 'Hitzewelle')->countries()->first()->pivot;

    expect((float) $pivot->latitude)->toBe(45.4642)
        ->and((float) $pivot->longitude)->toBe(9.19)
        ->and((bool) $pivot->use_default_coordinates)->toBeFalse();
});

it('zeigt im Reiter "Heute angelegt" nur die heute erfassten Ereignisse', function () {
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 10:00:00');

    $this->actingAs(adminUser());

    $make = fn (string $title, array $attributes = []) => CustomEvent::create($attributes + [
        'title' => $title, 'popup_content' => 'Text', 'event_type' => 'other', 'priority' => 'medium',
        'start_date' => now()->subDay(), 'is_active' => true, 'archived' => false, 'review_status' => 'approved',
    ]);

    $make('Heute live');
    $make('Heute als Entwurf', ['is_active' => false]);

    $old = $make('Von gestern');
    $old->forceFill(['created_at' => now()->subDay()])->saveQuietly();

    $list = \Livewire\Livewire::test(\App\Livewire\AdminV2\Events\Index::class)
        ->assertSee('Heute angelegt')
        ->set('tab', 'today')
        ->assertSee('Heute live')
        ->assertSee('Heute als Entwurf')
        ->assertDontSee('Von gestern');

    expect($list->instance()->tabCounts['today'])->toBe(2);

    \Illuminate\Support\Carbon::setTestNow();
});
