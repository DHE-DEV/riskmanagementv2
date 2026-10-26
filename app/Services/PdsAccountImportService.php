<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Legt Kunden aus pds-admin-Accounts an (Admin-Bereich der pds-api,
 * __internal/admin/accounts) und ordnet Unteraccounts ihrer Agentur zu.
 *
 * Bestehende Kunden werden erkannt – zuerst per pds_account_id, dann per
 * E-Mail – und nie doppelt angelegt. Bei ihnen wird nur ergaenzt, was fehlt
 * (Account-ID); Stammdaten bleiben unangetastet. Der Agentur werden alle
 * zugeordnet – auch solche im Papierkorb oder bei einer anderen Agentur
 * (die dann ihren Account-Zugriff verliert).
 *
 * Die Admin-Routen brauchen neben dem Token das Admin-Service-Secret
 * (X-PDS-Service/X-PDS-Secret, PASSOLUTION_INTERNAL_ADMIN_SECRET).
 */
class PdsAccountImportService
{
    public const STATUS_CREATED = 'created';

    public const STATUS_UPDATED = 'updated';

    public const STATUS_UNCHANGED = 'unchanged';

    public const STATUS_REASSIGNED = 'reassigned';

    public const STATUS_TRASHED = 'trashed';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_LABELS = [
        self::STATUS_CREATED => 'Neu angelegt',
        self::STATUS_UPDATED => 'Ergänzt',
        self::STATUS_UNCHANGED => 'Schon vorhanden',
        self::STATUS_REASSIGNED => 'Von anderer Agentur umgehängt',
        self::STATUS_TRASHED => 'Im Papierkorb, wird zugeordnet',
        self::STATUS_INVALID => 'Ohne gültige E-Mail',
        self::STATUS_DUPLICATE => 'E-Mail doppelt in der Liste',
    ];

    /**
     * PDS-Laender-ID => ISO-Code. Die Admin-Antwort liefert nur die interne ID;
     * unbekannte IDs bleiben leer und werden beim ersten Login nachgezogen.
     */
    public const COUNTRY_CODES = [
        24 => 'BE', 44 => 'DK', 46 => 'DE', 58 => 'FI', 59 => 'FR', 69 => 'GR',
        87 => 'IE', 90 => 'IT', 113 => 'HR', 122 => 'LI', 124 => 'LU', 154 => 'NL',
        162 => 'NO', 164 => 'AT', 175 => 'PL', 176 => 'PT', 190 => 'SE', 191 => 'CH',
        199 => 'SK', 200 => 'SI', 202 => 'ES', 227 => 'CZ', 229 => 'TR', 235 => 'HU',
        242 => 'US', 243 => 'GB',
    ];

    /** Groesste Seite, die die Children-Route liefert. */
    protected const PER_PAGE = 500;

    public function isConfigured(): bool
    {
        return $this->token() !== '' && $this->secret() !== '';
    }

    /**
     * Einen Account aus pds-admin; null, wenn es ihn nicht gibt.
     *
     * @return array<string, mixed>|null
     */
    public function fetchAccount(int $accountId): ?array
    {
        $response = $this->client()->get("admin/accounts/{$accountId}");

        if ($response->status() === 404) {
            return null;
        }

        $this->ensureSuccessful($response);

        return $response->json('data');
    }

    /**
     * Alle direkten Unteraccounts (alle Seiten).
     *
     * @return list<array<string, mixed>>
     */
    public function fetchChildren(int $accountId, bool $onlyActive = true): array
    {
        $children = [];
        $page = 1;

        do {
            $response = $this->client()->get("admin/accounts/{$accountId}/children", array_filter([
                'per_page' => self::PER_PAGE,
                'page' => $page,
                'filters' => $onlyActive ? ['status' => 'active'] : null,
            ]));

            $this->ensureSuccessful($response);

            array_push($children, ...($response->json('data') ?? []));
            $lastPage = (int) $response->json('meta.last_page', 1);
        } while ($page++ < $lastPage);

        return $children;
    }

    /**
     * Accounts aus einem CSV-Export der pds-admin-Accountliste. Spalten wie in
     * der API, verschachtelte mit Punkt ("status.active", "parent.id");
     * Trennzeichen Semikolon oder Komma.
     *
     * @return list<array<string, mixed>>
     */
    public function parseCsv(string $path): array
    {
        $handle = @fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('Die CSV-Datei lässt sich nicht lesen.');
        }

        $firstLine = (string) fgets($handle);
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
        $header = array_map('trim', str_getcsv(preg_replace('/^\xEF\xBB\xBF/', '', trim($firstLine)), $delimiter, '"', ''));

        if (! in_array('id', $header, true) || ! in_array('email', $header, true)) {
            fclose($handle);

            throw new RuntimeException('Die CSV braucht mindestens die Spalten id und email.');
        }

        $accounts = [];

        while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            if (count($row) !== count($header)) {
                continue;
            }

            $account = Arr::undot(array_combine($header, array_map(fn ($value) => $value === '' ? null : $value, $row)));

            if (ctype_digit((string) $account['id'])) {
                $accounts[] = $account;
            }
        }

        fclose($handle);

        return $accounts;
    }

    /**
     * Der Plattform-Kunde zu einem Account – per Account-ID, sonst per E-Mail.
     *
     * @param  array<string, mixed>  $account
     */
    public function findCustomer(array $account): ?Customer
    {
        $customer = Customer::withTrashed()
            ->where('pds_account_id', (int) $account['id'])
            ->orderBy('id')
            ->first();

        $email = $this->email($account);

        return $customer ?? ($email === null ? null : Customer::withTrashed()->where('email', $email)->orderBy('id')->first());
    }

    /**
     * Was ein Import mit dem Account machen wuerde, ohne zu schreiben.
     *
     * @param  array<string, mixed>  $account
     */
    public function plan(array $account, ?Customer $agency = null): string
    {
        return $this->import($account, $agency, dryRun: true)['status'];
    }

    /**
     * Legt den Kunden an bzw. ergaenzt den gefundenen.
     *
     * @param  array<string, mixed>  $account
     * @return array{status: string, customer: ?Customer}
     */
    public function import(array $account, ?Customer $agency = null, bool $dryRun = false): array
    {
        $customer = $this->findCustomer($account);

        if ($customer) {
            $changes = [];
            $previousAgencyId = null;

            if ($customer->pds_account_id === null) {
                $changes['pds_account_id'] = (int) $account['id'];
            }

            if ($agency && $customer->id !== $agency->id && (int) $customer->assign_to !== $agency->id) {
                $previousAgencyId = $customer->assign_to;
                $changes['assign_to'] = $agency->id;
            }

            $grantsAccess = $agency && $customer->id !== $agency->id
                && ! $agency->accessibleAccounts()->withTrashed()->whereKey($customer->id)->exists();

            if ($changes === [] && ! $grantsAccess) {
                return ['status' => self::STATUS_UNCHANGED, 'customer' => $customer];
            }

            if (! $dryRun) {
                DB::transaction(function () use ($customer, $changes, $agency, $previousAgencyId) {
                    $customer->forceFill($changes)->save();
                    $agency?->accessibleAccounts()->syncWithoutDetaching([$customer->id]);

                    if ($previousAgencyId !== null) {
                        Customer::withTrashed()->find($previousAgencyId)?->accessibleAccounts()->detach($customer->id);
                    }
                });
            }

            return ['status' => match (true) {
                $previousAgencyId !== null => self::STATUS_REASSIGNED,
                $customer->trashed() => self::STATUS_TRASHED,
                default => self::STATUS_UPDATED,
            }, 'customer' => $customer];
        }

        $email = $this->email($account);

        if ($email === null) {
            return ['status' => self::STATUS_INVALID, 'customer' => null];
        }

        if ($dryRun) {
            return ['status' => self::STATUS_CREATED, 'customer' => null];
        }

        $country = self::COUNTRY_CODES[(int) ($account['country_id'] ?? 0)] ?? null;
        $contact = Customer::resolveContactName($account);
        $company = Customer::resolveCompanyName($account);

        // create() statt saveQuietly(): der created-Hook legt Gruppen und den
        // Inhaber als Mitarbeiter an. Der Login per SSO findet den Kunden
        // spaeter ueber die E-Mail. Alles in einer Transaktion, damit ein
        // Abbruch keinen halb angelegten Kunden hinterlaesst.
        $customer = DB::transaction(fn () => tap(Customer::create([
            'name' => $contact ?? $company ?? $email,
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'phone' => $account['phone'] ?? null,
            'customer_type' => 'business',
            'company_name' => $company,
            'company_additional' => $contact,
            'company_street' => Customer::parseStreet($account['address_line_1'] ?? null),
            'company_house_number' => Customer::parseHouseNumber($account['address_line_1'] ?? null),
            'company_postal_code' => $account['zip_code'] ?? null,
            'company_city' => $account['city'] ?? null,
            'company_country' => $country,
            'pds_account_id' => (int) $account['id'],
            'pds_account_name' => $account['name'] ?? null,
            'pds_account_first_name' => $account['first_name'] ?? null,
            'pds_account_last_name' => $account['last_name'] ?? null,
            'pds_account_email' => $account['email'] ?? null,
            'pds_account_phone' => $account['phone'] ?? null,
            'pds_account_address_line_1' => $account['address_line_1'] ?? null,
            'pds_account_zip_code' => $account['zip_code'] ?? null,
            'pds_account_city' => $account['city'] ?? null,
            'pds_account_state' => $account['state'] ?? null,
            'pds_account_country' => $country,
            'assign_to' => $agency?->id,
            'email_verified_at' => now(),
        ]), fn (Customer $customer) => $agency?->accessibleAccounts()->syncWithoutDetaching([$customer->id])));

        return ['status' => self::STATUS_CREATED, 'customer' => $customer];
    }

    /**
     * @param  array<string, mixed>  $account
     */
    protected function email(array $account): ?string
    {
        $email = strtolower(trim((string) ($account['email'] ?? '')));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    protected function client(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('PDS-Import ist nicht eingerichtet: PASSOLUTION_INTERNAL_TOKEN und PASSOLUTION_INTERNAL_ADMIN_SECRET setzen.');
        }

        return Http::baseUrl(rtrim((string) config('services.passolution.internal_api_url'), '/').'/__internal')
            ->withToken($this->token())
            ->withHeaders([
                'Accept' => 'application/json',
                'X-PDS-Service' => 'admin',
                'X-PDS-Secret' => $this->secret(),
            ])
            ->timeout(60);
    }

    protected function ensureSuccessful(\Illuminate\Http\Client\Response $response): void
    {
        if ($response->failed()) {
            throw new RuntimeException("pds-api antwortet mit HTTP {$response->status()}.");
        }
    }

    protected function token(): string
    {
        return (string) (config('services.passolution.internal_token') ?: config('services.passolution.api_key'));
    }

    protected function secret(): string
    {
        return (string) config('services.passolution.internal_admin_secret');
    }
}
