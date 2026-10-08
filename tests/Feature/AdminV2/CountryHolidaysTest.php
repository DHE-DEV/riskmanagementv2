<?php

use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Models\Continent;
use App\Models\Country;
use App\Models\CountryHoliday;
use App\Models\Region;
use App\Models\User;
use App\Services\DeepLTranslationService;
use Livewire\Livewire;

/**
 * Feiertage je Land: anlegen, bearbeiten, loeschen, je Jahr anzeigen und
 * per DeepL uebersetzen.
 */
function holidayAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function holidayCountry(): Country
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create(['name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'], 'iso_code' => 'DE', 'iso3_code' => 'DEU', 'continent_id' => $continent->id]);
}

it('zeigt die Feiertage des gewaehlten Jahres mit Wochentag und legt neue an', function () {
    $germany = holidayCountry();
    $germany->holidays()->create(['date' => '2026-10-03', 'name_translations' => ['de' => 'Tag der Deutschen Einheit', 'en' => 'German Unity Day'], 'source' => CountryHoliday::SOURCE_LIBRARY]);
    $germany->holidays()->create(['date' => '2027-01-01', 'name_translations' => ['de' => 'Neujahr', 'en' => "New Year's Day"], 'source' => CountryHoliday::SOURCE_LIBRARY]);

    $this->travelTo('2026-06-15');
    $this->actingAs(holidayAdmin());

    $editor = Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->assertSet('holidayYear', 2026)
        ->assertSee('Feiertage')
        ->assertSee('Samstag, 03.10.2026')
        ->assertDontSee('Neujahr')
        ->set('holidayYear', 2027)
        ->assertSee('Freitag, 01.01.2027')
        ->assertDontSee('03.10.2026');

    expect($editor->instance()->holidayYears)->toBe([2026, 2027]);

    // Neuer Feiertag: ohne Datum und Namen geht es nicht; danach springt die Karte in sein Jahr.
    $editor
        ->call('addHoliday')
        ->assertHasErrors(['holidayRows.new.date', 'holidayRows.new.name.de'])
        ->set('holidayRows.new.date', '2026-12-26')
        ->set('holidayRows.new.name.de', 'Zweiter Weihnachtstag')
        ->set('holidayRows.new.name.en', 'Boxing Day')
        ->set('holidayRows.new.comment.de', 'Geschäfte geschlossen.')
        ->call('addHoliday')
        ->assertHasNoErrors()
        ->assertSet('holidayYear', 2026)
        ->assertSee('Zweiter Weihnachtstag')
        ->assertSet('holidayRows.new.date', '');

    $boxing = $germany->holidays()->whereDate('date', '2026-12-26')->first();

    expect($boxing->name_translations)->toEqual(['de' => 'Zweiter Weihnachtstag', 'en' => 'Boxing Day'])
        ->and($boxing->comment_translations)->toEqual(['de' => 'Geschäfte geschlossen.'])
        ->and($boxing->is_national)->toBeTrue()
        ->and($boxing->source)->toBe(CountryHoliday::SOURCE_MANUAL)
        ->and($boxing->toApiArray()['weekday'])->toBe(6)
        ->and($boxing->isNational())->toBeTrue();

    // Regionaler Feiertag: einmal angelegt, mit mehreren Regionen – fremde Regionen werden abgewiesen.
    $bavaria = Region::create(['name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria'], 'code' => 'DE-BY', 'country_id' => $germany->id]);
    $hesse = Region::create(['name_translations' => ['de' => 'Hessen', 'en' => 'Hesse'], 'code' => 'DE-HE', 'country_id' => $germany->id]);
    $austria = Country::create(['name_translations' => ['de' => 'Österreich'], 'iso_code' => 'AT', 'iso3_code' => 'AUT', 'continent_id' => $germany->continent_id]);
    $tyrol = Region::create(['name_translations' => ['de' => 'Tirol'], 'code' => 'AT-7', 'country_id' => $austria->id]);

    $editor = Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->set('holidayRows.new.date', '2026-06-04')
        ->set('holidayRows.new.name.de', 'Fronleichnam')
        ->set('holidayRows.new.add_region', (string) $bavaria->id)
        ->call('addHolidayRegion', 'new')
        ->assertSet('holidayRows.new.region_ids', [(string) $bavaria->id])
        ->assertSet('holidayRows.new.add_region', '')
        ->set('holidayRows.new.add_region', (string) $bavaria->id)
        ->call('addHolidayRegion', 'new')
        ->assertSet('holidayRows.new.region_ids', [(string) $bavaria->id])
        ->set('holidayRows.new.add_region', (string) $tyrol->id)
        ->call('addHolidayRegion', 'new')
        ->call('addHoliday')
        ->assertHasErrors(['holidayRows.new.region_ids.1'])
        ->call('removeHolidayRegion', 'new', $tyrol->id)
        ->set('holidayRows.new.add_region', (string) $hesse->id)
        ->call('addHolidayRegion', 'new')
        ->call('addHoliday')
        ->assertHasNoErrors()
        ->assertSee('davon 1 regional')
        ->assertSee('Bayern')
        ->assertSee('Hessen');

    $corpus = $germany->holidays()->whereDate('date', '2026-06-04')->first();

    expect($corpus->regions->pluck('code')->all())->toBe(['DE-BY', 'DE-HE'])
        ->and($corpus->isNational())->toBeFalse()
        ->and($corpus->is_national)->toBeFalse()
        ->and(collect($corpus->toApiArray()['regions'])->pluck('code')->all())->toBe(['DE-BY', 'DE-HE'])
        ->and($germany->holidays()->national()->count())->toBe(3);

    // Eine Region abwaehlen und speichern: der Feiertag bleibt, gilt nur noch in Hessen.
    $editor->call('removeHolidayRegion', (string) $corpus->id, $bavaria->id)
        ->call('saveHoliday', $corpus->id)
        ->assertHasNoErrors();

    expect($corpus->fresh()->regions->pluck('code')->all())->toBe(['DE-HE']);

    $this->travelBack();
});

it('bearbeitet und loescht Feiertage und uebersetzt ihre Namen', function () {
    $germany = holidayCountry();
    $unity = $germany->holidays()->create(['date' => '2026-10-03', 'name_translations' => ['de' => 'Tag der Deutschen Einheit'], 'source' => CountryHoliday::SOURCE_LIBRARY]);
    $other = $germany->holidays()->create(['date' => '2026-05-01', 'name_translations' => ['de' => 'Tag der Arbeit', 'en' => 'Labour Day'], 'source' => CountryHoliday::SOURCE_LIBRARY]);

    $deepl = Mockery::mock(DeepLTranslationService::class);
    $deepl->shouldReceive('isConfigured')->andReturn(true);
    $deepl->shouldReceive('translate')->andReturnUsing(fn (string $text, string $to) => '['.$to.'] '.$text);
    app()->instance(DeepLTranslationService::class, $deepl);

    $this->travelTo('2026-06-15');
    $this->actingAs(holidayAdmin());

    Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->set('holidayRows.'.$unity->id.'.comment.de', 'Nationalfeiertag')
        ->set('holidayRows.'.$unity->id.'.date', '')
        ->call('saveHoliday', $unity->id)
        ->assertHasErrors(['holidayRows.'.$unity->id.'.date'])
        ->set('holidayRows.'.$unity->id.'.date', '2026-10-03')
        ->call('saveHoliday', $unity->id)
        ->assertHasNoErrors()
        ->call('translateTexts', 'holidays')
        ->assertDispatched('adminv2-toast')
        ->call('deleteHoliday', $other->id)
        ->assertDontSee('Tag der Arbeit')
        ->call('openAiCheck', 'holidays')
        ->assertSet('aiSection', 'holidays');

    $unity->refresh();

    expect($unity->comment_translations)->toEqual(['de' => 'Nationalfeiertag', 'en' => '[en] Nationalfeiertag', 'nl' => '[nl] Nationalfeiertag'])
        ->and($unity->name_translations)->toEqual(['de' => 'Tag der Deutschen Einheit', 'en' => '[en] Tag der Deutschen Einheit', 'nl' => '[nl] Tag der Deutschen Einheit'])
        ->and(CountryHoliday::whereKey($other->id)->exists())->toBeFalse();

    // Fremde Feiertage lassen sich hier nicht aendern.
    $austria = Country::create(['name_translations' => ['de' => 'Österreich'], 'iso_code' => 'AT', 'iso3_code' => 'AUT', 'continent_id' => $germany->continent_id]);
    $foreign = $austria->holidays()->create(['date' => '2026-10-26', 'name_translations' => ['de' => 'Nationalfeiertag']]);

    expect(fn () => Livewire::test(CountryEditor::class, ['country' => $germany->id])->call('deleteHoliday', $foreign->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class)
        ->and(CountryHoliday::whereKey($foreign->id)->exists())->toBeTrue();

    $this->travelBack();
});
