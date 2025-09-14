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
            $table->string('data_source')->nullable()->after('event_category_id');
            $table->string('data_source_id')->nullable()->after('data_source');
            $table->index('data_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropIndex(['data_source']);
            $table->dropColumn(['data_source', 'data_source_id']);
        });
    }
};