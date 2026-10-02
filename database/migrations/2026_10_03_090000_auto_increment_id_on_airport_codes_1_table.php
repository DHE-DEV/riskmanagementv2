<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Das Verzeichnis der Flughafen-Codes stammt aus einem Import; sein
 * Primaerschluessel zaehlt nicht automatisch hoch. Damit sich im Admin neue
 * Eintraege anlegen lassen, bekommt "id" AUTO_INCREMENT.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // MySQL 8 liefert die Spalten der information_schema in Grossbuchstaben – daher der Alias.
        $column = DB::selectOne("select extra as extra from information_schema.columns where table_schema = database() and table_name = 'airport_codes_1' and column_name = 'id'");

        if ($column && ! str_contains(strtolower((string) $column->extra), 'auto_increment')) {
            // Der Spaltentyp bleibt gleich – die Fremdschluessel darauf bleiben gueltig, MySQL
            // laesst die Aenderung aber nur mit abgeschalteter Pruefung zu.
            DB::statement('set foreign_key_checks = 0');
            try {
                DB::statement('alter table `airport_codes_1` modify `id` bigint unsigned not null auto_increment');
            } finally {
                DB::statement('set foreign_key_checks = 1');
            }
        }
    }

    public function down(): void
    {
        // Ein Primaerschluessel ohne AUTO_INCREMENT bringt nichts zurueck – bleibt.
    }
};
