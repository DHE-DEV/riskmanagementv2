<?php

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;

/**
 * Sprachen fuer die Laender, bei denen noch keine gepflegt waren (Stand Live-Server 19.10.2026): Amtssprachen und
 * die fuer Reisende wichtigen Verkehrssprachen, als ISO-639-Codes wie die vorhandenen Eintraege (zwei Buchstaben,
 * drei nur ohne Zwei-Buchstaben-Code, z. B. pap = Papiamentu). Grundlage Wikidata (P37), von Hand bereinigt.
 * Ueberschreibt keine gepflegten Sprachen.
 */
return new class extends Migration
{
    private const LANGUAGES = [
        'AE' => ['ar', 'en'], 'AG' => ['en'], 'AI' => ['en'], 'AO' => ['pt'], 'AS' => ['en', 'sm'], 'AW' => ['nl', 'pap'],
        'BB' => ['en'], 'BF' => ['fr', 'mos'], 'BI' => ['rn', 'fr', 'en'], 'BJ' => ['fr'], 'BL' => ['fr'], 'BM' => ['en'],
        'BO' => ['es', 'qu', 'ay', 'gn'], 'BQ' => ['nl', 'pap', 'en'], 'BS' => ['en'], 'BW' => ['en', 'tn'], 'BZ' => ['en', 'es'],
        'CD' => ['fr', 'ln', 'sw'], 'CF' => ['fr', 'sg'], 'CG' => ['fr', 'ln'], 'CI' => ['fr'], 'CK' => ['en', 'rar'],
        'CM' => ['fr', 'en'], 'CU' => ['es'], 'CV' => ['pt', 'kea'], 'CW' => ['nl', 'pap', 'en'], 'DJ' => ['fr', 'ar'],
        'DM' => ['en'], 'DO' => ['es'], 'ER' => ['ti', 'ar', 'en'], 'FK' => ['en'], 'FM' => ['en'], 'FO' => ['fo', 'da'],
        'GA' => ['fr'], 'GD' => ['en'], 'GF' => ['fr'], 'GG' => ['en', 'fr'], 'GI' => ['en', 'es'], 'GL' => ['kl', 'da'],
        'GM' => ['en'], 'GN' => ['fr'], 'GP' => ['fr'], 'GQ' => ['es', 'fr', 'pt'], 'GU' => ['en', 'ch'], 'GW' => ['pt'],
        'GY' => ['en'], 'HK' => ['yue', 'en'], 'HN' => ['es'], 'HT' => ['fr', 'ht'], 'IM' => ['en', 'gv'], 'JE' => ['en', 'fr'],
        'JM' => ['en'], 'KI' => ['en', 'gil'], 'KM' => ['ar', 'fr'], 'KN' => ['en'], 'KP' => ['ko'], 'KY' => ['en'],
        'LC' => ['en'], 'LR' => ['en'], 'LS' => ['st', 'en'], 'LY' => ['ar'], 'MF' => ['fr'], 'MG' => ['mg', 'fr'],
        'MH' => ['mh', 'en'], 'ML' => ['fr', 'bm'], 'MO' => ['yue', 'pt'], 'MP' => ['en', 'ch'], 'MQ' => ['fr'],
        'MR' => ['ar', 'fr'], 'MS' => ['en'], 'MU' => ['en', 'fr'], 'MW' => ['en', 'ny'], 'MZ' => ['pt'],
        'NA' => ['en', 'af', 'de'], 'NC' => ['fr'], 'NE' => ['fr', 'ha'], 'NF' => ['en'], 'NI' => ['es'], 'NR' => ['na', 'en'],
        'NU' => ['en', 'niu'], 'PA' => ['es'], 'PF' => ['fr', 'ty'], 'PM' => ['fr'], 'PN' => ['en'], 'PR' => ['es', 'en'],
        'PW' => ['en', 'pau'], 'PY' => ['es', 'gn'], 'RE' => ['fr'], 'RW' => ['rw', 'fr', 'en', 'sw'], 'SB' => ['en'],
        'SC' => ['fr', 'en', 'crs'], 'SD' => ['ar', 'en'], 'SH' => ['en'], 'SJ' => ['no'], 'SL' => ['en'], 'SN' => ['fr', 'wo'],
        'SO' => ['so', 'ar'], 'SR' => ['nl'], 'SS' => ['en'], 'ST' => ['pt'], 'SV' => ['es'], 'SX' => ['en', 'nl'],
        'SZ' => ['en', 'ss'], 'TC' => ['en'], 'TD' => ['fr', 'ar'], 'TG' => ['fr'], 'TK' => ['en', 'tkl'], 'TN' => ['ar', 'fr'],
        'TO' => ['to', 'en'], 'TT' => ['en'], 'TV' => ['en', 'tvl'], 'TZ' => ['sw', 'en'], 'UG' => ['en', 'sw'], 'UY' => ['es'],
        'VC' => ['en'], 'VG' => ['en'], 'VI' => ['en'], 'VU' => ['bi', 'en', 'fr'], 'WF' => ['fr'], 'WS' => ['sm', 'en'],
        'XK' => ['sq', 'sr'], 'YT' => ['fr'], 'ZM' => ['en'], 'ZW' => ['en', 'sn', 'nd'],
    ];

    public function up(): void
    {
        foreach (Country::whereIn('iso_code', array_keys(self::LANGUAGES))->get() as $country) {
            if (empty($country->languages)) {
                $country->languages = self::LANGUAGES[$country->iso_code];
                $country->saveQuietly();
            }
        }
    }

    public function down(): void
    {
        // Daten bleiben – sie lassen sich nicht von spaeter gepflegten unterscheiden.
    }
};
