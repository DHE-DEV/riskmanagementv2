<?php

namespace App\Services\QrCode;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use InvalidArgumentException;

/**
 * QR-Codes als Modul-Matrix, SVG (Bildschirm) und PDF (Download) – ohne Rasterbilder,
 * damit der Code in jeder Groesse scharf bleibt.
 *
 * Kodiert wird mit bacon/bacon-qr-code (Fehlerkorrektur M, ~15 %).
 */
class QrCodeGenerator
{
    /** Ruhezone um den Code in Modulen (Norm: mindestens 4). */
    public const QUIET_ZONE = 4;

    /** Reisenden-Seite der Travel Detail Links. */
    public const TRAVEL_DETAILS_URL = 'https://travel-details.eu';

    public const TRAVEL_DETAILS_LANGUAGES = ['de', 'en', 'nl'];

    /** Arten von QR-Codes – gleich fuer adminv2 (System > QR-Codes) und die API (GET /v1/qr-codes). */
    public const TYPE_TRIPDATA = 'tripdata';

    public const TYPE_TRIPDATA_APP = 'tripdata-app';

    public const TYPE_TRAVEL_DETAILS = 'travel-details';

    public const TYPE_FREE = 'free';

    public const TYPES = [
        self::TYPE_TRIPDATA => ['label' => 'TripData-Link', 'description' => 'https://platform.passolution.de/reise/{code} – oeffnet die TripData-App, ohne App die Reisenden-Seite.'],
        self::TYPE_TRIPDATA_APP => ['label' => 'App-Direktlink', 'description' => 'tripdata://reise/{code} – oeffnet die TripData-App direkt, ohne App passiert nichts.'],
        self::TYPE_TRAVEL_DETAILS => ['label' => 'Travel Details', 'description' => 'https://travel-details.eu/{lang}?tid={code} – die Reisenden-Seite im Browser.'],
        self::TYPE_FREE => ['label' => 'Freier Inhalt', 'description' => 'Text oder Link, unveraendert kodiert.'],
    ];

    /** Hoechstlaenge fuer freien Inhalt, damit der Code gut lesbar bleibt. */
    public const MAX_FREE_LENGTH = 1500;

    /**
     * Inhalt des QR-Codes fuer eine Art: Reise-ID bzw. Text/Link -> Link oder Text.
     * null, wenn die Eingabe nicht passt (keine Reise-ID, leer oder zu lang).
     */
    public static function contentFor(string $type, string $value, string $language = 'de'): ?string
    {
        if ($type === self::TYPE_FREE) {
            $text = trim($value);

            return $text === '' || strlen($text) > self::MAX_FREE_LENGTH ? null : $text;
        }

        $code = self::normalizeTravelDetailsCode($value);
        if ($code === null) {
            return null;
        }

        return match ($type) {
            self::TYPE_TRIPDATA => self::tripDataUrl($code),
            self::TYPE_TRIPDATA_APP => self::tripDataAppUrl($code),
            self::TYPE_TRAVEL_DETAILS => self::travelDetailsUrl($code, $language),
            default => null,
        };
    }

    /**
     * Code eines Travel Detail Links normalisieren: "ZIPT-HRG1-Z6Y5", "zipthrg1z6y5",
     * ein Link mit "?tid=…" oder ein Link, der auf den Code endet. null, wenn kein Code erkennbar ist.
     */
    public static function normalizeTravelDetailsCode(string $input): ?string
    {
        $candidate = trim($input);
        if ($candidate === '') {
            return null;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $candidate)) {
            parse_str((string) parse_url($candidate, PHP_URL_QUERY), $query);
            $tid = collect($query)->first(fn ($value, $key) => strtolower((string) $key) === 'tid');
            $path = trim((string) parse_url($candidate, PHP_URL_PATH), '/');
            $candidate = is_string($tid) && $tid !== '' ? $tid : (string) last(explode('/', $path));
        }

        $letters = preg_replace('/[^A-Z0-9]/', '', strtoupper($candidate));

        return strlen($letters) === 12 ? implode('-', str_split($letters, 4)) : null;
    }

    /** Link der Reisenden-Seite zu einem (normalisierten) Code, z. B. https://travel-details.eu/de?tid=ZIPTHRG1Z6Y5. */
    public static function travelDetailsUrl(string $code, string $language = 'de'): string
    {
        $language = in_array($language, self::TRAVEL_DETAILS_LANGUAGES, true) ? $language : 'de';

        return self::TRAVEL_DETAILS_URL.'/'.$language.'?tid='.str_replace('-', '', $code);
    }

    /**
     * Reise-Link der TripData-App, z. B. https://platform.passolution.de/reise/ZIPTHRG1Z6Y5 – mit
     * installierter App oeffnet ihn das Smartphone direkt in TripData, sonst leitet die Plattform auf
     * travel-details.eu weiter.
     */
    public static function tripDataUrl(string $code): string
    {
        return rtrim((string) config('services.tripdata.link_base', 'https://platform.passolution.de/reise'), '/')
            .'/'.str_replace('-', '', $code);
    }

    /**
     * App-Direktlink der TripData-App, z. B. tripdata://reise/ZIPTHRG1Z6Y5 – oeffnet die App ueber ihr
     * eigenes Schema, ohne Server und Associated Domains; ohne installierte App passiert beim Scannen nichts.
     */
    public static function tripDataAppUrl(string $code): string
    {
        return 'tripdata://reise/'.str_replace('-', '', $code);
    }

    /**
     * Modul-Matrix ohne Ruhezone: $matrix[$y][$x] === true heisst dunkles Modul.
     *
     * @return array<int, array<int, bool>>
     */
    public function matrix(string $content): array
    {
        if ($content === '') {
            throw new InvalidArgumentException('Der Inhalt des QR-Codes ist leer.');
        }

        // ohne ECI-Praefix: manche Scanner lesen UTF-8 mit Praefix nicht (Hinweis in Bacon).
        $byteMatrix = Encoder::encode($content, ErrorCorrectionLevel::M(), Encoder::DEFAULT_BYTE_MODE_ENCODING, null, false)->getMatrix();
        $size = $byteMatrix->getWidth();

        $matrix = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $matrix[$y][$x] = $byteMatrix->get($x, $y) === 1;
            }
        }

        return $matrix;
    }

    /** SVG mit einem einzigen Pfad; skaliert ueber die CSS-Breite. */
    public function svg(string $content): string
    {
        $matrix = $this->matrix($content);
        $size = count($matrix) + 2 * self::QUIET_ZONE;

        $path = '';
        foreach ($matrix as $y => $row) {
            $x = 0;
            $width = count($row);
            while ($x < $width) {
                if (! $row[$x]) {
                    $x++;

                    continue;
                }
                $start = $x;
                while ($x < $width && $row[$x]) {
                    $x++;
                }
                // waagerechte Laeufe zusammenfassen: deutlich kleineres SVG als ein Rechteck je Modul
                $path .= sprintf('M%d %dh%dv1h-%dz', $start + self::QUIET_ZONE, $y + self::QUIET_ZONE, $x - $start, $x - $start);
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %1$d" shape-rendering="crispEdges" role="img">'
            .'<rect width="%1$d" height="%1$d" fill="#fff"/><path fill="#000" d="%2$s"/></svg>',
            $size,
            $path,
        );
    }

    /**
     * A4-PDF mit dem QR-Code als Vektorgrafik, Titel, gross gesetztem Code bzw. Untertitel und dem Inhalt.
     */
    public function pdf(string $content, ?string $title = null, ?string $caption = null): string
    {
        return (new QrPdfDocument)->render($this->matrix($content), $content, $title, $caption);
    }
}
