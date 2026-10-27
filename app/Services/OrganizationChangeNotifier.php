<?php

namespace App\Services;

use App\Mail\BranchChangedMail;
use App\Mail\CustomerAddressChangedMail;
use App\Mail\OrganizationChangeMail;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EmailAddress;
use App\Models\Employee;
use App\Support\OrgMailSettings;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Mails bei Aenderungen an Agenturen und Filialen einer Organisation:
 *  - immer eine Infomail an Passolution (config app.customer_address_change_mail),
 *  - zusaetzlich die Mail der Organisation, wenn sie das unter Einstellungen ›
 *    Organisationsstruktur › Einstellungen eingeschaltet hat (OrgMailSettings).
 *
 * Fehler beim Einstellen der Mails werden nur protokolliert – gespeichert ist
 * die Aenderung dann schon.
 */
class OrganizationChangeNotifier
{
    public const KIND_ADDRESS = 'Adressänderung';

    public const KIND_BRANCH_CREATED = 'Neue Filiale';

    public const KIND_BRANCH_UPDATED = 'Adressänderung';

    public const KIND_BRANCH_DELETED = 'Filiale gelöscht';

    public const KIND_BRANCH_SCHEDULED = 'Löschung geplant';

    public const KIND_BRANCH_CANCELLED = 'Löschung abgebrochen';

    /** Adressfelder einer Filiale mit Bezeichnung. */
    public const BRANCH_FIELDS = [
        'location_type' => 'Art',
        'name' => 'Name',
        'additional' => 'Zusatz',
        'street' => 'Straße',
        'house_number' => 'Hausnummer',
        'postal_code' => 'PLZ',
        'city' => 'Ort',
        'country' => 'Land',
        'start_date' => 'Startdatum',
        'end_date' => 'Enddatum',
    ];

    /**
     * Firmen- oder Rechnungsadresse eines Kunden hat sich geaendert. Die
     * Organisation ist seine Zentrale (assign_to).
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function customerAddressChanged(Customer $customer, string $type, array $before, array $after): void
    {
        if (! CustomerAddressChangedMail::hasChanges($type, $before, $after)) {
            return;
        }

        $actor = $this->actor($customer);

        $this->safely($customer->id, function () use ($customer, $type, $before, $after, $actor) {
            if ($internal = $this->internalRecipients()) {
                Mail::to($internal['to'])->cc($internal['cc'])
                    ->queue(new CustomerAddressChangedMail($customer, $type, $before, $after, $actor['label']));
            }

            $organization = $customer->assign_to ? $customer->assignedTo : null;
            if ($organization) {
                $rows = $this->rows(CustomerAddressChangedMail::FIELDS[$type], $before, $after);
                $this->sendOrganizationMail($organization, [
                    'art' => self::KIND_ADDRESS.' ('.CustomerAddressChangedMail::TYPE_LABELS[$type].')',
                    'agentur' => $customer->company_name ?: ($customer->name ?: $customer->email),
                    'app_id' => (string) $customer->app_code,
                    'alte_adresse' => $this->addressLine($before, $type.'_'),
                    'neue_adresse' => $this->addressLine($after, $type.'_'),
                ], $rows, $actor, $this->agencyEmails($organization, $customer));
            }
        });
    }

    public function branchCreated(Branch $branch): void
    {
        $after = $branch->only(array_keys(self::BRANCH_FIELDS));
        $this->branchEvent($branch, self::KIND_BRANCH_CREATED, array_fill_keys(array_keys(self::BRANCH_FIELDS), null), $after);
    }

    /**
     * @param  array<string, mixed>  $before
     */
    public function branchUpdated(Branch $branch, array $before): void
    {
        $after = $branch->only(array_keys(self::BRANCH_FIELDS));
        if (! $this->differs($before, $after)) {
            return;
        }

        $this->branchEvent($branch, self::KIND_BRANCH_UPDATED, $before, $after);
    }

    /**
     * Filiale geloescht oder Loeschung geplant; $before ist der Stand vor dem
     * Eintragen des Enddatums.
     *
     * @param  array<string, mixed>  $before
     */
    public function branchDeletionRequested(Branch $branch, array $before, ?CarbonInterface $scheduledAt, ?string $reason): void
    {
        $after = $branch->only(array_keys(self::BRANCH_FIELDS));
        $this->branchEvent($branch, $scheduledAt ? self::KIND_BRANCH_SCHEDULED : self::KIND_BRANCH_DELETED, $before, $after, $reason, $scheduledAt);
    }

    public function branchDeletionCancelled(Branch $branch): void
    {
        $current = $branch->only(array_keys(self::BRANCH_FIELDS));
        $this->branchEvent($branch, self::KIND_BRANCH_CANCELLED, $current, $current);
    }

    /**
     * Wer die Aenderung vorgenommen hat: Mitarbeiter-Login, Agentur-Wechsel,
     * die Zentrale fuer ein Buero oder der Kunde selbst.
     *
     * @return array{label: string, email: ?string}
     */
    public function actor(?Customer $subject = null): array
    {
        if ($employee = Employee::find(session('logged_in_employee_id'))) {
            return ['label' => trim($employee->first_name.' '.$employee->last_name).' ('.$employee->email.', Mitarbeiter)', 'email' => $employee->email];
        }

        if (session()->has('original_customer_id') && ($original = Customer::find(session('original_customer_id')))) {
            return ['label' => ($original->company_name ?: $original->name).' ('.$original->email.', über Agentur-Wechsel)', 'email' => $original->email];
        }

        $actor = auth('customer')->user();
        if ($actor && $subject && $actor->id !== $subject->id) {
            return ['label' => ($actor->company_name ?: $actor->name).' ('.$actor->email.', Zentrale)', 'email' => $actor->email];
        }

        $actor ??= $subject;

        return ['label' => 'Kunde selbst ('.$actor?->email.')', 'email' => $actor?->email];
    }

    /**
     * Platzhalter in Betreff und Inhalt ersetzen. Im Textformat wird der Inhalt
     * maskiert und in das Standard-Layout gesetzt, im HTML-Format so verwendet,
     * wie die Organisation ihn gestaltet hat (ohne Skripte).
     *
     * @param  array<string, string>  $vars
     * @param  list<array{label: string, old: string, new: string, changed: bool}>  $rows
     * @return array{subject: string, html: string}
     */
    public static function render(array $settings, array $vars, array $rows): array
    {
        $table = view('emails.partials.change-rows', ['rows' => $rows])->render();
        $placeholders = [];
        foreach ($vars as $key => $value) {
            $placeholders['{'.$key.'}'] = (string) $value;
        }

        $subject = trim(preg_replace('/\s+/', ' ', strtr($settings['subject'], $placeholders)));

        $escaped = array_map(fn ($value) => e($value), $placeholders);
        $escaped['{aenderungen}'] = $table;

        if ($settings['format'] === 'html') {
            $body = preg_replace('#<(script|iframe|object|embed)\b[^>]*>.*?</\1>|<(script|iframe|object|embed)\b[^>]*/?>#is', '', $settings['body']);
            $html = strtr($body, $escaped);
        } else {
            $text = nl2br(e($settings['body']), false);
            $html = view('emails.organization-change', ['content' => strtr($text, $escaped)])->render();
        }

        return ['subject' => $subject, 'html' => $html];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    protected function branchEvent(Branch $branch, string $kind, array $before, array $after, ?string $reason = null, ?CarbonInterface $scheduledAt = null): void
    {
        $owner = $branch->customer;
        $actor = $this->actor($owner);
        $rows = $this->rows(self::BRANCH_FIELDS, $before, $after);

        $this->safely($owner?->id, function () use ($branch, $owner, $kind, $rows, $before, $after, $actor, $reason, $scheduledAt) {
            if ($internal = $this->internalRecipients()) {
                Mail::to($internal['to'])->cc($internal['cc'])
                    ->queue(new BranchChangedMail($branch, $kind, $rows, $actor['label'], $reason, $scheduledAt));
            }

            if ($owner) {
                $this->sendOrganizationMail($owner, [
                    'art' => $kind,
                    'agentur' => (string) ($after['name'] ?: $before['name']),
                    'app_id' => (string) $branch->app_code,
                    'alte_adresse' => $kind === self::KIND_BRANCH_CREATED ? '' : $this->addressLine($before),
                    'neue_adresse' => $this->addressLine($after),
                    'grund' => (string) $reason,
                    'loeschdatum' => $scheduledAt?->format('d.m.Y') ?? '',
                    'startdatum' => $branch->start_date?->format('d.m.Y') ?? '',
                    'enddatum' => $branch->end_date?->format('d.m.Y') ?? '',
                ], $rows, $actor, $this->branchEmails($owner, $branch));
            }
        });
    }

    /**
     * Mail der Organisation, sofern eingeschaltet. Empfaenger: die eingestellten
     * An/CC/BCC (ohne An die E-Mail der Organisation), dazu auf Wunsch die
     * Agentur und wer die Aenderung vorgenommen hat.
     *
     * @param  array<string, string>  $vars
     * @param  list<array{label: string, old: string, new: string, changed: bool}>  $rows
     * @param  array{label: string, email: ?string}  $actor
     * @param  list<string>  $agencyEmails
     */
    protected function sendOrganizationMail(Customer $organization, array $vars, array $rows, array $actor, array $agencyEmails): void
    {
        $settings = OrgMailSettings::for($organization);
        if (! $settings['enabled']) {
            return;
        }

        $to = $settings['to'] ?: OrgMailSettings::emails([$organization->email]);
        if ($settings['notify_agency']) {
            $to = [...$to, ...$agencyEmails];
        }
        if ($settings['notify_actor'] && $actor['email']) {
            $to[] = $actor['email'];
        }
        $to = OrgMailSettings::emails($to);
        if ($to === []) {
            return;
        }

        $vars += ['grund' => '', 'loeschdatum' => '', 'startdatum' => '', 'enddatum' => ''];
        $vars['geaendert_von'] = $actor['label'];
        $vars['datum'] = now()->timezone('Europe/Berlin')->format('d.m.Y H:i').' Uhr';
        $vars['organisation'] = $organization->company_name ?: ($organization->name ?: '');

        $mail = self::render($settings, $vars, $rows);

        Mail::to($to)
            ->cc(array_values(array_diff($settings['cc'], $to)))
            ->bcc(array_values(array_diff($settings['bcc'], $to, $settings['cc'])))
            ->queue(new OrganizationChangeMail($mail['subject'], $mail['html']));
    }

    /**
     * E-Mail-Adressen eines zugeordneten Bueros (Unteraccount) nach Einstellung.
     *
     * @return list<string>
     */
    protected function agencyEmails(Customer $organization, Customer $agency): array
    {
        $choice = OrgMailSettings::for($organization)['agency_email'];
        $stored = EmailAddress::where('customer_id', $agency->id)->whereNull('branch_id')
            ->orderByDesc('is_primary')->orderBy('sort_order')->pluck('email')->all();

        return OrgMailSettings::emails(match ($choice) {
            'primary' => array_slice($stored, 0, 1) ?: [$agency->email],
            'all' => $stored ?: [$agency->email],
            default => [$agency->email],
        });
    }

    /**
     * E-Mail-Adressen einer Filiale. Eine Filiale hat keinen eigenen Login –
     * "Login-E-Mail" nimmt dann ihre Haupt-E-Mail-Adresse.
     *
     * @return list<string>
     */
    protected function branchEmails(Customer $organization, Branch $branch): array
    {
        $choice = OrgMailSettings::for($organization)['agency_email'];
        $stored = EmailAddress::where('branch_id', $branch->id)
            ->orderByDesc('is_primary')->orderBy('sort_order')->pluck('email')->all();

        return OrgMailSettings::emails($choice === 'all' ? $stored : array_slice($stored, 0, 1));
    }

    /** @return array{to: list<string>, cc: list<string>}|null */
    protected function internalRecipients(): ?array
    {
        $config = config('app.customer_address_change_mail');

        return empty($config['to']) ? null : ['to' => $config['to'], 'cc' => $config['cc'] ?? []];
    }

    /**
     * @param  array<string, string>  $fields
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<array{label: string, old: string, new: string, changed: bool}>
     */
    protected function rows(array $fields, array $before, array $after): array
    {
        $rows = [];
        $value = fn ($v) => $v instanceof CarbonInterface ? $v->format('d.m.Y') : trim((string) (Branch::LOCATION_TYPES[(string) $v] ?? $v));
        foreach ($fields as $field => $label) {
            $old = $value($before[$field] ?? '');
            $new = $value($after[$field] ?? '');
            $rows[] = ['label' => $label, 'old' => $old, 'new' => $new, 'changed' => $old !== $new];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    protected function differs(array $before, array $after): bool
    {
        $normalize = fn ($v) => $v instanceof CarbonInterface ? $v->format('Y-m-d') : trim((string) $v);
        foreach ($after as $field => $value) {
            if ($normalize($before[$field] ?? '') !== $normalize($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Anschrift in einer Zeile, z. B. "Hauptstr. 1, 10115 Berlin, Deutschland".
     *
     * @param  array<string, mixed>  $address
     */
    protected function addressLine(array $address, string $prefix = ''): string
    {
        $get = fn ($field) => trim((string) ($address[$prefix.$field] ?? ''));

        return implode(', ', array_filter([
            trim($get('street').' '.$get('house_number')),
            trim($get('postal_code').' '.$get('city')),
            $get('country'),
        ]));
    }

    protected function safely(?int $customerId, callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning('Infomail zur Organisationsaenderung nicht eingestellt', ['customer_id' => $customerId, 'error' => $e->getMessage()]);
        }
    }
}
