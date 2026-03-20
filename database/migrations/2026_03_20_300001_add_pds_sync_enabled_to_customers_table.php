<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('pds_sync_enabled')->default(false)->after('gtm_api_enabled');
            $table->timestamp('pds_last_synced_at')->nullable()->after('pds_sync_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['pds_sync_enabled', 'pds_last_synced_at']);
        });
    }
};
