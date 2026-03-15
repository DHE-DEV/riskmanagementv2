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
        Schema::table('custom_events', function (Blueprint $table) {
            $table->date('community_start_date')->nullable()->after('visible_community');
            $table->date('community_end_date')->nullable()->after('community_start_date');
            $table->date('organization_start_date')->nullable()->after('visible_organization');
            $table->date('organization_end_date')->nullable()->after('organization_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropColumn(['community_start_date', 'community_end_date', 'organization_start_date', 'organization_end_date']);
        });
    }
};
