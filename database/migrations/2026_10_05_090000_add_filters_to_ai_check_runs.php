<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ein Sammellauf laesst sich eingrenzen (nur aktive Eintraege, ein Land, ein
 * Typ …) – die Eingrenzung gilt fuer den ganzen Lauf und steht deshalb an ihm.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_check_runs', function (Blueprint $table) {
            $table->json('filters')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ai_check_runs', function (Blueprint $table) {
            $table->dropColumn('filters');
        });
    }
};
