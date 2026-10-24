<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sicherung der TripData-App je Geraet (PUT /v1/devices/{id}/backup). Der Inhalt ist fuer die Plattform
 * undurchsichtiges JSON, gespeichert gzip-komprimiert. Je Geraet genau eine Sicherung, sie geht mit dem Geraet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_device_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('app_device_id')->unique()->constrained('app_devices')->cascadeOnDelete();
            // Format der App-Daten (zaehlt die App hoch)
            $table->unsignedInteger('schema');
            $table->string('platform', 16);
            $table->string('app_version', 64)->nullable();
            // gzip-komprimiertes JSON; unter MySQL als LONGBLOB (siehe unten)
            $table->binary('payload');
            // unkomprimierte Groesse in Bytes
            $table->unsignedInteger('size');
            // nur zur Anzeige, von der App geliefert, z. B. {"journeys":3,"visited_countries":12}
            $table->json('counts')->nullable();
            $table->timestamp('backed_up_at');
            $table->timestamps();
        });

        // binary() ist unter MySQL ein BLOB (64 KB) – fuer bis zu 8 MB reicht erst LONGBLOB.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE app_device_backups MODIFY payload LONGBLOB NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_device_backups');
    }
};
