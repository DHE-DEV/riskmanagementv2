<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class CountryFeedController extends Controller
{
    /**
     * Base URL for the application
     */
    private string $baseUrl;

    /**
     * Cache duration in seconds (from .env: FEED_CACHE_DURATION, default 3600)
     */
    private int $cacheDuration;

    public function __construct()
    {
        $baseUrl = config('app.url');
        // Fix common URL typo: https// -> https://
        $this->baseUrl = preg_replace('#^(https?)//(?!/)#', '$1://', $baseUrl);
        $this->cacheDuration = (int) config('feed.cache_duration', 3600);
    }

    /**
     * Get all countries with their details in RSS format
     * Route: /feed/countries/names/all.xml
     */
    public function allCountries(): Response
    {
        $cacheKey = 'feed:countries:all:rss';

        $content = Cache::remember($cacheKey, $this->cacheDuration, function () {
            $countries = $this->getCountriesWithDetails();
            return $this->generateRss(
                $countries,
                'Länderverzeichnis - Alle Länder',
                'Vollständiges Verzeichnis aller Länder mit Details'
            );
        });

        return response($content, 200)->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Get countries by continent
     * Route: /feed/countries/continent/{code}.xml
     */
    public function byContinent(string $continentCode): Response
    {
        $cacheKey = "feed:countries:continent:{$continentCode}:rss";

        $content = Cache::remember($cacheKey, $this->cacheDuration, function () use ($continentCode) {
            $countries = $this->getCountriesWithDetails()
                ->filter(fn($country) => $country->continent && strtoupper($country->continent->code) === strtoupper($continentCode));

            $continentName = $countries->first()?->continent?->getName('de') ?? strtoupper($continentCode);

            return $this->generateRss(
                $countries,
                "Länderverzeichnis - {$continentName}",
                "Länder in {$continentName}"
            );
        });

        return response($content, 200)->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Get EU member countries
     * Route: /feed/countries/eu.xml
     */
    public function euCountries(): Response
    {
        $cacheKey = 'feed:countries:eu:rss';

        $content = Cache::remember($cacheKey, $this->cacheDuration, function () {
            $countries = $this->getCountriesWithDetails()
                ->filter(fn($country) => $country->is_eu_member);

            return $this->generateRss(
                $countries,
                'Länderverzeichnis - EU-Mitgliedsstaaten',
                'Alle Mitgliedsstaaten der Europäischen Union'
            );
        });

        return response($content, 200)->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Get Schengen member countries
     * Route: /feed/countries/schengen.xml
     */
    public function schengenCountries(): Response
    {
        $cacheKey = 'feed:countries:schengen:rss';

        $content = Cache::remember($cacheKey, $this->cacheDuration, function () {
            $countries = $this->getCountriesWithDetails()
                ->filter(fn($country) => $country->is_schengen_member);

            return $this->generateRss(
                $countries,
                'Länderverzeichnis - Schengen-Staaten',
                'Alle Mitgliedsstaaten des Schengen-Raums'
            );
        });

        return response($content, 200)->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Get all countries with eager loading
     */
    private function getCountriesWithDetails()
    {
        return Country::with(['continent', 'capital'])
            ->orderBy('iso_code')
            ->get();
    }

    /**
     * Generate RSS 2.0 feed for countries
     */
    private function generateRss($countries, string $title, string $description): string
    {
        $buildDate = now()->toRfc2822String();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#" xmlns:country="http://riskmanagement.local/xmlns/country">' . PHP_EOL;
        $xml .= '  <channel>' . PHP_EOL;
        $xml .= '    <title>' . $this->escapeXml($title) . '</title>' . PHP_EOL;
        $xml .= '    <link>' . $this->escapeXml($this->baseUrl) . '</link>' . PHP_EOL;
        $xml .= '    <description>' . $this->escapeXml($description) . '</description>' . PHP_EOL;
        $xml .= '    <language>de-DE</language>' . PHP_EOL;
        $xml .= '    <lastBuildDate>' . $buildDate . '</lastBuildDate>' . PHP_EOL;
        $xml .= '    <atom:link href="' . $this->escapeXml(url()->current()) . '" rel="self" type="application/rss+xml" />' . PHP_EOL;
        $xml .= '    <ttl>1440</ttl>' . PHP_EOL; // 24 hours - country data doesn't change often

        foreach ($countries as $country) {
            $xml .= $this->generateCountryItem($country);
        }

        $xml .= '  </channel>' . PHP_EOL;
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Generate RSS item for a country
     */
    private function generateCountryItem($country): string
    {
        $countryName = $country->getName('de');
        $capital = $country->capital;
        $continent = $country->continent;

        // Build description with all required fields
        $details = [];
        $details[] = 'ISO: ' . ($country->iso_code ?? '-');
        $details[] = 'ISO3: ' . ($country->iso3_code ?? '-');
        $details[] = 'EU: ' . ($country->is_eu_member ? 'Ja' : 'Nein');
        $details[] = 'Schengen: ' . ($country->is_schengen_member ? 'Ja' : 'Nein');
        $details[] = 'Kontinent: ' . ($continent ? $continent->getName('de') : '-');
        $details[] = 'Währung: ' . ($country->currency_code ?? '-');
        $details[] = 'Vorwahl: ' . ($country->phone_prefix ?? '-');

        if ($capital) {
            $details[] = 'Hauptstadt: ' . $capital->getName('de');
            $details[] = 'Hauptstadt-Koordinaten: ' . ($capital->lat ?? '-') . ', ' . ($capital->lng ?? '-');
        } else {
            $details[] = 'Hauptstadt: -';
            $details[] = 'Hauptstadt-Koordinaten: -, -';
        }

        $descriptionText = implode(' | ', $details);

        $xml = '    <item>' . PHP_EOL;
        $xml .= '      <title>' . $this->escapeXml($countryName) . '</title>' . PHP_EOL;
        $xml .= '      <link>' . $this->escapeXml($this->baseUrl . '/?country=' . $country->iso_code) . '</link>' . PHP_EOL;
        $xml .= '      <guid isPermaLink="false">country-' . $this->escapeXml($country->iso_code) . '</guid>' . PHP_EOL;
        $xml .= '      <description>' . $this->escapeXml($descriptionText) . '</description>' . PHP_EOL;

        // Custom country namespace elements for structured data
        $xml .= '      <country:name>' . $this->escapeXml($countryName) . '</country:name>' . PHP_EOL;
        $xml .= '      <country:iso_code>' . $this->escapeXml($country->iso_code ?? '') . '</country:iso_code>' . PHP_EOL;
        $xml .= '      <country:iso3_code>' . $this->escapeXml($country->iso3_code ?? '') . '</country:iso3_code>' . PHP_EOL;
        $xml .= '      <country:is_eu_member>' . ($country->is_eu_member ? 'true' : 'false') . '</country:is_eu_member>' . PHP_EOL;
        $xml .= '      <country:is_schengen_member>' . ($country->is_schengen_member ? 'true' : 'false') . '</country:is_schengen_member>' . PHP_EOL;
        $xml .= '      <country:continent>' . $this->escapeXml($continent ? $continent->getName('de') : '') . '</country:continent>' . PHP_EOL;
        $xml .= '      <country:currency_code>' . $this->escapeXml($country->currency_code ?? '') . '</country:currency_code>' . PHP_EOL;
        $xml .= '      <country:phone_prefix>' . $this->escapeXml($country->phone_prefix ?? '') . '</country:phone_prefix>' . PHP_EOL;

        // Capital information
        if ($capital) {
            $xml .= '      <country:capital>' . PHP_EOL;
            $xml .= '        <country:capital_name>' . $this->escapeXml($capital->getName('de')) . '</country:capital_name>' . PHP_EOL;
            if ($capital->lat !== null && $capital->lng !== null) {
                $xml .= '        <geo:lat>' . $capital->lat . '</geo:lat>' . PHP_EOL;
                $xml .= '        <geo:long>' . $capital->lng . '</geo:long>' . PHP_EOL;
            }
            $xml .= '      </country:capital>' . PHP_EOL;
        }

        // Categories
        if ($continent) {
            $xml .= '      <category>' . $this->escapeXml($continent->getName('de')) . '</category>' . PHP_EOL;
        }
        if ($country->is_eu_member) {
            $xml .= '      <category>EU-Mitglied</category>' . PHP_EOL;
        }
        if ($country->is_schengen_member) {
            $xml .= '      <category>Schengen</category>' . PHP_EOL;
        }

        // Country flag as enclosure
        $imageUrl = $this->getCountryImageUrl($country->iso_code);
        if ($imageUrl) {
            $xml .= '      <enclosure url="' . $this->escapeXml($imageUrl) . '" type="image/jpeg" />' . PHP_EOL;
        }

        $xml .= '    </item>' . PHP_EOL;

        return $xml;
    }

    /**
     * Get country cover image URL
     */
    private function getCountryImageUrl(string $countryCode): ?string
    {
        $imagePath = public_path('images/countries/' . strtolower($countryCode) . '.jpg');

        if (file_exists($imagePath)) {
            return $this->baseUrl . '/images/countries/' . strtolower($countryCode) . '.jpg';
        }

        return null;
    }

    /**
     * Escape XML special characters
     */
    private function escapeXml(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
