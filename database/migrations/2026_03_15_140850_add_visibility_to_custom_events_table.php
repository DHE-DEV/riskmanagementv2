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
            $table->boolean('visible_community')->default(false)->after('is_active');
            $table->boolean('visible_organization')->default(true)->after('visible_community');
        });
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropColumn(['visible_community', 'visible_organization']);
        });
    }
};
