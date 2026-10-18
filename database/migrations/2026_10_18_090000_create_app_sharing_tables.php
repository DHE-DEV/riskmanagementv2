<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teilen in der TripData-App: jedes Geraet hat eine kurze App-ID (share_code) und einen freiwilligen
 * Anzeigenamen. Geraete bilden Gruppen (Familie, Reisegruppe) und teilen Reisen mit einzelnen
 * App-IDs oder ganzen Gruppen. Einladungen muessen angenommen werden; Gruppenmitglieder sehen
 * Reisen der Gruppe ohne eigene Einladung. Alles haengt am Geraet und verschwindet mit ihm.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_devices', function (Blueprint $table) {
            // 8 Zeichen ohne Verwechsler (0/O, 1/I/L, U), angezeigt als XXXX-XXXX.
            $table->char('share_code', 8)->nullable()->unique()->after('secret_hash');
            $table->string('display_name', 60)->nullable()->after('share_code');
        });

        Schema::create('app_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_device_id')->constrained('app_devices')->cascadeOnDelete();
            $table->string('name', 60);
            $table->timestamps();
        });

        Schema::create('app_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('app_group_id')->constrained('app_groups')->cascadeOnDelete();
            $table->foreignUlid('app_device_id')->constrained('app_devices')->cascadeOnDelete();
            // invited | accepted
            $table->string('status', 16)->default('invited');
            $table->timestamps();

            $table->unique(['app_group_id', 'app_device_id']);
            $table->index(['app_device_id', 'status']);
        });

        Schema::create('app_shared_trips', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_device_id')->constrained('app_devices')->cascadeOnDelete();
            // ID der Reise in der App des Erstellers (UUID).
            $table->string('client_trip_id', 64);
            $table->string('name', 120);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('country_codes');
            // Reise-ID des Travel Detail Links (12 Zeichen ohne Striche) – fuer Push und Bestimmungen.
            $table->char('tid', 12)->nullable();
            // Steigt bei jeder Aenderung durch den Ersteller.
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();

            $table->unique(['owner_device_id', 'client_trip_id']);
        });

        Schema::create('app_shared_trip_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('app_shared_trip_id')->constrained('app_shared_trips')->cascadeOnDelete();
            $table->foreignUlid('app_device_id')->constrained('app_devices')->cascadeOnDelete();
            // invited | accepted | declined (declined auch fuer „verlassen“ bei Reisen einer Gruppe)
            $table->string('status', 16)->default('invited');
            // Direkt eingeladen (true) oder nur ueber eine Gruppe (false, dann nur fuer declined).
            $table->boolean('direct')->default(true);
            $table->timestamps();

            $table->unique(['app_shared_trip_id', 'app_device_id'], 'app_trip_recipients_trip_device_unique');
            $table->index(['app_device_id', 'status'], 'app_trip_recipients_device_status_index');
        });

        Schema::create('app_shared_trip_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('app_shared_trip_id')->constrained('app_shared_trips')->cascadeOnDelete();
            $table->foreignUlid('app_group_id')->constrained('app_groups')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['app_shared_trip_id', 'app_group_id'], 'app_trip_groups_trip_group_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_shared_trip_groups');
        Schema::dropIfExists('app_shared_trip_recipients');
        Schema::dropIfExists('app_shared_trips');
        Schema::dropIfExists('app_group_members');
        Schema::dropIfExists('app_groups');
        Schema::table('app_devices', function (Blueprint $table) {
            $table->dropUnique(['share_code']);
            $table->dropColumn(['share_code', 'display_name']);
        });
    }
};
