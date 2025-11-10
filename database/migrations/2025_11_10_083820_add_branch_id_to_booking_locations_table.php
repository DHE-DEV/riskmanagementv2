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
        Schema::table('booking_locations', function (Blueprint $table) {
            // Prüfen ob customer_id existiert, um after() Position zu bestimmen
            if (Schema::hasColumn('booking_locations', 'customer_id')) {
                $table->foreignId('branch_id')->nullable()->after('customer_id')->constrained('branches')->onDelete('cascade');
            } else {
                // Wenn customer_id nicht existiert, füge branch_id einfach hinzu
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_locations', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
