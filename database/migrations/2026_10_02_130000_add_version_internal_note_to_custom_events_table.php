<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Interne Notiz je Version – nur fuer den Admin-Bereich. Anders als
     * "version_note" geht sie nie an Kunden (API, Benachrichtigungen).
     */
    public function up(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->text('version_internal_note')->nullable()->after('version_note');
        });
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropColumn('version_internal_note');
        });
    }
};
