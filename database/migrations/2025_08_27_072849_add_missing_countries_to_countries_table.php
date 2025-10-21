<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fehlende Länder hinzufügen
        $missingCountries = [
            // Afrika
            ['code' => 'DZ', 'iso3' => 'DZA', 'name' => 'Algerien', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'AO', 'iso3' => 'AGO', 'name' => 'Angola', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'BJ', 'iso3' => 'BEN', 'name' => 'Benin', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'BW', 'iso3' => 'BWA', 'name' => 'Botswana', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'BF', 'iso3' => 'BFA', 'name' => 'Burkina Faso', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'BI', 'iso3' => 'BDI', 'name' => 'Burundi', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'CM', 'iso3' => 'CMR', 'name' => 'Kamerun', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'CV', 'iso3' => 'CPV', 'name' => 'Kap Verde', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'CF', 'iso3' => 'CAF', 'name' => 'Zentralafrikanische Republik', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'TD', 'iso3' => 'TCD', 'name' => 'Tschad', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'KM', 'iso3' => 'COM', 'name' => 'Komoren', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'CG', 'iso3' => 'COG', 'name' => 'Republik Kongo', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'CD', 'iso3' => 'COD', 'name' => 'Demokratische Republik Kongo', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'CI', 'iso3' => 'CIV', 'name' => 'Elfenbeinküste', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'DJ', 'iso3' => 'DJI', 'name' => 'Dschibuti', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'GQ', 'iso3' => 'GNQ', 'name' => 'Äquatorialguinea', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'ER', 'iso3' => 'ERI', 'name' => 'Eritrea', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'SZ', 'iso3' => 'SWZ', 'name' => 'Eswatini', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'GA', 'iso3' => 'GAB', 'name' => 'Gabun', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'GM', 'iso3' => 'GMB', 'name' => 'Gambia', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'GN', 'iso3' => 'GIN', 'name' => 'Guinea', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'GW', 'iso3' => 'GNB', 'name' => 'Guinea-Bissau', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'LS', 'iso3' => 'LSO', 'name' => 'Lesotho', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'LR', 'iso3' => 'LBR', 'name' => 'Liberia', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'LY', 'iso3' => 'LBY', 'name' => 'Libyen', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'MG', 'iso3' => 'MDG', 'name' => 'Madagaskar', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'MW', 'iso3' => 'MWI', 'name' => 'Malawi', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'ML', 'iso3' => 'MLI', 'name' => 'Mali', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'MR', 'iso3' => 'MRT', 'name' => 'Mauretanien', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'MU', 'iso3' => 'MUS', 'name' => 'Mauritius', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'MZ', 'iso3' => 'MOZ', 'name' => 'Mosambik', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'NA', 'iso3' => 'NAM', 'name' => 'Namibia', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'NE', 'iso3' => 'NER', 'name' => 'Niger', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'RW', 'iso3' => 'RWA', 'name' => 'Ruanda', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'ST', 'iso3' => 'STP', 'name' => 'São Tomé und Príncipe', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'SN', 'iso3' => 'SEN', 'name' => 'Senegal', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'SC', 'iso3' => 'SYC', 'name' => 'Seychellen', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'SL', 'iso3' => 'SLE', 'name' => 'Sierra Leone', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'SO', 'iso3' => 'SOM', 'name' => 'Somalia', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'SS', 'iso3' => 'SSD', 'name' => 'Südsudan', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'SD', 'iso3' => 'SDN', 'name' => 'Sudan', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'TZ', 'iso3' => 'TZA', 'name' => 'Tansania', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'TG', 'iso3' => 'TGO', 'name' => 'Togo', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'TN', 'iso3' => 'TUN', 'name' => 'Tunesien', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'UG', 'iso3' => 'UGA', 'name' => 'Uganda', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'ZM', 'iso3' => 'ZMB', 'name' => 'Sambia', 'continent_id' => 1, 'is_active' => true],
            ['code' => 'ZW', 'iso3' => 'ZWE', 'name' => 'Simbabwe', 'continent_id' => 1, 'is_active' => true],

            // Asien
            ['code' => 'BH', 'iso3' => 'BHR', 'name' => 'Bahrain', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'BT', 'iso3' => 'BTN', 'name' => 'Bhutan', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'BN', 'iso3' => 'BRN', 'name' => 'Brunei', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'KH', 'iso3' => 'KHM', 'name' => 'Kambodscha', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'JP', 'iso3' => 'JPN', 'name' => 'Japan', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'JO', 'iso3' => 'JOR', 'name' => 'Jordanien', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'KP', 'iso3' => 'PRK', 'name' => 'Nordkorea', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'KW', 'iso3' => 'KWT', 'name' => 'Kuwait', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'LA', 'iso3' => 'LAO', 'name' => 'Laos', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'LB', 'iso3' => 'LBN', 'name' => 'Libanon', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'MY', 'iso3' => 'MYS', 'name' => 'Malaysia', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'MV', 'iso3' => 'MDV', 'name' => 'Malediven', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'OM', 'iso3' => 'OMN', 'name' => 'Oman', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'PH', 'iso3' => 'PHL', 'name' => 'Philippinen', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'QA', 'iso3' => 'QAT', 'name' => 'Katar', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'SG', 'iso3' => 'SGP', 'name' => 'Singapur', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'LK', 'iso3' => 'LKA', 'name' => 'Sri Lanka', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'TH', 'iso3' => 'THA', 'name' => 'Thailand', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'TL', 'iso3' => 'TLS', 'name' => 'Osttimor', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'AE', 'iso3' => 'ARE', 'name' => 'Vereinigte Arabische Emirate', 'continent_id' => 2, 'is_active' => true],
            ['code' => 'VN', 'iso3' => 'VNM', 'name' => 'Vietnam', 'continent_id' => 2, 'is_active' => true],

            // Europa
            ['code' => 'AD', 'iso3' => 'AND', 'name' => 'Andorra', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'BY', 'iso3' => 'BLR', 'name' => 'Belarus', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'FO', 'iso3' => 'FRO', 'name' => 'Färöer', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'GI', 'iso3' => 'GIB', 'name' => 'Gibraltar', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'GG', 'iso3' => 'GGY', 'name' => 'Guernsey', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'IM', 'iso3' => 'IMN', 'name' => 'Isle of Man', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'JE', 'iso3' => 'JEY', 'name' => 'Jersey', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'XK', 'iso3' => 'XKX', 'name' => 'Kosovo', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'LI', 'iso3' => 'LIE', 'name' => 'Liechtenstein', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'MC', 'iso3' => 'MCO', 'name' => 'Monaco', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'SM', 'iso3' => 'SMR', 'name' => 'San Marino', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'SJ', 'iso3' => 'SJM', 'name' => 'Spitzbergen und Jan Mayen', 'continent_id' => 3, 'is_active' => true],
            ['code' => 'VA', 'iso3' => 'VAT', 'name' => 'Vatikanstadt', 'continent_id' => 3, 'is_active' => true],

            // Nordamerika
            ['code' => 'AI', 'iso3' => 'AIA', 'name' => 'Anguilla', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'AG', 'iso3' => 'ATG', 'name' => 'Antigua und Barbuda', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'AW', 'iso3' => 'ABW', 'name' => 'Aruba', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'BS', 'iso3' => 'BHS', 'name' => 'Bahamas', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'BB', 'iso3' => 'BRB', 'name' => 'Barbados', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'BZ', 'iso3' => 'BLZ', 'name' => 'Belize', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'BM', 'iso3' => 'BMU', 'name' => 'Bermuda', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'VG', 'iso3' => 'VGB', 'name' => 'Britische Jungferninseln', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'KY', 'iso3' => 'CYM', 'name' => 'Kaimaninseln', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'CU', 'iso3' => 'CUB', 'name' => 'Kuba', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'CW', 'iso3' => 'CUW', 'name' => 'Curaçao', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'DM', 'iso3' => 'DMA', 'name' => 'Dominica', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'DO', 'iso3' => 'DOM', 'name' => 'Dominikanische Republik', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'SV', 'iso3' => 'SLV', 'name' => 'El Salvador', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'GL', 'iso3' => 'GRL', 'name' => 'Grönland', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'GD', 'iso3' => 'GRD', 'name' => 'Grenada', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'GP', 'iso3' => 'GLP', 'name' => 'Guadeloupe', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'HT', 'iso3' => 'HTI', 'name' => 'Haiti', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'HN', 'iso3' => 'HND', 'name' => 'Honduras', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'JM', 'iso3' => 'JAM', 'name' => 'Jamaika', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'MQ', 'iso3' => 'MTQ', 'name' => 'Martinique', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'MS', 'iso3' => 'MSR', 'name' => 'Montserrat', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'NI', 'iso3' => 'NIC', 'name' => 'Nicaragua', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'PA', 'iso3' => 'PAN', 'name' => 'Panama', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'PR', 'iso3' => 'PRI', 'name' => 'Puerto Rico', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'BL', 'iso3' => 'BLM', 'name' => 'Saint-Barthélemy', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'KN', 'iso3' => 'KNA', 'name' => 'St. Kitts und Nevis', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'LC', 'iso3' => 'LCA', 'name' => 'St. Lucia', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'MF', 'iso3' => 'MAF', 'name' => 'Saint-Martin', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'PM', 'iso3' => 'SPM', 'name' => 'Saint-Pierre und Miquelon', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'VC', 'iso3' => 'VCT', 'name' => 'St. Vincent und die Grenadinen', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'SX', 'iso3' => 'SXM', 'name' => 'Sint Maarten', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'TT', 'iso3' => 'TTO', 'name' => 'Trinidad und Tobago', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'TC', 'iso3' => 'TCA', 'name' => 'Turks- und Caicosinseln', 'continent_id' => 5, 'is_active' => true],
            ['code' => 'VI', 'iso3' => 'VIR', 'name' => 'Amerikanische Jungferninseln', 'continent_id' => 5, 'is_active' => true],

            // Südamerika
            ['code' => 'BO', 'iso3' => 'BOL', 'name' => 'Bolivien', 'continent_id' => 6, 'is_active' => true],
            ['code' => 'FK', 'iso3' => 'FLK', 'name' => 'Falklandinseln', 'continent_id' => 6, 'is_active' => true],
            ['code' => 'GF', 'iso3' => 'GUF', 'name' => 'Französisch-Guayana', 'continent_id' => 6, 'is_active' => true],
            ['code' => 'GY', 'iso3' => 'GUY', 'name' => 'Guyana', 'continent_id' => 6, 'is_active' => true],
            ['code' => 'PY', 'iso3' => 'PRY', 'name' => 'Paraguay', 'continent_id' => 6, 'is_active' => true],
            ['code' => 'SR', 'iso3' => 'SUR', 'name' => 'Suriname', 'continent_id' => 6, 'is_active' => true],
            ['code' => 'UY', 'iso3' => 'URY', 'name' => 'Uruguay', 'continent_id' => 6, 'is_active' => true],

            // Ozeanien
            ['code' => 'AS', 'iso3' => 'ASM', 'name' => 'Amerikanisch-Samoa', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'CK', 'iso3' => 'COK', 'name' => 'Cookinseln', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'FJ', 'iso3' => 'FJI', 'name' => 'Fidschi', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'PF', 'iso3' => 'PYF', 'name' => 'Französisch-Polynesien', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'GU', 'iso3' => 'GUM', 'name' => 'Guam', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'KI', 'iso3' => 'KIR', 'name' => 'Kiribati', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'MH', 'iso3' => 'MHL', 'name' => 'Marshallinseln', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'FM', 'iso3' => 'FSM', 'name' => 'Mikronesien', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'NR', 'iso3' => 'NRU', 'name' => 'Nauru', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'NC', 'iso3' => 'NCL', 'name' => 'Neukaledonien', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'NU', 'iso3' => 'NIU', 'name' => 'Niue', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'NF', 'iso3' => 'NFK', 'name' => 'Norfolkinsel', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'MP', 'iso3' => 'MNP', 'name' => 'Nördliche Marianen', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'PW', 'iso3' => 'PLW', 'name' => 'Palau', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'PG', 'iso3' => 'PNG', 'name' => 'Papua-Neuguinea', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'PN', 'iso3' => 'PCN', 'name' => 'Pitcairninseln', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'WS', 'iso3' => 'WSM', 'name' => 'Samoa', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'SB', 'iso3' => 'SLB', 'name' => 'Salomonen', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'TK', 'iso3' => 'TKL', 'name' => 'Tokelau', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'TO', 'iso3' => 'TON', 'name' => 'Tonga', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'TV', 'iso3' => 'TUV', 'name' => 'Tuvalu', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'VU', 'iso3' => 'VUT', 'name' => 'Vanuatu', 'continent_id' => 7, 'is_active' => true],
            ['code' => 'WF', 'iso3' => 'WLF', 'name' => 'Wallis und Futuna', 'continent_id' => 7, 'is_active' => true]
        ];

        foreach ($missingCountries as $country) {
            // Überprüfen ob das Land bereits existiert
            $exists = DB::table('countries')->where('code', $country['code'])->exists();
            if (!$exists) {
                DB::table('countries')->insert(array_merge($country, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Die hinzugefügten Länder wieder entfernen
        $addedCountryCodes = [
            'DZ', 'AO', 'BJ', 'BW', 'BF', 'BI', 'CM', 'CV', 'CF', 'TD', 'KM', 'CG', 'CD', 'CI', 'DJ', 'GQ', 'ER', 'SZ', 'GA', 'GM', 'GN', 'GW', 'LS', 'LR', 'LY', 'MG', 'MW', 'ML', 'MR', 'MU', 'MZ', 'NA', 'NE', 'RW', 'ST', 'SN', 'SC', 'SL', 'SO', 'SS', 'SD', 'TZ', 'TG', 'TN', 'UG', 'ZM', 'ZW',
            'BH', 'BT', 'BN', 'KH', 'JP', 'JO', 'KP', 'KW', 'LA', 'LB', 'MY', 'MV', 'OM', 'PH', 'QA', 'SG', 'LK', 'TH', 'TL', 'AE', 'VN',
            'AD', 'BY', 'FO', 'GI', 'GG', 'IM', 'JE', 'XK', 'LI', 'MC', 'SM', 'SJ', 'VA',
            'AI', 'AG', 'AW', 'BS', 'BB', 'BZ', 'BM', 'VG', 'KY', 'CU', 'CW', 'DM', 'DO', 'SV', 'GL', 'GD', 'GP', 'HT', 'HN', 'JM', 'MQ', 'MS', 'NI', 'PA', 'PR', 'BL', 'KN', 'LC', 'MF', 'PM', 'VC', 'SX', 'TT', 'TC', 'VI',
            'BO', 'FK', 'GF', 'GY', 'PY', 'SR', 'UY',
            'AS', 'CK', 'FJ', 'PF', 'GU', 'KI', 'MH', 'FM', 'NR', 'NC', 'NU', 'NF', 'MP', 'PW', 'PG', 'PN', 'WS', 'SB', 'TK', 'TO', 'TV', 'VU', 'WF'
        ];

        DB::table('countries')->whereIn('code', $addedCountryCodes)->delete();
    }
};
