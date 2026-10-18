<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wer einen Push aus Admin v2 (System > Push senden) verschickt hat.
     */
    public function up(): void
    {
        Schema::table('app_push_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('customer_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('app_push_messages', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
