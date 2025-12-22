<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plugin_clients', function (Blueprint $table) {
            // Drop foreign key and unique constraint
            $table->dropForeign(['customer_id']);
            $table->dropUnique(['customer_id']);
        });

        Schema::table('plugin_clients', function (Blueprint $table) {
            // Make customer_id nullable
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });

        Schema::table('plugin_clients', function (Blueprint $table) {
            // Re-add foreign key (without unique constraint - allows multiple clients per customer or none)
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plugin_clients', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::table('plugin_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
        });

        Schema::table('plugin_clients', function (Blueprint $table) {
            $table->unique('customer_id');
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();
        });
    }
};
