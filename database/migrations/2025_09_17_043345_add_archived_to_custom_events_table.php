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
            $table->boolean('archived')->default(false)->after('is_active');
            $table->timestamp('archived_at')->nullable()->after('archived');
            $table->index('archived');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropIndex(['archived']);
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived');
            $table->dropColumn('archived_at');
        });
    }
};