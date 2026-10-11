<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teilgebiete einer Region (subregions): Inseln, Inselgruppen und
 * Reisegebiete wie Teneriffa, die Liparischen Inseln oder die Costa Brava.
 * Sie liegen zwischen Region und Stadt; Staedte und Sehenswuerdigkeiten
 * koennen einem Teilgebiet zugeordnet werden, muessen aber nicht.
 * Die Infos haben denselben Aufbau wie die Regionsinfos (RegionInfo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subregions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('area');
            $table->json('name_translations');
            $table->string('code', 20)->nullable();
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->json('info')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['region_id', 'sort_order']);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->foreignId('subregion_id')->nullable()->after('region_id')->constrained()->nullOnDelete();
        });

        Schema::table('sights', function (Blueprint $table) {
            $table->foreignId('subregion_id')->nullable()->after('region_id')->constrained()->nullOnDelete();
        });

        Schema::table('region_info_runs', function (Blueprint $table) {
            $table->foreignId('subregion_id')->nullable()->after('region_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['region_info_runs', 'sights', 'cities'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('subregion_id');
            });
        }

        Schema::dropIfExists('subregions');
    }
};
