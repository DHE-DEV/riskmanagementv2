<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Push-Benachrichtigungen der Apps: angemeldete Geraete, die Reise-Links, fuer die
 * ein Geraet benachrichtigt werden will, und je Versand eine Zeile mit Zaehlern
 * (keine Zeile je Empfaenger – das bleibt auch bei Hunderttausenden klein).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_devices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // Kunde, dessen API-Token die App nutzt (z. B. der App-Kunde von TripData).
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('app', 32)->default('tripdata');
            $table->string('platform', 16);
            $table->string('push_token', 255)->nullable();
            $table->string('push_environment', 16)->default('production');
            $table->string('locale', 8)->default('de');
            $table->string('app_version', 64)->nullable();
            $table->boolean('notifications_enabled')->default(true);
            $table->string('secret_hash', 64);
            $table->timestamp('last_seen_at')->nullable();
            // APNs/FCM haben den Token als ungueltig gemeldet – bis zum naechsten Token keine Pushs.
            $table->timestamp('token_invalid_at')->nullable();
            $table->timestamps();

            $table->index('push_token');
            $table->index(['app', 'notifications_enabled']);
        });

        Schema::create('app_device_travel_links', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('app_device_id')->constrained('app_devices')->cascadeOnDelete();
            // Reise-ID des Travel Detail Links, 12 Zeichen ohne Striche.
            $table->char('tid', 12);
            // Letzte wichtige Aenderung, fuer die dieses Geraet benachrichtigt wurde.
            $table->timestamp('notified_change_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['app_device_id', 'tid']);
            $table->index('tid');
        });

        Schema::create('app_push_messages', function (Blueprint $table) {
            $table->id();
            $table->string('app', 32)->default('tripdata');
            $table->string('type', 48);
            // scanner | api
            $table->string('source', 16);
            // Absender bei source=api (Token-Inhaber).
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('title');
            $table->json('body');
            $table->json('data')->nullable();
            $table->json('target')->nullable();
            // queued | sending | done
            $table->string('status', 16)->default('queued');
            $table->unsignedInteger('targeted')->default(0);
            $table->unsignedInteger('sent')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('invalid')->default(0);
            $table->unsignedInteger('pending_batches')->default(0);
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_push_messages');
        Schema::dropIfExists('app_device_travel_links');
        Schema::dropIfExists('app_devices');
    }
};
