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
        Schema::create('td_pds_share_links', function (Blueprint $table) {
            $table->id();

            // Foreign key to trip
            $table->foreignId('trip_id')->constrained('td_trips')->cascadeOnDelete();

            // Share link details
            $table->string('share_url', 512);
            $table->string('tid', 128);

            // Link metadata
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            // Usage tracking
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();

            // Indexes
            $table->index('trip_id', 'idx_trip_share');
            $table->index('tid', 'idx_tid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_pds_share_links');
    }
};
