<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ein Feiertag steht nur einmal je Land, Datum und Name; die Regionen, in
 * denen er gilt, haengen als Liste daran (keine Region = landesweit).
 * Bisherige Zeilen je Region werden zusammengefuehrt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_holiday_region', function (Blueprint $table) {
            $table->foreignId('country_holiday_id')->constrained('country_holidays')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->primary(['country_holiday_id', 'region_id']);
        });

        // Zeilen mit Region: je Land, Datum und englischem Namen eine behalten, die Regionen anhaengen.
        $rows = DB::table('country_holidays')->whereNotNull('region_id')->orderBy('id')->get();
        $groups = [];

        foreach ($rows as $row) {
            $name = mb_strtolower((string) (json_decode($row->name_translations, true)['en'] ?? $row->name_translations));
            $groups[$row->country_id.'|'.$row->date.'|'.$name][] = $row;
        }

        foreach ($groups as $members) {
            $keep = array_shift($members);
            $regionIds = array_unique(array_merge([$keep->region_id], array_map(fn ($row) => $row->region_id, $members)));

            DB::table('country_holiday_region')->insertOrIgnore(array_map(fn ($regionId) => [
                'country_holiday_id' => $keep->id,
                'region_id' => $regionId,
            ], $regionIds));

            if ($members !== []) {
                DB::table('country_holidays')->whereIn('id', array_map(fn ($row) => $row->id, $members))->delete();
            }
        }

        Schema::table('country_holidays', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropIndex(['region_id', 'date']);
            $table->dropColumn('region_id');
        });
    }

    public function down(): void
    {
        Schema::table('country_holidays', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('country_id')->constrained('regions')->cascadeOnDelete();
            $table->index(['region_id', 'date']);
        });

        Schema::dropIfExists('country_holiday_region');
    }
};
