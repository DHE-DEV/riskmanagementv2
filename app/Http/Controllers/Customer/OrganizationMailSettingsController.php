<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\OrganizationChangeNotifier;
use App\Support\OrgMailSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Einstellungen › Organisationsstruktur › Einstellungen: Mail bei Aenderungen
 * der Agenturen und Filialen (Empfaenger, Betreff, Inhalt).
 */
class OrganizationMailSettingsController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'to' => 'array',
            'to.*' => 'email',
            'cc' => 'array',
            'cc.*' => 'email',
            'bcc' => 'array',
            'bcc.*' => 'email',
            'notify_actor' => 'boolean',
            'notify_agency' => 'boolean',
            'agency_email' => ['required', Rule::in(array_keys(OrgMailSettings::AGENCY_EMAIL_OPTIONS))],
            'subject' => 'nullable|string|max:255',
            'format' => ['required', Rule::in(['text', 'html'])],
            'body' => 'nullable|string|max:100000',
        ], [
            '*.*.email' => 'Bitte nur gültige E-Mail-Adressen eintragen.',
        ]);

        $customer = auth('customer')->user();
        $customer->update([
            'notify_address_changes' => (bool) ($validated['enabled'] ?? false),
            'org_mail_settings' => [
                'to' => OrgMailSettings::emails($validated['to'] ?? []),
                'cc' => OrgMailSettings::emails($validated['cc'] ?? []),
                'bcc' => OrgMailSettings::emails($validated['bcc'] ?? []),
                'notify_actor' => (bool) ($validated['notify_actor'] ?? false),
                'notify_agency' => (bool) ($validated['notify_agency'] ?? false),
                'agency_email' => $validated['agency_email'],
                'subject' => trim((string) ($validated['subject'] ?? '')),
                'format' => $validated['format'],
                'body' => (string) ($validated['body'] ?? ''),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Einstellungen gespeichert',
            'settings' => OrgMailSettings::for($customer->fresh()),
        ]);
    }

    /**
     * Vorschau mit Beispieldaten – so, wie die Mail mit den aktuellen (noch nicht
     * gespeicherten) Eingaben aussehen wuerde.
     */
    public function preview(Request $request)
    {
        $customer = auth('customer')->user();
        $settings = [
            'subject' => trim((string) $request->input('subject')) ?: OrgMailSettings::DEFAULT_SUBJECT,
            'format' => $request->input('format') === 'html' ? 'html' : 'text',
            'body' => (string) $request->input('body') !== '' ? (string) $request->input('body') : OrgMailSettings::DEFAULT_BODY,
        ];

        $mail = OrganizationChangeNotifier::render($settings, [
            'art' => OrganizationChangeNotifier::KIND_ADDRESS,
            'agentur' => 'Reisebüro Muster',
            'app_id' => 'AB12',
            'alte_adresse' => 'Altstraße 1, 10115 Berlin, Deutschland',
            'neue_adresse' => 'Neuweg 5, 80331 München, Deutschland',
            'geaendert_von' => 'Max Mustermann (max@example.com)',
            'datum' => now()->timezone('Europe/Berlin')->format('d.m.Y H:i').' Uhr',
            'grund' => '',
            'loeschdatum' => '',
            'startdatum' => now()->format('d.m.Y'),
            'enddatum' => '',
            'organisation' => $customer->company_name ?: (string) $customer->name,
        ], [
            ['label' => 'Straße', 'old' => 'Altstraße', 'new' => 'Neuweg', 'changed' => true],
            ['label' => 'Hausnummer', 'old' => '1', 'new' => '5', 'changed' => true],
            ['label' => 'PLZ', 'old' => '10115', 'new' => '80331', 'changed' => true],
            ['label' => 'Ort', 'old' => 'Berlin', 'new' => 'München', 'changed' => true],
            ['label' => 'Land', 'old' => 'Deutschland', 'new' => 'Deutschland', 'changed' => false],
        ]);

        return response()->json(['success' => true, ...$mail]);
    }
}
