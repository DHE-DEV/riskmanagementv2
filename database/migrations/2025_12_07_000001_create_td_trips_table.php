<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('td_trips', function (Blueprint $table) {
            $table->id();

            // Unique identification (upsert key)
            $table->string('provider_id', 64);
            $table->string('external_trip_id', 128);

            // Provider metadata
            $table->string('provider_name', 255)->nullable();
            $table->timestamp('provider_sent_at');

            // Trip reference
            $table->string('booking_reference', 64)->nullable();
            $table->string('schema_version', 16)->default('1.1');

            // Computed fields (calculated after import)
            $table->timestamp('computed_start_at')->nullable();
            $table->timestamp('computed_end_at')->nullable();

            // Countries visited (JSON array of ISO codes)
            $table->json('countries_visited')->nullable();

            // Trip status
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->boolean('is_archived')->default(false);

            // PDS Share Link integration
            $table->string('pds_share_url', 512)->nullable();
            $table->string('pds_tid', 128)->nullable();
            $table->timestamp('pds_share_created_at')->nullable();

            // Raw payload storage for debugging/reprocessing
            $table->json('raw_payload')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint for upsert
            $table->unique(['provider_id', 'external_trip_id'], 'uk_provider_trip');

            // Indexes for common queries
            $table->index('computed_end_at', 'idx_computed_end_at');
            $table->index('status', 'idx_status');
            $table->index('is_archived', 'idx_is_archived');
            $table->index(['computed_start_at', 'computed_end_at'], 'idx_computed_dates');
            $table->index('provider_sent_at', 'idx_provider_sent_at');
            $table->index(['computed_end_at', 'is_archived'], 'idx_archival');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_trips');
    }
};
