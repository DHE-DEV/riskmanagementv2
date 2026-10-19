<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planung der gemeinsamen Sehenswuerdigkeiten: je Geraet und Sehenswuerdigkeit eine Stimme (must = unbedingt,
 * like = gerne, no = eher nicht) und „Ich bin dabei“; der geplante Tag haengt am Eintrag in `sights` (fuer alle gleich).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->json('sight_votes')->nullable()->after('sight_visits');
        });
    }

    public function down(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->dropColumn('sight_votes');
        });
    }
};
