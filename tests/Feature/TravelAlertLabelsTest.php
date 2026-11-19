<?php

use App\Models\Customer;
use App\Models\CustomEvent;
use App\Models\Label;
use Illuminate\Support\Facades\DB;

/**
 * Travel Alert: Labels anlegen, umbenennen, umfärben, löschen und vergeben –
 * je Kunde getrennt, mit Farben aus der Palette und frischem Cache für Label-Filter.
 */
function labelEvent(): CustomEvent
{
    return CustomEvent::create([
        'title' => 'Streik',
        'popup_content' => 'Text',
        'event_type' => 'other',
        'priority' => 'high',
        'start_date' => now()->subDay(),
        'is_active' => true,
        'review_status' => 'approved',
    ]);
}

beforeEach(function () {
    config(['app.event_auto_translate' => false]);
    $this->customer = Customer::factory()->create();
    $this->actingAs($this->customer, 'customer');
});

it('legt Labels mit wechselnden Palettenfarben an und lehnt doppelte Namen ab', function () {
    $this->postJson(route('risk-overview.labels.store'), ['name' => 'Kunde informiert'])
        ->assertCreated()
        ->assertJsonPath('label.color', Label::PALETTE[0]);
    $this->postJson(route('risk-overview.labels.store'), ['name' => 'Geprüft'])
        ->assertCreated()
        ->assertJsonPath('label.color', Label::PALETTE[1]);

    $this->postJson(route('risk-overview.labels.store'), ['name' => 'Geprüft'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name' => 'Ein Label mit diesem Namen gibt es bereits.']);
});

it('vergibt beim Tippen eines neuen Namens ein Label mit eigener Farbe an ein Ereignis', function () {
    Label::create(['customer_id' => $this->customer->id, 'name' => 'Vorhanden', 'color' => Label::PALETTE[0]]);
    $event = labelEvent();

    $response = $this->postJson(route('risk-overview.event.labels.attach', $event->id), ['name' => 'Rückruf']);

    $response->assertOk()
        ->assertJsonPath('label.name', 'Rückruf')
        ->assertJsonPath('label.color', Label::PALETTE[1])
        ->assertJsonPath('labels.0.name', 'Rückruf');
});

it('benennt ein Label um und färbt es um', function () {
    $label = Label::create(['customer_id' => $this->customer->id, 'name' => 'Alt', 'color' => Label::PALETTE[0]]);

    $this->patchJson(route('risk-overview.labels.update', $label->id), ['name' => 'Neu', 'color' => '#DC2626'])
        ->assertOk()
        ->assertJsonPath('label.name', 'Neu')
        ->assertJsonPath('label.color', '#DC2626');

    expect($label->refresh()->only(['name', 'color']))->toBe(['name' => 'Neu', 'color' => '#DC2626']);
});

it('löscht ein Label samt seinen Zuordnungen zu Reisen und Ereignissen', function () {
    $label = Label::create(['customer_id' => $this->customer->id, 'name' => 'Weg damit']);
    $event = labelEvent();
    $event->labels()->attach($label->id);
    DB::table('pds_trip_label')->insert(['customer_id' => $this->customer->id, 'pds_tid' => 'trip-1', 'label_id' => $label->id, 'created_at' => now(), 'updated_at' => now()]);

    $this->getJson(route('risk-overview.labels.index'))
        ->assertOk()
        ->assertJsonPath('labels.0.trips_count', 1)
        ->assertJsonPath('labels.0.events_count', 1);

    $this->deleteJson(route('risk-overview.labels.destroy', $label->id))->assertOk();

    $this->assertModelMissing($label);
    $this->assertDatabaseMissing('custom_event_label', ['label_id' => $label->id]);
    $this->assertDatabaseMissing('pds_trip_label', ['label_id' => $label->id]);
});

it('lässt Labels anderer Kunden unangetastet', function () {
    $foreign = Label::withoutGlobalScopes()->create(['customer_id' => Customer::factory()->create()->id, 'name' => 'Fremd']);

    $this->patchJson(route('risk-overview.labels.update', $foreign->id), ['name' => 'Übernommen'])->assertNotFound();
    $this->deleteJson(route('risk-overview.labels.destroy', $foreign->id))->assertNotFound();

    expect(Label::withoutGlobalScopes()->find($foreign->id)->name)->toBe('Fremd');
});

it('verwirft Label-gefilterte Caches, sobald ein Label vergeben wird', function () {
    $label = Label::create(['customer_id' => $this->customer->id, 'name' => 'Geprüft']);
    $before = Label::filterCacheKey($this->customer->id, [$label->id]);

    $this->postJson(route('risk-overview.event.labels.attach', labelEvent()->id), ['label_id' => $label->id])->assertOk();

    expect(Label::filterCacheKey($this->customer->id, [$label->id]))->not->toBe($before)
        ->and(Label::eventIdsForLabels($this->customer->id, [$label->id]))->toHaveCount(1);
});

it('filtert die Länderübersicht nach mehreren Labels', function () {
    $italy = \App\Models\Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);
    $spain = \App\Models\Country::factory()->create(['iso_code' => 'ES', 'iso3_code' => 'ESP']);
    $checked = Label::create(['customer_id' => $this->customer->id, 'name' => 'Geprüft']);
    $callBack = Label::create(['customer_id' => $this->customer->id, 'name' => 'Rückruf']);
    $italian = labelEvent();
    $italian->update(['country_id' => $italy->id]);
    $spanish = labelEvent();
    $spanish->update(['country_id' => $spain->id]);
    labelEvent()->update(['country_id' => $spain->id]);
    $italian->labels()->attach($checked->id);
    $spanish->labels()->attach($callBack->id);
    \Illuminate\Support\Facades\Cache::flush();

    $response = $this->getJson(route('risk-overview.data', ['labels' => $checked->id.','.$callBack->id, 'days' => 30]));

    $response->assertOk()->assertJsonPath('data.summary.total_events', 2);
    expect(collect($response->json('data.countries'))->pluck('country.code')->sort()->values()->all())->toBe(['ES', 'IT']);
});

it('zeigt auf der Travel-Alert-Seite Label-Filter, Verwaltung und Label-Chips', function () {
    $response = $this->get(route('risk-overview'));

    $response->assertOk()
        ->assertSee('Labels verwalten')
        ->assertSee('toggleLabelFilter(chip.id)', false)
        ->assertSee('openQuickLabel(event, $event, \'event\')', false)
        ->assertSee('openQuickLabel(trip, $event, \'trip\')', false)
        ->assertSee('Mehrere auswählen')
        ->assertSee('Label suchen oder neu anlegen')
        ->assertSee(route('risk-overview.labels.index'), false);
});

it('zeigt mit "none" nur Ereignisse ohne Label des Kunden, auch kombiniert mit einem Label', function () {
    $italy = \App\Models\Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA']);
    $spain = \App\Models\Country::factory()->create(['iso_code' => 'ES', 'iso3_code' => 'ESP']);
    $france = \App\Models\Country::factory()->create(['iso_code' => 'FR', 'iso3_code' => 'FRA']);
    $done = Label::create(['customer_id' => $this->customer->id, 'name' => 'Erledigt']);
    $callBack = Label::create(['customer_id' => $this->customer->id, 'name' => 'Rückruf']);
    $foreign = Label::withoutGlobalScopes()->create(['customer_id' => Customer::factory()->create()->id, 'name' => 'Fremd']);
    tap(labelEvent(), fn ($e) => $e->update(['country_id' => $italy->id]))->labels()->attach($done->id);
    tap(labelEvent(), fn ($e) => $e->update(['country_id' => $spain->id]))->labels()->attach($foreign->id);
    tap(labelEvent(), fn ($e) => $e->update(['country_id' => $france->id]))->labels()->attach($callBack->id);
    \Illuminate\Support\Facades\Cache::flush();

    $unlabeled = $this->getJson(route('risk-overview.data', ['labels' => 'none', 'days' => 30]));
    $combined = $this->getJson(route('risk-overview.data', ['labels' => 'none,'.$callBack->id, 'days' => 30]));

    expect(collect($unlabeled->json('data.countries'))->pluck('country.code')->all())->toBe(['ES'])
        ->and(collect($combined->json('data.countries'))->pluck('country.code')->sort()->values()->all())->toBe(['ES', 'FR']);
});

it('vergibt und entfernt ein Label an mehreren Ereignissen und API-Reisen auf einmal', function () {
    $first = labelEvent();
    $second = labelEvent();

    $response = $this->postJson(route('risk-overview.labels.bulk'), [
        'name' => 'Rückruf',
        'event_ids' => [$first->id, $second->id],
        'trip_ids' => ['api-trip-7'],
    ]);

    $label = Label::where('name', 'Rückruf')->first();
    $response->assertOk()
        ->assertJsonPath("events.{$first->id}.0.name", 'Rückruf')
        ->assertJsonPath('trips.api-trip-7.0.name', 'Rückruf');
    expect($response->json("events.{$first->id}.0.assigned_at"))->not->toBeNull();
    $this->assertDatabaseHas('pds_trip_label', ['customer_id' => $this->customer->id, 'pds_tid' => 'trip-7', 'label_id' => $label->id]);

    $this->postJson(route('risk-overview.labels.bulk'), ['label_id' => $label->id, 'detach' => true, 'event_ids' => [$first->id]])
        ->assertOk()
        ->assertJsonPath("events.{$first->id}", []);
    $this->assertDatabaseMissing('custom_event_label', ['custom_event_id' => $first->id, 'label_id' => $label->id]);
    $this->assertDatabaseHas('custom_event_label', ['custom_event_id' => $second->id, 'label_id' => $label->id]);
});

it('führt ein Label in ein anderes zusammen, ohne doppelte Zuordnungen', function () {
    $source = Label::create(['customer_id' => $this->customer->id, 'name' => 'Erledigt']);
    $target = Label::create(['customer_id' => $this->customer->id, 'name' => 'Bearbeitet']);
    $both = labelEvent();
    $onlySource = labelEvent();
    $both->labels()->attach([$source->id, $target->id]);
    $onlySource->labels()->attach($source->id);

    $this->postJson(route('risk-overview.labels.merge', $source->id), ['target_id' => $target->id])->assertOk();

    $this->assertModelMissing($source);
    expect($both->labels()->pluck('labels.id')->all())->toBe([$target->id])
        ->and($onlySource->labels()->pluck('labels.id')->all())->toBe([$target->id]);
});

it('speichert Reihenfolge und Beschreibung und liefert die Labels in dieser Reihenfolge', function () {
    $this->postJson(route('risk-overview.labels.store'), ['name' => 'B-Label', 'description' => 'Kunde telefonisch informiert'])->assertCreated();
    $this->postJson(route('risk-overview.labels.store'), ['name' => 'A-Label'])->assertCreated();
    $ids = Label::where('customer_id', $this->customer->id)->orderBy('name')->pluck('id')->all();

    $this->postJson(route('risk-overview.labels.order'), ['ids' => array_reverse($ids)])->assertOk();

    $this->getJson(route('risk-overview.labels.search', ['q' => '']))
        ->assertOk()
        ->assertJsonPath('0.name', 'B-Label')
        ->assertJsonPath('0.description', 'Kunde telefonisch informiert')
        ->assertJsonPath('1.name', 'A-Label');
});
