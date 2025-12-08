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
        Schema::create('td_import_logs', function (Blueprint $table) {
            $table->id();

            // Provider info
            $table->string('provider_id', 64);
            $table->string('external_trip_id', 128)->nullable();

            // Import details
            $table->enum('action', ['create', 'update', 'error']);
            $table->enum('status', ['success', 'failed', 'partial']);

            // Error tracking
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();

            // Request metadata
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();

            // Performance tracking
            $table->unsignedInteger('processing_time_ms')->nullable();

            // Timestamp
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('provider_id', 'idx_provider');
            $table->index('created_at', 'idx_created_at');
            $table->index('status', 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_import_logs');
    }
};
