<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('continents')) {
            Schema::create('continents', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 2)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Seed continents if table is empty
        if (DB::table('continents')->count() === 0) {
            DB::table('continents')->insert([
                ['id' => 1, 'name' => 'Afrika', 'code' => 'AF', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 2, 'name' => 'Asien', 'code' => 'AS', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 3, 'name' => 'Europa', 'code' => 'EU', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 4, 'name' => 'Antarktis', 'code' => 'AN', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 5, 'name' => 'Nordamerika', 'code' => 'NA', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 6, 'name' => 'Südamerika', 'code' => 'SA', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 7, 'name' => 'Ozeanien', 'code' => 'OC', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('continents');
    }
};
