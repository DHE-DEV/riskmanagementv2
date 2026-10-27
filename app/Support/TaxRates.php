<?php

namespace App\Support;

/**
 * Umsatzsteuer auf die netto erfassten Betraege (Kunden, Kooperationen).
 */
class TaxRates
{
    public const DEFAULT = '19';

    /** Wert => Bezeichnung */
    public const OPTIONS = [
        '19' => '19 % Umsatzsteuer',
        '0' => '0 % (steuerfrei bzw. Reverse Charge)',
    ];

    public static function label(mixed $rate): string
    {
        return self::OPTIONS[self::key($rate)] ?? rtrim(rtrim(number_format((float) $rate, 2, ',', '.'), '0'), ',').' %';
    }

    /** 19.00 → "19" */
    public static function key(mixed $rate): string
    {
        return $rate === null ? self::DEFAULT : (string) (int) round((float) $rate);
    }
}
