<?php

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;

/**
 * Grundangaben fuer Endkunden-Apps je Land: Gebietstyp mit Mutterland,
 * Fahrseite, Notrufnummern, Religionen nach Verbreitung und Nationaltag
 * mit Bezeichnung in drei Sprachen.
 *
 * Quellen (Stand Oktober 2026): mledoze/countries (Unabhaengigkeit),
 * Wikidata (Fahrseite, Notrufnummern), Wikipedia "List of emergency
 * telephone numbers" (Rollen der Nummern), CIA World Factbook (Religionen
 * mit Anteilen, Nationalfeiertag), DeepL (Uebersetzung der Bezeichnungen).
 *
 * Es wird nur gefuellt, was leer ist – von Hand gepflegte Angaben bleiben.
 * Ein Nationaltag ohne bekanntes Ursprungsjahr traegt das Jahr 2000.
 */
return new class extends Migration
{
    public function up(): void
    {
        $byIso = Country::query()->withTrashed()->get()->keyBy(fn (Country $country) => strtoupper((string) $country->iso_code));

        foreach (self::DATA as $iso => $row) {
            $country = $byIso->get($iso);

            if (! $country) {
                continue;
            }

            $info = is_array($country->travel_info) ? $country->travel_info : [];
            $changes = [];

            if (($country->territory_type ?? 'sovereign') === 'sovereign' && $row['territory_type'] !== 'sovereign') {
                $changes['territory_type'] = $row['territory_type'];
            }

            if ($country->parent_country_id === null && isset($row['parent']) && ($parent = $byIso->get($row['parent']))) {
                $changes['parent_country_id'] = $parent->id;
            }

            if ($country->driving_side === null && isset($row['driving_side'])) {
                $changes['driving_side'] = $row['driving_side'];
            }

            if (empty($info['emergency']) && isset($row['emergency'])) {
                $info['emergency'] = $row['emergency'];
            }

            if (empty($info['religions']) && isset($row['religions'])) {
                $info['religions'] = $row['religions'];
            }

            if (empty($info['national_day']) && isset($row['national_day'])) {
                $info['national_day'] = ['date' => $row['national_day']['date'], 'name' => $row['national_day']['name']];
            }

            if ($info !== ($country->travel_info ?? [])) {
                $changes['travel_info'] = $info ?: null;
            }

            if ($changes !== []) {
                $country->forceFill($changes)->saveQuietly();
            }
        }
    }

    public function down(): void
    {
        // Die Angaben lassen sich von Hand aendern – ein Zuruecknehmen wuerde auch das treffen.
    }

    /** @var array<string, array<string, mixed>> ISO-Code => Angaben */
    private const DATA = [
        'AD' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '110',
                'ambulance' => '116',
                'fire' => '118',
            ],
            'religions' => ['none'],
            'national_day' => [
                'date' => '1278-09-08',
                'year_known' => true,
                'name' => [
                    'en' => 'Our Lady of Meritxell Day',
                    'de' => 'Festtag der Muttergottes von Meritxell',
                    'nl' => 'De feestdag van Onze-Lieve-Vrouw van Meritxell',
                ],
            ],
        ],
        'AE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '999',
                'ambulance' => '998',
                'fire' => '997',
            ],
            'religions' => ['islam', 'christianity', 'hinduism', 'buddhism', 'none'],
            'national_day' => [
                'date' => '1971-12-02',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (National Day)',
                    'de' => 'Unabhängigkeitstag (Nationalfeiertag)',
                    'nl' => 'Onafhankelijkheidsdag (Nationale Feestdag)',
                ],
            ],
        ],
        'AF' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '119',
                'fire' => '119',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1919-08-19',
                'year_known' => true,
                'name' => [
                    'en' => 'previous: Independence Day',
                    'de' => 'vorherige Seite: Unabhängigkeitstag',
                    'nl' => 'vorige: Onafhankelijkheidsdag',
                ],
            ],
        ],
        'AG' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'police' => '911',
                'ambulance' => '911',
                'fire' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1981-11-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'AI' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1967-05-30',
                'year_known' => true,
                'name' => [
                    'en' => 'Anguilla Day',
                    'de' => 'Anguilla-Tag',
                    'nl' => 'Anguilla-dag',
                ],
            ],
        ],
        'AL' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '129',
                'ambulance' => '127',
                'fire' => '128',
            ],
            'religions' => ['islam', 'christianity', 'none'],
            'national_day' => [
                'date' => '1912-11-28',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'AM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '102',
                'ambulance' => '103',
                'fire' => '101',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1991-09-21',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'AO' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '113',
                'ambulance' => '116',
                'fire' => '115',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1975-11-11',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'AR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'police' => '101',
                'ambulance' => '107',
                'fire' => '100',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1810-05-25',
                'year_known' => true,
                'name' => [
                    'en' => 'Revolution Day (May Revolution Day)',
                    'de' => 'Tag der Revolution (Tag der Mai-Revolution)',
                    'nl' => 'Dag van de Revolutie (Dag van de Meirevolutie)',
                ],
            ],
        ],
        'AS' => [
            'territory_type' => 'dependent',
            'parent' => 'US',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1900-04-17',
                'year_known' => true,
                'name' => [
                    'en' => 'Flag Day',
                    'de' => 'Flaggentag',
                    'nl' => 'Vlaggendag',
                ],
            ],
        ],
        'AT' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '133',
                'ambulance' => '144',
                'fire' => '122',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1955-10-26',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'AU' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '000',
            ],
            'religions' => ['christianity', 'none', 'islam', 'hinduism', 'buddhism'],
            'national_day' => [
                'date' => '1788-01-26',
                'year_known' => true,
                'name' => [
                    'en' => 'Australia Day',
                    'de' => 'Australia Day',
                    'nl' => 'Australië-dag',
                ],
            ],
        ],
        'AW' => [
            'territory_type' => 'dependent',
            'parent' => 'NL',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1976-03-18',
                'year_known' => true,
                'name' => [
                    'en' => 'National Anthem and Flag Day',
                    'de' => 'Tag der Nationalhymne und der Flagge',
                    'nl' => 'Nationaal volkslied en Vlaggendag',
                ],
            ],
        ],
        'AZ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '102',
                'ambulance' => '103',
                'fire' => '101',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1918-05-28',
                'year_known' => true,
                'name' => [
                    'en' => 'Republic Day',
                    'de' => 'Tag der Republik',
                    'nl' => 'Dag van de Republiek',
                ],
            ],
        ],
        'BA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '122',
                'ambulance' => '124',
                'fire' => '123',
            ],
            'religions' => ['islam', 'christianity', 'none'],
            'national_day' => [
                'date' => '1992-03-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'BB' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
                'police' => '211',
                'ambulance' => '511',
                'fire' => '311',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1966-11-30',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'BD' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
            ],
            'religions' => ['islam', 'hinduism'],
            'national_day' => [
                'date' => '1971-03-26',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'BE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '101',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1831-07-21',
                'year_known' => true,
                'name' => [
                    'en' => 'Belgian National Day',
                    'de' => 'Belgischer Nationalfeiertag',
                    'nl' => 'Belgische nationale feestdag',
                ],
            ],
        ],
        'BF' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'fire' => '18',
            ],
            'religions' => ['islam', 'christianity', 'traditional'],
            'national_day' => [
                'date' => '1958-12-11',
                'year_known' => true,
                'name' => [
                    'en' => 'Republic Day',
                    'de' => 'Tag der Republik',
                    'nl' => 'Dag van de Republiek',
                ],
            ],
        ],
        'BG' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '166',
                'ambulance' => '150',
                'fire' => '160',
            ],
            'religions' => ['christianity', 'islam', 'none'],
            'national_day' => [
                'date' => '1878-03-03',
                'year_known' => true,
                'name' => [
                    'en' => 'Liberation Day',
                    'de' => 'Tag der Befreiung',
                    'nl' => 'Bevrijdingsdag',
                ],
            ],
        ],
        'BH' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '999',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1971-12-16',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'BI' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '117',
                'fire' => '118',
            ],
            'religions' => ['christianity', 'islam', 'none'],
            'national_day' => [
                'date' => '1962-07-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'BJ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '117',
                'fire' => '118',
            ],
            'religions' => ['christianity', 'islam', 'traditional', 'none'],
            'national_day' => [
                'date' => '1960-08-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'BL' => [
            'territory_type' => 'dependent',
            'parent' => 'FR',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '17',
                'ambulance' => '17',
                'fire' => '18',
            ],
            'national_day' => [
                'date' => '1790-07-14',
                'year_known' => true,
                'name' => [
                    'en' => 'Fête de la Fédération',
                    'de' => 'Fête de la Fédération',
                    'nl' => 'Fête de la Fédération',
                ],
            ],
        ],
        'BM' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '2000-05-24',
                'year_known' => false,
                'name' => [
                    'en' => 'Bermuda Day',
                    'de' => 'Bermuda-Tag',
                    'nl' => 'Bermuda-dag',
                ],
            ],
        ],
        'BN' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'police' => '993',
                'ambulance' => '991',
                'fire' => '995',
            ],
            'religions' => ['islam', 'christianity', 'buddhism'],
            'national_day' => [
                'date' => '1984-02-23',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'BO' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'police' => '110',
                'ambulance' => '118',
                'fire' => '119',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1825-08-06',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'BQ' => [
            'territory_type' => 'dependent',
            'parent' => 'NL',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
        ],
        'BR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '190',
                'ambulance' => '192',
                'fire' => '193',
            ],
            'religions' => ['christianity', 'none', 'traditional'],
            'national_day' => [
                'date' => '1822-09-07',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'BS' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
                'police' => '919',
                'ambulance' => '919',
                'fire' => '919',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1973-07-10',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'BT' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '113',
                'fire' => '110',
            ],
            'religions' => ['buddhism', 'hinduism'],
            'national_day' => [
                'date' => '1907-12-17',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'BW' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'ambulance' => '997',
                'fire' => '998',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1966-09-30',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (Botswana Day)',
                    'de' => 'Unabhängigkeitstag (Botswana-Tag)',
                    'nl' => 'Onafhankelijkheidsdag (Botswana-dag)',
                ],
            ],
        ],
        'BY' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '102',
                'ambulance' => '103',
                'fire' => '101',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1944-07-03',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'BZ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1798-09-10',
                'year_known' => true,
                'name' => [
                    'en' => 'Battle of St. George\'s Caye Day (National Day)',
                    'de' => 'Tag der Schlacht von St. George’s Caye (Nationalfeiertag)',
                    'nl' => 'Dag van de Slag bij St. George\'s Caye (Nationale Feestdag)',
                ],
            ],
        ],
        'CA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none', 'islam', 'hinduism', 'sikhism', 'buddhism'],
            'national_day' => [
                'date' => '1867-07-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Canada Day',
                    'de' => 'Kanada-Tag',
                    'nl' => 'Canada-dag',
                ],
            ],
        ],
        'CD' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'religions' => ['christianity', 'islam', 'none'],
            'national_day' => [
                'date' => '1960-06-30',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'CF' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '117',
                'ambulance' => '1220',
                'fire' => '118',
            ],
            'religions' => ['christianity', 'islam', 'traditional'],
            'national_day' => [
                'date' => '1958-12-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Republic Day',
                    'de' => 'Tag der Republik',
                    'nl' => 'Dag van de Republiek',
                ],
            ],
        ],
        'CG' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '117',
                'fire' => '118',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1960-08-15',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'CH' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '117',
                'ambulance' => '144',
                'fire' => '118',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1291-08-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Swiss National Day',
                    'de' => 'Schweizer Nationalfeiertag',
                    'nl' => 'Zwitserse nationale feestdag',
                ],
            ],
        ],
        'CI' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '111',
                'police' => '110 / 170',
                'ambulance' => '185',
                'fire' => '180',
            ],
            'religions' => ['islam', 'christianity', 'none', 'traditional'],
            'national_day' => [
                'date' => '1960-08-07',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'CK' => [
            'territory_type' => 'dependent',
            'parent' => 'NZ',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
            ],
            'religions' => ['christianity'],
        ],
        'CL' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '133',
                'ambulance' => '131',
                'fire' => '132',
            ],
            'religions' => ['christianity', 'none', 'buddhism'],
            'national_day' => [
                'date' => '1810-09-18',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'CM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '117',
                'ambulance' => '119',
                'fire' => '118',
            ],
            'religions' => ['christianity', 'islam', 'traditional', 'none'],
            'national_day' => [
                'date' => '1972-05-20',
                'year_known' => true,
                'name' => [
                    'en' => 'State Unification Day (National Day)',
                    'de' => 'Tag der Wiedervereinigung (Nationalfeiertag)',
                    'nl' => 'Dag van de staatshereniging (Nationale Feestdag)',
                ],
            ],
        ],
        'CN' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '110',
                'ambulance' => '120',
                'fire' => '119',
            ],
            'religions' => ['none', 'traditional', 'buddhism', 'christianity', 'islam'],
            'national_day' => [
                'date' => '1949-10-01',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'CO' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'ambulance' => '125',
                'fire' => '119',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1810-07-20',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'CR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1821-09-15',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'CU' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '106',
                'ambulance' => '104',
                'fire' => '105',
            ],
            'religions' => ['christianity', 'none', 'traditional'],
            'national_day' => [
                'date' => '1959-01-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Triumph of the Revolution (Liberation Day)',
                    'de' => 'Triumph der Revolution (Tag der Befreiung)',
                    'nl' => 'De overwinning van de revolutie (Bevrijdingsdag)',
                ],
            ],
        ],
        'CV' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '132',
                'ambulance' => '130',
                'fire' => '131',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1975-07-05',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'CW' => [
            'territory_type' => 'dependent',
            'parent' => 'NL',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'ambulance' => '910 / 912',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1967-04-27',
                'year_known' => true,
                'name' => [
                    'en' => 'King\'s Day',
                    'de' => 'Königstag',
                    'nl' => 'Koningsdag',
                ],
            ],
        ],
        'CY' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '155',
                'fire' => '199',
            ],
            'religions' => ['christianity', 'islam', 'buddhism'],
            'national_day' => [
                'date' => '1960-10-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'CZ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '158',
                'ambulance' => '155',
                'fire' => '150',
            ],
            'religions' => ['none', 'christianity'],
            'national_day' => [
                'date' => '1918-10-28',
                'year_known' => true,
                'name' => [
                    'en' => 'Czechoslovak Founding Day',
                    'de' => 'Tag der Gründung der Tschechoslowakei',
                    'nl' => 'Dag van de Oprichting van Tsjechoslowakije',
                ],
            ],
        ],
        'DE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '110',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1990-10-03',
                'year_known' => true,
                'name' => [
                    'en' => 'German Unity Day',
                    'de' => 'Tag der Deutschen Einheit',
                    'nl' => 'Dag van de Duitse Eenheid',
                ],
            ],
        ],
        'DJ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '17',
                'ambulance' => '19',
                'fire' => '18',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1977-06-27',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'DK' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '1849-06-05',
                'year_known' => true,
                'name' => [
                    'en' => 'Constitution Day',
                    'de' => 'Tag der Verfassung',
                    'nl' => 'Dag van de Grondwet',
                ],
            ],
        ],
        'DM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1978-11-03',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'DO' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1844-02-27',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'DZ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '1548',
                'ambulance' => '14',
                'fire' => '14',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1962-07-05',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'EC' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'police' => '101',
                'ambulance' => '131',
                'fire' => '102',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1809-08-10',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (independence of Quito)',
                    'de' => 'Unabhängigkeitstag (Unabhängigkeit von Quito)',
                    'nl' => 'Onafhankelijkheidsdag (onafhankelijkheid van Quito)',
                ],
            ],
        ],
        'EE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['none', 'christianity'],
            'national_day' => [
                'date' => '1918-02-24',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'EG' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '122',
                'ambulance' => '123',
                'fire' => '180',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1952-07-23',
                'year_known' => true,
                'name' => [
                    'en' => 'Revolution Day',
                    'de' => 'Tag der Revolution',
                    'nl' => 'Dag van de Revolutie',
                ],
            ],
        ],
        'ER' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '113',
                'ambulance' => '114',
                'fire' => '116',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '1991-05-24',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'ES' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '091',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1492-10-12',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day (Hispanic Day)',
                    'de' => 'Nationalfeiertag (Tag der hispanischen Kultur)',
                    'nl' => 'Nationale feestdag (Hispanic Day)',
                ],
            ],
        ],
        'ET' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'police' => '991',
                'ambulance' => '907',
                'fire' => '939',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '1991-05-28',
                'year_known' => true,
                'name' => [
                    'en' => 'Derg Downfall Day',
                    'de' => 'Tag des Untergangs von Derg',
                    'nl' => 'Derg-ondergangdag',
                ],
            ],
        ],
        'FI' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1917-12-06',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'FJ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
                'police' => '917',
                'fire' => '910',
            ],
            'religions' => ['christianity', 'hinduism', 'islam'],
            'national_day' => [
                'date' => '1970-10-10',
                'year_known' => true,
                'name' => [
                    'en' => 'Fiji (Independence) Day',
                    'de' => 'Tag der Unabhängigkeit von Fidschi',
                    'nl' => 'Dag van de Onafhankelijkheid van Fiji',
                ],
            ],
        ],
        'FK' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1982-06-14',
                'year_known' => true,
                'name' => [
                    'en' => 'Liberation Day',
                    'de' => 'Tag der Befreiung',
                    'nl' => 'Bevrijdingsdag',
                ],
            ],
        ],
        'FM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1979-05-10',
                'year_known' => true,
                'name' => [
                    'en' => 'Constitution Day',
                    'de' => 'Tag der Verfassung',
                    'nl' => 'Dag van de Grondwet',
                ],
            ],
        ],
        'FO' => [
            'territory_type' => 'dependent',
            'parent' => 'DK',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1030-07-29',
                'year_known' => true,
                'name' => [
                    'en' => 'Olaifest (Olavsoka)',
                    'de' => 'Olaifest (Olavsoka)',
                    'nl' => 'Olaifest (Olavsoka)',
                ],
            ],
        ],
        'FR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
            'religions' => ['christianity', 'none', 'islam', 'buddhism', 'judaism'],
            'national_day' => [
                'date' => '1790-07-14',
                'year_known' => true,
                'name' => [
                    'en' => 'Fête de la Fédération',
                    'de' => 'Fête de la Fédération',
                    'nl' => 'Fête de la Fédération',
                ],
            ],
        ],
        'GA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '1730',
                'ambulance' => '1300',
                'fire' => '18',
            ],
            'religions' => ['christianity', 'islam', 'none', 'traditional'],
            'national_day' => [
                'date' => '1960-08-17',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'GB' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '999',
                'ambulance' => '999',
                'fire' => '999',
            ],
            'religions' => ['christianity', 'none', 'islam', 'hinduism'],
        ],
        'GD' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1974-02-07',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'GE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '1918-05-26',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'GF' => [
            'territory_type' => 'dependent',
            'parent' => 'FR',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
        ],
        'GG' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1945-05-09',
                'year_known' => true,
                'name' => [
                    'en' => 'Liberation Day',
                    'de' => 'Tag der Befreiung',
                    'nl' => 'Bevrijdingsdag',
                ],
            ],
        ],
        'GH' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '191',
                'ambulance' => '193',
                'fire' => '192',
            ],
            'religions' => ['christianity', 'islam', 'traditional', 'none'],
            'national_day' => [
                'date' => '1957-03-06',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'GI' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '199',
                'ambulance' => '190',
                'fire' => '190',
            ],
            'religions' => ['christianity', 'none', 'islam', 'judaism', 'hinduism'],
            'national_day' => [
                'date' => '1967-09-10',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'GL' => [
            'territory_type' => 'dependent',
            'parent' => 'DK',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'traditional'],
            'national_day' => [
                'date' => '2000-06-21',
                'year_known' => false,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'GM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '117',
                'ambulance' => '116',
                'fire' => '118',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1965-02-18',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'GN' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '117',
                'ambulance' => '18',
                'fire' => '442 / 020',
            ],
            'religions' => ['islam', 'christianity', 'none'],
            'national_day' => [
                'date' => '1958-10-02',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'GP' => [
            'territory_type' => 'dependent',
            'parent' => 'FR',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
        ],
        'GQ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '114',
                'ambulance' => '115',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '1968-10-12',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'GR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '100',
                'ambulance' => '166',
                'fire' => '199',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1821-03-25',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'GT' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '110',
                'ambulance' => '122 / 123',
                'fire' => '122 / 123',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1821-09-15',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'GU' => [
            'territory_type' => 'dependent',
            'parent' => 'US',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none', 'traditional', 'buddhism'],
        ],
        'GW' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '117',
                'ambulance' => '119',
                'fire' => '118',
            ],
            'religions' => ['islam', 'traditional', 'christianity'],
            'national_day' => [
                'date' => '1973-09-24',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'GY' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
                'ambulance' => '913',
                'fire' => '912',
            ],
            'religions' => ['christianity', 'hinduism', 'islam', 'none'],
            'national_day' => [
                'date' => '1970-02-23',
                'year_known' => true,
                'name' => [
                    'en' => 'Republic Day',
                    'de' => 'Tag der Republik',
                    'nl' => 'Dag van de Republiek',
                ],
            ],
        ],
        'HK' => [
            'territory_type' => 'special',
            'parent' => 'CN',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
            ],
            'religions' => ['buddhism', 'christianity', 'islam', 'hinduism'],
            'national_day' => [
                'date' => '1949-10-01',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'HN' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'ambulance' => '195',
                'fire' => '198',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1821-09-15',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'HR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '192',
                'ambulance' => '194',
                'fire' => '193',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1990-05-30',
                'year_known' => true,
                'name' => [
                    'en' => 'Statehood Day (National Day)',
                    'de' => 'Tag der Staatsgründung (Nationalfeiertag)',
                    'nl' => 'Dag van de Onafhankelijkheid (Nationale Feestdag)',
                ],
            ],
        ],
        'HT' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '114',
                'ambulance' => '116',
                'fire' => '115',
            ],
            'religions' => ['christianity', 'none', 'traditional'],
            'national_day' => [
                'date' => '1804-01-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'HU' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '107',
                'ambulance' => '104',
                'fire' => '105',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1083-08-20',
                'year_known' => true,
                'name' => [
                    'en' => 'Saint Stephen\'s Day',
                    'de' => 'Stephanstag',
                    'nl' => 'Sint-Stefanusdag',
                ],
            ],
        ],
        'ID' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '110',
                'ambulance' => '118',
                'fire' => '113',
            ],
            'religions' => ['islam', 'christianity', 'hinduism'],
            'national_day' => [
                'date' => '1945-08-17',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'IE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '2000-03-17',
                'year_known' => false,
                'name' => [
                    'en' => 'Saint Patrick\'s Day',
                    'de' => 'St. Patrick\'s Day',
                    'nl' => 'Sint-Patricksdag',
                ],
            ],
        ],
        'IL' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '100',
                'ambulance' => '101',
                'fire' => '102',
            ],
            'religions' => ['judaism', 'islam', 'christianity', 'traditional'],
            'national_day' => [
                'date' => '1948-05-14',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'IM' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1417-07-05',
                'year_known' => true,
                'name' => [
                    'en' => 'Tynwald Day',
                    'de' => 'Tynwald-Tag',
                    'nl' => 'Tynwald-dag',
                ],
            ],
        ],
        'IN' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '100',
                'ambulance' => '108',
            ],
            'religions' => ['hinduism', 'islam', 'christianity', 'sikhism'],
            'national_day' => [
                'date' => '1950-01-26',
                'year_known' => true,
                'name' => [
                    'en' => 'Republic Day',
                    'de' => 'Tag der Republik',
                    'nl' => 'Dag van de Republiek',
                ],
            ],
        ],
        'IQ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '104',
                'ambulance' => '122',
                'fire' => '115',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1932-10-03',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'IR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '110',
                'ambulance' => '115',
                'fire' => '125',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1979-04-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Republic Day',
                    'de' => 'Tag der Republik',
                    'nl' => 'Dag van de Republiek',
                ],
            ],
        ],
        'IS' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1944-06-17',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'IT' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1946-06-02',
                'year_known' => true,
                'name' => [
                    'en' => 'Republic Day',
                    'de' => 'Tag der Republik',
                    'nl' => 'Dag van de Republiek',
                ],
            ],
        ],
        'JE' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1945-05-09',
                'year_known' => true,
                'name' => [
                    'en' => 'Liberation Day',
                    'de' => 'Tag der Befreiung',
                    'nl' => 'Bevrijdingsdag',
                ],
            ],
        ],
        'JM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '119',
                'ambulance' => '110',
                'fire' => '110',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1962-08-06',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'JO' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1946-05-25',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'JP' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'police' => '110',
                'ambulance' => '119',
                'fire' => '119',
            ],
            'religions' => ['shinto', 'buddhism', 'christianity'],
            'national_day' => [
                'date' => '1960-02-23',
                'year_known' => true,
                'name' => [
                    'en' => 'Birthday of Emperor Naruhito',
                    'de' => 'Geburtstag von Kaiser Naruhito',
                    'nl' => 'Verjaardag van keizer Naruhito',
                ],
            ],
        ],
        'KE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'islam', 'none'],
            'national_day' => [
                'date' => '1963-12-12',
                'year_known' => true,
                'name' => [
                    'en' => 'Jamhuri Day (Independence Day)',
                    'de' => 'Jamhuri-Tag (Unabhängigkeitstag)',
                    'nl' => 'Jamhuri-dag (Onafhankelijkheidsdag)',
                ],
            ],
        ],
        'KG' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1991-08-31',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'KH' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '117',
                'ambulance' => '119',
                'fire' => '118',
            ],
            'religions' => ['buddhism', 'islam'],
            'national_day' => [
                'date' => '1953-11-09',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'KI' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'police' => '100',
                'ambulance' => '100',
                'fire' => '100',
            ],
            'religions' => ['christianity', 'bahai'],
            'national_day' => [
                'date' => '1979-07-12',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'KM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '17',
                'ambulance' => '772 / 03',
                'fire' => '18',
            ],
            'religions' => ['islam', 'traditional'],
            'national_day' => [
                'date' => '1975-07-06',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'KN' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
            ],
            'national_day' => [
                'date' => '1983-09-19',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'KP' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'religions' => ['buddhism', 'christianity'],
            'national_day' => [
                'date' => '1948-09-09',
                'year_known' => true,
                'name' => [
                    'en' => 'Founding of the Democratic People\'s Republic of Korea (Dprk)',
                    'de' => 'Gründung der Demokratischen Volksrepublik Korea (DVRK)',
                    'nl' => 'Oprichting van de Democratische Volksrepubliek Korea (DVK)',
                ],
            ],
        ],
        'KR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '112',
                'ambulance' => '119',
                'fire' => '119',
            ],
            'religions' => ['none', 'christianity', 'buddhism'],
            'national_day' => [
                'date' => '1945-08-15',
                'year_known' => true,
                'name' => [
                    'en' => 'Liberation Day',
                    'de' => 'Tag der Befreiung',
                    'nl' => 'Bevrijdingsdag',
                ],
            ],
        ],
        'KW' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1950-02-25',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'KY' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none', 'hinduism'],
        ],
        'KZ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '102',
                'ambulance' => '103',
                'fire' => '101',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1991-12-16',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'LA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '191',
                'ambulance' => '195',
                'fire' => '190',
            ],
            'religions' => ['buddhism', 'none', 'christianity'],
            'national_day' => [
                'date' => '1975-12-02',
                'year_known' => true,
                'name' => [
                    'en' => 'Republic Day (National Day)',
                    'de' => 'Tag der Republik (Nationalfeiertag)',
                    'nl' => 'Dag van de Republiek (Nationale Feestdag)',
                ],
            ],
        ],
        'LB' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '160',
                'ambulance' => '140',
                'fire' => '175',
            ],
            'religions' => ['islam', 'christianity', 'traditional'],
            'national_day' => [
                'date' => '1943-11-22',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'LC' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'police' => '911',
                'ambulance' => '911',
                'fire' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1979-02-22',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'LI' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '117',
                'ambulance' => '144',
                'fire' => '118',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1940-08-15',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'LK' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'police' => '119',
                'ambulance' => '110',
                'fire' => '110',
            ],
            'religions' => ['buddhism', 'hinduism', 'islam', 'christianity'],
            'national_day' => [
                'date' => '1948-02-04',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (National Day)',
                    'de' => 'Unabhängigkeitstag (Nationalfeiertag)',
                    'nl' => 'Onafhankelijkheidsdag (Nationale Feestdag)',
                ],
            ],
        ],
        'LR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'islam', 'none'],
            'national_day' => [
                'date' => '1847-07-26',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'LS' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '123',
                'ambulance' => '121',
                'fire' => '122',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1966-10-04',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'LT' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '022',
                'ambulance' => '033',
                'fire' => '011',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1918-02-16',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (or National Day)',
                    'de' => 'Unabhängigkeitstag (oder Nationalfeiertag)',
                    'nl' => 'Onafhankelijkheidsdag (of Nationale Feestdag)',
                ],
            ],
        ],
        'LU' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '113',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '2000-06-23',
                'year_known' => false,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'LV' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '110',
                'ambulance' => '113',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1918-11-18',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'LY' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '1515',
                'ambulance' => '1515',
                'fire' => '1515',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '2011-10-23',
                'year_known' => true,
                'name' => [
                    'en' => 'Liberation Day',
                    'de' => 'Tag der Befreiung',
                    'nl' => 'Bevrijdingsdag',
                ],
            ],
        ],
        'MA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '19',
                'ambulance' => '15',
                'fire' => '15',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1999-07-30',
                'year_known' => true,
                'name' => [
                    'en' => 'Throne Day',
                    'de' => 'Thronfest',
                    'nl' => 'Troondag',
                ],
            ],
        ],
        'MC' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'ambulance' => '18',
                'fire' => '18',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1857-11-19',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day (Saint Rainier\'s Day)',
                    'de' => 'Nationalfeiertag (Tag des Heiligen Rainier)',
                    'nl' => 'Nationale feestdag (Sint-Rainiersdag)',
                ],
            ],
        ],
        'MD' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1991-08-27',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'ME' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '122',
                'ambulance' => '124',
                'fire' => '123',
            ],
            'religions' => ['christianity', 'islam', 'none'],
            'national_day' => [
                'date' => '1878-07-13',
                'year_known' => true,
                'name' => [
                    'en' => 'Statehood Day',
                    'de' => 'Tag der Staatsgründung',
                    'nl' => 'Dag van de Staatsvorming',
                ],
            ],
        ],
        'MF' => [
            'territory_type' => 'dependent',
            'parent' => 'FR',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
            'religions' => ['christianity', 'hinduism'],
            'national_day' => [
                'date' => '1790-07-14',
                'year_known' => true,
                'name' => [
                    'en' => 'Fête de la Fédération',
                    'de' => 'Fête de la Fédération',
                    'nl' => 'Fête de la Fédération',
                ],
            ],
        ],
        'MG' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '117',
                'ambulance' => '124',
                'fire' => '118',
            ],
            'religions' => ['christianity', 'none', 'traditional', 'islam'],
            'national_day' => [
                'date' => '1960-06-26',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'MH' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1979-05-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Constitution Day',
                    'de' => 'Tag der Verfassung',
                    'nl' => 'Dag van de Grondwet',
                ],
            ],
        ],
        'MK' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '192',
                'ambulance' => '194',
                'fire' => '193',
            ],
            'religions' => ['christianity', 'islam', 'none'],
            'national_day' => [
                'date' => '1991-09-08',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'ML' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
            'religions' => ['islam', 'christianity', 'none'],
            'national_day' => [
                'date' => '1960-09-22',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'MM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '999',
                'police' => '199',
                'ambulance' => '192',
                'fire' => '191',
            ],
            'religions' => ['buddhism', 'christianity', 'islam'],
            'national_day' => [
                'date' => '1948-01-04',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'MN' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '105',
                'ambulance' => '105',
                'fire' => '105',
            ],
            'religions' => ['buddhism', 'none', 'islam', 'christianity'],
            'national_day' => [
                'date' => '2000-07-11',
                'year_known' => false,
                'name' => [
                    'en' => 'Naadam (games) holiday',
                    'de' => 'Naadam (Spiele)-Feiertag',
                    'nl' => 'Naadam (spelen) - feestdag',
                ],
            ],
        ],
        'MO' => [
            'territory_type' => 'special',
            'parent' => 'CN',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'police' => '993',
            ],
            'religions' => ['traditional', 'buddhism', 'none', 'christianity'],
            'national_day' => [
                'date' => '1949-10-01',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'MP' => [
            'territory_type' => 'dependent',
            'parent' => 'US',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1978-01-08',
                'year_known' => true,
                'name' => [
                    'en' => 'Commonwealth Day',
                    'de' => 'Tag des Commonwealth',
                    'nl' => 'Dag van het Gemenebest',
                ],
            ],
        ],
        'MQ' => [
            'territory_type' => 'dependent',
            'parent' => 'FR',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
        ],
        'MR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '117',
                'ambulance' => '101',
                'fire' => '118',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1960-11-28',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'MS' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'police' => '911',
                'ambulance' => '911',
                'fire' => '911',
            ],
            'religions' => ['christianity', 'none', 'hinduism'],
        ],
        'MT' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1964-09-21',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'MU' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '999',
                'ambulance' => '114',
                'fire' => '115',
            ],
            'religions' => ['hinduism', 'christianity', 'islam'],
            'national_day' => [
                'date' => '1968-03-12',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence and Republic Day',
                    'de' => 'Tag der Unabhängigkeit und Tag der Republik',
                    'nl' => 'Onafhankelijkheidsdag en Dag van de Republiek',
                ],
            ],
        ],
        'MV' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
                'police' => '119',
                'ambulance' => '100',
                'fire' => '118',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1965-07-26',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'MW' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'police' => '997',
                'ambulance' => '998',
            ],
            'religions' => ['christianity', 'islam', 'none', 'traditional'],
            'national_day' => [
                'date' => '1964-07-06',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'MX' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1810-09-16',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'MY' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
            ],
            'religions' => ['islam', 'buddhism', 'christianity', 'hinduism'],
            'national_day' => [
                'date' => '1957-08-31',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (or Merdeka Day)',
                    'de' => 'Tag der Unabhängigkeit (oder Merdeka-Tag)',
                    'nl' => 'Onafhankelijkheidsdag (of Merdeka-dag)',
                ],
            ],
        ],
        'MZ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'police' => '119',
                'ambulance' => '117',
                'fire' => '198',
            ],
            'religions' => ['christianity', 'islam', 'none'],
            'national_day' => [
                'date' => '1975-06-25',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'NA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '111',
                'police' => '10',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1990-03-21',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'NC' => [
            'territory_type' => 'dependent',
            'parent' => 'FR',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1790-07-14',
                'year_known' => true,
                'name' => [
                    'en' => 'Fête de la Fédération',
                    'de' => 'Fête de la Fédération',
                    'nl' => 'Fête de la Fédération',
                ],
            ],
        ],
        'NE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
            'religions' => ['islam', 'traditional'],
            'national_day' => [
                'date' => '1958-12-18',
                'year_known' => true,
                'name' => [
                    'en' => 'Republic Day',
                    'de' => 'Tag der Republik',
                    'nl' => 'Dag van de Republiek',
                ],
            ],
        ],
        'NF' => [
            'territory_type' => 'dependent',
            'parent' => 'AU',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '000',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1856-06-08',
                'year_known' => true,
                'name' => [
                    'en' => 'Bounty Day',
                    'de' => 'Tag der Belohnung',
                    'nl' => 'Bounty-dag',
                ],
            ],
        ],
        'NG' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1960-10-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (National Day)',
                    'de' => 'Unabhängigkeitstag (Nationalfeiertag)',
                    'nl' => 'Onafhankelijkheidsdag (Nationale Feestdag)',
                ],
            ],
        ],
        'NI' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '118',
                'ambulance' => '128',
                'fire' => '115',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1821-09-15',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'NL' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['none', 'christianity', 'islam'],
            'national_day' => [
                'date' => '1967-04-27',
                'year_known' => true,
                'name' => [
                    'en' => 'King\'s Day',
                    'de' => 'Königstag',
                    'nl' => 'Koningsdag',
                ],
            ],
        ],
        'NO' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'ambulance' => '113',
                'fire' => '110',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '1814-05-17',
                'year_known' => true,
                'name' => [
                    'en' => 'Constitution Day',
                    'de' => 'Tag der Verfassung',
                    'nl' => 'Dag van de Grondwet',
                ],
            ],
        ],
        'NP' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'police' => '100',
                'ambulance' => '102',
                'fire' => '101',
            ],
            'religions' => ['hinduism', 'buddhism', 'islam', 'christianity'],
            'national_day' => [
                'date' => '2015-09-20',
                'year_known' => true,
                'name' => [
                    'en' => 'Constitution Day',
                    'de' => 'Tag der Verfassung',
                    'nl' => 'Dag van de Grondwet',
                ],
            ],
        ],
        'NR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '111',
                'police' => '110',
                'fire' => '112',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1968-01-31',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'NU' => [
            'territory_type' => 'dependent',
            'parent' => 'NZ',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'police' => '4333',
                'ambulance' => '4202',
                'fire' => '4133',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1840-02-06',
                'year_known' => true,
                'name' => [
                    'en' => 'Waitangi Day',
                    'de' => 'Waitangi-Tag',
                    'nl' => 'Waitangi-dag',
                ],
            ],
        ],
        'NZ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '111',
            ],
            'religions' => ['none', 'christianity', 'hinduism', 'buddhism'],
            'national_day' => [
                'date' => '1840-02-06',
                'year_known' => true,
                'name' => [
                    'en' => 'Waitangi Day',
                    'de' => 'Waitangi-Tag',
                    'nl' => 'Waitangi-dag',
                ],
            ],
        ],
        'OM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '9999',
            ],
            'religions' => ['islam', 'christianity', 'hinduism'],
            'national_day' => [
                'date' => '2000-11-18',
                'year_known' => false,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'PA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'police' => '104',
                'fire' => '103',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1903-11-03',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (Separation Day)',
                    'de' => 'Unabhängigkeitstag (Tag der Trennung)',
                    'nl' => 'Onafhankelijkheidsdag (Scheidingsdag)',
                ],
            ],
        ],
        'PE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'police' => '105',
                'fire' => '116',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1821-07-28',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'PF' => [
            'territory_type' => 'dependent',
            'parent' => 'FR',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1790-07-14',
                'year_known' => true,
                'name' => [
                    'en' => 'Fête de la Fédération',
                    'de' => 'Fête de la Fédération',
                    'nl' => 'Fête de la Fédération',
                ],
            ],
        ],
        'PG' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'ambulance' => '111',
                'fire' => '110',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1975-09-16',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'PH' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '1898-06-12',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'PK' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'police' => '15',
                'ambulance' => '115 / 1122',
                'fire' => '16',
            ],
            'religions' => ['islam', 'hinduism', 'christianity'],
            'national_day' => [
                'date' => '2000-03-23',
                'year_known' => false,
                'name' => [
                    'en' => 'Pakistan Day',
                    'de' => 'Pakistan-Tag',
                    'nl' => 'Pakistaanse Dag',
                ],
            ],
        ],
        'PL' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '997',
                'ambulance' => '999',
                'fire' => '998',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1791-05-03',
                'year_known' => true,
                'name' => [
                    'en' => 'Constitution Day',
                    'de' => 'Tag der Verfassung',
                    'nl' => 'Dag van de Grondwet',
                ],
            ],
        ],
        'PM' => [
            'territory_type' => 'dependent',
            'parent' => 'FR',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1790-07-14',
                'year_known' => true,
                'name' => [
                    'en' => 'Fête de la Fédération',
                    'de' => 'Fête de la Fédération',
                    'nl' => 'Fête de la Fédération',
                ],
            ],
        ],
        'PN' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'religions' => ['christianity'],
        ],
        'PR' => [
            'territory_type' => 'dependent',
            'parent' => 'US',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1776-07-04',
                'year_known' => true,
                'name' => [
                    'en' => 'US Independence Day',
                    'de' => 'US-Unabhängigkeitstag',
                    'nl' => 'Onafhankelijkheidsdag van de VS',
                ],
            ],
        ],
        'PS' => [
            'territory_type' => 'disputed',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '100',
                'ambulance' => '101',
                'fire' => '102',
            ],
        ],
        'PT' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1580-06-10',
                'year_known' => true,
                'name' => [
                    'en' => 'Portugal Day (Dia de Portugal)',
                    'de' => 'Portugals Tag (Dia de Portugal)',
                    'nl' => 'Dag van Portugal (Dia de Portugal)',
                ],
            ],
        ],
        'PW' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'traditional', 'islam'],
            'national_day' => [
                'date' => '1981-07-09',
                'year_known' => true,
                'name' => [
                    'en' => 'Constitution Day',
                    'de' => 'Tag der Verfassung',
                    'nl' => 'Dag van de Grondwet',
                ],
            ],
        ],
        'PY' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'police' => '912',
                'ambulance' => '141',
                'fire' => '132',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1811-05-14',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'QA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '999',
            ],
            'religions' => ['islam', 'hinduism', 'christianity', 'buddhism'],
            'national_day' => [
                'date' => '1878-12-18',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'RE' => [
            'territory_type' => 'dependent',
            'parent' => 'FR',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
        ],
        'RO' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1918-12-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Unification Day',
                    'de' => 'Tag der Wiedervereinigung',
                    'nl' => 'Dag van de Hereniging',
                ],
            ],
        ],
        'RS' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '192',
                'ambulance' => '194',
                'fire' => '193',
            ],
            'religions' => ['christianity', 'islam', 'none'],
            'national_day' => [
                'date' => '1835-02-15',
                'year_known' => true,
                'name' => [
                    'en' => 'Statehood Day',
                    'de' => 'Tag der Staatsgründung',
                    'nl' => 'Dag van de Staatsvorming',
                ],
            ],
        ],
        'RU' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '102',
                'ambulance' => '103',
                'fire' => '101',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '1990-06-12',
                'year_known' => true,
                'name' => [
                    'en' => 'Russia Day',
                    'de' => 'Russland-Tag',
                    'nl' => 'Dag van Rusland',
                ],
            ],
        ],
        'RW' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'ambulance' => '912',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1962-07-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'SA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'police' => '999',
                'ambulance' => '997',
                'fire' => '998',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1932-09-23',
                'year_known' => true,
                'name' => [
                    'en' => 'Saudi National Day',
                    'de' => 'Saudischer Nationalfeiertag',
                    'nl' => 'Nationale feestdag van Saoedi-Arabië',
                ],
            ],
        ],
        'SB' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'ambulance' => '111',
                'fire' => '988',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1978-07-07',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'SC' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '133',
                'ambulance' => '151',
            ],
            'religions' => ['christianity', 'hinduism', 'islam'],
            'national_day' => [
                'date' => '1993-06-18',
                'year_known' => true,
                'name' => [
                    'en' => 'Constitution Day',
                    'de' => 'Tag der Verfassung',
                    'nl' => 'Dag van de Grondwet',
                ],
            ],
        ],
        'SD' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '999',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1956-01-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'SE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1983-06-06',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'SG' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'ambulance' => '995',
                'fire' => '995',
            ],
            'religions' => ['buddhism', 'none', 'christianity', 'islam', 'taoism', 'hinduism'],
            'national_day' => [
                'date' => '1965-08-09',
                'year_known' => true,
                'name' => [
                    'en' => 'National Day',
                    'de' => 'Nationalfeiertag',
                    'nl' => 'Nationale feestdag',
                ],
            ],
        ],
        'SH' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
            ],
            'religions' => ['christianity', 'none'],
        ],
        'SI' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '113',
            ],
            'religions' => ['christianity', 'none', 'islam'],
            'national_day' => [
                'date' => '1991-06-25',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day/Statehood Day',
                    'de' => 'Unabhängigkeitstag/Tag der Staatsgründung',
                    'nl' => 'Onafhankelijkheidsdag/Dag van de staatsvorming',
                ],
            ],
        ],
        'SJ' => [
            'territory_type' => 'dependent',
            'parent' => 'NO',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
        ],
        'SK' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '158',
                'ambulance' => '155',
                'fire' => '150',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1992-09-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Constitution Day',
                    'de' => 'Tag der Verfassung',
                    'nl' => 'Dag van de Grondwet',
                ],
            ],
        ],
        'SL' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '999',
                'police' => '019',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1961-04-27',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'SM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '113',
                'ambulance' => '118',
                'fire' => '115',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '2000-09-03',
                'year_known' => false,
                'name' => [
                    'en' => 'Founding of the Republic',
                    'de' => 'Gründung der Republik',
                    'nl' => 'De oprichting van de Republiek',
                ],
            ],
        ],
        'SN' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '17',
                'ambulance' => '18',
                'fire' => '1515',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1960-04-04',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'SO' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '999',
                'police' => '888',
                'fire' => '555',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1960-07-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Foundation of the Somali Republic',
                    'de' => 'Gründung der Republik Somalia',
                    'nl' => 'Oprichting van de Republiek Somalië',
                ],
            ],
        ],
        'SR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '115',
                'ambulance' => '115',
                'fire' => '115',
            ],
            'religions' => ['christianity', 'hinduism', 'islam', 'none'],
            'national_day' => [
                'date' => '1975-11-25',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'SS' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '999',
            ],
            'religions' => ['christianity', 'traditional', 'islam'],
            'national_day' => [
                'date' => '2011-07-09',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'ST' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1975-07-12',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'SV' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'ambulance' => '132',
                'fire' => '913',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1821-09-15',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'SX' => [
            'territory_type' => 'dependent',
            'parent' => 'NL',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'ambulance' => '912',
                'fire' => '919',
            ],
            'religions' => ['christianity', 'none', 'hinduism', 'islam'],
            'national_day' => [
                'date' => '1967-04-27',
                'year_known' => true,
                'name' => [
                    'en' => 'King\'s Day',
                    'de' => 'Königstag',
                    'nl' => 'Koningsdag',
                ],
            ],
        ],
        'SY' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'ambulance' => '110',
                'fire' => '113',
            ],
            'religions' => ['islam', 'christianity', 'traditional'],
            'national_day' => [
                'date' => '1946-04-17',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (Evacuation Day)',
                    'de' => 'Unabhängigkeitstag (Evakuierungstag)',
                    'nl' => 'Onafhankelijkheidsdag (Evacuatiedag)',
                ],
            ],
        ],
        'SZ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'ambulance' => '977',
                'fire' => '933',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '1968-09-06',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (Somhlolo Day)',
                    'de' => 'Unabhängigkeitstag (Somhlolo-Tag)',
                    'nl' => 'Onafhankelijkheidsdag (Somhlolo-dag)',
                ],
            ],
        ],
        'TC' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity'],
        ],
        'TD' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '17',
                'ambulance' => '2251 / 4242',
                'fire' => '18',
            ],
            'religions' => ['islam', 'christianity', 'none'],
            'national_day' => [
                'date' => '1960-08-11',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'TG' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '117',
                'ambulance' => '8200',
                'fire' => '118',
            ],
            'religions' => ['christianity', 'traditional', 'islam', 'none'],
            'national_day' => [
                'date' => '1960-04-27',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'TH' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'police' => '191',
                'ambulance' => '1669',
                'fire' => '199',
            ],
            'religions' => ['buddhism', 'islam', 'christianity'],
            'national_day' => [
                'date' => '1952-07-28',
                'year_known' => true,
                'name' => [
                    'en' => 'Birthday of King Wachiralongkon',
                    'de' => 'Geburtstag von König Wachiralongkon',
                    'nl' => 'Verjaardag van koning Wachiralongkon',
                ],
            ],
        ],
        'TJ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '102',
                'ambulance' => '103',
                'fire' => '101',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1991-09-09',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (or National Day)',
                    'de' => 'Unabhängigkeitstag (oder Nationalfeiertag)',
                    'nl' => 'Onafhankelijkheidsdag (of Nationale Feestdag)',
                ],
            ],
        ],
        'TK' => [
            'territory_type' => 'dependent',
            'parent' => 'NZ',
            'driving_side' => 'left',
            'emergency' => [
                'police' => '2116',
                'ambulance' => '2112',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1840-02-06',
                'year_known' => true,
                'name' => [
                    'en' => 'Waitangi Day',
                    'de' => 'Waitangi-Tag',
                    'nl' => 'Waitangi-dag',
                ],
            ],
        ],
        'TL' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '2002-05-20',
                'year_known' => true,
                'name' => [
                    'en' => 'Restoration of Independence Day',
                    'de' => 'Wiederherstellung des Unabhängigkeitstags',
                    'nl' => 'Herstel van Onafhankelijkheidsdag',
                ],
            ],
        ],
        'TM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '02',
                'ambulance' => '03',
                'fire' => '01',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1991-10-27',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'TN' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '197',
                'ambulance' => '190',
                'fire' => '198',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1956-03-20',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'TO' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
                'police' => '922',
                'ambulance' => '933',
                'fire' => '999',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1959-07-04',
                'year_known' => true,
                'name' => [
                    'en' => 'Official birthday of King Tupou VI',
                    'de' => 'Offizieller Geburtstag von König Tupou VI.',
                    'nl' => 'Officiële verjaardag van koning Tupou VI',
                ],
            ],
        ],
        'TR' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '155',
                'fire' => '110',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1923-10-29',
                'year_known' => true,
                'name' => [
                    'en' => 'Republic Day',
                    'de' => 'Tag der Republik',
                    'nl' => 'Dag van de Republiek',
                ],
            ],
        ],
        'TT' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'ambulance' => '811',
                'fire' => '990',
            ],
            'religions' => ['christianity', 'hinduism', 'islam', 'none'],
            'national_day' => [
                'date' => '1962-08-31',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'TV' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
                'ambulance' => '999',
                'fire' => '000',
            ],
            'religions' => ['christianity', 'bahai'],
            'national_day' => [
                'date' => '1978-10-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'TW' => [
            'territory_type' => 'special',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '110',
                'ambulance' => '119',
                'fire' => '119',
            ],
            'religions' => ['buddhism', 'taoism', 'traditional', 'christianity'],
            'national_day' => [
                'date' => '1911-10-10',
                'year_known' => true,
                'name' => [
                    'en' => 'Republic Day (National Day)',
                    'de' => 'Tag der Republik (Nationalfeiertag)',
                    'nl' => 'Dag van de Republiek (Nationale Feestdag)',
                ],
            ],
        ],
        'TZ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '999',
                'ambulance' => '114',
                'fire' => '115',
            ],
            'religions' => ['christianity', 'islam', 'traditional'],
            'national_day' => [
                'date' => '1964-04-26',
                'year_known' => true,
                'name' => [
                    'en' => 'Union Day (Tanganyika and Zanzibar)',
                    'de' => 'Tag der Vereinigung (Tanganjika und Sansibar)',
                    'nl' => 'Dag van de Unie (Tanganyika en Zanzibar)',
                ],
            ],
        ],
        'UA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '102',
                'ambulance' => '103',
                'fire' => '101',
            ],
            'religions' => ['christianity', 'islam', 'judaism'],
            'national_day' => [
                'date' => '1991-08-24',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'UG' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '999',
                'ambulance' => '911',
                'fire' => '999',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '1962-10-09',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'US' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none', 'judaism'],
            'national_day' => [
                'date' => '1776-07-04',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'UY' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'police' => '109',
                'ambulance' => '105',
                'fire' => '104',
            ],
            'religions' => ['none', 'christianity'],
            'national_day' => [
                'date' => '1825-08-25',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'UZ' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '102',
                'ambulance' => '103',
                'fire' => '101',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '1991-09-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'VA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
            ],
            'religions' => ['christianity'],
        ],
        'VC' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '999',
                'ambulance' => '999',
                'fire' => '999',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1979-10-27',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'VE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '911',
                'police' => '171',
                'ambulance' => '171',
                'fire' => '171',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1811-07-05',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'VG' => [
            'territory_type' => 'dependent',
            'parent' => 'GB',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'police' => '311',
                'ambulance' => '911',
                'fire' => '911',
            ],
            'religions' => ['christianity', 'none', 'hinduism'],
            'national_day' => [
                'date' => '1956-07-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Territory Day',
                    'de' => 'Tag des Territoriums',
                    'nl' => 'Dag van het Grondgebied',
                ],
            ],
        ],
        'VI' => [
            'territory_type' => 'dependent',
            'parent' => 'US',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '911',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1917-03-31',
                'year_known' => true,
                'name' => [
                    'en' => 'Transfer Day',
                    'de' => 'Wechseltag',
                    'nl' => 'Overdrachtsdag',
                ],
            ],
        ],
        'VN' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '113',
                'ambulance' => '115',
                'fire' => '114',
            ],
            'religions' => ['none', 'christianity', 'buddhism'],
            'national_day' => [
                'date' => '1945-09-02',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day (National Day)',
                    'de' => 'Unabhängigkeitstag (Nationalfeiertag)',
                    'nl' => 'Onafhankelijkheidsdag (Nationale Feestdag)',
                ],
            ],
        ],
        'VU' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '111',
                'ambulance' => '112',
                'fire' => '113',
            ],
            'religions' => ['christianity', 'traditional', 'none'],
            'national_day' => [
                'date' => '1980-07-30',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'WF' => [
            'territory_type' => 'dependent',
            'parent' => 'FR',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '15',
                'ambulance' => '15',
                'fire' => '15',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1790-07-14',
                'year_known' => true,
                'name' => [
                    'en' => 'Fête de la Fédération',
                    'de' => 'Fête de la Fédération',
                    'nl' => 'Fête de la Fédération',
                ],
            ],
        ],
        'WS' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'police' => '995',
                'ambulance' => '996',
                'fire' => '994',
            ],
            'religions' => ['christianity'],
            'national_day' => [
                'date' => '1962-06-01',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day Celebration',
                    'de' => 'Feier zum Unabhängigkeitstag',
                    'nl' => 'Viering van Onafhankelijkheidsdag',
                ],
            ],
        ],
        'XK' => [
            'territory_type' => 'disputed',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '192',
                'ambulance' => '194',
                'fire' => '193',
            ],
            'religions' => ['islam', 'christianity'],
            'national_day' => [
                'date' => '2008-02-17',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'YE' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'right',
            'emergency' => [
                'police' => '194',
                'ambulance' => '191',
                'fire' => '191',
            ],
            'religions' => ['islam'],
            'national_day' => [
                'date' => '1990-05-22',
                'year_known' => true,
                'name' => [
                    'en' => 'Unification Day',
                    'de' => 'Tag der Wiedervereinigung',
                    'nl' => 'Dag van de Hereniging',
                ],
            ],
        ],
        'YT' => [
            'territory_type' => 'dependent',
            'parent' => 'FR',
            'driving_side' => 'right',
            'emergency' => [
                'general' => '112',
                'police' => '17',
                'ambulance' => '15',
                'fire' => '18',
            ],
        ],
        'ZA' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '112',
                'police' => '10111',
                'ambulance' => '10177',
                'fire' => '10177',
            ],
            'religions' => ['christianity', 'islam'],
            'national_day' => [
                'date' => '1994-04-27',
                'year_known' => true,
                'name' => [
                    'en' => 'Freedom Day',
                    'de' => 'Tag der Freiheit',
                    'nl' => 'Dag van de Vrijheid',
                ],
            ],
        ],
        'ZM' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'police' => '911',
                'ambulance' => '992',
                'fire' => '993',
            ],
            'religions' => ['christianity', 'none'],
            'national_day' => [
                'date' => '1964-10-24',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
        'ZW' => [
            'territory_type' => 'sovereign',
            'driving_side' => 'left',
            'emergency' => [
                'general' => '999',
                'police' => '995',
                'ambulance' => '994',
                'fire' => '993',
            ],
            'religions' => ['christianity', 'none', 'traditional'],
            'national_day' => [
                'date' => '1980-04-18',
                'year_known' => true,
                'name' => [
                    'en' => 'Independence Day',
                    'de' => 'Tag der Unabhängigkeit',
                    'nl' => 'Onafhankelijkheidsdag',
                ],
            ],
        ],
    ];
};
