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
        Schema::table('branch_exports', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('count'); // pending, processing, completed
            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_exports', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
