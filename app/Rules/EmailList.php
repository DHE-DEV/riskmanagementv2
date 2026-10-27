<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Mehrere E-Mail-Adressen in einem Feld, durch Komma, Semikolon oder
 * Leerzeichen getrennt (z. B. Rechnungsempfaenger An/CC/BCC).
 */
class EmailList implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $invalid = array_filter(
            preg_split('/[\s,;]+/', (string) $value),
            fn ($email) => $email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL),
        );

        if ($invalid) {
            $fail('Keine gültige E-Mail-Adresse: '.implode(', ', $invalid));
        }
    }
}
