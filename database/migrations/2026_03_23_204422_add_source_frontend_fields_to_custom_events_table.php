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
            $table->boolean('source_show_frontend')->default(true)->after('source');
            $table->string('source_link_text')->nullable()->after('source_show_frontend');
            $table->string('source_link_url')->nullable()->after('source_link_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropColumn(['source_show_frontend', 'source_link_text', 'source_link_url']);
        });
    }
};
