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
        foreach (['phone_numbers', 'email_addresses', 'websites'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('customer_id')->constrained('branches')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['phone_numbers', 'email_addresses', 'websites'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
