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
        if (!Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->json('name_translations');
                $table->foreignId('country_id')->constrained()->onDelete('cascade');
                $table->foreignId('region_id')->nullable()->constrained()->onDelete('cascade');
                $table->integer('population')->nullable();
                $table->decimal('lat', 10, 6)->nullable();
                $table->decimal('lng', 11, 6)->nullable();
                $table->boolean('is_capital')->default(false);
                $table->boolean('is_regional_capital')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
