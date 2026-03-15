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
        foreach (['phone_numbers', 'email_addresses', 'websites', 'departments'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach (['phone_numbers', 'email_addresses', 'websites', 'departments'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
