<?php

namespace App\Support;

use App\Models\Customer;

/**
 * Mail-Einstellungen einer Organisation (Einstellungen › Organisationsstruktur
 * › Einstellungen): wer bei Aenderungen ihrer Agenturen und Filialen eine Mail
 * bekommt und wie sie aussieht. Gespeichert in customers.org_mail_settings;
 * "enabled" ist customers.notify_address_changes, damit Admin v2 und die
 * Organisation denselben Schalter bedienen.
 */
class OrgMailSettings
{
    /** Welche E-Mail-Adresse der Agentur bzw. Filiale die Mail bekommt. */
    public const AGENCY_EMAIL_OPTIONS = [
        'account' => 'Login-E-Mail des Accounts',
        'primary' => 'Haupt-E-Mail-Adresse (erste hinterlegte)',
        'all' => 'Alle hinterlegten E-Mail-Adressen',
    ];

    /** Platzhalter fuer Betreff und Inhalt mit Beschreibung. */
    public const PLACEHOLDERS = [
        '{art}' => 'Art der Änderung, z. B. „Adressänderung“ oder „Filiale gelöscht“',
        '{agentur}' => 'Name der Agentur bzw. Filiale',
        '{app_id}' => 'App-ID der Agentur bzw. Filiale',
        '{alte_adresse}' => 'Bisherige Anschrift in einer Zeile',
        '{neue_adresse}' => 'Neue Anschrift in einer Zeile',
        '{aenderungen}' => 'Tabelle mit allen Feldern, bisher und neu',
        '{geaendert_von}' => 'Wer die Änderung vorgenommen hat',
        '{datum}' => 'Datum und Uhrzeit der Änderung',
        '{grund}' => 'Angegebener Grund (beim Löschen)',
        '{loeschdatum}' => 'Geplanter Löschtermin',
        '{startdatum}' => 'Startdatum der Filiale',
        '{enddatum}' => 'Enddatum der Filiale (beim Löschen)',
        '{organisation}' => 'Name Ihrer Organisation',
    ];

    public const DEFAULT_SUBJECT = '{art}: {agentur}';

    public const DEFAULT_BODY = "Guten Tag,\n\nbei {agentur} ({app_id}) gab es eine Änderung: {art}.\n\n{aenderungen}\n\nGeändert von: {geaendert_von}\nZeitpunkt: {datum}\n\nMit freundlichen Grüßen\n{organisation}";

    /**
     * @return array{enabled: bool, to: list<string>, cc: list<string>, bcc: list<string>, notify_actor: bool, notify_agency: bool, agency_email: string, subject: string, format: string, body: string}
     */
    public static function for(Customer $customer): array
    {
        $stored = is_array($customer->org_mail_settings) ? $customer->org_mail_settings : [];

        return [
            'enabled' => (bool) $customer->notify_address_changes,
            'to' => self::emails($stored['to'] ?? []),
            'cc' => self::emails($stored['cc'] ?? []),
            'bcc' => self::emails($stored['bcc'] ?? []),
            'notify_actor' => (bool) ($stored['notify_actor'] ?? false),
            'notify_agency' => (bool) ($stored['notify_agency'] ?? true),
            'agency_email' => array_key_exists($stored['agency_email'] ?? '', self::AGENCY_EMAIL_OPTIONS) ? $stored['agency_email'] : 'account',
            'subject' => trim((string) ($stored['subject'] ?? '')) ?: self::DEFAULT_SUBJECT,
            'format' => ($stored['format'] ?? 'text') === 'html' ? 'html' : 'text',
            'body' => trim((string) ($stored['body'] ?? '')) !== '' ? (string) $stored['body'] : self::DEFAULT_BODY,
        ];
    }

    /**
     * Gueltige, eindeutige Adressen aus Liste oder kommagetrennter Eingabe.
     *
     * @return list<string>
     */
    public static function emails(mixed $value): array
    {
        $items = is_array($value) ? $value : preg_split('/[\s,;]+/', (string) $value);

        return array_values(array_unique(array_filter(
            array_map(fn ($email) => mb_strtolower(trim((string) $email)), $items),
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        )));
    }
}
