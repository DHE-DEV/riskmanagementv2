<?php

namespace App\Services\QrCode;

/**
 * Minimaler PDF-Schreiber fuer genau eine A4-Seite mit QR-Code (Vektor) und Text in Helvetica.
 *
 * Bewusst ohne PDF-Bibliothek: Rechtecke und Text mit einer der 14 Standardschriften braucht
 * keine Einbettung, und die Ausgabe bleibt klein und scharf. Text wird nach Windows-1252
 * umgesetzt (WinAnsiEncoding), damit Umlaute stimmen.
 */
class QrPdfDocument
{
    private const PAGE_WIDTH = 595.28;   // A4 in pt

    private const PAGE_HEIGHT = 841.89;

    private const QR_SIZE = 340.0;       // Kantenlaenge inkl. Ruhezone in pt (~12 cm)

    /**
     * @param  array<int, array<int, bool>>  $matrix
     */
    public function render(array $matrix, string $content, ?string $title, ?string $caption): string
    {
        $modules = count($matrix) + 2 * QrCodeGenerator::QUIET_ZONE;
        $module = self::QR_SIZE / $modules;
        $left = (self::PAGE_WIDTH - self::QR_SIZE) / 2;
        $top = 640.0; // Oberkante des QR-Codes (PDF-Koordinaten zaehlen von unten)

        $ops = [];

        if ($title !== null && trim($title) !== '') {
            $ops[] = $this->centeredText(trim($title), 'F2', 22, 720);
        }

        $ops[] = '0 g';
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
                $ops[] = sprintf(
                    '%.3F %.3F %.3F %.3F re',
                    $left + ($start + QrCodeGenerator::QUIET_ZONE) * $module,
                    $top - ($y + QrCodeGenerator::QUIET_ZONE + 1) * $module,
                    ($x - $start) * $module,
                    $module,
                );
            }
        }
        $ops[] = 'f';

        $below = $top - self::QR_SIZE - 40;
        if ($caption !== null && trim($caption) !== '') {
            $ops[] = $this->centeredText(trim($caption), 'F3', 26, $below);
            $below -= 30;
        }
        foreach ($this->wrap($content, 80) as $line) {
            $ops[] = $this->centeredText($line, 'F1', 10, $below, '0.35 g');
            $below -= 14;
        }

        $stream = implode("\n", $ops);

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Contents 4 0 R '
                .'/Resources << /Font << /F1 5 0 R /F2 6 0 R /F3 7 0 R >> >> >>',
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
            ),
            sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($stream), $stream),
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold /Encoding /WinAnsiEncoding >>',
            sprintf('<< /Producer (Passolution Plattform) /Title (%s) >>', $this->escape($title ?: 'QR-Code')),
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= sprintf("trailer\n<< /Size %d /Root 1 0 R /Info %d 0 R >>\nstartxref\n%d\n%%%%EOF\n", count($objects) + 1, count($objects), $xref);

        return $pdf;
    }

    private function centeredText(string $text, string $font, float $size, float $y, string $color = '0 g'): string
    {
        $width = $this->textWidth($text, $font, $size);

        return sprintf('BT %s /%s %.1F Tf %.2F %.2F Td (%s) Tj ET', $color, $font, $size, (self::PAGE_WIDTH - $width) / 2, $y, $this->escape($text));
    }

    /**
     * Textbreite zum Zentrieren: Courier exakt (0,6 em), Helvetica naeherungsweise je Zeichenart
     * (Werte aus den AFM-Metriken gemittelt) – genau genug fuer eine zentrierte Zeile.
     */
    private function textWidth(string $text, string $font, float $size): float
    {
        if ($font === 'F3') {
            return mb_strlen($text) * 0.6 * $size;
        }

        $bold = $font === 'F2';
        $em = 0.0;
        foreach (mb_str_split($text) as $char) {
            $em += match (true) {
                $char === ' ' => 0.278,
                (bool) preg_match('/[0-9]/', $char) => 0.556,
                (bool) preg_match('/[iljtfI.,:;!\'|]/u', $char) => $bold ? 0.3 : 0.25,
                (bool) preg_match('/[mwMW]/u', $char) => $bold ? 0.9 : 0.85,
                (bool) preg_match('/\p{Lu}/u', $char) => $bold ? 0.72 : 0.67,
                (bool) preg_match('/\p{Ll}/u', $char) => $bold ? 0.58 : 0.53,
                $char === '–' || $char === '—' => $bold ? 0.556 : 0.556,
                default => 0.4,
            };
        }

        return $em * $size;
    }

    /** @return array<int, string> */
    private function wrap(string $text, int $width): array
    {
        return mb_str_split($text, $width) ?: [''];
    }

    private function escape(string $text): string
    {
        $encoded = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);

        return strtr($encoded === false ? $text : $encoded, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => ' ']);
    }
}
