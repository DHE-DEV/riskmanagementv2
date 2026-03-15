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
        Schema::table('branch_org_node', function (Blueprint $table) {
            $table->string('customer_number', 100)->nullable();
            $table->string('contract_number', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('branch_org_node', function (Blueprint $table) {
            $table->dropColumn(['customer_number', 'contract_number']);
        });
    }
};
