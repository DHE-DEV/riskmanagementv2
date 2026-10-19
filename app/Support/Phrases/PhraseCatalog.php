<?php

namespace App\Support\Phrases;

/**
 * Wichtige Saetze fuer Reisende (TripData-App, Laenderseite › Saetze): Ausgangstexte auf Deutsch, Englisch und
 * Niederlaendisch; die Uebersetzungen je Landessprache liegen in `language_phrases` (per KI erzeugt, Migration).
 */
class PhraseCatalog
{
    public const CATEGORIES = [
        'basics' => ['de' => 'Grundlagen', 'en' => 'Basics', 'nl' => 'Basis'],
        'travel' => ['de' => 'Unterwegs', 'en' => 'Getting around', 'nl' => 'Onderweg'],
        'food' => ['de' => 'Essen & Trinken', 'en' => 'Food & drink', 'nl' => 'Eten & drinken'],
        'health' => ['de' => 'Gesundheit', 'en' => 'Health', 'nl' => 'Gezondheid'],
        'emergency' => ['de' => 'Notfall', 'en' => 'Emergency', 'nl' => 'Noodgeval'],
        'numbers' => ['de' => 'Zahlen', 'en' => 'Numbers', 'nl' => 'Getallen'],
    ];

    /** Schluessel => [Kategorie, de, en, nl] */
    public const PHRASES = [
        'hello' => ['basics', 'Hallo', 'Hello', 'Hallo'],
        'goodbye' => ['basics', 'Auf Wiedersehen', 'Goodbye', 'Tot ziens'],
        'please' => ['basics', 'Bitte', 'Please', 'Alstublieft'],
        'thank_you' => ['basics', 'Danke', 'Thank you', 'Dank u wel'],
        'excuse_me' => ['basics', 'Entschuldigung', 'Excuse me', 'Pardon'],
        'yes' => ['basics', 'Ja', 'Yes', 'Ja'],
        'no' => ['basics', 'Nein', 'No', 'Nee'],
        'dont_understand' => ['basics', 'Ich verstehe nicht.', 'I don’t understand.', 'Ik begrijp het niet.'],
        'speak_english' => ['basics', 'Sprechen Sie Englisch?', 'Do you speak English?', 'Spreekt u Engels?'],
        'where_is' => ['travel', 'Wo ist …?', 'Where is …?', 'Waar is …?'],
        'toilet' => ['travel', 'Wo ist die Toilette?', 'Where is the toilet?', 'Waar is het toilet?'],
        'station' => ['travel', 'Wie komme ich zum Bahnhof?', 'How do I get to the train station?', 'Hoe kom ik bij het station?'],
        'airport' => ['travel', 'Zum Flughafen, bitte.', 'To the airport, please.', 'Naar het vliegveld, alstublieft.'],
        'how_much' => ['travel', 'Wie viel kostet das?', 'How much is this?', 'Hoeveel kost dit?'],
        'ticket' => ['travel', 'Eine Fahrkarte nach …, bitte.', 'One ticket to …, please.', 'Een kaartje naar …, alstublieft.'],
        'table' => ['food', 'Einen Tisch für zwei, bitte.', 'A table for two, please.', 'Een tafel voor twee, alstublieft.'],
        'menu' => ['food', 'Die Speisekarte, bitte.', 'The menu, please.', 'De menukaart, alstublieft.'],
        'water' => ['food', 'Ein Wasser, bitte.', 'A water, please.', 'Een water, alstublieft.'],
        'vegetarian' => ['food', 'Ich bin Vegetarier.', 'I am vegetarian.', 'Ik ben vegetariër.'],
        'bill' => ['food', 'Die Rechnung, bitte.', 'The bill, please.', 'De rekening, alstublieft.'],
        'delicious' => ['food', 'Das war sehr lecker.', 'That was delicious.', 'Dat was heerlijk.'],
        'allergy_nuts' => ['health', 'Ich bin allergisch gegen Nüsse.', 'I am allergic to nuts.', 'Ik ben allergisch voor noten.'],
        'allergy_gluten' => ['health', 'Ich vertrage kein Gluten.', 'I can’t eat gluten.', 'Ik verdraag geen gluten.'],
        'pharmacy' => ['health', 'Wo ist die nächste Apotheke?', 'Where is the nearest pharmacy?', 'Waar is de dichtstbijzijnde apotheek?'],
        'doctor' => ['health', 'Ich brauche einen Arzt.', 'I need a doctor.', 'Ik heb een dokter nodig.'],
        'help' => ['emergency', 'Hilfe!', 'Help!', 'Help!'],
        'police' => ['emergency', 'Rufen Sie die Polizei!', 'Call the police!', 'Bel de politie!'],
        'ambulance' => ['emergency', 'Rufen Sie einen Krankenwagen!', 'Call an ambulance!', 'Bel een ambulance!'],
        'lost' => ['emergency', 'Ich habe mich verlaufen.', 'I am lost.', 'Ik ben verdwaald.'],
        'stolen' => ['emergency', 'Mir wurde etwas gestohlen.', 'Something was stolen from me.', 'Er is iets van mij gestolen.'],
        'n1' => ['numbers', 'eins', 'one', 'een'],
        'n2' => ['numbers', 'zwei', 'two', 'twee'],
        'n3' => ['numbers', 'drei', 'three', 'drie'],
        'n4' => ['numbers', 'vier', 'four', 'vier'],
        'n5' => ['numbers', 'fünf', 'five', 'vijf'],
        'n6' => ['numbers', 'sechs', 'six', 'zes'],
        'n7' => ['numbers', 'sieben', 'seven', 'zeven'],
        'n8' => ['numbers', 'acht', 'eight', 'acht'],
        'n9' => ['numbers', 'neun', 'nine', 'negen'],
        'n10' => ['numbers', 'zehn', 'ten', 'tien'],
    ];

    public static function source(string $key, string $lang): string
    {
        $index = ['de' => 1, 'en' => 2, 'nl' => 3][$lang] ?? 1;

        return self::PHRASES[$key][$index];
    }
}
