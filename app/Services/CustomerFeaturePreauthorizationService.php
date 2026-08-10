<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\CustomerFeaturePreauthorization;
use Illuminate\Support\Facades\Log;

/**
 * Loest vorgemerkte Feature-Freischaltungen (pds_account_id) in konkrete
 * Overrides (customer_id) auf.
 *
 * Eine im Admin getroffene Entscheidung hat immer Vorrang: Steht im Override
 * bereits true oder false, bleibt der Wert unangetastet. Nur Felder ohne Wert
 * (null = .env-Default) werden aus der Vormerkung gefuellt.
 */
class CustomerFeaturePreauthorizationService
{
    /**
     * Vormerkungen anlegen oder aktualisieren.
     *
     * @param  array<int|string>  $pdsAccountIds
     * @return int Anzahl geschriebener Zeilen
     */
    public function record(string $featureKey, array $pdsAccountIds, bool $enabled = true, ?string $note = null): int
    {
        $this->assertKnownFeature($featureKey);

        $ids = collect($pdsAccountIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $now = now();

        // upsert haelt den Import wiederholbar: bereits vorgemerkte Accounts
        // werden aktualisiert statt zu kollidieren. applied_at bleibt stehen.
        CustomerFeaturePreauthorization::upsert(
            $ids->map(fn (int $id) => [
                'pds_account_id' => $id,
                'feature_key' => $featureKey,
                'enabled' => $enabled,
                'note' => $note,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['pds_account_id', 'feature_key'],
            ['enabled', 'note', 'updated_at'],
        );

        return $ids->count();
    }

    /**
     * Vormerkungen fuer einen konkreten Kunden einloesen.
     *
     * Wird bei jedem Login aufgerufen. Ist nichts vorgemerkt, kostet das eine
     * indizierte Abfrage und bricht sofort ab.
     *
     * @param  int|string|null  $fallbackPdsAccountId  Account-ID aus dem SSO-Token.
     *                                                 Noetig fuer Mitarbeiter-Logins: dort wird
     *                                                 pds_account_id nicht am Kundendatensatz
     *                                                 nachgefuehrt und kann fehlen.
     * @return array<string, bool> angewandte Feature-Keys mit ihrem Wert
     */
    public function applyForCustomer(Customer $customer, int|string|null $fallbackPdsAccountId = null): array
    {
        $accountId = $customer->pds_account_id ?: $fallbackPdsAccountId;

        if (! $accountId) {
            return [];
        }

        $preauthorizations = CustomerFeaturePreauthorization::query()
            ->where('pds_account_id', $accountId)
            ->get();

        if ($preauthorizations->isEmpty()) {
            return [];
        }

        $override = CustomerFeatureOverride::firstOrNew(['customer_id' => $customer->id]);
        $knownKeys = CustomerFeatureOverride::getFeatureKeys();

        $applied = [];
        $appliedIds = [];

        foreach ($preauthorizations as $preauthorization) {
            $key = $preauthorization->feature_key;

            if (! in_array($key, $knownKeys, true)) {
                continue;
            }

            // Bereits explizit gesetzt -> Admin-Entscheidung gewinnt.
            if ($override->{$key} !== null) {
                continue;
            }

            $override->{$key} = $preauthorization->enabled;
            $applied[$key] = $preauthorization->enabled;
            $appliedIds[] = $preauthorization->id;
        }

        if ($applied === []) {
            return [];
        }

        // Der Observer auf CustomerFeatureOverride leert den Feature-Cache.
        $override->save();

        CustomerFeaturePreauthorization::whereIn('id', $appliedIds)
            ->whereNull('applied_at')
            ->update([
                'applied_at' => now(),
                'applied_customer_id' => $customer->id,
                'updated_at' => now(),
            ]);

        Log::info('Feature-Vormerkung eingeloest', [
            'customer_id' => $customer->id,
            'pds_account_id' => $accountId,
            'features' => $applied,
        ]);

        return $applied;
    }

    /**
     * Vormerkungen auf alle bereits existierenden Kunden anwenden.
     *
     * Fuer Accounts, die sich schon einmal eingeloggt haben - die warten sonst
     * bis zum naechsten Login.
     *
     * @param  array<int>|null  $pdsAccountIds  Auf diese Accounts einschraenken (null = alle vorgemerkten)
     * @return array{customers: int, applied: int}
     */
    public function applyToExistingCustomers(?string $featureKey = null, ?array $pdsAccountIds = null): array
    {
        $accountIds = CustomerFeaturePreauthorization::query()
            ->when($featureKey, fn ($query) => $query->forFeature($featureKey))
            ->when($pdsAccountIds !== null, fn ($query) => $query->whereIn('pds_account_id', $pdsAccountIds))
            ->distinct()
            ->pluck('pds_account_id');

        if ($accountIds->isEmpty()) {
            return ['customers' => 0, 'applied' => 0];
        }

        $customersTouched = 0;
        $featuresApplied = 0;

        Customer::query()
            ->whereIn('pds_account_id', $accountIds)
            ->chunkById(200, function ($customers) use (&$customersTouched, &$featuresApplied) {
                foreach ($customers as $customer) {
                    $applied = $this->applyForCustomer($customer);

                    if ($applied !== []) {
                        $customersTouched++;
                        $featuresApplied += count($applied);
                    }
                }
            });

        return ['customers' => $customersTouched, 'applied' => $featuresApplied];
    }

    private function assertKnownFeature(string $featureKey): void
    {
        if (! in_array($featureKey, CustomerFeatureOverride::getFeatureKeys(), true)) {
            throw new \InvalidArgumentException(
                "Unbekanntes Feature [{$featureKey}]. Erlaubt: ".implode(', ', CustomerFeatureOverride::getFeatureKeys())
            );
        }
    }
}
