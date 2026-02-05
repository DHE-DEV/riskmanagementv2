<?php

namespace App\Http\Controllers;

use App\Services\VisumPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisumPointController extends Controller
{
    public function __construct(
        protected VisumPointService $visumPointService
    ) {}

    /**
     * Show the visa check form
     */
    public function index(): View
    {
        $countries = $this->getCountries();
        $isConfigured = $this->visumPointService->isConfigured();

        return view('livewire.pages.visumpoint-check', [
            'countries' => $countries,
            'isConfigured' => $isConfigured,
        ]);
    }

    /**
     * Check visa requirements
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nationality' => 'required|string|size:2',
            'destinationCountry' => 'required|string|size:2',
            'residenceCountry' => 'required|string|size:2',
            'language' => 'nullable|string|in:de,en,fr',
            'format' => 'nullable|string|in:markdown,html,text,json',
        ]);

        if (!$this->visumPointService->isConfigured()) {
            return response()->json([
                'success' => false,
                'error' => 'Der VisumPoint Service ist nicht konfiguriert.',
            ], 503);
        }

        // Set language if provided, defaults to 'de'
        $language = $validated['language'] ?? 'de';
        $this->visumPointService->setLanguage($language);

        // Convert 2-letter to 3-letter ISO codes
        $nationality3 = VisumPointService::iso2to3($validated['nationality']);
        $destination3 = VisumPointService::iso2to3($validated['destinationCountry']);
        $residence3 = VisumPointService::iso2to3($validated['residenceCountry']);
        $format = $validated['format'] ?? 'markdown';

        $result = $this->visumPointService->checkVisa(
            $destination3,
            $nationality3,
            $residence3,
            $format
        );

        $debugLog = $this->visumPointService->getDebugLog();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'debugLog' => $debugLog,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Ein Fehler ist aufgetreten',
            'details' => $result['details'] ?? null,
            'debugLog' => $result['debugLog'] ?? $debugLog,
        ], 422);
    }

    /**
     * Manually end the current session
     */
    public function endSession(): JsonResponse
    {
        $result = $this->visumPointService->endSession();

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Session erfolgreich beendet',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Fehler beim Beenden der Session',
        ], 500);
    }

    /**
     * Get list of countries with ISO codes
     */
    protected function getCountries(): array
    {
        return [
            'AF' => 'Afghanistan',
            'AL' => 'Albanien',
            'DZ' => 'Algerien',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AG' => 'Antigua und Barbuda',
            'AR' => 'Argentinien',
            'AM' => 'Armenien',
            'AU' => 'Australien',
            'AT' => 'Österreich',
            'AZ' => 'Aserbaidschan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesch',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgien',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BT' => 'Bhutan',
            'BO' => 'Bolivien',
            'BA' => 'Bosnien und Herzegowina',
            'BW' => 'Botswana',
            'BR' => 'Brasilien',
            'BN' => 'Brunei',
            'BG' => 'Bulgarien',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Kambodscha',
            'CM' => 'Kamerun',
            'CA' => 'Kanada',
            'CV' => 'Kap Verde',
            'CF' => 'Zentralafrikanische Republik',
            'TD' => 'Tschad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CO' => 'Kolumbien',
            'KM' => 'Komoren',
            'CG' => 'Kongo',
            'CD' => 'Demokratische Republik Kongo',
            'CR' => 'Costa Rica',
            'CI' => 'Elfenbeinküste',
            'HR' => 'Kroatien',
            'CU' => 'Kuba',
            'CY' => 'Zypern',
            'CZ' => 'Tschechien',
            'DK' => 'Dänemark',
            'DJ' => 'Dschibuti',
            'DM' => 'Dominica',
            'DO' => 'Dominikanische Republik',
            'EC' => 'Ecuador',
            'EG' => 'Ägypten',
            'SV' => 'El Salvador',
            'GQ' => 'Äquatorialguinea',
            'ER' => 'Eritrea',
            'EE' => 'Estland',
            'SZ' => 'Eswatini',
            'ET' => 'Äthiopien',
            'FJ' => 'Fidschi',
            'FI' => 'Finnland',
            'FR' => 'Frankreich',
            'GA' => 'Gabun',
            'GM' => 'Gambia',
            'GE' => 'Georgien',
            'DE' => 'Deutschland',
            'GH' => 'Ghana',
            'GR' => 'Griechenland',
            'GD' => 'Grenada',
            'GT' => 'Guatemala',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HN' => 'Honduras',
            'HU' => 'Ungarn',
            'IS' => 'Island',
            'IN' => 'Indien',
            'ID' => 'Indonesien',
            'IR' => 'Iran',
            'IQ' => 'Irak',
            'IE' => 'Irland',
            'IL' => 'Israel',
            'IT' => 'Italien',
            'JM' => 'Jamaika',
            'JP' => 'Japan',
            'JO' => 'Jordanien',
            'KZ' => 'Kasachstan',
            'KE' => 'Kenia',
            'KI' => 'Kiribati',
            'KP' => 'Nordkorea',
            'KR' => 'Südkorea',
            'KW' => 'Kuwait',
            'KG' => 'Kirgisistan',
            'LA' => 'Laos',
            'LV' => 'Lettland',
            'LB' => 'Libanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libyen',
            'LI' => 'Liechtenstein',
            'LT' => 'Litauen',
            'LU' => 'Luxemburg',
            'MG' => 'Madagaskar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Malediven',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshallinseln',
            'MR' => 'Mauretanien',
            'MU' => 'Mauritius',
            'MX' => 'Mexiko',
            'FM' => 'Mikronesien',
            'MD' => 'Moldawien',
            'MC' => 'Monaco',
            'MN' => 'Mongolei',
            'ME' => 'Montenegro',
            'MA' => 'Marokko',
            'MZ' => 'Mosambik',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Niederlande',
            'NZ' => 'Neuseeland',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'MK' => 'Nordmazedonien',
            'NO' => 'Norwegen',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palästina',
            'PA' => 'Panama',
            'PG' => 'Papua-Neuguinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippinen',
            'PL' => 'Polen',
            'PT' => 'Portugal',
            'QA' => 'Katar',
            'RO' => 'Rumänien',
            'RU' => 'Russland',
            'RW' => 'Ruanda',
            'KN' => 'St. Kitts und Nevis',
            'LC' => 'St. Lucia',
            'VC' => 'St. Vincent und die Grenadinen',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'São Tomé und Príncipe',
            'SA' => 'Saudi-Arabien',
            'SN' => 'Senegal',
            'RS' => 'Serbien',
            'SC' => 'Seychellen',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapur',
            'SK' => 'Slowakei',
            'SI' => 'Slowenien',
            'SB' => 'Salomonen',
            'SO' => 'Somalia',
            'ZA' => 'Südafrika',
            'SS' => 'Südsudan',
            'ES' => 'Spanien',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SE' => 'Schweden',
            'CH' => 'Schweiz',
            'SY' => 'Syrien',
            'TW' => 'Taiwan',
            'TJ' => 'Tadschikistan',
            'TZ' => 'Tansania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TO' => 'Tonga',
            'TT' => 'Trinidad und Tobago',
            'TN' => 'Tunesien',
            'TR' => 'Türkei',
            'TM' => 'Turkmenistan',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'Vereinigte Arabische Emirate',
            'GB' => 'Vereinigtes Königreich',
            'US' => 'Vereinigte Staaten',
            'UY' => 'Uruguay',
            'UZ' => 'Usbekistan',
            'VU' => 'Vanuatu',
            'VA' => 'Vatikanstadt',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'YE' => 'Jemen',
            'ZM' => 'Sambia',
            'ZW' => 'Simbabwe',
        ];
    }
}
