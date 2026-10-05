<?php

use App\Livewire\AdminV2\CustomerManagement\ApiClients\Editor;
use App\Livewire\AdminV2\CustomerManagement\ApiClients\Index;
use App\Models\ApiClient;
use App\Models\ApiClientRequestLog;
use App\Models\CustomEvent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Kundenverwaltung > API-Kunden: Liste, Formular, API-Tokens und die
 * Freigabe der per API angelegten Events.
 */
function apiClientAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function apiClient(array $attributes = []): ApiClient
{
    // forceFill, damit sich auch created_at vorgeben laesst.
    $client = (new ApiClient)->forceFill(array_merge([
        'name' => 'Reisewelt API',
        'company_name' => 'Reisewelt GmbH',
        'contact_email' => 'api@reisewelt.test',
    ], $attributes));
    $client->save();

    return $client;
}

function apiClientEvent(ApiClient $client, array $attributes = []): CustomEvent
{
    return CustomEvent::factory()->create(array_merge([
        'api_client_id' => $client->id,
        'priority' => 'high',
        'review_status' => 'pending_review',
        'is_active' => false,
    ], $attributes));
}

it('zeigt die Liste mit Suche, Statusfilter und Sortierung', function () {
    $this->actingAs(apiClientAdmin());

    $this->get(route('adminv2.customer-management.api-clients.index'))->assertOk()->assertSee('Keine API-Kunden gefunden');

    $first = apiClient(['name' => 'Alpha Zugang', 'company_name' => 'Zebra Reisen', 'created_at' => now()->subDays(2)]);
    apiClient(['name' => 'Beta Zugang', 'company_name' => 'Adler Touristik', 'status' => 'suspended', 'created_at' => now()->subDay()]);
    apiClientEvent($first);

    $this->get(route('adminv2.customer-management.api-clients.index'))
        ->assertOk()
        ->assertSee('Neuer API-Kunde')
        // Vorgabe: neueste zuerst.
        ->assertSeeInOrder(['Beta Zugang', 'Alpha Zugang'])
        ->assertSee('Gesperrt')
        ->assertSee('1 Event');

    Livewire::test(Index::class)
        ->set('search', 'Zebra')
        ->assertSee('Alpha Zugang')
        ->assertDontSee('Beta Zugang')
        ->assertSet('selected', [])
        ->call('resetFilters')
        ->set('status', 'suspended')
        ->assertSee('Beta Zugang')
        ->assertDontSee('Alpha Zugang')
        ->call('resetFilters')
        ->assertSet('status', '')
        ->set('sort', 'company_name')
        ->call('toggleDirection')
        ->assertSet('direction', 'asc')
        ->assertSeeInOrder(['Beta Zugang', 'Alpha Zugang'])
        ->call('toggleDirection')
        ->assertSeeInOrder(['Alpha Zugang', 'Beta Zugang']);
});

it('loescht API-Kunden einzeln und mehrere auf einmal', function () {
    $this->actingAs(apiClientAdmin());

    $one = apiClient(['name' => 'Eins']);
    $two = apiClient(['name' => 'Zwei']);
    $three = apiClient(['name' => 'Drei']);

    Livewire::test(Index::class)
        ->call('delete', $one->id)
        ->assertDispatched('adminv2-toast')
        ->assertDontSee('Eins')
        ->call('selectPage')
        ->assertCount('selected', 2)
        ->call('clearSelection')
        ->set('selected', [(string) $two->id])
        ->call('deleteSelected')
        ->assertSet('selected', [])
        ->assertDontSee('Zwei')
        ->assertSee('Drei');

    // Geloescht heisst: im Papierkorb, nicht aus der Datenbank entfernt.
    expect(ApiClient::count())->toBe(1)
        ->and(ApiClient::withTrashed()->count())->toBe(3)
        ->and($three->fresh()->trashed())->toBeFalse();
});

it('legt einen API-Kunden an und prueft die Eingaben', function () {
    $this->actingAs(apiClientAdmin());

    $this->get(route('adminv2.customer-management.api-clients.create'))->assertOk()->assertSee('Neuer API-Kunde');

    Livewire::test(Editor::class)
        ->assertSet('status', 'active')
        ->assertSet('rateLimit', '60')
        ->set('contactEmail', 'keine-adresse')
        ->set('rateLimit', '5000')
        ->set('status', 'unbekannt')
        ->set('description', str_repeat('a', 1001))
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'companyName' => 'required', 'contactEmail' => 'email', 'rateLimit' => 'max', 'status' => 'in', 'description' => 'max'])
        ->set('name', 'Reisewelt API')
        ->set('companyName', 'Reisewelt GmbH')
        ->set('contactEmail', 'api@reisewelt.test')
        ->set('description', 'Liefert eigene Ereignisse.')
        ->set('status', 'inactive')
        ->set('rateLimit', '120')
        ->set('canCreateEvents', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.customer-management.api-clients.edit', ApiClient::firstOrFail()->id));

    $client = ApiClient::firstOrFail();

    expect($client->name)->toBe('Reisewelt API')
        ->and($client->company_name)->toBe('Reisewelt GmbH')
        ->and($client->contact_email)->toBe('api@reisewelt.test')
        ->and($client->description)->toBe('Liefert eigene Ereignisse.')
        ->and($client->status)->toBe('inactive')
        ->and($client->rate_limit)->toBe(120)
        ->and($client->can_create_events)->toBeTrue()
        ->and($client->auto_approve_events)->toBeFalse()
        ->and($client->logo_path)->toBeNull();
});

it('bearbeitet einen API-Kunden und zeigt seine Kennzahlen', function () {
    $this->actingAs(apiClientAdmin());

    $client = apiClient(['description' => 'Alt']);
    apiClientEvent($client, ['title' => 'Streik am Flughafen']);
    ApiClientRequestLog::create(['api_client_id' => $client->id, 'method' => 'GET', 'endpoint' => '/api/v1/events', 'response_status' => 200, 'created_at' => now()->subDays(3)]);
    ApiClientRequestLog::create(['api_client_id' => $client->id, 'method' => 'GET', 'endpoint' => '/api/v1/events', 'response_status' => 200, 'created_at' => now()->subDays(40)]);

    $this->get(route('adminv2.customer-management.api-clients.edit', $client->id))
        ->assertOk()
        ->assertSee('Reisewelt GmbH')
        ->assertSee('API-Requests (30 Tage)')
        ->assertSee('Streik am Flughafen')
        ->assertSee('API-Token generieren');

    Livewire::test(Editor::class, ['apiClient' => $client->id])
        ->assertSet('name', 'Reisewelt API')
        ->assertSet('description', 'Alt')
        ->assertSet('stats', ['events' => 1, 'requests' => 1, 'tokens' => 0])
        ->set('name', 'Reisewelt Schnittstelle')
        ->set('description', '')
        ->set('autoApproveEvents', true)
        ->set('status', 'suspended')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertDispatched('adminv2-toast', message: 'Gespeichert.');

    $client->refresh();

    expect($client->name)->toBe('Reisewelt Schnittstelle')
        ->and($client->description)->toBeNull()
        ->and($client->auto_approve_events)->toBeTrue()
        ->and($client->status)->toBe('suspended');

    // Geloeschte API-Kunden lassen sich nicht mehr oeffnen.
    $client->delete();
    $this->get(route('adminv2.customer-management.api-clients.edit', $client->id))->assertNotFound();
});

it('speichert, ersetzt und entfernt das Logo', function () {
    Storage::fake('public');
    $this->actingAs(apiClientAdmin());

    $client = apiClient();

    Livewire::test(Editor::class, ['apiClient' => $client->id])
        ->set('logo', UploadedFile::fake()->create('vertrag.pdf', 100, 'application/pdf'))
        ->assertHasErrors('logo')
        // Die ungueltige Datei bleibt nicht haengen – Speichern geht weiterhin.
        ->assertSet('logo', null)
        ->call('save')
        ->assertHasNoErrors()
        ->set('logo', UploadedFile::fake()->image('zu-gross.png')->size(3000))
        ->assertHasErrors('logo')
        ->set('logo', UploadedFile::fake()->image('logo.png', 200, 80))
        ->assertHasNoErrors()
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('logo', null);

    $firstLogo = $client->refresh()->logo_path;

    expect($firstLogo)->toStartWith('api-client-logos/');
    Storage::disk('public')->assertExists($firstLogo);

    // Ein neues Logo ersetzt das alte, die alte Datei verschwindet.
    Livewire::test(Editor::class, ['apiClient' => $client->id])
        ->set('logo', UploadedFile::fake()->image('neu.jpg', 200, 80))
        ->call('save')
        ->assertHasNoErrors();

    $secondLogo = $client->refresh()->logo_path;

    expect($secondLogo)->not->toBe($firstLogo);
    Storage::disk('public')->assertMissing($firstLogo);
    Storage::disk('public')->assertExists($secondLogo);

    Livewire::test(Editor::class, ['apiClient' => $client->id])
        ->set('removeLogo', true)
        ->call('save')
        ->assertSet('removeLogo', false);

    expect($client->refresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($secondLogo);
});

it('erzeugt einen API-Token, zeigt ihn einmalig und widerruft alle Tokens', function () {
    $this->actingAs(apiClientAdmin());

    $client = apiClient();

    $editor = Livewire::test(Editor::class, ['apiClient' => $client->id])
        ->assertSet('newToken', null)
        ->call('generateToken')
        ->assertDispatched('adminv2-toast')
        ->assertSee('Er wird nicht erneut angezeigt.');

    $token = $client->tokens()->sole();
    $plain = $editor->get('newToken');

    expect($token->name)->toBe('api-token')
        ->and($token->abilities)->toBe(['events:write'])
        ->and($token->expires_at->isSameDay(now()->addYear()))->toBeTrue()
        // Der angezeigte Klartext passt zum gespeicherten Token.
        ->and(hash('sha256', explode('|', $plain, 2)[1]))->toBe($token->token);

    $editor->assertSee($plain)
        ->call('dismissToken')
        ->assertSet('newToken', null)
        ->assertDontSee($plain)
        ->call('generateToken')
        ->call('revokeTokens')
        ->assertSet('newToken', null)
        ->assertDispatched('adminv2-toast', message: '2 Token(s) wurden widerrufen.');

    expect($client->tokens()->count())->toBe(0);

    // Auf der Seite "neu" gibt es noch keinen Kunden fuer einen Token.
    Livewire::test(Editor::class)->call('generateToken')->assertNotFound();
});

it('listet die Events des API-Kunden und gibt sie frei oder lehnt sie ab', function () {
    $admin = apiClientAdmin();
    $this->actingAs($admin);

    $client = apiClient();
    $other = apiClient(['name' => 'Anderer Kunde']);

    $pending = apiClientEvent($client, ['title' => 'Streik am Flughafen', 'created_at' => now()->subDays(2)]);
    $second = apiClientEvent($client, ['title' => 'Unwetter in der Region', 'created_at' => now()->subDay()]);
    $approved = apiClientEvent($client, ['title' => 'Bereits freigegeben', 'review_status' => 'approved', 'is_active' => true, 'created_at' => now()->subDays(3)]);
    $foreign = apiClientEvent($other, ['title' => 'Fremdes Event']);

    $editor = Livewire::test(Editor::class, ['apiClient' => $client->id])
        // Vorgabe: neueste zuerst; fremde Events fehlen.
        ->assertSeeInOrder(['Unwetter in der Region', 'Streik am Flughafen', 'Bereits freigegeben'])
        ->assertDontSee('Fremdes Event')
        ->assertSeeHtml(route('adminv2.events.edit', $pending->id))
        ->call('sortEvents', 'title')
        ->assertSeeInOrder(['Bereits freigegeben', 'Streik am Flughafen', 'Unwetter in der Region'])
        ->call('sortEvents', 'unbekannt')
        ->assertSet('eventSort', 'title')
        ->set('eventSearch', 'Streik')
        ->assertSee('Streik am Flughafen')
        ->assertDontSee('Unwetter in der Region')
        ->set('eventSearch', '')
        ->set('eventReviewStatus', 'approved')
        ->assertSee('Bereits freigegeben')
        ->assertDontSee('Streik am Flughafen')
        ->set('eventReviewStatus', '');

    $editor->call('approveEvent', $pending->id)->assertDispatched('adminv2-toast', message: 'Event freigegeben.');

    expect($pending->refresh()->review_status)->toBe('approved')
        ->and($pending->is_active)->toBeTrue()
        ->and($pending->reviewed_by)->toBe($admin->id)
        ->and($pending->reviewed_at)->not->toBeNull();

    $editor->call('rejectEvent', $second->id)->assertDispatched('adminv2-toast', message: 'Event abgelehnt.');

    expect($second->refresh()->review_status)->toBe('rejected')
        ->and($second->is_active)->toBeFalse()
        ->and($second->reviewed_by)->toBe($admin->id);

    // Bereits gepruefte Events bleiben, wie sie sind.
    $editor->call('rejectEvent', $approved->id);
    expect($approved->refresh()->review_status)->toBe('approved');

    // Events anderer API-Kunden lassen sich von hier aus nicht freigeben.
    expect(fn () => $editor->call('approveEvent', $foreign->id))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect($foreign->refresh()->review_status)->toBe('pending_review');
});

it('loescht den API-Kunden von seiner Seite aus', function () {
    $this->actingAs(apiClientAdmin());

    $client = apiClient();

    Livewire::test(Editor::class, ['apiClient' => $client->id])
        ->call('delete')
        ->assertRedirect(route('adminv2.customer-management.api-clients.index'));

    expect($client->fresh()->trashed())->toBeTrue();
});

it('laesst nur Admins auf die API-Kunden', function () {
    $client = apiClient();

    $this->get(route('adminv2.customer-management.api-clients.index'))->assertRedirect();

    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]));

    $this->get(route('adminv2.customer-management.api-clients.index'))->assertForbidden();
    $this->get(route('adminv2.customer-management.api-clients.create'))->assertForbidden();
    $this->get(route('adminv2.customer-management.api-clients.edit', $client->id))->assertForbidden();

    Livewire::test(Index::class)->assertForbidden();
    Livewire::test(Editor::class, ['apiClient' => $client->id])->assertForbidden();
});
