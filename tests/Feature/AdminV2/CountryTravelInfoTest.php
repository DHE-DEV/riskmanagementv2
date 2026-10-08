<?php

use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Models\Continent;
use App\Models\Country;
use App\Models\CountryImage;
use App\Models\User;
use App\Services\DeepLTranslationService;
use App\Support\AdminV2\CountryTravelInfo;
use Database\Seeders\CurrencySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Laender-Stammdaten fuer Endkunden-Apps: Gebietstyp, Reiseinformationen in
 * mehreren Sprachen, Flagge und Bilder.
 */
function travelAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function travelCountry(string $german, string $iso, string $iso3, array $attributes = []): Country
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create(array_merge([
        'name_translations' => ['de' => $german, 'en' => $german],
        'iso_code' => $iso,
        'iso3_code' => $iso3,
        'continent_id' => $continent->id,
    ], $attributes));
}

it('speichert Gebietstyp, Fahrseite und Reiseinformationen in mehreren Sprachen', function () {
    $this->seed(CurrencySeeder::class);
    $france = travelCountry('Frankreich', 'FR', 'FRA');
    $martinique = travelCountry('Martinique', 'MQ', 'MTQ');

    $this->actingAs(travelAdmin());

    Livewire::test(CountryEditor::class, ['country' => $martinique->id])
        ->assertSet('territoryType', 'sovereign')
        ->set('territoryType', 'dependent')
        ->set('parentCountryId', (string) $france->id)
        ->set('drivingSide', 'right')
        ->set('travelInfo.plug_types', ['F', 'C', 'E'])
        ->set('travelInfo.voltage', '220')
        ->set('travelInfo.frequency', '50')
        ->set('travelInfo.emergency.general', '112')
        ->set('travelInfo.emergency.police', '17')
        ->set('timezone', 'America/Martinique')
        ->set('travelInfo.religions', ['islam', 'christianity', 'none'])
        ->set('travelInfo.national_day.date', '1848-05-22')
        ->set('travelInfo.national_day.name.de', 'Tag der Abschaffung der Sklaverei')
        ->set('travelInfo.national_day.name.en', 'Abolition of Slavery Day')
        ->set('travelInfo.tipping.restaurants.from', '5')
        ->set('travelInfo.tipping.restaurants.to', '10')
        ->set('travelInfo.tipping.restaurants.unit', 'percent')
        ->set('travelInfo.tipping.restaurants.description.de', 'Service ist meist enthalten.')
        ->set('travelInfo.tipping.guides.from', '7,5')
        ->set('travelInfo.tipping.guides.unit', 'amount')
        ->set('travelInfo.tipping.guides.currency', 'EUR')
        ->set('travelInfo.tipping.taxi.mode', 'fixed')
        ->set('travelInfo.tipping.taxi.from', '2')
        ->set('travelInfo.tipping.taxi.to', '99')
        ->set('travelInfo.tipping.taxi.unit', 'amount')
        ->set('travelInfo.tipping.taxi.currency', 'USD')
        ->set('travelInfo.tipping.guides.description.en', 'Per person and day.')
        ->set('travelInfo.texts.intro.de', 'Karibikinsel mit französischem Flair.')
        ->set('travelInfo.texts.intro.en', 'Caribbean island with French flair.')
        ->set('travelInfo.texts.known_for.de', 'Strände, Rum, Vulkan Pelée, ')
        ->set('travelInfo.texts.power_notes.de', 'Teils 110 V in älteren Hotels.')
        ->call('save')
        ->assertHasNoErrors();

    $martinique->refresh();

    expect($martinique->territory_type)->toBe('dependent')
        ->and($martinique->parent_country_id)->toBe($france->id)
        ->and($martinique->parentCountry->getName('de'))->toBe('Frankreich')
        ->and($france->territories()->pluck('iso_code')->all())->toBe(['MQ'])
        ->and($martinique->driving_side)->toBe('right')
        ->and($martinique->timezone)->toBe('America/Martinique')
        ->and($martinique->travel_info)->toEqual([
            'plug_types' => ['C', 'E', 'F'],
            'voltage' => 220,
            'frequency' => 50,
            'emergency' => ['general' => '112', 'police' => '17'],
            'religions' => ['islam', 'christianity', 'none'],
            'national_day' => ['date' => '1848-05-22', 'name' => ['de' => 'Tag der Abschaffung der Sklaverei', 'en' => 'Abolition of Slavery Day']],
            'tipping' => [
                'restaurants' => ['from' => 5, 'to' => 10, 'unit' => 'percent', 'description' => ['de' => 'Service ist meist enthalten.']],
                'guides' => ['from' => 7.5, 'unit' => 'amount', 'currency' => 'EUR', 'description' => ['en' => 'Per person and day.']],
                // Fester Wert: "bis" faellt weg.
                'taxi' => ['mode' => 'fixed', 'from' => 2, 'unit' => 'amount', 'currency' => 'USD'],
            ],
            'intro' => ['de' => 'Karibikinsel mit französischem Flair.', 'en' => 'Caribbean island with French flair.'],
            'known_for' => ['de' => ['Strände', 'Rum', 'Vulkan Pelée']],
            'power_notes' => ['de' => 'Teils 110 V in älteren Hotels.'],
        ]);

    // Ein souveraener Staat verliert sein Mutterland; ohne Angaben bleibt travel_info leer.
    Livewire::test(CountryEditor::class, ['country' => $martinique->id])
        ->assertSet('parentCountryId', (string) $france->id)
        ->assertSet('travelInfo.plug_types', ['C', 'E', 'F'])
        ->assertSet('travelInfo.texts.intro.en', 'Caribbean island with French flair.')
        ->assertSet('travelInfo.tipping.guides.from', '7,5')
        ->assertSet('travelInfo.tipping.guides.currency', 'EUR')
        ->assertSet('travelInfo.tipping.taxi.mode', 'fixed')
        ->assertSet('travelInfo.tipping.hotels.mode', 'range')
        ->assertSet('travelInfo.religions', ['islam', 'christianity', 'none'])
        ->assertSet('travelInfo.national_day.date', '1848-05-22')
        ->assertSet('travelInfo.national_day.name.en', 'Abolition of Slavery Day')
        ->assertSeeInOrder(['Reihenfolge', 'Islam', 'Christentum', 'Konfessionslos'])
        // Nach vorn bzw. hinten schieben aendert die Reihenfolge fuer Kunden.
        ->call('moveReligion', 'christianity', 'up')
        ->assertSet('travelInfo.religions', ['christianity', 'islam', 'none'])
        ->call('moveReligion', 'christianity', 'up')
        ->assertSet('travelInfo.religions', ['christianity', 'islam', 'none'])
        ->call('moveReligion', 'islam', 'down')
        ->assertSet('travelInfo.religions', ['christianity', 'none', 'islam'])
        ->assertSet('travelInfo.tipping.taxi.from', '2')
        ->set('territoryType', 'sovereign')
        ->call('save')
        ->assertHasNoErrors();

    expect($martinique->fresh()->parent_country_id)->toBeNull();

    $empty = travelCountry('Testland', 'XT', 'XTT');
    Livewire::test(CountryEditor::class, ['country' => $empty->id])->call('save')->assertHasNoErrors();
    expect($empty->fresh()->travel_info)->toBeNull();
});

it('prueft Steckertypen, Strom, Notrufnummern und das Mutterland', function () {
    $this->seed(CurrencySeeder::class);
    $country = travelCountry('Testland', 'XT', 'XTT');

    $this->actingAs(travelAdmin());

    Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->set('territoryType', 'dependent')
        ->set('parentCountryId', (string) $country->id)
        ->set('drivingSide', 'middle')
        ->set('travelInfo.plug_types', ['Z'])
        ->set('travelInfo.religions', ['jediism'])
        ->set('travelInfo.national_day.date', '03.10.1990')
        ->set('travelInfo.voltage', '12')
        ->set('travelInfo.frequency', '70')
        ->set('travelInfo.emergency.fire', 'Feuerwehr anrufen')
        ->set('travelInfo.tipping.taxi.from', 'zehn')
        ->set('travelInfo.tipping.hotels.from', '10')
        ->set('travelInfo.tipping.hotels.to', '5')
        ->set('travelInfo.tipping.hotels.currency', 'XYZ')
        ->call('save')
        ->assertHasErrors([
            'parentCountryId',
            'drivingSide',
            'travelInfo.plug_types.0',
            'travelInfo.religions.0',
            'travelInfo.national_day.date',
            'travelInfo.voltage',
            'travelInfo.frequency',
            'travelInfo.emergency.fire',
            'travelInfo.tipping.taxi.from',
            'travelInfo.tipping.hotels.to',
            'travelInfo.tipping.hotels.currency',
        ]);
});

it('liefert zu jedem Steckertyp ein Bild', function () {
    foreach (array_keys(CountryTravelInfo::PLUG_TYPES) as $type) {
        expect(CountryTravelInfo::plugImage($type))->toEndWith('/images/plug-types/'.strtolower($type).'.svg')
            ->and(file_exists(public_path('images/plug-types/'.strtolower($type).'.svg')))->toBeTrue();
    }

    expect(CountryTravelInfo::plugImage('Z'))->toBeNull()
        ->and(CountryTravelInfo::plugTypesForApi(['plug_types' => ['F', 'Z', 'C']]))->toHaveCount(2)
        ->and(CountryTravelInfo::plugTypesForApi(['plug_types' => ['F']])[0])->toMatchArray(['type' => 'F', 'description' => 'Schuko (Deutschland)']);

    $this->actingAs(travelAdmin());

    Livewire::test(CountryEditor::class, ['country' => travelCountry('Testland', 'XT', 'XTT')->id])
        ->assertSeeHtml('images/plug-types/g.svg')
        ->assertSee('Schuko (Deutschland)');
});

it('liefert Flagge und Standardbild aus dem ISO-Code', function () {
    $germany = travelCountry('Deutschland', 'DE', 'DEU');

    expect($germany->flag_url)->toBe('https://flagcdn.com/de.svg')
        ->and($germany->flag_emoji)->toBe('🇩🇪')
        ->and($germany->hero_image_url)->toBe(file_exists(public_path('images/countries/de.jpg')) ? asset('images/countries/de.jpg') : null);

    $this->actingAs(travelAdmin())
        ->get(route('adminv2.master-data.countries.index'))
        ->assertOk()
        ->assertSee('https://flagcdn.com/de.svg');
});

it('legt Bilder ab, macht das erste zum Titelbild und speichert Texte je Sprache', function () {
    Storage::fake('public');

    $italy = travelCountry('Italien', 'IT', 'ITA');

    $this->actingAs(travelAdmin());

    $editor = Livewire::test(CountryEditor::class, ['country' => $italy->id])
        ->set('newImages', [
            UploadedFile::fake()->image('rom.jpg', 1600, 900),
            UploadedFile::fake()->image('venedig.png', 800, 1200),
        ])
        ->assertHasNoErrors()
        ->assertSet('newImages', [])
        ->assertDispatched('adminv2-toast');

    $images = $italy->images()->get();
    $hero = $images->first();
    $gallery = $images->last();

    expect($images)->toHaveCount(2)
        ->and($hero->kind)->toBe(CountryImage::KIND_HERO)
        ->and($hero->original_name)->toBe('rom.jpg')
        ->and($hero->width)->toBe(1600)
        ->and($hero->height)->toBe(900)
        ->and($hero->mime_type)->toBe('image/jpeg')
        ->and($hero->thumb_path)->toEndWith('_thumb.jpg')
        ->and($gallery->kind)->toBe(CountryImage::KIND_GALLERY)
        ->and($gallery->sort_order)->toBeGreaterThan($hero->sort_order)
        ->and($italy->fresh()->hero_image_url)->toBe($hero->url());

    Storage::disk('public')->assertExists($hero->path);
    Storage::disk('public')->assertExists($hero->thumb_path);

    // Die verkleinerte Fassung ist hoechstens 640 Pixel breit.
    $thumb = getimagesizefromstring(Storage::disk('public')->get($hero->thumb_path));
    expect($thumb[0])->toBe(640)->and($thumb[1])->toBe(360);

    $editor
        ->set('imageMeta.'.$hero->id.'.alt.de', 'Kolosseum in Rom')
        ->set('imageMeta.'.$hero->id.'.alt.en', 'Colosseum in Rome')
        ->set('imageMeta.'.$hero->id.'.caption.de', 'Das Kolosseum am Abend')
        ->set('imageMeta.'.$hero->id.'.credit', 'Anna Beispiel')
        ->set('imageMeta.'.$hero->id.'.license', 'CC BY 4.0')
        ->set('imageMeta.'.$hero->id.'.source_url', 'keine-adresse')
        ->call('saveImage', $hero->id)
        ->assertHasErrors(['imageMeta.'.$hero->id.'.source_url'])
        ->set('imageMeta.'.$hero->id.'.source_url', 'https://example.org/rom')
        ->set('imageMeta.'.$hero->id.'.is_published', false)
        ->call('saveImage', $hero->id)
        ->assertHasNoErrors();

    $hero->refresh();

    expect($hero->alt_translations)->toEqual(['de' => 'Kolosseum in Rom', 'en' => 'Colosseum in Rome'])
        ->and($hero->caption_translations)->toEqual(['de' => 'Das Kolosseum am Abend'])
        ->and($hero->credit)->toBe('Anna Beispiel')
        ->and($hero->license)->toBe('CC BY 4.0')
        ->and($hero->source_url)->toBe('https://example.org/rom')
        ->and($hero->is_published)->toBeFalse()
        ->and($hero->alt('nl'))->toBe('Colosseum in Rome')
        ->and($hero->toApiArray()['alt'])->toEqual(['de' => 'Kolosseum in Rom', 'en' => 'Colosseum in Rome']);

    // Titelbild wechseln, dann das alte Titelbild loeschen – samt Dateien.
    $editor->call('setHeroImage', $gallery->id);

    expect($gallery->fresh()->kind)->toBe(CountryImage::KIND_HERO)
        ->and($hero->fresh()->kind)->toBe(CountryImage::KIND_GALLERY);

    $editor->call('deleteImage', $hero->id);

    Storage::disk('public')->assertMissing($hero->path);
    Storage::disk('public')->assertMissing($hero->thumb_path);
    expect(CountryImage::whereKey($hero->id)->exists())->toBeFalse()
        ->and($italy->images()->count())->toBe(1);
});

it('lehnt andere Dateien als Bilder ab und laedt bei einem neuen Land nichts hoch', function () {
    Storage::fake('public');

    $country = travelCountry('Testland', 'XT', 'XTT');

    $this->actingAs(travelAdmin());

    Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->set('newImages', [UploadedFile::fake()->create('liste.pdf', 100, 'application/pdf')])
        ->assertHasErrors(['newImages.0']);

    expect($country->images()->count())->toBe(0);

    Livewire::test(CountryEditor::class)
        ->assertSee('sobald das Land gespeichert ist')
        ->set('newImages', [UploadedFile::fake()->image('x.jpg')])
        ->assertSet('newImages', []);

    expect(CountryImage::count())->toBe(0);
});

it('bietet die KI-Pruefung fuer Reiseinformationen und Bilder an und uebernimmt Vorschlaege', function () {
    $this->seed(CurrencySeeder::class);
    $france = travelCountry('Frankreich', 'FR', 'FRA');
    $country = travelCountry('Martinique', 'MQ', 'MTQ');

    $this->actingAs(travelAdmin());

    $editor = Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->call('openAiCheck', 'details')
        ->assertSet('aiSection', 'details')
        ->call('openAiCheck', 'travel')
        ->assertSet('aiSection', 'details')
        ->call('openAiCheck', 'power')
        ->assertSet('aiSection', 'power')
        ->call('openAiCheck', 'tipping')
        ->assertSet('aiSection', 'tipping')
        ->call('openAiCheck', 'images')
        ->assertSet('aiSection', 'images');

    $apply = fn (string $key, string $value) => (fn () => $this->aiApply($key, $value))->call($editor->instance());

    expect($apply('territory_type', 'Abhängiges Gebiet'))->toBeTrue()
        ->and($editor->instance()->territoryType)->toBe('dependent')
        ->and($apply('parent_country', 'Frankreich'))->toBeTrue()
        ->and($editor->instance()->parentCountryId)->toBe((string) $france->id)
        ->and($apply('driving_side', 'Rechtsverkehr'))->toBeTrue()
        ->and($editor->instance()->drivingSide)->toBe('right')
        ->and($apply('plug_types', 'Typ C und Typ E'))->toBeTrue()
        ->and($editor->instance()->travelInfo['plug_types'])->toBe(['C', 'E'])
        ->and($apply('voltage', '220 V'))->toBeTrue()
        ->and($editor->instance()->travelInfo['voltage'])->toBe('220')
        ->and($apply('emergency_police', '17'))->toBeTrue()
        ->and($editor->instance()->travelInfo['emergency']['police'])->toBe('17')
        ->and($apply('religions', 'Islam, Christentum (katholisch) und Hinduism'))->toBeTrue()
        ->and($editor->instance()->travelInfo['religions'])->toBe(['islam', 'christianity', 'hinduism'])
        ->and($apply('religions', 'unbekannt'))->toBeFalse()
        ->and($apply('national_day_date', '22.05.1848'))->toBeTrue()
        ->and($editor->instance()->travelInfo['national_day']['date'])->toBe('1848-05-22')
        ->and($apply('national_day_name_en', 'Abolition Day'))->toBeTrue()
        ->and($apply('national_day_name_xx', 'x'))->toBeFalse()
        ->and($apply('tipping_restaurants_from', '5 %'))->toBeTrue()
        ->and($apply('tipping_restaurants_to', '10'))->toBeTrue()
        ->and($apply('tipping_restaurants_unit', 'Prozent'))->toBeTrue()
        ->and($apply('tipping_taxi_description_de', 'Aufrunden reicht.'))->toBeTrue()
        ->and($apply('tipping_guides_currency', 'US-Dollar (USD)'))->toBeTrue()
        ->and($editor->instance()->travelInfo['tipping']['guides']['currency'])->toBe('USD')
        ->and($apply('tipping_guides_currency', 'Taler (TLR)'))->toBeFalse()
        ->and($apply('tipping_taxi_mode', 'fester Wert'))->toBeTrue()
        ->and($editor->instance()->travelInfo['tipping']['taxi']['mode'])->toBe('fixed')
        ->and($editor->instance()->travelInfo['tipping']['restaurants'])->toMatchArray(['from' => '5', 'to' => '10', 'unit' => 'percent'])
        ->and($editor->instance()->travelInfo['tipping']['taxi']['description']['de'])->toBe('Aufrunden reicht.')
        ->and($apply('tipping_taxi_from', 'keine Angabe'))->toBeFalse()
        ->and($apply('known_for_en', 'beaches, rum, Mount Pelée'))->toBeTrue()
        ->and($editor->instance()->travelInfo['texts']['known_for']['en'])->toBe('beaches, rum, Mount Pelée')
        ->and($apply('intro_de', 'Karibikinsel.'))->toBeTrue()
        ->and($apply('unbekannt', 'x'))->toBeFalse();

    expect(CountryTravelInfo::placeholders())->toHaveKeys(['territory_type', 'emergency_fire', 'religions', 'national_day_date', 'national_day_name_de', 'intro_de', 'known_for_en'])
        ->and(CountryTravelInfo::placeholders())->not->toHaveKey('plug_types')
        ->and(CountryTravelInfo::placeholders())->not->toHaveKey('timezones')
        ->and(\App\Support\AdminV2\AiAreas::placeholders('countries', 'details'))->toHaveKeys(['timezone', 'territory_type', 'emergency_police', 'intro_de'])
        ->and(CountryTravelInfo::powerPlaceholders())->toHaveKeys(['plug_types', 'voltage', 'frequency', 'power_notes_de', 'power_notes_en'])
        ->and(CountryTravelInfo::tippingPlaceholders())->toHaveKeys(['tipping_hotels_from', 'tipping_taxi_to', 'tipping_guides_unit', 'tipping_guides_currency', 'tipping_restaurants_description_de']);
});

it('uebersetzt Texte, Trinkgeld-Beschreibungen und Bildtexte per DeepL in die uebrigen Sprachen', function () {
    Storage::fake('public');

    $deepl = Mockery::mock(DeepLTranslationService::class);
    $deepl->shouldReceive('isConfigured')->andReturn(true);
    $deepl->shouldReceive('translate')->andReturnUsing(fn (string $text, string $to, string $from) => '['.$to.'] '.$text);
    app()->instance(DeepLTranslationService::class, $deepl);

    $country = travelCountry('Testland', 'XT', 'XTT');

    $this->actingAs(travelAdmin());

    $editor = Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->set('travelInfo.texts.intro.de', 'Ein Land am Meer.')
        ->set('travelInfo.texts.intro.en', 'Schon vorhanden.')
        ->set('travelInfo.texts.known_for.de', 'Strände, Küche')
        ->set('travelInfo.national_day.name.de', 'Nationalfeiertag')
        ->set('travelInfo.tipping.taxi.description.de', 'Aufrunden reicht.')
        ->set('travelInfo.texts.power_notes.de', 'Zwei Spannungen im Land.')
        ->call('translateTexts', 'details')
        ->assertSet('travelInfo.texts.power_notes.en', '')
        ->call('translateTexts', 'power')
        ->assertSet('travelInfo.texts.power_notes.en', '[en] Zwei Spannungen im Land.')
        ->assertSet('travelInfo.texts.power_notes.nl', '[nl] Zwei Spannungen im Land.')
        ->assertDispatched('adminv2-toast')
        // Vorhandene Uebersetzungen bleiben, leere werden gefuellt.
        ->assertSet('travelInfo.texts.intro.en', 'Schon vorhanden.')
        ->assertSet('travelInfo.texts.intro.nl', '[nl] Ein Land am Meer.')
        ->assertSet('travelInfo.texts.known_for.en', '[en] Strände, Küche')
        ->assertSet('travelInfo.national_day.name.en', '[en] Nationalfeiertag')
        // Trinkgeld ist ein eigener Abschnitt und noch unberuehrt.
        ->assertSet('travelInfo.tipping.taxi.description.en', '')
        ->call('translateTexts', 'tipping')
        ->assertSet('travelInfo.tipping.taxi.description.en', '[en] Aufrunden reicht.')
        ->assertSet('travelInfo.tipping.taxi.description.nl', '[nl] Aufrunden reicht.')
        // Mit "ueberschreiben" wird auch das Vorhandene ersetzt.
        ->set('overwriteNoteTranslations', true)
        ->call('translateTexts', 'details')
        ->assertSet('travelInfo.texts.intro.en', '[en] Ein Land am Meer.');

    // Bildtexte werden uebersetzt und sofort gespeichert.
    $editor->set('newImages', [UploadedFile::fake()->image('strand.jpg', 800, 600)]);
    $image = $country->images()->first();

    $editor
        ->set('imageMeta.'.$image->id.'.alt.de', 'Strand bei Sonnenuntergang')
        ->call('translateTexts', 'images')
        ->assertSet('imageMeta.'.$image->id.'.alt.en', '[en] Strand bei Sonnenuntergang');

    expect($image->fresh()->alt_translations)->toEqual(['de' => 'Strand bei Sonnenuntergang', 'en' => '[en] Strand bei Sonnenuntergang', 'nl' => '[nl] Strand bei Sonnenuntergang']);
});

it('weist beim Uebersetzen ohne DeepL-Schluessel darauf hin', function () {
    config(['services.deepl.api_key' => null]);
    $country = travelCountry('Testland', 'XT', 'XTT');

    $this->actingAs(travelAdmin());

    Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->set('travelInfo.texts.intro.de', 'Text')
        ->call('translateTexts', 'details')
        ->assertDispatched('adminv2-toast', fn (string $name, array $params) => str_contains($params['message'], 'DeepL ist nicht konfiguriert'))
        ->assertSet('travelInfo.texts.intro.en', '');
});

it('fuellt die Waehrungstabelle nach ISO 4217 mit Namen in drei Sprachen', function () {
    $this->seed(CurrencySeeder::class);
    $this->seed(CurrencySeeder::class);

    $euro = \App\Models\Currency::find('EUR');

    expect(\App\Models\Currency::count())->toBeGreaterThan(150)
        ->and($euro->getName('de'))->toBe('Euro')
        ->and($euro->name_translations)->toHaveKeys(['de', 'en', 'nl'])
        ->and($euro->symbol)->toBe('€')
        ->and($euro->numeric_code)->toBe(978)
        ->and(\App\Models\Currency::find('JPY')->minor_unit)->toBe(0)
        ->and(\App\Models\Currency::find('XTS'))->toBeNull()
        ->and(collect(\App\Models\Currency::options('de'))->firstWhere('value', 'CHF')['label'])->toContain('Schweizer Franken');
});
