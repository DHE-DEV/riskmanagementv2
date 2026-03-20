<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add customer_id to td_trips for direct customer-to-trip association.
 *
 * Previously, trips were only linked to customers via pds_trip_label (loose coupling).
 * With customer_id, trips can be directly queried per customer for listings, sync, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('td_trips', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('id');
            $table->index(['customer_id', 'status'], 'idx_customer_status');
            $table->index(['customer_id', 'computed_start_at', 'computed_end_at'], 'idx_customer_dates');
        });
    }

    public function down(): void
    {
        Schema::table('td_trips', function (Blueprint $table) {
            $table->dropIndex('idx_customer_status');
            $table->dropIndex('idx_customer_dates');
            $table->dropColumn('customer_id');
        });
    }
};
