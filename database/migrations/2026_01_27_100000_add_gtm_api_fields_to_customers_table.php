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
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('gtm_api_enabled')->default(false)->after('travelers_refresh_interval');
            $table->unsignedInteger('gtm_api_rate_limit')->default(60)->after('gtm_api_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['gtm_api_enabled', 'gtm_api_rate_limit']);
        });
    }
};
