<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Merkt Travel Alert fuer die abgestimmte Account-Liste vor.
 *
 * Die Vormerkung wird beim naechsten Login des jeweiligen Accounts automatisch
 * in ein CustomerFeatureOverride uebersetzt. Konten, die es schon gibt, holt man
 * direkt nach dem Deploy nach:
 *
 *     php artisan customers:preauthorize-feature --apply-pending
 *
 * Bewusst nur ein Insert, keine Anwendung auf bestehende Kunden: die Regel
 * "im Admin gesetzte Werte gewinnen" steht genau einmal im Service und soll
 * nicht als SQL-Kopie in einer Migration mitgepflegt werden.
 */
return new class extends Migration
{
    private const FEATURE_KEY = 'navigation_risk_overview_enabled';

    private const ACCOUNT_IDS = [
        25893, 25427, 23854, 23853, 21305, 20145, 20093, 20092, 20091, 20090,
        20089, 20088, 20087, 20086, 20085, 20084, 20083, 20082, 20080, 20079,
        20077, 20076, 20075, 20074, 20072, 20071, 20069, 20068, 20067, 20066,
        20064, 20063, 20062, 20061, 20060, 20059, 20057, 20056, 20055, 20054,
        20053, 20051, 20050, 20049, 20048, 20046, 20045, 20044, 20043, 20042,
        20041, 20040, 20039, 20037, 20036, 20035, 20034, 20033, 20032, 20030,
        20029, 20028, 6433,
    ];

    public function up(): void
    {
        $now = now();

        DB::table('customer_feature_preauthorizations')->upsert(
            array_map(fn (int $accountId) => [
                'pds_account_id' => $accountId,
                'feature_key' => self::FEATURE_KEY,
                'enabled' => true,
                'note' => 'Initiale Travel-Alert-Liste (2026-08)',
                'created_at' => $now,
                'updated_at' => $now,
            ], self::ACCOUNT_IDS),
            ['pds_account_id', 'feature_key'],
            ['enabled', 'note', 'updated_at'],
        );
    }

    public function down(): void
    {
        // Nur die Vormerkungen zuruecknehmen. Bereits erteilte Overrides bleiben
        // bestehen - die sind ab dem Login eine eigenstaendige Entscheidung.
        DB::table('customer_feature_preauthorizations')
            ->where('feature_key', self::FEATURE_KEY)
            ->whereIn('pds_account_id', self::ACCOUNT_IDS)
            ->delete();
    }
};
