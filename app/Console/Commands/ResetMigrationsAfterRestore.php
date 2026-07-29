<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Einmal-Helfer: nachdem eine ältere Live-Datenbank auf diese Umgebung kopiert
 * wurde (ohne die `migrations`-Tabelle abzugleichen), fehlen die tatsächlichen
 * Schema-Änderungen der neueren Migrationen — die `migrations`-Tabelle behauptet
 * aber, sie seien schon gelaufen. Dieser Command löscht die betreffenden
 * Migration-Einträge und startet danach `migrate --force`, damit die
 * Schema-Änderungen erneut laufen.
 *
 * Liste der Migrationen = alle, die zwischen `origin/main` (Live-Stand) und
 * `HEAD` von `platform-2026-07` neu hinzugekommen sind. Stand: 2026-07-29.
 */
class ResetMigrationsAfterRestore extends Command
{
    protected $signature = 'migrations:reset-after-restore {--force : Ohne Rückfrage ausführen}';

    protected $description = 'Löscht die Migration-Einträge, die nach dem Live-Datenbank-Import erneut laufen müssen, und startet migrate --force.';

    private const MIGRATIONS = [
        '2026_03_15_090343_create_employees_table',
        '2026_03_15_091104_add_salutation_and_title_to_employees_table',
        '2026_03_15_091322_add_mobile_and_notes_to_employees_table',
        '2026_03_15_092538_create_departments_table',
        '2026_03_15_092612_add_department_id_to_employees_table',
        '2026_03_15_095246_create_phone_numbers_table',
        '2026_03_15_095247_create_email_addresses_table',
        '2026_03_15_100003_create_websites_table',
        '2026_03_15_100011_add_notes_to_phone_numbers_and_email_addresses',
        '2026_03_15_100634_add_department_id_to_phone_numbers_and_email_addresses',
        '2026_03_15_102442_add_sort_order_to_contact_tables',
        '2026_03_15_115046_create_org_nodes_table',
        '2026_03_15_120431_add_code_to_org_nodes_table',
        '2026_03_15_120733_add_relation_label_to_org_nodes_table',
        '2026_03_15_123644_create_branch_org_node_table',
        '2026_03_15_125516_add_branch_id_to_contact_tables',
        '2026_03_15_130405_add_customer_and_contract_number_to_branch_org_node',
        '2026_03_15_131332_add_dates_to_branch_org_node',
        '2026_03_15_134114_create_branch_contacts_table',
        '2026_03_15_140850_add_visibility_to_custom_events_table',
        '2026_03_15_140857_create_custom_event_org_node_table',
        '2026_03_15_142601_add_dates_to_custom_event_org_node',
        '2026_03_15_144856_add_visibility_dates_to_custom_events',
        '2026_03_15_212210_add_template_and_test_to_notification_logs',
        '2026_03_16_205217_update_system_notification_template_content',
        '2026_03_17_194512_add_source_to_custom_events_table',
        '2026_03_20_100001_partition_td_import_logs_table',
        '2026_03_20_100002_partition_td_trip_locations_table',
        '2026_03_20_100003_partition_td_flight_segments_table',
        '2026_03_20_200001_add_customer_id_to_td_trips_table',
        '2026_03_20_200002_partition_td_trips_table',
        '2026_03_20_200003_create_td_pds_sync_log_table',
        '2026_03_20_300001_add_pds_sync_enabled_to_customers_table',
        '2026_03_20_300002_add_travel_links_enabled_to_customers_table',
        '2026_03_21_100001_add_pds_detail_fields_to_td_trips_table',
        '2026_03_21_100002_add_is_cruise_to_td_trips_table',
        '2026_03_23_204422_add_source_frontend_fields_to_custom_events_table',
        '2026_03_25_181605_add_source_to_notification_rules_table',
        '2026_03_25_182611_create_notification_queue_logs_table',
        '2026_03_25_185041_add_source_to_notification_templates_and_create_travel_alert_template',
        '2026_03_26_100000_partition_notification_queue_logs_table',
        '2026_03_26_100001_partition_notification_logs_table',
        '2026_03_27_100000_update_travel_alert_system_template_content',
        '2026_03_30_100000_add_is_test_data_to_td_trips_table',
        '2026_03_30_120000_add_affected_trips_count_to_notification_logs',
        '2026_03_31_100000_add_uuid_to_plugin_domains_table',
        '2026_04_02_100001_partition_td_air_legs_table',
        '2026_04_02_100002_partition_td_stays_table',
        '2026_04_02_100003_partition_td_transfers_table',
        '2026_04_02_100004_partition_td_travellers_table',
        '2026_04_02_100005_partition_td_pds_share_links_table',
        '2026_04_03_073552_add_has_seen_travel_alert_tour_to_customers_table',
        '2026_04_03_081802_rename_platform_tour_and_add_travel_alert_tour_to_customers_table',
        '2026_04_03_083104_add_has_seen_gtm_tour_to_customers_table',
        '2026_04_03_083654_add_all_tour_flags_to_customers_table',
        '2026_04_03_090237_add_has_seen_settings_tour_to_customers_table',
        '2026_04_05_105437_add_login_code_to_customers_table',
        '2026_04_06_120000_add_customer_type_fields_to_travel_alert_orders_table',
        '2026_04_06_140000_create_employee_groups_table',
        '2026_04_06_160000_seed_default_employee_groups',
        '2026_04_06_170000_add_is_system_to_employee_groups_table',
        '2026_04_06_180000_add_active_dates_to_employees_table',
        '2026_04_06_190000_add_legacy_password_to_customers_table',
        '2026_04_08_220251_add_legacy_client_account_fields_to_customers_table',
        '2026_04_09_080648_add_legacy_ids_to_employees_table',
        '2026_04_09_080652_add_legacy_ids_to_employees_table',
        '2026_04_09_112847_drop_unique_email_from_customers_table',
        '2026_04_12_081246_add_app_code_to_customers_table',
        '2026_04_12_090000_create_customer_access_table',
        '2026_04_12_100000_add_assign_to_to_customers_table',
        '2026_04_12_213343_add_legacy_usersweb_fields_to_employees_table',
        '2026_04_15_100000_create_products_table',
        '2026_04_15_120000_add_setup_fee_to_product_tables',
        '2026_06_22_120000_add_translations_to_custom_events_table',
        '2026_06_25_000001_add_pds_account_id_to_td_trips_table',
        '2026_06_25_000002_add_pds_account_id_to_customers_table',
        '2026_06_25_000003_create_pds_global_sync_states_table',
        '2026_07_13_000001_add_username_to_customers_table',
        '2026_07_13_000002_add_pds_userinfo_fields_to_customers_table',
        '2026_07_13_000003_add_pds_account_fields_to_customers_table',
        '2026_07_23_180000_add_confirmation_and_approval_to_travel_alert_orders_table',
    ];

    public function handle(): int
    {
        $existing = DB::table('migrations')
            ->whereIn('migration', self::MIGRATIONS)
            ->pluck('migration')
            ->all();

        if (empty($existing)) {
            $this->info('Keine der aufgelisteten Migrationen ist aktuell in der migrations-Tabelle eingetragen. Nichts zu tun.');
            return self::SUCCESS;
        }

        $this->warn('Die folgenden ' . count($existing) . ' Migration-Einträge werden aus der `migrations`-Tabelle GELÖSCHT:');
        foreach ($existing as $name) {
            $this->line('  - ' . $name);
        }
        $this->newLine();
        $this->warn('Danach wird `php artisan migrate --force` ausgeführt, wodurch die Schema-Änderungen erneut angewandt werden.');
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Fortfahren?', false)) {
            $this->info('Abgebrochen.');
            return self::SUCCESS;
        }

        $deleted = DB::table('migrations')
            ->whereIn('migration', self::MIGRATIONS)
            ->delete();

        $this->info("Gelöscht: {$deleted} Einträge.");
        $this->newLine();

        $this->info('Starte migrate --force ...');
        $exitCode = Artisan::call('migrate', ['--force' => true], $this->getOutput());

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
