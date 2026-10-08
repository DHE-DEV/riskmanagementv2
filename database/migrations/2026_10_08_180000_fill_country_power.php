<?php

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;

/**
 * Strom je Land: Steckertypen nach IEC, Netzspannung und Netzfrequenz.
 *
 * Quelle (Stand Oktober 2026): Wikidata – Eigenschaften "electrical plug
 * type" und "mains voltage" samt Frequenz. Bei mehreren Spannungen gilt
 * 230 V, sonst die hoechste im Bereich 200–250 V (etwa Brasilien 220 statt
 * 127). Gebiete ohne eigene Angabe uebernehmen die ihres Mutterlands.
 * Marshallinseln, Suedsudan und Vatikan sind von Hand ergaenzt.
 *
 * Es wird nur gefuellt, was leer ist.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Country::query()->withTrashed()->get() as $country) {
            $row = self::DATA[strtoupper((string) $country->iso_code)] ?? null;

            if (! $row) {
                continue;
            }

            $info = is_array($country->travel_info) ? $country->travel_info : [];

            foreach (['plug_types', 'voltage', 'frequency'] as $field) {
                if (empty($info[$field]) && isset($row[$field])) {
                    $info[$field] = $row[$field];
                }
            }

            if ($info !== ($country->travel_info ?? [])) {
                $country->forceFill(['travel_info' => $info])->saveQuietly();
            }
        }
    }

    public function down(): void
    {
        // Die Angaben lassen sich von Hand aendern – ein Zuruecknehmen wuerde auch das treffen.
    }

    /** @var array<string, array<string, mixed>> ISO-Code => Steckertypen, Spannung, Frequenz */
    private const DATA = [
        'AD' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'AE' => [
            'plug_types' => ['C', 'G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'AF' => [
            'plug_types' => ['C', 'F', 'G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'AG' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 230,
            'frequency' => 60,
        ],
        'AI' => [
            'plug_types' => ['A'],
            'voltage' => 110,
            'frequency' => 60,
        ],
        'AL' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'AM' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'AO' => [
            'plug_types' => ['C'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'AR' => [
            'plug_types' => ['C', 'I'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'AS' => [
            'plug_types' => ['A', 'B', 'F', 'I'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'AT' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'AU' => [
            'plug_types' => ['I'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'AW' => [
            'plug_types' => ['A', 'B', 'F'],
            'voltage' => 127,
            'frequency' => 60,
        ],
        'AZ' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'BA' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'BB' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 115,
            'frequency' => 50,
        ],
        'BD' => [
            'plug_types' => ['A', 'C', 'G', 'K'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'BE' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'BF' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'BG' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'BH' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'BI' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'BJ' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'BL' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'BM' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'BN' => [
            'plug_types' => ['G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'BO' => [
            'plug_types' => ['A', 'C'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'BQ' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'BR' => [
            'plug_types' => ['C', 'N'],
            'voltage' => 220,
            'frequency' => 60,
        ],
        'BS' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'BT' => [
            'plug_types' => ['C', 'D', 'F', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'BW' => [
            'plug_types' => ['D', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'BY' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'BZ' => [
            'plug_types' => ['A', 'B', 'G'],
            'voltage' => 220,
            'frequency' => 60,
        ],
        'CA' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'CD' => [
            'plug_types' => ['C', 'E', 'G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'CF' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'CG' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'CH' => [
            'plug_types' => ['C', 'J'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'CI' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'CK' => [
            'plug_types' => ['I'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'CL' => [
            'plug_types' => ['C', 'L'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'CM' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'CN' => [
            'plug_types' => ['A', 'C', 'I'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'CO' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 110,
            'frequency' => 60,
        ],
        'CR' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'CU' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 110,
            'frequency' => 60,
        ],
        'CV' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'CW' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
        ],
        'CY' => [
            'plug_types' => ['G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'CZ' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'DE' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'DJ' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'DK' => [
            'plug_types' => ['C', 'E', 'F', 'K'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'DM' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'DO' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 110,
            'frequency' => 60,
        ],
        'DZ' => [
            'plug_types' => ['C', 'E', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'EC' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'EE' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'EG' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'ER' => [
            'plug_types' => ['C', 'L'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'ES' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'ET' => [
            'plug_types' => ['C', 'E', 'F', 'G', 'J', 'L'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'FI' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'FJ' => [
            'plug_types' => ['I'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'FK' => [
            'plug_types' => ['G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'FM' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'FO' => [
            'plug_types' => ['C', 'E', 'F', 'K'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'FR' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'GA' => [
            'plug_types' => ['C'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'GB' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'GD' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'GE' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'GF' => [
            'plug_types' => ['C', 'E', 'G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'GG' => [
            'plug_types' => ['C', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'GH' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'GI' => [
            'plug_types' => ['G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'GL' => [
            'plug_types' => ['C', 'E', 'F', 'K'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'GM' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'GN' => [
            'plug_types' => ['C', 'F', 'K'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'GP' => [
            'plug_types' => ['C', 'E', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'GQ' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'GR' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'GT' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'GU' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 110,
            'frequency' => 60,
        ],
        'GW' => [
            'plug_types' => ['C'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'GY' => [
            'plug_types' => ['A', 'B', 'G'],
            'voltage' => 240,
            'frequency' => 60,
        ],
        'HK' => [
            'plug_types' => ['G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'HN' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 110,
            'frequency' => 60,
        ],
        'HR' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'HT' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 110,
            'frequency' => 60,
        ],
        'HU' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'ID' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'IE' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'IL' => [
            'plug_types' => ['C', 'D', 'H'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'IM' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'IN' => [
            'plug_types' => ['C', 'D', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'IQ' => [
            'plug_types' => ['C', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'IR' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'IS' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'IT' => [
            'plug_types' => ['C', 'F', 'L'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'JE' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'JM' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 116,
            'frequency' => 50,
        ],
        'JO' => [
            'plug_types' => ['B', 'C', 'F', 'G', 'J'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'JP' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 100,
            'frequency' => 50,
        ],
        'KE' => [
            'plug_types' => ['G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'KG' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'KH' => [
            'plug_types' => ['A', 'C', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'KI' => [
            'plug_types' => ['I'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'KM' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'KN' => [
            'plug_types' => ['A', 'B', 'G'],
            'voltage' => 230,
            'frequency' => 60,
        ],
        'KP' => [
            'plug_types' => ['A', 'C', 'F'],
            'voltage' => 220,
        ],
        'KR' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 60,
        ],
        'KW' => [
            'plug_types' => ['C', 'G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'KY' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'KZ' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'LA' => [
            'plug_types' => ['A', 'B', 'C', 'E', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'LB' => [
            'plug_types' => ['A', 'B', 'C', 'G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'LC' => [
            'plug_types' => ['G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'LI' => [
            'plug_types' => ['C', 'J'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'LK' => [
            'plug_types' => ['D', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'LR' => [
            'plug_types' => ['A', 'B', 'C', 'E', 'F'],
            'voltage' => 220,
        ],
        'LS' => [
            'plug_types' => ['D'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'LT' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'LU' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'LV' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'LY' => [
            'plug_types' => ['C', 'F', 'G', 'L'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'MA' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'MC' => [
            'plug_types' => ['C', 'E', 'F', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'MD' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'ME' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'MF' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 60,
        ],
        'MG' => [
            'plug_types' => ['C', 'E', 'G', 'J', 'K'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'MH' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'MK' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'ML' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'MM' => [
            'plug_types' => ['C', 'F', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'MN' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'MO' => [
            'plug_types' => ['D', 'F', 'G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'MP' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'MQ' => [
            'plug_types' => ['C', 'E', 'G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'MR' => [
            'plug_types' => ['C'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'MS' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 230,
            'frequency' => 60,
        ],
        'MT' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'MU' => [
            'plug_types' => ['C', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'MV' => [
            'plug_types' => ['A', 'C', 'G', 'J', 'K', 'L'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'MW' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'MX' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 127,
            'frequency' => 60,
        ],
        'MY' => [
            'plug_types' => ['G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'MZ' => [
            'plug_types' => ['C', 'D', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'NA' => [
            'plug_types' => ['D', 'G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'NC' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'NE' => [
            'plug_types' => ['A', 'B', 'C', 'E', 'F', 'G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'NF' => [
            'plug_types' => ['I'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'NG' => [
            'plug_types' => ['D', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'NI' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'NL' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'NO' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'NP' => [
            'plug_types' => ['C', 'D', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'NR' => [
            'plug_types' => ['I'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'NU' => [
            'plug_types' => ['I'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'NZ' => [
            'plug_types' => ['I'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'OM' => [
            'plug_types' => ['C', 'G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'PA' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 110,
            'frequency' => 60,
        ],
        'PE' => [
            'plug_types' => ['A', 'B', 'C'],
            'voltage' => 220,
            'frequency' => 60,
        ],
        'PF' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'PG' => [
            'plug_types' => ['I'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'PH' => [
            'plug_types' => ['A', 'B', 'C'],
            'voltage' => 220,
            'frequency' => 60,
        ],
        'PK' => [
            'plug_types' => ['C', 'D', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'PL' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'PM' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'PN' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'PR' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'PS' => [
            'plug_types' => ['C', 'D', 'H'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'PT' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'PW' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'PY' => [
            'plug_types' => ['C'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'QA' => [
            'plug_types' => ['G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'RE' => [
            'plug_types' => ['E'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'RO' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'RS' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'RU' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'RW' => [
            'plug_types' => ['C', 'E', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SA' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 60,
        ],
        'SB' => [
            'plug_types' => ['G', 'I'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'SC' => [
            'plug_types' => ['G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'SD' => [
            'plug_types' => ['C', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SE' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SG' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SH' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SI' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SJ' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SK' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SL' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SM' => [
            'plug_types' => ['C', 'F', 'L'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SN' => [
            'plug_types' => ['C', 'E', 'G', 'K'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SO' => [
            'plug_types' => ['C'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'SR' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 127,
            'frequency' => 60,
        ],
        'SS' => [
            'plug_types' => ['C', 'D'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'ST' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'SV' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 115,
            'frequency' => 60,
        ],
        'SX' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'SY' => [
            'plug_types' => ['C', 'E', 'L'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'SZ' => [
            'plug_types' => ['D'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'TC' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'TD' => [
            'plug_types' => ['C', 'E', 'F', 'G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'TG' => [
            'plug_types' => ['C'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'TH' => [
            'plug_types' => ['A', 'B', 'C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'TJ' => [
            'plug_types' => ['C', 'F', 'I'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'TK' => [
            'plug_types' => ['I'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'TL' => [
            'plug_types' => ['C', 'E', 'F', 'I'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'TM' => [
            'plug_types' => ['B', 'C', 'F'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'TN' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'TO' => [
            'plug_types' => ['I'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'TR' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'TT' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 115,
            'frequency' => 60,
        ],
        'TV' => [
            'plug_types' => ['I'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'TW' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 110,
            'frequency' => 60,
        ],
        'TZ' => [
            'plug_types' => ['G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'UA' => [
            'plug_types' => ['C', 'F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'UG' => [
            'plug_types' => ['G'],
            'voltage' => 240,
            'frequency' => 50,
        ],
        'US' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'UY' => [
            'plug_types' => ['C', 'F', 'I', 'L'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'UZ' => [
            'plug_types' => ['C', 'F', 'I'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'VA' => [
            'plug_types' => ['C', 'F', 'L'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'VC' => [
            'plug_types' => ['A', 'C', 'E', 'G', 'I', 'K'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'VE' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 120,
            'frequency' => 60,
        ],
        'VG' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 110,
            'frequency' => 60,
        ],
        'VI' => [
            'plug_types' => ['A', 'B'],
            'voltage' => 110,
            'frequency' => 60,
        ],
        'VN' => [
            'plug_types' => ['A', 'C', 'F', 'G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'VU' => [
            'plug_types' => ['C', 'G', 'I'],
            'voltage' => 220,
            'frequency' => 50,
        ],
        'WF' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'WS' => [
            'plug_types' => ['I'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'XK' => [
            'plug_types' => ['F'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'YE' => [
            'plug_types' => ['A', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'YT' => [
            'plug_types' => ['C', 'E'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'ZA' => [
            'plug_types' => ['C', 'D', 'G', 'N'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'ZM' => [
            'plug_types' => ['C', 'G'],
            'voltage' => 230,
            'frequency' => 50,
        ],
        'ZW' => [
            'plug_types' => ['G'],
            'voltage' => 220,
            'frequency' => 50,
        ],
    ];
};
