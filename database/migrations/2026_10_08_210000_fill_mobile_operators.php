<?php

use App\Models\Country;
use App\Models\MobileOperator;
use Illuminate\Database\Migrations\Migration;

/**
 * Mobilfunkanbieter weltweit und ihre Laender – aus den Wikipedia-Listen
 * "List of mobile network operators in Europe / the Americas / Asia and
 * Oceania / the Middle East and Africa" (Stand Oktober 2026). Je Land die
 * Netzbetreiber-Tabelle; Marken, die in mehreren Laendern auftreten
 * (Orange, Vodafone, MTN, …), sind ein Eintrag mit mehreren Laendern.
 *
 * Vorhandene Anbieter (gleicher Name) bleiben, wie sie sind; es werden nur
 * Zuordnungen ergaenzt. Logo, Website und Beschreibung sind nachzupflegen.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Stammdaten-Import – in Tests bleibt die Tabelle leer.
        if (app()->runningUnitTests()) {
            return;
        }

        $byIso = Country::query()->withTrashed()->get()->keyBy(fn (Country $country) => strtoupper((string) $country->iso_code));
        $existing = MobileOperator::query()->get()->keyBy(fn (MobileOperator $operator) => mb_strtolower($operator->name));
        $position = (int) MobileOperator::query()->max('sort_order');

        foreach (self::OPERATORS as $name => $row) {
            $operator = $existing->get(mb_strtolower($name));

            if (! $operator) {
                $operator = MobileOperator::create([
                    'name' => $name,
                    'description_translations' => isset($row['owner']) ? ['de' => 'Eigentümer: '.$row['owner'], 'en' => 'Owner: '.$row['owner'], 'nl' => 'Eigenaar: '.$row['owner']] : null,
                    'is_active' => true,
                    'sort_order' => ++$position,
                ]);
                $existing->put(mb_strtolower($name), $operator);
            }

            $ids = collect($row['countries'])->map(fn (string $iso) => $byIso->get($iso)?->id)->filter()->all();
            $operator->countries()->syncWithoutDetaching($ids);
        }
    }

    public function down(): void
    {
        // Anbieter lassen sich von Hand pflegen – ein Zuruecknehmen wuerde auch das treffen.
    }

    /** @var array<string, array{countries: array<int, string>, owner?: string}> Name => Laender und Eigentuemer */
    private const OPERATORS = [
        '+móvil' => [
            'countries' => ['PA'],
            'owner' => 'Cable & Wireless',
        ],
        '1&1' => [
            'countries' => ['DE'],
            'owner' => 'United Internet AG, free float, Norman Rentrop, 1&1 AG, Management board',
        ],
        '2degrees' => [
            'countries' => ['NZ'],
            'owner' => 'Voyage Australia Pty Limited',
        ],
        '3 Macau' => [
            'countries' => ['MO'],
            'owner' => 'CTM',
        ],
        '3 Mob' => [
            'countries' => ['UA'],
            'owner' => 'Ukrtelecom',
        ],
        '4ka' => [
            'countries' => ['SK'],
            'owner' => 'SWAN',
        ],
        '7acht' => [
            'countries' => ['LI'],
            'owner' => 'Salt Mobile',
        ],
        'A-Mobile' => [
            'countries' => ['GE'],
            'owner' => 'A-Mobile LLSC',
        ],
        'A1' => [
            'countries' => ['AT', 'BG', 'BY', 'HR', 'MK', 'RS', 'SI'],
            'owner' => 'A1 Group',
        ],
        'Access' => [
            'countries' => ['MW'],
        ],
        'Afghan Wireless' => [
            'countries' => ['AF'],
            'owner' => 'Telephone Systems International',
        ],
        'Africell' => [
            'countries' => ['AO', 'CD', 'GM', 'SL'],
            'owner' => 'Africell Group',
        ],
        'AIL' => [
            'countries' => ['VU'],
            'owner' => 'ACES International',
        ],
        'Airtel' => [
            'countries' => ['CD', 'CG', 'GA', 'IN', 'MG', 'MW', 'NE', 'NG', 'RW', 'SC', 'TD', 'TZ', 'ZM'],
            'owner' => 'Bharti Airtel',
        ],
        'Airtel Kenya' => [
            'countries' => ['KE'],
            'owner' => 'Bharti Airtel',
        ],
        'Airtel Uganda' => [
            'countries' => ['UG'],
            'owner' => 'Bharti Airtel',
        ],
        'Airtel-Vodafone' => [
            'countries' => ['GG', 'JE'],
            'owner' => 'Batelco',
        ],
        'AIS' => [
            'countries' => ['TH'],
            'owner' => 'Major shareholders: Intouch Holdings / Singtel / Thai NVDR',
        ],
        'AJ SIM' => [
            'countries' => ['TH'],
            'owner' => 'AJ Advance Technology Pcl.',
        ],
        'Alfa' => [
            'countries' => ['LB'],
            'owner' => 'government - Managed by Ministry of Telecom',
        ],
        'Algar Telecom' => [
            'countries' => ['BR'],
            'owner' => 'Grupo Algar',
        ],
        'Aliv' => [
            'countries' => ['BS'],
            'owner' => 'Cable Bahamas Government of the Bahamas',
        ],
        'Almadar Aljadid' => [
            'countries' => ['LY'],
            'owner' => 'State-owned via LPTIC',
        ],
        'ALTEL Communications Sdn. Bhd.' => [
            'countries' => ['MY'],
            'owner' => 'Puncak Semangat Sdn. Bhd. .',
        ],
        'altice' => [
            'countries' => ['DO'],
            'owner' => 'Altice Hispaniola',
        ],
        'American Samoa Telecommunications Authority' => [
            'countries' => ['AS'],
            'owner' => 'Government of American Samoa',
        ],
        'Andorra Telecom' => [
            'countries' => ['AD'],
            'owner' => 'Government of Andorra',
        ],
        'Antel' => [
            'countries' => ['UY'],
            'owner' => 'State-owned',
        ],
        'Aquafon' => [
            'countries' => ['GE'],
            'owner' => 'Aquafon JSC',
        ],
        'Asia Cell' => [
            'countries' => ['IQ'],
            'owner' => 'Ooredoo',
        ],
        'AT Ghana' => [
            'countries' => ['GH'],
            'owner' => 'Government of Ghana',
        ],
        'AT&T' => [
            'countries' => ['US'],
            'owner' => 'AT&T Inc.',
        ],
        'AT&T Mexico' => [
            'countries' => ['MX'],
            'owner' => 'AT&T Inc.',
        ],
        'ATOM' => [
            'countries' => ['MM'],
            'owner' => 'M1 Group , Investcom Pte. Ltd.',
        ],
        'ATOMA' => [
            'countries' => ['AF'],
            'owner' => 'M1 Group',
        ],
        'au' => [
            'countries' => ['JP'],
            'owner' => 'KDDI',
        ],
        'Azercell' => [
            'countries' => ['AZ'],
            'owner' => 'AzInTelecom',
        ],
        'azur' => [
            'countries' => ['CF', 'CG'],
            'owner' => 'Bintel',
        ],
        'AȘTU' => [
            'countries' => ['TM'],
            'owner' => 'Ashgabat City Telephone Network',
        ],
        'B-Mobile' => [
            'countries' => ['BT', 'JP'],
            'owner' => 'Bhutan Telecom Limited',
        ],
        'Babilon-Mobile' => [
            'countries' => ['TJ'],
        ],
        'Bakcell' => [
            'countries' => ['AZ'],
            'owner' => 'Neqsol Holding',
        ],
        'Banglalink with Digital Sub Brand - RYZE' => [
            'countries' => ['BD'],
            'owner' => 'veon',
        ],
        'Base' => [
            'countries' => ['BE'],
            'owner' => 'Telenet Group N.V./S.A. /',
        ],
        'Batelco' => [
            'countries' => ['BH'],
            'owner' => 'Bahrain Telecommunications Company',
        ],
        'BBcom' => [
            'countries' => ['BJ'],
            'owner' => 'Bell Benin Communications',
        ],
        'beeline' => [
            'countries' => ['KG', 'RU', 'UZ'],
            'owner' => 'PJSC VimpelCom',
        ],
        'Beeline Kazakhstan' => [
            'countries' => ['KZ'],
            'owner' => 'VEON, Verny Capital [ ru ]',
        ],
        'Bell' => [
            'countries' => ['CA'],
            'owner' => 'Bell Canada',
        ],
        'BH Telecom' => [
            'countries' => ['BA'],
            'owner' => 'Federation of Bosnia and Herzegovina, free float',
        ],
        'Bitel' => [
            'countries' => ['PE'],
            'owner' => 'Viettel Mobile',
        ],
        'Bitė' => [
            'countries' => ['LT', 'LV'],
            'owner' => 'Providence Equity Partners',
        ],
        'Blue Sky Communications' => [
            'countries' => ['AS'],
            'owner' => 'Amper SpA',
        ],
        'Blueline' => [
            'countries' => ['MG'],
            'owner' => 'Gulfsat',
        ],
        'bmobile' => [
            'countries' => ['PG', 'TT'],
            'owner' => 'Cable & Wireless Communications Government of Trinidad and Tobago',
        ],
        'Bouygues Telecom' => [
            'countries' => ['FR'],
            'owner' => 'Bouygues Group, JCDecaux',
        ],
        'BSNL' => [
            'countries' => ['IN'],
            'owner' => 'Government of India',
        ],
        'BTC' => [
            'countries' => ['BS', 'BW'],
            'owner' => 'Liberty Latin America,',
        ],
        'C Spire' => [
            'countries' => ['US'],
            'owner' => 'Telapex, Inc.',
        ],
        'Cable & Wireless' => [
            'countries' => ['SC'],
            'owner' => 'Cable & Wireless',
        ],
        'Camtel' => [
            'countries' => ['CM'],
            'owner' => 'Camtel',
        ],
        'Canar' => [
            'countries' => ['SD'],
            'owner' => 'Bank of Khartoum',
        ],
        'CCT' => [
            'countries' => ['VG'],
            'owner' => 'CCT Global Communications',
        ],
        'CelcomDigi' => [
            'countries' => ['MY'],
            'owner' => 'Major shareholders: Axiata / Telenor / KWSP Malaysia',
        ],
        'Cell C' => [
            'countries' => ['ZA'],
            'owner' => 'Oger Telecom in process of diluting it\'s 70% stake to 27% Blue Label Telecoms is planning to acquire a 30% stake in Cell C',
        ],
        'Cellcard' => [
            'countries' => ['KH'],
            'owner' => 'The Royal Group',
        ],
        'Cellcom' => [
            'countries' => ['IL'],
            'owner' => 'DIC, Public',
        ],
        'Cellcom Guinée' => [
            'countries' => ['GN'],
            'owner' => 'Cellcom Telecommunications Ltd',
        ],
        'Cellfie' => [
            'countries' => ['GE'],
            'owner' => 'CBS Group',
        ],
        'CHiLi' => [
            'countries' => ['MU'],
            'owner' => 'Mahanagar Telephone Mauritius Limited',
        ],
        'China Broadnet' => [
            'countries' => ['CN'],
            'owner' => 'State-owned',
        ],
        'China Mobile' => [
            'countries' => ['CN'],
            'owner' => 'State-owned / Public-traded',
        ],
        'China Mobile CMLink SG' => [
            'countries' => ['SG'],
            'owner' => 'China Mobile International Limited',
        ],
        'China Telecom' => [
            'countries' => ['CN', 'MO'],
            'owner' => 'Government of China',
        ],
        'China Unicom' => [
            'countries' => ['CN'],
        ],
        'China Unicom CUniq SG' => [
            'countries' => ['SG'],
            'owner' => 'China Unicom Operations Pte. Ltd.',
        ],
        'Chinguitel' => [
            'countries' => ['MR'],
            'owner' => 'Sudatel',
        ],
        'Chunghwa Telecom' => [
            'countries' => ['TW'],
            'owner' => 'Republic of China Ministry of Transportation and Communications / Shin Kong Life Insurance / Cathay Life Insurance / Chunghwa Post',
        ],
        'Circles.Life' => [
            'countries' => ['SG'],
            'owner' => 'Liberty Wireless Pte. Ltd.',
        ],
        'Claro' => [
            'countries' => ['AR', 'BR', 'CL', 'CO', 'CR', 'DO', 'EC', 'GT', 'HN', 'NI', 'PE', 'PR', 'PY', 'SV', 'UY', 'VI'],
            'owner' => 'América Móvil',
        ],
        'CMHK' => [
            'countries' => ['HK'],
            'owner' => 'China Mobile',
        ],
        'Cnt' => [
            'countries' => ['EC'],
            'owner' => 'Corporación Nacional de Telecomunicaciones, CNT EP',
        ],
        'Comium' => [
            'countries' => ['GM', 'SL'],
            'owner' => 'Comium Group',
        ],
        'Compass Mobile' => [
            'countries' => ['NZ'],
            'owner' => 'Compass Communications',
        ],
        'Contact Mobile' => [
            'countries' => ['NZ'],
            'owner' => 'Contact Energy',
        ],
        'Crnogorski Telekom' => [
            'countries' => ['ME'],
            'owner' => 'Hrvatski Telekom /',
        ],
        'csl' => [
            'countries' => ['HK'],
            'owner' => 'Hong Kong Telecom',
        ],
        'CST' => [
            'countries' => ['ST'],
            'owner' => 'Portugal Telecom',
        ],
        'CTM' => [
            'countries' => ['MO'],
            'owner' => 'CITIC Telecom International / Correios de Macau',
        ],
        'Cubacel' => [
            'countries' => ['CU'],
            'owner' => 'ETECSA',
        ],
        'Cuy Móvil' => [
            'countries' => ['PE'],
            'owner' => 'Cuy Móvil',
        ],
        'CV Móvel' => [
            'countries' => ['CV'],
            'owner' => 'Portugal Telecom',
        ],
        'Cyta' => [
            'countries' => ['CY'],
            'owner' => 'Cyta',
        ],
        'Datastream Digital' => [
            'countries' => ['BN'],
            'owner' => 'Unified National Networks',
        ],
        'Dhiraagu' => [
            'countries' => ['MV'],
            'owner' => 'Dhiraagu',
        ],
        'Dialog' => [
            'countries' => ['LK'],
            'owner' => 'Axiata Group Berhad / Bharti Airtel Limited',
        ],
        'DIGI' => [
            'countries' => ['BE', 'BZ', 'ES', 'PT', 'RO'],
            'owner' => 'Digi Communications N.V.',
        ],
        'Digicel' => [
            'countries' => ['AG', 'AI', 'AW', 'BB', 'BM', 'BQ', 'CW', 'DM', 'FJ', 'GD', 'GF', 'GP', 'GY', 'HT', 'JM', 'KN', 'KY', 'LC', 'MQ', 'MS', 'NR', 'PG', 'SR', 'SV', 'TC', 'TO', 'TT', 'VC', 'VG', 'VU', 'WS'],
            'owner' => 'Digicel',
        ],
        'Digital Nasional Berhad' => [
            'countries' => ['MY'],
            'owner' => 'Major Shareholders: MOF / CelcomDigi / Maxis / Yes /',
        ],
        'Digitel GSM' => [
            'countries' => ['VE'],
            'owner' => 'Televenco',
        ],
        'Disney Mobile' => [
            'countries' => ['JP'],
            'owner' => 'SoftBank / The Walt Disney Company',
        ],
        'Dito Telecommunity' => [
            'countries' => ['PH'],
            'owner' => 'Dito CME Holdings Corporation / China Telecommunications Corporation',
        ],
        'Djezzy' => [
            'countries' => ['DZ'],
            'owner' => 'State-owned',
        ],
        'DNA' => [
            'countries' => ['FI'],
            'owner' => 'Telenor',
        ],
        'Docomo Pacific' => [
            'countries' => ['GU', 'MP'],
            'owner' => 'NTT Docomo',
        ],
        'Drei' => [
            'countries' => ['AT'],
            'owner' => 'CK Hutchison Holdings',
        ],
        'du' => [
            'countries' => ['AE'],
            'owner' => 'UAE Federal Government, Mubadala Development Company, TECOM Investments, and public shareholders',
        ],
        'Econet' => [
            'countries' => ['ZW'],
            'owner' => 'Econet Wireless Limited',
        ],
        'Econet Leo' => [
            'countries' => ['BI'],
            'owner' => 'Econet Wireless /',
        ],
        'Econet Telecom Lesotho' => [
            'countries' => ['LS'],
            'owner' => 'Econet Ezi Cel Lesotho',
        ],
        'EE' => [
            'countries' => ['GB'],
            'owner' => 'BT Group',
        ],
        'eir' => [
            'countries' => ['IE'],
            'owner' => 'Iliad, NJJ',
        ],
        'Elisa' => [
            'countries' => ['EE', 'FI'],
            'owner' => 'Elisa',
        ],
        'Emtel' => [
            'countries' => ['MU'],
            'owner' => 'Currimjee',
        ],
        'Entel' => [
            'countries' => ['BO', 'CL', 'PE'],
            'owner' => 'Ministry of Public Works, Services and Housing and 1200 other shareholders',
        ],
        'Epic' => [
            'countries' => ['CY', 'MT'],
            'owner' => 'Monaco Telecom SAM',
        ],
        'Eritel' => [
            'countries' => ['ER'],
            'owner' => 'Eritrea Telecommunications Services Corporation',
        ],
        'Eswatini Mobile' => [
            'countries' => ['SZ'],
        ],
        'ETB' => [
            'countries' => ['CO'],
            'owner' => 'ETB',
        ],
        'Ethiotelecom' => [
            'countries' => ['ET'],
            'owner' => 'Ethiopian Telecommunications Corporation',
        ],
        'Etisalat' => [
            'countries' => ['AE', 'EG'],
            'owner' => 'Etisalat, Egypt Post, Other Investors',
        ],
        'Etisalat Afghanistan' => [
            'countries' => ['AF'],
            'owner' => 'Emirates Telecommunications Corporation',
        ],
        'ETL' => [
            'countries' => ['LA'],
            'owner' => 'Jiafu Holdings / Government of Lao P.D.R.',
        ],
        'Evatis' => [
            'countries' => ['DJ'],
            'owner' => 'Djibouti Telecom',
        ],
        'Expresso' => [
            'countries' => ['SN'],
            'owner' => 'Sudatel',
        ],
        'Far EasTone' => [
            'countries' => ['TW'],
            'owner' => 'Far Eastern Group',
        ],
        'Fastweb + Vodafone' => [
            'countries' => ['IT'],
            'owner' => 'Swisscom',
        ],
        'Feels' => [
            'countries' => ['TH'],
            'owner' => 'Feels Telecom Corporation Co., Ltd.',
        ],
        'FL1' => [
            'countries' => ['LI'],
            'owner' => 'State-owned',
        ],
        'FLOW' => [
            'countries' => ['AG', 'AI', 'BB', 'BQ', 'CW', 'DM', 'GD', 'JM', 'KN', 'KY', 'LC', 'MS', 'TC', 'VC', 'VG'],
            'owner' => 'Liberty Latin America',
        ],
        'Fonex' => [
            'countries' => ['KG'],
            'owner' => 'Aktel Ltd.',
        ],
        'FREE' => [
            'countries' => ['RE', 'SN'],
            'owner' => 'fr: ILIAD and TELCO OI',
        ],
        'Free Mobile' => [
            'countries' => ['FR'],
            'owner' => 'Iliad',
        ],
        'Friendi Mobile' => [
            'countries' => ['JO'],
        ],
        'FSM Telecom' => [
            'countries' => ['FM'],
            'owner' => 'FSMTC',
        ],
        'Fullmóvil' => [
            'countries' => ['CR'],
            'owner' => 'Virtualis',
        ],
        'Føroya Tele' => [
            'countries' => ['FO'],
            'owner' => 'Government of Faroe Islands',
        ],
        'G-Expresso' => [
            'countries' => ['MW'],
            'owner' => 'Expresso Telecom Group Limited',
        ],
        'G-Mobile' => [
            'countries' => ['MN', 'MW'],
            'owner' => 'Globally Advanced Integrated Networks, Beryl',
        ],
        'Gamcel' => [
            'countries' => ['GM'],
            'owner' => 'Gambia Telecommunications Cellular Company Ltd',
        ],
        'GCI' => [
            'countries' => ['US'],
            'owner' => 'GCI Liberty Inc.',
        ],
        'Gecomsa' => [
            'countries' => ['GQ'],
            'owner' => 'Equatorial Guinea state, China state',
        ],
        'Gemtel' => [
            'countries' => ['SS'],
            'owner' => 'Lap Green Networks',
        ],
        'Gibtelecom' => [
            'countries' => ['GI'],
            'owner' => 'Government of Gibraltar',
        ],
        'Glo' => [
            'countries' => ['BJ'],
            'owner' => 'Globacom',
        ],
        'Glo Mobile' => [
            'countries' => ['NG'],
            'owner' => 'Globacom',
        ],
        'Globe Telecom' => [
            'countries' => ['PH'],
            'owner' => 'Ayala Corporation / Singtel / Asiacom Philippines, Inc. / Directors, Officers, ESOP / Public Stock',
        ],
        'GO' => [
            'countries' => ['MT'],
            'owner' => 'Tunisie Telecom',
        ],
        'Golis Telecom Somalia' => [
            'countries' => ['SO'],
            'owner' => 'Golis Telecom Somalia , Gaani Wireless',
        ],
        'GOMO' => [
            'countries' => ['PH'],
        ],
        'Grameenphone -with Digital Sub Brand -Skitto' => [
            'countries' => ['BD'],
            'owner' => '.mw-parser-output .plainlist ol,.mw-parser-output .plainlist ul{line-height:inherit;list-style:none;margin:0;padding:0}.mw-parser-output .plainlist ol li,.mw-parser-output .plainlist ul li{margin-bottom:0} Telenor Grameen Telecom General Public & other Institutions',
        ],
        'GreenN' => [
            'countries' => ['CI', 'SL'],
            'owner' => 'LAP Oricel',
        ],
        'GRID Communications' => [
            'countries' => ['SG'],
            'owner' => 'GRID Communications Pte. Ltd.',
        ],
        'GTA' => [
            'countries' => ['GU'],
            'owner' => 'TeleGuam Holdings LLC',
        ],
        'GTD' => [
            'countries' => ['CL'],
            'owner' => 'Grupo GTD',
        ],
        'Guinetel' => [
            'countries' => ['GW'],
            'owner' => 'Guinea Telecom',
        ],
        'Halotel' => [
            'countries' => ['TZ'],
            'owner' => 'Viettel Tanzania Limited',
        ],
        'Hamrahe Aval' => [
            'countries' => ['IR'],
            'owner' => 'Telecommunication Company of Iran',
        ],
        'Hayo Telecom' => [
            'countries' => ['SN'],
            'owner' => 'CSU',
        ],
        'HelloSIM' => [
            'countries' => ['MY'],
            'owner' => 'Merchantrade Asia',
        ],
        'HONDUTEL' => [
            'countries' => ['HN'],
            'owner' => 'HONDUTEL',
        ],
        'Hormuud' => [
            'countries' => ['SO'],
            'owner' => 'Hormuud Telecom Somalia Inc',
        ],
        'Hot Mobile' => [
            'countries' => ['IL'],
            'owner' => 'HOT',
        ],
        'Hrvatski Telekom' => [
            'countries' => ['HR'],
            'owner' => 'Deutsche Telekom AG',
        ],
        'HT ERONET' => [
            'countries' => ['BA'],
            'owner' => 'Federation of Bosnia and Herzegovina, Hrvatski Telekom, Hrvatska pošta, free float',
        ],
        'HURI' => [
            'countries' => ['KM'],
            'owner' => 'government',
        ],
        'Hutch' => [
            'countries' => ['LK'],
            'owner' => 'CK Hutchison Holdings Limited / Emirates Telecommunication Group Company PJSC',
        ],
        'i-Kool 3G' => [
            'countries' => ['TH'],
            'owner' => 'Loxley Public Company Limited.',
        ],
        'Ibon Mobile' => [
            'countries' => ['TW'],
            'owner' => '7-Eleven',
        ],
        'Ice' => [
            'countries' => ['NO'],
            'owner' => 'Lyse AS',
        ],
        'IEC3G Buzzme' => [
            'countries' => ['TH'],
            'owner' => 'Mobile 8 Telco Sdn Bhd.',
        ],
        'IIJmio' => [
            'countries' => ['JP'],
            'owner' => 'Internet Initiative Japan',
        ],
        'Iliad' => [
            'countries' => ['IT'],
            'owner' => 'Iliad SA',
        ],
        'imobile-3GX' => [
            'countries' => ['TH'],
            'owner' => 'Samart Corporation Public Company Limited.',
        ],
        'Indosat Ooredoo Hutchison' => [
            'countries' => ['ID'],
            'owner' => 'Ooredoo Hutchison Asia / Government of Indonesia',
        ],
        'Integratel' => [
            'countries' => ['PE'],
            'owner' => 'Integra Tec International',
        ],
        'Intercel' => [
            'countries' => ['GN'],
            'owner' => 'Telecel Guinee SARL',
        ],
        'Interdnestrcom' => [
            'countries' => ['MD'],
            'owner' => 'Sheriff',
        ],
        'inwi' => [
            'countries' => ['MA'],
            'owner' => 'Zain Group',
        ],
        'IPKO' => [
            'countries' => ['XK'],
            'owner' => 'Telekom Slovenije',
        ],
        'Irancell' => [
            'countries' => ['IR'],
            'owner' => 'Iran Electronic Development Company / MTN Group',
        ],
        'IT&E' => [
            'countries' => ['GU', 'MP'],
            'owner' => 'PTI Pacifica Inc.',
        ],
        'iTel' => [
            'countries' => ['KG'],
        ],
        'Itisaluna' => [
            'countries' => ['IQ'],
            'owner' => 'Itisaluna',
        ],
        'Jamii Telecommunications' => [
            'countries' => ['KE'],
            'owner' => 'Jamii Telecommunications Limited',
        ],
        'Jawwal' => [
            'countries' => ['PS'],
            'owner' => 'Paltel',
        ],
        'Jazz' => [
            'countries' => ['PK'],
            'owner' => 'VEON Ltd.',
        ],
        'Jio' => [
            'countries' => ['IN'],
            'owner' => 'Jio Platforms',
        ],
        'JT' => [
            'countries' => ['GG', 'JE'],
            'owner' => 'JT Group',
        ],
        'K-Telecom' => [
            'countries' => ['RU'],
            'owner' => 'OOO K-Telecom',
        ],
        'Kangsong NET' => [
            'countries' => ['KP'],
            'owner' => 'Ministry of Information Industry',
        ],
        'Katel' => [
            'countries' => ['KG'],
            'owner' => 'KATEL',
        ],
        'Kcell' => [
            'countries' => ['KZ'],
            'owner' => 'Kazakhtelecom, Others',
        ],
        'KiQ' => [
            'countries' => ['PH'],
        ],
        'Kiwi Mobile' => [
            'countries' => ['NZ'],
            'owner' => 'Electric Kiwi',
        ],
        'Kogan Mobile' => [
            'countries' => ['NZ'],
            'owner' => 'Kogan.com',
        ],
        'Korek' => [
            'countries' => ['IQ'],
            'owner' => 'Korek Telecom Ltd.',
        ],
        'Koryolink' => [
            'countries' => ['KP'],
            'owner' => 'Global Telecom Holding S.A.E. / Korea Post and Telecommunications Corporation',
        ],
        'Koz' => [
            'countries' => ['CI'],
            'owner' => 'Comium',
        ],
        'KPN' => [
            'countries' => ['NL'],
            'owner' => 'Koninklijke KPN',
        ],
        'KT' => [
            'countries' => ['KR'],
            'owner' => 'KT Corporation',
        ],
        'Kyivstar' => [
            'countries' => ['UA'],
            'owner' => 'VEON /, The Stichting, Lingotto Investment Management LLP, Shah Capital Management Inc., free float 31.8%)',
        ],
        'Kölbi' => [
            'countries' => ['CR'],
            'owner' => 'Instituto Costarricense de Electricidad',
        ],
        'Lacell' => [
            'countries' => ['MW'],
            'owner' => 'La Cell Private Limited',
        ],
        'Lao Telecom' => [
            'countries' => ['LA'],
            'owner' => 'Government of Lao P.D.R., Shennington Investments',
        ],
        'Lebara Mobile KSA' => [
            'countries' => ['SA'],
            'owner' => 'Lebara',
        ],
        'Letai' => [
            'countries' => ['RU'],
            'owner' => 'PJSC Tattelecom',
        ],
        'LG U' => [
            'countries' => ['KR'],
            'owner' => 'LG Corporation /',
        ],
        'Libercom' => [
            'countries' => ['BJ'],
            'owner' => 'Benin Telecom',
        ],
        'Liberty' => [
            'countries' => ['CR', 'PR', 'VI'],
            'owner' => 'Liberty Latin America',
        ],
        'Libyana' => [
            'countries' => ['LY'],
            'owner' => 'State-owned via LPTIC',
        ],
        'Life' => [
            'countries' => ['BY'],
            'owner' => 'Turkcell',
        ],
        'lifecell' => [
            'countries' => ['UA'],
            'owner' => 'DVL Telecom',
        ],
        'LMT' => [
            'countries' => ['LV'],
            'owner' => 'Telia, Tet and Telia), Latvian State Radio and Television Centre, Possessor',
        ],
        'Lonestar Cell MTN' => [
            'countries' => ['LR'],
            'owner' => 'MTN',
        ],
        'LTC Mobile' => [
            'countries' => ['LR'],
            'owner' => 'Liberia Telecommunications Corporation',
        ],
        'Lumitel' => [
            'countries' => ['BI'],
            'owner' => 'Viettel Group',
        ],
        'Luxembourg Online' => [
            'countries' => ['LU'],
            'owner' => 'Luxembourg Online',
        ],
        'Lycamobile' => [
            'countries' => ['UG'],
        ],
        'M1' => [
            'countries' => ['SG'],
            'owner' => 'Keppel Corporation / Singapore Press Holdings / Connectivity Pte. Ltd.',
        ],
        'm:tel' => [
            'countries' => ['BA', 'ME'],
            'owner' => 'Telekom Srbija',
        ],
        'Magenta Telekom' => [
            'countries' => ['AT'],
            'owner' => 'Deutsche Telekom AG',
        ],
        'Magticom' => [
            'countries' => ['GE'],
            'owner' => 'International Tellcell LLC',
        ],
        'Magyar Telekom' => [
            'countries' => ['HU'],
            'owner' => 'Deutsche Telekom AG',
        ],
        'Manx Telecom' => [
            'countries' => ['IM'],
            'owner' => 'Basalt Investment Partners',
        ],
        'Maroc Telecom' => [
            'countries' => ['MA'],
            'owner' => 'Etisalat, Government Morocco',
        ],
        'Mascom' => [
            'countries' => ['BW'],
            'owner' => 'Mascom , MTN',
        ],
        'MATTEL' => [
            'countries' => ['MR'],
            'owner' => 'Tunisie Telecom, Mauritanian Investors',
        ],
        'Mauritius Telecom' => [
            'countries' => ['MU'],
            'owner' => 'Orange S.A.| Government of Mauritius| SBM Investments Managers Ltd| National Pensions Fund| Employees of Mauritius Telecom',
        ],
        'Maxis' => [
            'countries' => ['MY'],
            'owner' => 'Major shareholders: BGSM Equity) / Harapan Nusantara") / Saudi Telecom Company',
        ],
        'mcom' => [
            'countries' => ['NG'],
            'owner' => 'Mafab Communications',
        ],
        'MegaCom' => [
            'countries' => ['KG'],
            'owner' => 'JSC Alpha Telecom',
        ],
        'MegaFon' => [
            'countries' => ['GE', 'RU', 'TJ'],
            'owner' => 'CJSC Ostelkom',
        ],
        'Melita' => [
            'countries' => ['MT'],
            'owner' => 'Goldman Sachs Alternatives',
        ],
        'MEO' => [
            'countries' => ['PT'],
            'owner' => 'Altice Portugal',
        ],
        'Metfone' => [
            'countries' => ['KH'],
            'owner' => 'Viettel Cambodia',
        ],
        'Mighty Mobile' => [
            'countries' => ['NZ'],
            'owner' => 'Mighty Ape',
        ],
        'Miranda' => [
            'countries' => ['RU'],
            'owner' => 'OOO Miranda-Media',
        ],
        'MKS' => [
            'countries' => ['RU'],
            'owner' => 'OOO MKS',
        ],
        'Mobicom' => [
            'countries' => ['MN'],
            'owner' => 'KDDI, Sumitomo Corporation, Newcom Group',
        ],
        'MobiFone' => [
            'countries' => ['VN'],
            'owner' => 'Ministry of Public Security',
        ],
        'Mobile Cafe' => [
            'countries' => ['CI'],
            'owner' => 'Aircomm',
        ],
        'Mobilis' => [
            'countries' => ['DZ', 'NC'],
            'owner' => 'Office des Postes et Télécommunications de Nouvelle-Calédonie',
        ],
        'Mobily' => [
            'countries' => ['SA'],
            'owner' => 'Etisalat',
        ],
        'Mobiuz' => [
            'countries' => ['UZ'],
            'owner' => 'OOO «UMS»',
        ],
        'Mojo 3G' => [
            'countries' => ['TH'],
            'owner' => 'MConzult Asia Co., Ltd.',
        ],
        'Moldcell' => [
            'countries' => ['MD'],
            'owner' => 'CG Corp Global',
        ],
        'Moldtelecom' => [
            'countries' => ['MD'],
            'owner' => 'Moldtelecom',
        ],
        'Monaco Telecom' => [
            'countries' => ['MC'],
            'owner' => 'NJJ 55%, Société Nationale de Financement 45%',
        ],
        'Moov' => [
            'countries' => ['BJ', 'CI', 'TD'],
            'owner' => 'Etisalat',
        ],
        'Moov Africa' => [
            'countries' => ['CF'],
            'owner' => 'Maroc Telecom',
        ],
        'Moov Africa Gabon Telecom' => [
            'countries' => ['GA'],
            'owner' => 'Maroc Telecom, / Gabonese government',
        ],
        'Moov Africa Malitel' => [
            'countries' => ['ML'],
            'owner' => 'Maroc Telecom 51%, local investors 20%, government 19%, staff 10%',
        ],
        'Moov Africa Niger' => [
            'countries' => ['NE'],
            'owner' => 'Maroc Telecom',
        ],
        'Moov Africa Togo' => [
            'countries' => ['TG'],
            'owner' => 'Maroc Telecom',
        ],
        'Moov Mauritel' => [
            'countries' => ['MR'],
            'owner' => 'Maroc Telecom',
        ],
        'MOTIV' => [
            'countries' => ['RU'],
            'owner' => 'OOO EKATERINBURG-2000',
        ],
        'Movicel' => [
            'countries' => ['AO'],
            'owner' => 'Government, Correios Telegrafos de Angola, / Porturil, Modus Comicare, Ipang, Lambda',
        ],
        'Movilnet' => [
            'countries' => ['VE'],
            'owner' => 'CANTV',
        ],
        'Movistar' => [
            'countries' => ['AR', 'CO', 'ES', 'MX', 'SV', 'VE'],
            'owner' => 'Telefónica',
        ],
        'Movitel' => [
            'countries' => ['MZ'],
            'owner' => 'Viettel, SPI',
        ],
        'MPT' => [
            'countries' => ['MM'],
            'owner' => 'State-owned , KDDI , Sumitomo Corporation',
        ],
        'MTC' => [
            'countries' => ['NA'],
            'owner' => 'Namibia Post and Telecommunications Holdings / Portugal Telecom',
        ],
        'MTL' => [
            'countries' => ['MW'],
            'owner' => 'Press Corporation, NICO, Malawi Government',
        ],
        'MTN' => [
            'countries' => ['BJ', 'CG', 'CI', 'CM', 'GH', 'GN', 'GW', 'NG', 'SD', 'SS', 'SZ', 'ZA', 'ZM'],
            'owner' => 'MTN Group',
        ],
        'MTN Rwanda' => [
            'countries' => ['RW'],
            'owner' => 'MTN Group, Crystal Telecom',
        ],
        'MTN Syria' => [
            'countries' => ['SY'],
            'owner' => 'MTN Group',
        ],
        'MTN Uganda' => [
            'countries' => ['UG'],
            'owner' => 'MTN Group',
        ],
        'MTNL' => [
            'countries' => ['IN'],
            'owner' => 'Government of India / Subsidiary of Bharat Sanchar Nigam Limited',
        ],
        'MTS' => [
            'countries' => ['BY', 'RS', 'RU'],
            'owner' => 'Beltelecom / MTS',
        ],
        'MTS DOO' => [
            'countries' => ['XK'],
            'owner' => 'Telekom Srbija',
        ],
        'Mundo Móvil' => [
            'countries' => ['CL'],
            'owner' => 'DigitalBridge',
        ],
        'Muni' => [
            'countries' => ['GQ'],
            'owner' => 'Green-com S.A.',
        ],
        'my by NT' => [
            'countries' => ['TH'],
            'owner' => 'National Telecom',
        ],
        'MY Evolution Sdn. Bhd.' => [
            'countries' => ['MY'],
            'owner' => 'M2M services in Asia',
        ],
        'MyRepublic' => [
            'countries' => ['SG'],
            'owner' => 'MyRepublic',
        ],
        'Mytel' => [
            'countries' => ['MM'],
            'owner' => 'Viettel / Star High Telecom / Myanmar National Telecom Holding',
        ],
        'MyWorld 3G' => [
            'countries' => ['TH'],
            'owner' => 'Data CDMA Communication Co., Ltd.',
        ],
        'Móvil Éxito' => [
            'countries' => ['CO'],
            'owner' => 'Grupo Éxito',
        ],
        'Nakhtel' => [
            'countries' => ['AZ'],
            'owner' => 'Nakhtel LLC',
        ],
        'Nar' => [
            'countries' => ['AZ'],
            'owner' => 'Azerfon LLC',
        ],
        'NATCOM' => [
            'countries' => ['HT'],
            'owner' => '60% owned by Viettel Mobile and 40% by the Haitian State',
        ],
        'Nationlink' => [
            'countries' => ['SO'],
            'owner' => 'Bintel',
        ],
        'Ncell' => [
            'countries' => ['NP'],
            'owner' => 'Spectrlite UK Limited',
        ],
        'Nema' => [
            'countries' => ['FO'],
            'owner' => 'Nema',
        ],
        'Nepal Telecom' => [
            'countries' => ['NP'],
            'owner' => 'Nepal Doorsanchar Company Ltd.',
        ],
        'Netco' => [
            'countries' => ['SO'],
            'owner' => 'Somali Telecom Group',
        ],
        'NetOne' => [
            'countries' => ['ZW'],
            'owner' => 'NetOne Cellular Ltd',
        ],
        'Nexi' => [
            'countries' => ['KG'],
            'owner' => 'SoTel',
        ],
        'Nexttel' => [
            'countries' => ['CM'],
            'owner' => 'Viettel Global , Bestinver Cameroon S.A.R.L',
        ],
        'Niger Telecoms' => [
            'countries' => ['NE'],
            'owner' => 'Sahel-Com , Lap Green overtaken by government',
        ],
        'Norfolk Telecom' => [
            'countries' => ['NF'],
            'owner' => 'Norfolk Telecom',
        ],
        'Norlys' => [
            'countries' => ['DK'],
            'owner' => 'Norlys a.m.b.a.',
        ],
        'NOS' => [
            'countries' => ['PT'],
            'owner' => 'NOS',
        ],
        'Nova' => [
            'countries' => ['GR', 'IS'],
            'owner' => 'United Group',
        ],
        'Novafone' => [
            'countries' => ['LR'],
            'owner' => 'Comium Services BVI',
        ],
        'Now Telecom' => [
            'countries' => ['PH'],
            'owner' => 'Now Corporation',
        ],
        'NT Mobile' => [
            'countries' => ['TH'],
            'owner' => 'TOT Public Company Limited Migrating to my by nt in August 2025',
        ],
        'NTA' => [
            'countries' => ['MH'],
            'owner' => 'MINTA',
        ],
        'ntel' => [
            'countries' => ['NG'],
            'owner' => 'NatCom Development & Investment',
        ],
        'NTT Docomo' => [
            'countries' => ['JP'],
            'owner' => 'NTT',
        ],
        'Nuestro' => [
            'countries' => ['AR'],
            'owner' => 'FECOSUR',
        ],
        'O!' => [
            'countries' => ['KG'],
            'owner' => 'NUR Telecom Ltd.',
        ],
        'O2' => [
            'countries' => ['CZ', 'DE', 'GB', 'SK'],
            'owner' => 'PPF',
        ],
        'Odido' => [
            'countries' => ['NL'],
            'owner' => 'WP/AP Telecom Holdings IV B.V. /',
        ],
        'Omantel' => [
            'countries' => ['OM'],
            'owner' => 'Omantel',
        ],
        'Omnnea' => [
            'countries' => ['IQ'],
            'owner' => 'Omnnea',
        ],
        'ONAMOB' => [
            'countries' => ['BI'],
            'owner' => 'Onatel Burundi',
        ],
        'ONDO' => [
            'countries' => ['MN'],
            'owner' => 'ONDO LLC',
        ],
        'One' => [
            'countries' => ['BM', 'HU'],
            'owner' => '4iG',
        ],
        'One Albania' => [
            'countries' => ['AL'],
            'owner' => '4iG',
        ],
        'One Communications Guyana' => [
            'countries' => ['GY', 'PR', 'VI'],
            'owner' => 'Atlantic Tele-Network / Government of Guyana',
        ],
        'One Montenegro' => [
            'countries' => ['ME'],
            'owner' => '4iG',
        ],
        'One NZ' => [
            'countries' => ['NZ'],
            'owner' => 'Infratil Limited',
        ],
        'Ooredoo' => [
            'countries' => ['DZ', 'KW', 'MM', 'OM', 'PS', 'QA', 'TN'],
            'owner' => 'Ooredoo',
        ],
        'Ooredoo Maldives' => [
            'countries' => ['MV'],
            'owner' => 'Ooredoo',
        ],
        'OPEN SIM i-mobile' => [
            'countries' => ['TH'],
            'owner' => 'Samart Corporation Public Company Limited.',
        ],
        'Optus' => [
            'countries' => ['AU'],
            'owner' => 'Singtel',
        ],
        'Orange' => [
            'countries' => ['BE', 'BF', 'BW', 'CD', 'CF', 'CI', 'CM', 'EG', 'ES', 'FR', 'GN', 'GQ', 'GW', 'LR', 'LU', 'MD', 'MG', 'ML', 'PL', 'RE', 'RO', 'SK', 'SL', 'SN', 'TN'],
            'owner' => 'Orange S.A.',
        ],
        'Orange Caraïbe' => [
            'countries' => ['GF', 'GP', 'MQ'],
            'owner' => 'Orange S.A.',
        ],
        'Orange Jordan' => [
            'countries' => ['JO'],
            'owner' => 'Orange S.A.',
        ],
        'Orange Morocco' => [
            'countries' => ['MA'],
            'owner' => 'Orange S.A., / FinanceCom+CDG',
        ],
        'Our Telekom' => [
            'countries' => ['SB'],
            'owner' => 'Solomon Telekom Company Limited',
        ],
        'PalauCel' => [
            'countries' => ['PW'],
            'owner' => 'Palau National Communications Corporation',
        ],
        'Partner' => [
            'countries' => ['IL'],
            'owner' => 'Amphissa Holdings, Public and others',
        ],
        'Pavo Communications' => [
            'countries' => ['MY'],
            'owner' => 'Pavo Communications Sdn. Bhd.',
        ],
        'Pelephone' => [
            'countries' => ['IL'],
            'owner' => 'Bezeq',
        ],
        'Penguin' => [
            'countries' => ['TH'],
            'owner' => 'The WhiteSpace Co.Ltd.',
        ],
        'Personal' => [
            'countries' => ['AR', 'PY'],
            'owner' => 'Telecom Argentina',
        ],
        'Phoenix' => [
            'countries' => ['RU'],
            'owner' => 'SUE DPR "ROS"',
        ],
        'Planor' => [
            'countries' => ['ML'],
            'owner' => 'Planor',
        ],
        'Play' => [
            'countries' => ['PL'],
            'owner' => 'Iliad SA',
        ],
        'Plus' => [
            'countries' => ['PL'],
            'owner' => 'Grupa Polsat Plus',
        ],
        'POST' => [
            'countries' => ['LU'],
            'owner' => 'POST Luxembourg',
        ],
        'Progresif' => [
            'countries' => ['BN'],
            'owner' => 'Imagine Brunei',
        ],
        'Proximus' => [
            'countries' => ['BE'],
            'owner' => 'Proximus Group N.V./S.A. /',
        ],
        'Qcell' => [
            'countries' => ['GM', 'SL'],
            'owner' => 'Qcell',
        ],
        'Rain' => [
            'countries' => ['ZA'],
            'owner' => 'Rain 80% / ARC 20%',
        ],
        'Rakuten Mobile' => [
            'countries' => ['JP'],
            'owner' => 'Rakuten',
        ],
        'redONE' => [
            'countries' => ['MY', 'TH'],
            'owner' => 'red ONE Network Sdn. Bhd.',
        ],
        'Redtone' => [
            'countries' => ['MY'],
            'owner' => 'Berjaya Corporation Berhad',
        ],
        'Renna Mobile' => [
            'countries' => ['JO'],
        ],
        'RighTel' => [
            'countries' => ['IR'],
            'owner' => 'Social Security Investment Company',
        ],
        'Robi with Digital Sub Brand - Cirkle' => [
            'countries' => ['BD'],
            'owner' => 'Axiata Group Berhad Bharti Airtel General public',
        ],
        'Rocket Mobile' => [
            'countries' => ['NZ'],
            'owner' => 'My Republic',
        ],
        'Rogers' => [
            'countries' => ['CA'],
            'owner' => 'Rogers Communications',
        ],
        'Roshan' => [
            'countries' => ['AF'],
            'owner' => 'Aga Khan Fund for Economic Development',
        ],
        'Rwandatel' => [
            'countries' => ['RW'],
            'owner' => 'LAP Green Networks',
        ],
        'Sabafon' => [
            'countries' => ['YE'],
            'owner' => 'Al-Ahmar Group / Batelco / Hayel Saeed Anam & Co. Ltd. / Consolidated Constructors International company S.A.L.',
        ],
        'Safaricom' => [
            'countries' => ['KE'],
            'owner' => 'Vodacom / Government of Kenya / Public Float',
        ],
        'Safaricom Telecommunications Ethiopia' => [
            'countries' => ['ET'],
            'owner' => 'Safaricom| Sumitomo Corporation| British International Investment| Vodacom',
        ],
        'Saima Telecom' => [
            'countries' => ['KG'],
        ],
        'Salaam' => [
            'countries' => ['AF'],
        ],
        'Salt' => [
            'countries' => ['CH'],
            'owner' => 'NJJ',
        ],
        'Sapat Mobile' => [
            'countries' => ['KG'],
            'owner' => 'Winline Ltd.',
        ],
        'SaskTel' => [
            'countries' => ['CA'],
            'owner' => 'SaskTel',
        ],
        'Saudi Telecom Company' => [
            'countries' => ['KW'],
            'owner' => 'STC',
        ],
        'SemaTel' => [
            'countries' => ['CD'],
            'owner' => 'Hits Telecom',
        ],
        'SetarNV' => [
            'countries' => ['AW'],
            'owner' => 'Setar N.V.',
        ],
        'SFR' => [
            'countries' => ['FR', 'RE'],
            'owner' => 'Altice France',
        ],
        'SFR Caraïbe' => [
            'countries' => ['GF', 'GP', 'MQ'],
            'owner' => 'Numericable',
        ],
        'Sierratel' => [
            'countries' => ['SL'],
            'owner' => 'government, managed by MDIC',
        ],
        'Signalmover' => [
            'countries' => ['EE'],
            'owner' => 'Signalmover OÜ',
        ],
        'Silknet' => [
            'countries' => ['GE'],
            'owner' => 'Silk Road Group',
        ],
        'SIMBA' => [
            'countries' => ['SG'],
            'owner' => 'TUAS Limited',
        ],
        'Singtel' => [
            'countries' => ['SG'],
            'owner' => 'Temasek Holdings',
        ],
        'SK Telecom' => [
            'countries' => ['KR'],
            'owner' => 'SK Group',
        ],
        'Skinny' => [
            'countries' => ['NZ'],
            'owner' => 'Spark',
        ],
        'Skytel' => [
            'countries' => ['MN'],
            'owner' => 'Altai Holdings, Shunkhlai Group',
        ],
        'Slingshot Mobile' => [
            'countries' => ['NZ'],
            'owner' => 'Voyage Australia Pty Limited',
        ],
        'SLTMobitel' => [
            'countries' => ['LK'],
            'owner' => 'Sri Lanka Telecom PLC',
        ],
        'Smart' => [
            'countries' => ['BZ', 'KH'],
            'owner' => 'Speednet',
        ],
        'Smart Communications' => [
            'countries' => ['PH'],
            'owner' => 'PLDT /Public / NTT DoCoMo, Inc. / Philippine Telecommunications Investment Corp. / JG Summit Holdings / Metro Pacific Resources, Inc. / NTT Communications Corp / First Pacific',
        ],
        'Smart Mobile' => [
            'countries' => ['BI'],
            'owner' => 'Smart Telecom',
        ],
        'SmartCell' => [
            'countries' => ['NP'],
            'owner' => 'Smart Private Telcom Limited',
        ],
        'SmarTone' => [
            'countries' => ['HK'],
            'owner' => 'Sun Hung Kai Properties',
        ],
        'Smile' => [
            'countries' => ['CD'],
            'owner' => 'Smile Communications',
        ],
        'Smile Telecom' => [
            'countries' => ['UG'],
        ],
        'SoftBank Corp.' => [
            'countries' => ['JP'],
            'owner' => 'SoftBank Group',
        ],
        'Somafone' => [
            'countries' => ['SO'],
            'owner' => 'Somali Telecom Group',
        ],
        'SomNet' => [
            'countries' => ['SO'],
            'owner' => 'SomNet Telecom',
        ],
        'Somtel' => [
            'countries' => ['SO'],
            'owner' => 'Dahabshiil',
        ],
        'Sotelgui' => [
            'countries' => ['GN'],
            'owner' => 'Telekom Malaysia , government',
        ],
        'Spark' => [
            'countries' => ['NZ'],
            'owner' => 'Public',
        ],
        'SPM Telecom' => [
            'countries' => ['FR'],
            'owner' => 'Orange S.A., Landry',
        ],
        'StarHub' => [
            'countries' => ['SG'],
            'owner' => 'ST Telemedia / Ooredoo / NTT Docomo',
        ],
        'stc' => [
            'countries' => ['BH', 'SA'],
            'owner' => 'STC',
        ],
        'Sudani' => [
            'countries' => ['SD', 'SS'],
            'owner' => 'Sudatel[not operationally active]',
        ],
        'Suma Móvil' => [
            'countries' => ['CO'],
        ],
        'Sunrise' => [
            'countries' => ['CH'],
            'owner' => 'Free float',
        ],
        'Supercell' => [
            'countries' => ['CD'],
        ],
        'Sure' => [
            'countries' => ['FK', 'GG', 'IM', 'JE', 'SH'],
            'owner' => 'Batelco',
        ],
        'Surf' => [
            'countries' => ['BR'],
            'owner' => 'Surf Telecom S.A.',
        ],
        'Swisscom' => [
            'countries' => ['CH'],
            'owner' => 'Partially state-owned, free float',
        ],
        'Swisscom FL' => [
            'countries' => ['LI'],
            'owner' => 'Swisscom',
        ],
        'Syriatel' => [
            'countries' => ['SY'],
            'owner' => 'Rami Makhlouf',
        ],
        'Síminn' => [
            'countries' => ['IS'],
            'owner' => 'Síminn',
        ],
        'Sýn' => [
            'countries' => ['IS'],
            'owner' => 'Free float',
        ],
        'T-2' => [
            'countries' => ['SI'],
            'owner' => 'Garnol /',
        ],
        'T-MAIS' => [
            'countries' => ['CV'],
            'owner' => 'T-Mais',
        ],
        'T-Mobile' => [
            'countries' => ['CZ', 'PL', 'PR', 'US', 'VI'],
            'owner' => 'Deutsche Telekom AG',
        ],
        't2' => [
            'countries' => ['NG', 'RU'],
            'owner' => 'PJSC Rostelecom',
        ],
        'Taiwan Mobile' => [
            'countries' => ['TW'],
            'owner' => 'Fubon Group',
        ],
        'Tango' => [
            'countries' => ['LU'],
            'owner' => 'Proximus Group',
        ],
        'TashiCell' => [
            'countries' => ['BT'],
            'owner' => 'Tashi Group',
        ],
        'TCC' => [
            'countries' => ['TO'],
            'owner' => 'Tonga Government',
        ],
        'Tcell' => [
            'countries' => ['TJ'],
            'owner' => 'Aga Khan Fund for Economic Development',
        ],
        'TDC' => [
            'countries' => ['DK'],
            'owner' => 'Macquarie Group and Danish pension funds',
        ],
        'Team' => [
            'countries' => ['AM'],
            'owner' => 'Telecom Armenia CJSC',
        ],
        'Telcel' => [
            'countries' => ['MX'],
            'owner' => 'América Móvil',
        ],
        'Telcom Somalia' => [
            'countries' => ['SO'],
            'owner' => 'Haatif Telecom Somalia',
        ],
        'Tele2' => [
            'countries' => ['EE', 'LT', 'LV', 'SE'],
            'owner' => 'Tele2',
        ],
        'Tele2 Kazakhstan' => [
            'countries' => ['KZ'],
            'owner' => 'Power International Holding',
        ],
        'telecel' => [
            'countries' => ['CF', 'ZW'],
            'owner' => 'Orascom Telecom',
        ],
        'Telecel Faso' => [
            'countries' => ['BF'],
            'owner' => 'Planor Afrique',
        ],
        'Telecel Ghana' => [
            'countries' => ['GH'],
            'owner' => 'Telecel Group Government of Ghana',
        ],
        'Telecom Niue' => [
            'countries' => ['NU'],
            'owner' => 'Telecom Niue',
        ],
        'Telekom' => [
            'countries' => ['DE', 'MK', 'SK'],
            'owner' => 'Deutsche Telekom AG',
        ],
        'Telekom GR' => [
            'countries' => ['GR'],
            'owner' => 'OTE A.E. /',
        ],
        'Telekom Romania' => [
            'countries' => ['RO'],
            'owner' => 'Vodafone Group plc / DIGI',
        ],
        'Telekom Slovenije' => [
            'countries' => ['SI'],
            'owner' => 'Telekom Slovenije',
        ],
        'Telemach' => [
            'countries' => ['HR', 'SI'],
            'owner' => 'United Group',
        ],
        'Telemor' => [
            'countries' => ['TL'],
            'owner' => 'Viettel',
        ],
        'Telenor' => [
            'countries' => ['DK', 'NO', 'SE'],
            'owner' => 'Telenor',
        ],
        'Telenor Pakistan' => [
            'countries' => ['PK'],
            'owner' => 'PTCL',
        ],
        'Telesom Mobile' => [
            'countries' => ['SO'],
            'owner' => 'Telesom Group',
        ],
        'Telesur' => [
            'countries' => ['SR'],
            'owner' => 'Telesur',
        ],
        'Teletalk' => [
            'countries' => ['BD'],
            'owner' => 'State-owned',
        ],
        'Telia' => [
            'countries' => ['EE', 'FI', 'NO', 'SE'],
            'owner' => 'Telia Company',
        ],
        'Telia Lietuva' => [
            'countries' => ['LT'],
            'owner' => 'Telia Company',
        ],
        'Telikom PNG' => [
            'countries' => ['PG'],
            'owner' => 'Telikom LTD',
        ],
        'Telin Malaysia' => [
            'countries' => ['MY'],
            'owner' => 'Telin Malaysia is a joint venture company between Compudyne Holdings Sdn. Bhd. and PT. Telin , a company fully owned by PT. Telekomunikasi Indonesia.',
        ],
        'Telkom' => [
            'countries' => ['ZA'],
            'owner' => 'Telkom',
        ],
        'Telkom Kenya' => [
            'countries' => ['KE'],
            'owner' => 'Government of Kenya',
        ],
        'Telkomcel' => [
            'countries' => ['TL'],
            'owner' => 'Telekomunikasi Indonesia International S.A.',
        ],
        'Telkomsel' => [
            'countries' => ['ID'],
            'owner' => 'Telkom Indonesia / Singtel',
        ],
        'Telma' => [
            'countries' => ['KM'],
            'owner' => 'Telecom Madagascar',
        ],
        'Telma Mobile' => [
            'countries' => ['MG'],
            'owner' => 'Distacom Group, Telma SA, Hiridjee group',
        ],
        'Telmob' => [
            'countries' => ['BF'],
            'owner' => 'Maroc Telecom',
        ],
        'Telstra' => [
            'countries' => ['AU'],
            'owner' => 'Telstra',
        ],
        'Telus' => [
            'countries' => ['CA'],
            'owner' => 'Telus Communications',
        ],
        'Three' => [
            'countries' => ['IE'],
            'owner' => 'CK Hutchison Holdings',
        ],
        'Tigo' => [
            'countries' => ['BO', 'CL', 'CO', 'EC', 'GT', 'HN', 'NI', 'PA', 'PY', 'SV', 'TZ', 'UY'],
            'owner' => 'Millicom 42.2%)',
        ],
        'TIM' => [
            'countries' => ['BR', 'IT'],
            'owner' => 'TIM Group',
        ],
        'TIM San Marino' => [
            'countries' => ['SM'],
            'owner' => 'TIM',
        ],
        'Timor Telecom' => [
            'countries' => ['TL'],
            'owner' => 'Timor Telecom',
        ],
        'TM' => [
            'countries' => ['PH'],
        ],
        'TM CELL' => [
            'countries' => ['TM'],
            'owner' => 'TM CELL',
        ],
        'Tmcel' => [
            'countries' => ['MZ'],
            'owner' => 'Partially state-owned',
        ],
        'TN Mobile' => [
            'countries' => ['NA'],
            'owner' => 'Telecom Namibia Ltd.',
        ],
        'TNM' => [
            'countries' => ['MW'],
            'owner' => 'Malawi Telecommunications Limited , Telekom Malaysia',
        ],
        'TNT' => [
            'countries' => ['PH'],
            'owner' => 'Smart Communications',
        ],
        'Togocel' => [
            'countries' => ['TG'],
            'owner' => 'Togo Telecom',
        ],
        'Tosa' => [
            'countries' => ['FO'],
            'owner' => 'Tosa',
        ],
        'touch' => [
            'countries' => ['LB'],
            'owner' => 'government - Managed by Ministry of Telecom',
        ],
        'TPLUS' => [
            'countries' => ['LA'],
            'owner' => 'Government of Lao P.D.R.',
        ],
        'TracFone Wireless' => [
            'countries' => ['PR', 'VI'],
            'owner' => 'Verizon',
        ],
        'Tre' => [
            'countries' => ['SE'],
            'owner' => 'CK Hutchison Holdings, Investor AB',
        ],
        'Triatel' => [
            'countries' => ['LV'],
        ],
        'True' => [
            'countries' => ['TH'],
            'owner' => 'Major shareholders: Telenor / Thai NVDR / Charoen Pokphand / China Mobile',
        ],
        'TTC' => [
            'countries' => ['TV'],
            'owner' => 'TTC',
        ],
        'Tuenti' => [
            'countries' => ['AR', 'EC'],
            'owner' => 'Telefónica',
        ],
        'Tune Talk' => [
            'countries' => ['MY', 'TH'],
            'owner' => 'Gurtaj Singh CelcomDigi',
        ],
        'Tunisie Telecom' => [
            'countries' => ['TN'],
            'owner' => 'Tunisie Télécom Group, / Tecom DIG',
        ],
        'Turkcell' => [
            'countries' => ['TR'],
            'owner' => 'Turkey Wealth Fund, LetterOne, free float',
        ],
        'tusass' => [
            'countries' => ['GL'],
            'owner' => 'TELE Greenland',
        ],
        'Tuyo Móvil' => [
            'countries' => ['CR'],
            'owner' => 'Televisora de Costa Rica S.A.',
        ],
        'Türk Telekom' => [
            'countries' => ['TR'],
            'owner' => 'Türk Telekom /, Ministry of Treasury and Finance, free float)',
        ],
        'U Mobile' => [
            'countries' => ['MY'],
            'owner' => 'Major Shareholders: Mawar Setia Sdn. Bhd. Ibrahim Ismail of Johor / ST Telemedia',
        ],
        'U-Com' => [
            'countries' => ['CD'],
            'owner' => 'Global Vision Telecom',
        ],
        'Ucell' => [
            'countries' => ['UZ'],
            'owner' => 'ООО «COSCOM»',
        ],
        'Ucom' => [
            'countries' => ['AM'],
            'owner' => 'Ucom',
        ],
        'Ufone' => [
            'countries' => ['PK'],
            'owner' => 'Government of Pakistan Etisalat by e& General public',
        ],
        'Umniah' => [
            'countries' => ['JO'],
            'owner' => 'Batelco',
        ],
        'Unifi Mobile' => [
            'countries' => ['MY'],
            'owner' => 'Telekom Malaysia',
        ],
        'Unitel' => [
            'countries' => ['AO', 'LA', 'MN'],
            'owner' => 'Lao Asia Telecommunication State Enterprise, Viettel Global',
        ],
        'UTel' => [
            'countries' => ['UG'],
            'owner' => 'Uganda Telecommunications Corporation Limited',
        ],
        'Uzmobile' => [
            'countries' => ['UZ'],
            'owner' => 'Uztelecom',
        ],
        'Vainah Telecom' => [
            'countries' => ['RU'],
            'owner' => 'JSC Vainah Telecom',
        ],
        'Vala' => [
            'countries' => ['XK'],
            'owner' => 'Telecom of Kosovo',
        ],
        'Verizon' => [
            'countries' => ['US'],
            'owner' => 'Verizon Communications Inc.',
        ],
        'Vi' => [
            'countries' => ['IN'],
            'owner' => 'Government Of India / Vodafone Group Plc / Aditya Birla Group / Free Float',
        ],
        'Vidéotron' => [
            'countries' => ['CA'],
            'owner' => 'Quebecor',
        ],
        'Vietnamobile' => [
            'countries' => ['VN'],
            'owner' => 'Hanoi Telecom Hutchison Asia Telecom',
        ],
        'Viettel' => [
            'countries' => ['VN'],
            'owner' => 'Ministry of Defence',
        ],
        'Vinaphone' => [
            'countries' => ['VN'],
            'owner' => 'Vietnam Posts and Telecommunications Group',
        ],
        'VINI' => [
            'countries' => ['PF'],
            'owner' => 'ONATi',
        ],
        'Virgin Mobile' => [
            'countries' => ['AE', 'CL', 'CO', 'JO'],
            'owner' => 'Virgin Mobile',
        ],
        'Virgin mobile KSA' => [
            'countries' => ['SA'],
        ],
        'VITI' => [
            'countries' => ['PF'],
        ],
        'Viva' => [
            'countries' => ['AM', 'BO', 'DO'],
            'owner' => 'Viva Armenia / Fedilco Group Limited and Konstantin Sokolov) and Government of Armenia',
        ],
        'Vivacell' => [
            'countries' => ['SS'],
            'owner' => 'Fattouch Investment Group, Wawat Securities',
        ],
        'Vivacom' => [
            'countries' => ['BG'],
            'owner' => 'United Group',
        ],
        'VIVIFI' => [
            'countries' => ['SG'],
            'owner' => 'Icymi Pte. Ltd.',
        ],
        'Vivo' => [
            'countries' => ['BR'],
            'owner' => 'Telefônica Brasil',
        ],
        'Vodacom' => [
            'countries' => ['CD', 'LS', 'MZ', 'TZ', 'ZA'],
            'owner' => 'Vodacom',
        ],
        'Vodafone' => [
            'countries' => ['CK', 'CZ', 'ES', 'FJ', 'GB', 'GR', 'IE', 'NL', 'OM', 'PF', 'PT', 'QA', 'RO', 'TR', 'UA'],
            'owner' => 'Vodafone Group plc',
        ],
        'Vodafone Albania' => [
            'countries' => ['AL'],
            'owner' => 'Vodafone Group plc',
        ],
        'Vodafone AU' => [
            'countries' => ['AU'],
            'owner' => 'TPG Telecom',
        ],
        'Vodafone Egypt' => [
            'countries' => ['EG'],
            'owner' => 'Vodacom Group, Telecom Egypt',
        ],
        'Vodafone Germany' => [
            'countries' => ['DE'],
            'owner' => 'Vodafone Group plc',
        ],
        'Vodafone PNG' => [
            'countries' => ['PG'],
            'owner' => 'Amalgamated Telecom Holdings Limited',
        ],
        'Vodafone Samoa' => [
            'countries' => ['WS'],
            'owner' => 'Amalgamated Telecom Holdings Limited Unit Trust of Samoa',
        ],
        'Vodafone Turkey' => [
            'countries' => ['TR'],
            'owner' => 'Vodafone Group plc',
        ],
        'Vodafone Vanuatu' => [
            'countries' => ['VU'],
            'owner' => 'Vodafone Vanuatu',
        ],
        'VOX' => [
            'countries' => ['PY'],
            'owner' => 'COPACO S.A.',
        ],
        'VTR Móvil' => [
            'countries' => ['CL'],
            'owner' => 'ClaroVTR',
        ],
        'Wantok' => [
            'countries' => ['TO', 'VU'],
            'owner' => 'Tonga Government',
        ],
        'Warehouse Mobile' => [
            'countries' => ['NZ'],
            'owner' => 'The Warehouse Group',
        ],
        'Warid' => [
            'countries' => ['CI'],
            'owner' => 'Warid Telecom',
        ],
        'We' => [
            'countries' => ['EG'],
            'owner' => 'Egyptian government, free float',
        ],
        'wecom' => [
            'countries' => ['IL'],
            'owner' => 'Marathon Telecom Ltd',
        ],
        'Wind Tre' => [
            'countries' => ['IT'],
            'owner' => 'CK Hutchison Holdings',
        ],
        'WOM' => [
            'countries' => ['CL', 'CO'],
            'owner' => 'Novator Partners',
        ],
        'XLSMART' => [
            'countries' => ['ID'],
            'owner' => 'Axiata Investments Indonesia / Sinar Mas',
        ],
        'Xomobile' => [
            'countries' => ['LV'],
        ],
        'XOX' => [
            'countries' => ['MY'],
            'owner' => 'XOX Bhd.',
        ],
        'Y Telecom' => [
            'countries' => ['YE'],
            'owner' => 'Formerly, owned by Saudi and Kuwaiti corporations. Currently owned by the family of Abdrabbuh Mansur Hadi',
        ],
        'Yemen 4G يمن فورجي' => [
            'countries' => ['YE'],
            'owner' => 'المؤسسة العامة للاتصالات السلكية واللاسلكية',
        ],
        'Yemen Mobile' => [
            'countries' => ['YE'],
            'owner' => 'Government-run company with shareholders.',
        ],
        'Yes' => [
            'countries' => ['MY'],
            'owner' => 'YTL Power',
        ],
        'Yettel' => [
            'countries' => ['BG', 'HU', 'RS'],
            'owner' => 'e& PPF Telecom Group',
        ],
        'YooMee' => [
            'countries' => ['CI', 'CM'],
            'owner' => 'YooMee',
        ],
        'YOU' => [
            'countries' => ['YE'],
            'owner' => 'Emerald International Investment, a subsidiary of the Zubair Corporation',
        ],
        'Zain' => [
            'countries' => ['BH', 'IQ', 'KW', 'SA', 'SD', 'SS'],
            'owner' => 'Zain Group',
        ],
        'Zain Jordan' => [
            'countries' => ['JO'],
            'owner' => 'Zain Group',
        ],
        'Zamani Telecom' => [
            'countries' => ['NE'],
            'owner' => 'Zamani Com',
        ],
        'Zamtel' => [
            'countries' => ['ZM'],
            'owner' => 'Zambia Telecommunications Company Ltd',
        ],
        'Zantel' => [
            'countries' => ['TZ'],
            'owner' => 'Etisalat and Zanzibar Telecom Ltd',
        ],
        'Zero1' => [
            'countries' => ['SG'],
            'owner' => 'Zero1 Pte. Ltd.',
        ],
        'ZET Mobile' => [
            'countries' => ['TJ'],
            'owner' => 'ZET Mobile Ltd.',
        ],
        'Zong' => [
            'countries' => ['PK'],
            'owner' => 'China Mobile Limited',
        ],
        'ZYM Mobile' => [
            'countries' => ['SG'],
            'owner' => 'MDR Limited',
        ],
        'Ålcom' => [
            'countries' => ['FI'],
            'owner' => 'Mariehamns Telefon, Ålands Telefonandelslag',
        ],
    ];
};
