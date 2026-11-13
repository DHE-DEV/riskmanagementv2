<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jede KI-Vorlage kann ein eigenes Modell verwenden – ohne Eintrag gilt
     * das Standardmodell unter System > KI.
     */
    public function up(): void
    {
        Schema::table('ai_event_search_prompts', function (Blueprint $table) {
            $table->string('model', 80)->nullable()->after('prompt');
        });
    }

    public function down(): void
    {
        Schema::table('ai_event_search_prompts', function (Blueprint $table) {
            $table->dropColumn('model');
        });
    }
};
