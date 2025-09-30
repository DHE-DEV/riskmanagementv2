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
        Schema::table('continents', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('code');
        });

        // Set default sort orders for existing continents
        DB::table('continents')->where('code', 'EU')->update(['sort_order' => 1]);
        DB::table('continents')->where('code', 'AS')->update(['sort_order' => 2]);
        DB::table('continents')->where('code', 'AF')->update(['sort_order' => 3]);
        DB::table('continents')->where('code', 'NA')->update(['sort_order' => 4]);
        DB::table('continents')->where('code', 'SA')->update(['sort_order' => 5]);
        DB::table('continents')->where('code', 'OC')->update(['sort_order' => 6]);
        DB::table('continents')->where('code', 'AN')->update(['sort_order' => 7]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('continents', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
