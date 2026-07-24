<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gleicht die customers-Tabelle idempotent an das aktuelle Schema an.
 *
 * Hintergrund: Beim manuellen Kopieren einer aelteren customers-Tabelle (Stand
 * ~Commit 223b0af) wird deren Struktur ueberschrieben, die migrations-Tabelle
 * aber NICHT. Laravel haelt alle spaeteren customers-Migrationen daher fuer
 * "Ran" und wuerde sie nie erneut ausfuehren -> der SSO-Login bricht mit
 * "Column not found" (app_code, pds_account_id, ...).
 *
 * Dieser Befehl reproduziert das exakte Struktur-Delta zwischen dem alten und
 * dem aktuellen Stand (aus dem Dump-Vergleich ermittelt) und ist – anders als
 * eine Migration – beliebig oft ausfuehrbar. Nach jedem erneuten Daten-Kopieren
 * einfach wieder laufen lassen:
 *
 *   php artisan customers:repair-schema --dry-run   (nur anzeigen)
 *   php artisan customers:repair-schema             (anwenden)
 */
class RepairCustomersSchema extends Command
{
    protected $signature = 'customers:repair-schema
                            {--dry-run : Nur anzeigen, was fehlt – nichts aendern}';

    protected $description = 'Gleicht die customers-Tabelle idempotent an das aktuelle Schema an (nach Kopieren eines alten Tabellenstands)';

    /**
     * Fehlende Spalten: Name => Closure, die die Spalte auf dem Blueprint anlegt.
     * Reihenfolge/after() spielt keine funktionale Rolle und wird bewusst weggelassen.
     */
    private function columnDefinitions(): array
    {
        $string = fn (Blueprint $t, string $name, ?int $len = null) => $len
            ? $t->string($name, $len)->nullable()
            : $t->string($name)->nullable();

        $defs = [];

        // 16 Tour-Flags (tinyint(1) NOT NULL DEFAULT 0)
        foreach ([
            'has_seen_platform_tour', 'has_seen_travel_alert_tour', 'has_seen_gtm_tour',
            'has_seen_trs_tour', 'has_seen_entry_conditions_tour', 'has_seen_travel_data_tour',
            'has_seen_travel_links_tour', 'has_seen_booking_tour', 'has_seen_airports_tour',
            'has_seen_branches_tour', 'has_seen_my_travelers_tour', 'has_seen_customer_events_tour',
            'has_seen_cruise_tour', 'has_seen_business_visa_tour', 'has_seen_visumpoint_tour',
            'has_seen_settings_tour',
        ] as $flag) {
            $defs[$flag] = fn (Blueprint $t) => $t->boolean($flag)->default(false);
        }

        // Login-Code
        $defs['login_code'] = fn (Blueprint $t) => $t->string('login_code', 6)->nullable();
        $defs['login_code_expires_at'] = fn (Blueprint $t) => $t->timestamp('login_code_expires_at')->nullable();

        // Legacy-Felder
        $defs['legacy_password_md5'] = fn (Blueprint $t) => $string($t, 'legacy_password_md5');
        $defs['legacy_client_account_id'] = fn (Blueprint $t) => $t->unsignedInteger('legacy_client_account_id')->nullable();
        $defs['legacy_passolution_company_id'] = fn (Blueprint $t) => $t->unsignedTinyInteger('legacy_passolution_company_id')->nullable();
        $defs['legacy_account_id'] = fn (Blueprint $t) => $t->unsignedInteger('legacy_account_id')->nullable();
        $defs['legacy_organization_id'] = fn (Blueprint $t) => $t->unsignedBigInteger('legacy_organization_id')->nullable();
        $defs['legacy_language_id'] = fn (Blueprint $t) => $t->unsignedTinyInteger('legacy_language_id')->nullable();

        // Username + PDS-userinfo (roh)
        $defs['username'] = fn (Blueprint $t) => $string($t, 'username');
        $defs['pds_id'] = fn (Blueprint $t) => $string($t, 'pds_id');
        $defs['pds_name'] = fn (Blueprint $t) => $string($t, 'pds_name');
        $defs['pds_username'] = fn (Blueprint $t) => $string($t, 'pds_username');
        $defs['pds_email'] = fn (Blueprint $t) => $string($t, 'pds_email');

        // PDS-Account (verschachteltes account-Objekt, flach)
        $defs['pds_account_id'] = fn (Blueprint $t) => $t->unsignedBigInteger('pds_account_id')->nullable();
        foreach ([
            'pds_account_type', 'pds_account_name', 'pds_account_first_name', 'pds_account_last_name',
            'pds_account_email', 'pds_account_phone', 'pds_account_address_line_1', 'pds_account_zip_code',
            'pds_account_city', 'pds_account_state', 'pds_account_country', 'pds_account_subscription_type',
        ] as $col) {
            $defs[$col] = fn (Blueprint $t) => $string($t, $col);
        }

        // assign_to (Self-FK, wird weiter unten mit Constraint versehen)
        $defs['assign_to'] = fn (Blueprint $t) => $t->unsignedBigInteger('assign_to')->nullable();

        // app_code (varchar(4) NOT NULL) – Sonderbehandlung im handle(), hier nicht enthalten.

        return $defs;
    }

    public function handle(): int
    {
        if (! Schema::hasTable('customers')) {
            $this->error('Tabelle "customers" existiert nicht.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->comment('Testlauf – es wird nichts geaendert.');
        }

        $actions = [];

        // 1. Fehlende Standard-Spalten
        $missing = [];
        foreach ($this->columnDefinitions() as $name => $definition) {
            if (! Schema::hasColumn('customers', $name)) {
                $missing[$name] = $definition;
            }
        }
        if ($missing !== []) {
            $actions[] = 'Spalten: '.implode(', ', array_keys($missing));
            if (! $dryRun) {
                Schema::table('customers', function (Blueprint $table) use ($missing) {
                    foreach ($missing as $definition) {
                        $definition($table);
                    }
                });
            }
        }

        // 2. app_code (NOT NULL + Backfill eindeutiger Codes)
        if (! Schema::hasColumn('customers', 'app_code')) {
            $actions[] = 'Spalte app_code (+ Backfill eindeutiger Codes, danach NOT NULL)';
            if (! $dryRun) {
                Schema::table('customers', fn (Blueprint $t) => $t->string('app_code', 4)->nullable());

                foreach (DB::table('customers')->whereNull('app_code')->pluck('id') as $id) {
                    DB::table('customers')->where('id', $id)->update(['app_code' => $this->generateUniqueAppCode()]);
                }

                Schema::table('customers', fn (Blueprint $t) => $t->string('app_code', 4)->nullable(false)->change());
            }
        }

        // 3. email nullable machen (alter Stand: NOT NULL)
        if (! $this->emailIsNullable()) {
            $actions[] = 'email -> nullable';
            if (! $dryRun) {
                Schema::table('customers', fn (Blueprint $t) => $t->string('email')->nullable()->change());
            }
        }

        // 4. Indizes / Fremdschluessel angleichen (jeweils idempotent)
        $indexNames = $this->indexNames();

        if (in_array('customers_email_unique', $indexNames, true)) {
            $actions[] = 'Unique-Index customers_email_unique entfernen';
            if (! $dryRun) {
                Schema::table('customers', fn (Blueprint $t) => $t->dropUnique('customers_email_unique'));
                $indexNames = $this->indexNames();
            }
        }
        if (! in_array('customers_email_index', $indexNames, true)) {
            $actions[] = 'Index customers_email_index (email) anlegen';
            if (! $dryRun) {
                Schema::table('customers', fn (Blueprint $t) => $t->index('email', 'customers_email_index'));
            }
        }
        if (Schema::hasColumn('customers', 'pds_account_id') && ! in_array('idx_customers_pds_account_id', $this->indexNames(), true)) {
            $actions[] = 'Index idx_customers_pds_account_id anlegen';
            if (! $dryRun) {
                Schema::table('customers', fn (Blueprint $t) => $t->index('pds_account_id', 'idx_customers_pds_account_id'));
            }
        }

        // 5. Self-FK customers_assign_to_foreign
        if (Schema::hasColumn('customers', 'assign_to') && ! in_array('customers_assign_to_foreign', $this->foreignKeyNames(), true)) {
            $actions[] = 'Fremdschluessel customers_assign_to_foreign (assign_to -> customers.id, ON DELETE SET NULL)';
            if (! $dryRun) {
                Schema::table('customers', function (Blueprint $t) {
                    $t->foreign('assign_to', 'customers_assign_to_foreign')
                        ->references('id')->on('customers')->nullOnDelete();
                });
            }
        }

        // Ergebnis
        if ($actions === []) {
            $this->info('customers-Schema ist bereits aktuell – nichts zu tun.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line($dryRun ? 'Wuerde ausfuehren:' : 'Ausgefuehrt:');
        foreach ($actions as $a) {
            $this->line('  - '.$a);
        }
        $this->newLine();

        if ($dryRun) {
            $this->comment('Zum Anwenden ohne --dry-run erneut ausfuehren.');
        } else {
            $this->info('customers-Schema angeglichen.');
        }

        return self::SUCCESS;
    }

    private function emailIsNullable(): bool
    {
        foreach (Schema::getColumns('customers') as $col) {
            if (($col['name'] ?? null) === 'email') {
                return (bool) ($col['nullable'] ?? true);
            }
        }

        return true;
    }

    private function indexNames(): array
    {
        return array_map(fn ($i) => $i['name'], Schema::getIndexes('customers'));
    }

    private function foreignKeyNames(): array
    {
        return array_map(fn ($fk) => $fk['name'], Schema::getForeignKeys('customers'));
    }

    private function generateUniqueAppCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        } while (
            DB::table('customers')->where('app_code', $code)->exists()
            || (Schema::hasTable('branches') && DB::table('branches')->where('app_code', $code)->exists())
        );

        return $code;
    }
}
