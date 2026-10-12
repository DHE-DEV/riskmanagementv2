<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wann die KI zuletzt nach Teilgebieten (Inseln, Reisegebieten) der Region
 * gesucht hat – regions:fill-missing sucht danach nur noch auf Wunsch erneut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->timestamp('subregions_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('subregions_checked_at');
        });
    }
};
