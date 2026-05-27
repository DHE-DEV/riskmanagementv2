<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * Subscription ("Abo") handling for the Travel Requirements Service v2.
 *
 * Talks to pds-api as the logged-in customer (Bearer token via
 * {@see PdsApiService}). The general-notification subscription lets an
 * end customer receive e-mail alerts whenever the entry requirements of one
 * or more destinations change.
 *
 * Abo-Verwaltung für das Travel Requirements Service v2. Kommuniziert als
 * eingeloggter Kunde mit der pds-api.
 */
class TrsV2SubscriptionService
{
    /**
     * In-memory cache for the country code => id map (per request / instance).
     *
     * @var array<string, int>|null
     */
    protected ?array $countryMap = null;

    public function __construct(
        protected PdsApiService $pdsApi,
        protected TrsV2DataService $dataService,
    ) {
    }

    /**
     * Verified e-mail addresses of the customer's account.
     *
     * Reads the paginated list from `account/emails/paginate` and keeps only
     * entries that are verified (either a truthy `verified` flag or a
     * non-null `verified_at` timestamp).
     *
     * @return array<int, array{email:string, language:string}>
     */
    public function verifiedEmails(Customer $customer): array
    {
        $response = $this->pdsApi->post($customer, '__internal/account/emails/paginate', ['filters' => []]);

        if (! $response || ! $response->successful()) {
            return [];
        }

        $rows = $this->extractList($response);

        $emails = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $verified = ! empty($row['verified']) || ! empty($row['verified_at']);
            if (! $verified) {
                continue;
            }

            $email = $row['email_address'] ?? $row['email'] ?? null;
            if (empty($email)) {
                continue;
            }

            $emails[] = [
                'email' => (string) $email,
                'language' => (string) ($row['preferred_language'] ?? $row['language'] ?? 'de'),
            ];
        }

        return $emails;
    }

    /**
     * Add (and trigger verification for) a new e-mail address on the account.
     */
    public function addEmail(Customer $customer, string $email, string $language = 'de'): bool
    {
        $response = $this->pdsApi->post($customer, '__internal/account/emails/add', [
            'email_address' => $email,
            'preferred_language' => $language,
        ]);

        return (bool) ($response && $response->successful());
    }

    /**
     * Existing general-notification subscriptions of the customer.
     *
     * @return array<int, mixed>
     */
    public function subscriptions(Customer $customer): array
    {
        $response = $this->pdsApi->post($customer, '__internal/account/subscriptions/general-notification/paginate', ['filters' => []]);

        if (! $response || ! $response->successful()) {
            return [];
        }

        return $this->extractList($response);
    }

    /**
     * Create or update a general-notification subscription.
     *
     * The pds-api `save` endpoint expects country *IDs*, not ISO codes, so the
     * given codes are translated via {@see countryCodeToId()}. Unknown codes
     * are skipped. An empty country list is sent as `null`, which the API
     * interprets as "all destinations".
     *
     * @param  array<int, string>  $countryCodes  ISO-2 destination codes
     * @param  array<int, string>  $emails        selected e-mail addresses
     * @return array{ok:bool, status:int|null, body:mixed, error?:string}
     */
    public function save(
        Customer $customer,
        string $name,
        array $countryCodes,
        array $emails,
        bool $activeDestinationsOnly = false,
        ?int $id = null,
    ): array {
        $name = trim($name);
        if (mb_strlen($name) < 3 || mb_strlen($name) > 60) {
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Der Abo-Name muss zwischen 3 und 60 Zeichen lang sein.',
            ];
        }

        // Translate ISO codes -> pds-api country IDs.
        $map = $this->countryCodeToId($customer);
        $ids = [];
        foreach ($countryCodes as $code) {
            $code = strtoupper(trim((string) $code));
            if ($code !== '' && isset($map[$code])) {
                $ids[] = $map[$code];
            }
        }
        $ids = array_values(array_unique($ids));

        if (! empty($countryCodes) && empty($ids) && empty($map)) {
            Log::warning('TrsV2SubscriptionService: country code->id mapping unavailable, saving subscription for all destinations', [
                'customer_id' => $customer->id,
                'requested_codes' => $countryCodes,
            ]);
        }

        $payload = [
            'name' => $name,
            'countries' => empty($ids) ? null : $ids,
            'active_destinations_only' => $activeDestinationsOnly,
            'emails' => array_values($emails),
        ];

        if ($id !== null) {
            $payload['id'] = $id;
        }

        $response = $this->pdsApi->post($customer, '__internal/account/subscriptions/general-notification/save', $payload);

        $ok = (bool) ($response && $response->successful());

        $result = [
            'ok' => $ok,
            'status' => $response?->status(),
            'body' => $response?->json(),
        ];

        if (! $ok) {
            $result['error'] = $this->errorMessage($response);
        }

        return $result;
    }

    /**
     * Build a map of ISO-2 country code => pds-api country id.
     *
     * The public `/countries` list does NOT expose an `id` field, so we fall
     * back to the authenticated `destinations` endpoint whose rows carry both
     * `id` and `code`. If neither source provides an id the map is empty and
     * {@see save()} will send `countries => null` (all destinations) with a
     * logged warning. The result is cached in-memory for the instance.
     *
     * @return array<string, int>
     */
    protected function countryCodeToId(Customer $customer): array
    {
        if ($this->countryMap !== null) {
            return $this->countryMap;
        }

        $this->countryMap = [];

        try {
            // 1) Primary source: /countries. Only usable if rows carry an id.
            $response = $this->pdsApi->get($customer, 'countries', ['limit' => 1000]);
            $map = $this->mapFromRows($this->extractList($response));

            // 2) Fallback: /destinations (rows include id + code).
            if (empty($map)) {
                $response = $this->pdsApi->get($customer, 'destinations', ['limit' => 1000]);
                $map = $this->mapFromRows($this->extractList($response));

                if (empty($map)) {
                    $response = $this->pdsApi->post($customer, 'destinations', ['filters' => []]);
                    $map = $this->mapFromRows($this->extractList($response));
                }
            }

            if (empty($map)) {
                Log::warning('TrsV2SubscriptionService: no country code->id mapping available (neither /countries nor /destinations exposed an id)', [
                    'customer_id' => $customer->id,
                ]);
            }

            $this->countryMap = $map;
        } catch (\Throwable $e) {
            Log::warning('TrsV2SubscriptionService: building country code->id map failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            $this->countryMap = [];
        }

        return $this->countryMap;
    }

    /**
     * Build a code => id map from a list of country/destination rows.
     * Rows without both `id` and `code` are skipped.
     *
     * @param  array<int, mixed>  $rows
     * @return array<string, int>
     */
    protected function mapFromRows(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = $row['id'] ?? $row['country_id'] ?? null;
            $code = $row['code'] ?? $row['country_code'] ?? null;
            if ($id === null || empty($code)) {
                continue;
            }
            $map[strtoupper((string) $code)] = (int) $id;
        }

        return $map;
    }

    /**
     * Pull the list of rows from a paginated pds-api response.
     * Data may live at `data`, `result.data` or `result`.
     *
     * @return array<int, mixed>
     */
    protected function extractList(?Response $response): array
    {
        if (! $response || ! $response->successful()) {
            return [];
        }

        $list = $response->json('data')
            ?? $response->json('result.data')
            ?? $response->json('result')
            ?? [];

        return is_array($list) ? array_values($list) : [];
    }

    /**
     * Derive a human-readable error message from a failed response.
     */
    protected function errorMessage(?Response $response): string
    {
        if (! $response) {
            return 'Es konnte keine Verbindung zur API hergestellt werden.';
        }

        $message = $response->json('error.message')
            ?? $response->json('message')
            ?? null;

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return 'Das Abo konnte nicht gespeichert werden (Status '.$response->status().').';
    }
}
