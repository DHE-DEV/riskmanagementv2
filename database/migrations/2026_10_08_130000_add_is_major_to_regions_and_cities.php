<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Major Region" und "Major City": die wichtigsten Regionen und Staedte eines
 * Landes fuer Endkunden-Apps – wird am Land in der Seitenspalte markiert.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->boolean('is_major')->default(false)->after('code')->index();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->boolean('is_major')->default(false)->after('is_regional_capital')->index();
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropIndex(['is_major']);
            $table->dropColumn('is_major');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropIndex(['is_major']);
            $table->dropColumn('is_major');
        });
    }
};
