<?php

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;

/**
 * Bemerkung zum Strom fuer Laender mit mehreren Netzspannungen (Wikidata
 * "mains voltage") sowie Japan mit zwei Netzfrequenzen – in Deutsch,
 * Englisch und Niederlaendisch. Es wird nur gefuellt, was leer ist.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Country::query()->withTrashed()->get() as $country) {
            $notes = self::NOTES[strtoupper((string) $country->iso_code)] ?? null;

            if (! $notes) {
                continue;
            }

            $info = is_array($country->travel_info) ? $country->travel_info : [];

            if (! empty($info['power_notes'])) {
                continue;
            }

            $info['power_notes'] = $notes;
            $country->forceFill(['travel_info' => $info])->saveQuietly();
        }
    }

    public function down(): void
    {
        // Die Bemerkung laesst sich von Hand aendern – ein Zuruecknehmen wuerde auch das treffen.
    }

    /** @var array<string, array<string, string>> ISO-Code => Bemerkung je Sprache */
    private const NOTES = [
        'BO' => [
            'de' => 'Im Land sind mehrere Netzspannungen üblich (115 V / 230 V), je nach Region oder Gebäude. Hinterlegt ist 230 V – vor Ort prüfen, welche Spannung die Steckdose liefert.',
            'en' => 'Several mains voltages are in use (115 V / 230 V), depending on region or building. 230 V is recorded here – check locally which voltage the socket supplies.',
            'nl' => 'Er zijn meerdere netspanningen in gebruik (115 V / 230 V), afhankelijk van regio of gebouw. Hier is 230 V vastgelegd – controleer ter plaatse welke spanning het stopcontact levert.',
        ],
        'BR' => [
            'de' => 'Im Land sind mehrere Netzspannungen üblich (127 V / 220 V), je nach Region oder Gebäude. Hinterlegt ist 220 V – vor Ort prüfen, welche Spannung die Steckdose liefert. Die Netzfrequenz ist 60 Hz.',
            'en' => 'Several mains voltages are in use (127 V / 220 V), depending on region or building. 220 V is recorded here – check locally which voltage the socket supplies.',
            'nl' => 'Er zijn meerdere netspanningen in gebruik (127 V / 220 V), afhankelijk van regio of gebouw. Hier is 220 V vastgelegd – controleer ter plaatse welke spanning het stopcontact levert.',
        ],
        'ID' => [
            'de' => 'Im Land sind mehrere Netzspannungen üblich (127 V / 230 V), je nach Region oder Gebäude. Hinterlegt ist 230 V – vor Ort prüfen, welche Spannung die Steckdose liefert.',
            'en' => 'Several mains voltages are in use (127 V / 230 V), depending on region or building. 230 V is recorded here – check locally which voltage the socket supplies.',
            'nl' => 'Er zijn meerdere netspanningen in gebruik (127 V / 230 V), afhankelijk van regio of gebouw. Hier is 230 V vastgelegd – controleer ter plaatse welke spanning het stopcontact levert.',
        ],
        'JP' => [
            'de' => 'Netzspannung landesweit 100 V. Die Frequenz unterscheidet sich: 50 Hz im Osten (Tokio, Hokkaido), 60 Hz im Westen (Osaka, Kyoto, Kyushu). Geräte mit Motor oder Uhr können davon betroffen sein.',
            'en' => 'Mains voltage is 100 V nationwide. The frequency differs: 50 Hz in the east (Tokyo, Hokkaido), 60 Hz in the west (Osaka, Kyoto, Kyushu). Devices with motors or clocks may be affected.',
            'nl' => 'De netspanning is landelijk 100 V. De frequentie verschilt: 50 Hz in het oosten (Tokio, Hokkaido), 60 Hz in het westen (Osaka, Kioto, Kyushu). Apparaten met een motor of klok kunnen hier last van hebben.',
        ],
        'KP' => [
            'de' => 'Im Land sind mehrere Netzspannungen üblich (110 V / 220 V), je nach Region oder Gebäude. Hinterlegt ist 220 V – vor Ort prüfen, welche Spannung die Steckdose liefert.',
            'en' => 'Several mains voltages are in use (110 V / 220 V), depending on region or building. 220 V is recorded here – check locally which voltage the socket supplies.',
            'nl' => 'Er zijn meerdere netspanningen in gebruik (110 V / 220 V), afhankelijk van regio of gebouw. Hier is 220 V vastgelegd – controleer ter plaatse welke spanning het stopcontact levert.',
        ],
        'LR' => [
            'de' => 'Im Land sind mehrere Netzspannungen üblich (120 V / 220 V), je nach Region oder Gebäude. Hinterlegt ist 220 V – vor Ort prüfen, welche Spannung die Steckdose liefert.',
            'en' => 'Several mains voltages are in use (120 V / 220 V), depending on region or building. 220 V is recorded here – check locally which voltage the socket supplies.',
            'nl' => 'Er zijn meerdere netspanningen in gebruik (120 V / 220 V), afhankelijk van regio of gebouw. Hier is 220 V vastgelegd – controleer ter plaatse welke spanning het stopcontact levert.',
        ],
        'LY' => [
            'de' => 'Im Land sind mehrere Netzspannungen üblich (127 V / 230 V), je nach Region oder Gebäude. Hinterlegt ist 230 V – vor Ort prüfen, welche Spannung die Steckdose liefert.',
            'en' => 'Several mains voltages are in use (127 V / 230 V), depending on region or building. 230 V is recorded here – check locally which voltage the socket supplies.',
            'nl' => 'Er zijn meerdere netspanningen in gebruik (127 V / 230 V), afhankelijk van regio of gebouw. Hier is 230 V vastgelegd – controleer ter plaatse welke spanning het stopcontact levert.',
        ],
        'MA' => [
            'de' => 'Im Land sind mehrere Netzspannungen üblich (127 V / 220 V), je nach Region oder Gebäude. Hinterlegt ist 220 V – vor Ort prüfen, welche Spannung die Steckdose liefert.',
            'en' => 'Several mains voltages are in use (127 V / 220 V), depending on region or building. 220 V is recorded here – check locally which voltage the socket supplies.',
            'nl' => 'Er zijn meerdere netspanningen in gebruik (127 V / 220 V), afhankelijk van regio of gebouw. Hier is 220 V vastgelegd – controleer ter plaatse welke spanning het stopcontact levert.',
        ],
        'MF' => [
            'de' => 'Im Land sind mehrere Netzspannungen üblich (120 V / 220 V), je nach Region oder Gebäude. Hinterlegt ist 220 V – vor Ort prüfen, welche Spannung die Steckdose liefert.',
            'en' => 'Several mains voltages are in use (120 V / 220 V), depending on region or building. 220 V is recorded here – check locally which voltage the socket supplies.',
            'nl' => 'Er zijn meerdere netspanningen in gebruik (120 V / 220 V), afhankelijk van regio of gebouw. Hier is 220 V vastgelegd – controleer ter plaatse welke spanning het stopcontact levert.',
        ],
        'MG' => [
            'de' => 'Im Land sind mehrere Netzspannungen üblich (127 V / 220 V), je nach Region oder Gebäude. Hinterlegt ist 220 V – vor Ort prüfen, welche Spannung die Steckdose liefert.',
            'en' => 'Several mains voltages are in use (127 V / 220 V), depending on region or building. 220 V is recorded here – check locally which voltage the socket supplies.',
            'nl' => 'Er zijn meerdere netspanningen in gebruik (127 V / 220 V), afhankelijk van regio of gebouw. Hier is 220 V vastgelegd – controleer ter plaatse welke spanning het stopcontact levert.',
        ],
    ];
};
