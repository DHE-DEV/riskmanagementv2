<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laender-Stammdaten fuer Endkunden-Apps: Gebietstyp, Fahrseite und die
 * praktischen Reiseinformationen (Steckertypen, Notrufnummern, Zeitzonen,
 * Trinkgeld, mehrsprachige Texte) sowie Bilder je Land.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('territory_type', 20)->default('sovereign')->after('continent_id');
            $table->foreignId('parent_country_id')->nullable()->after('territory_type')->constrained('countries')->nullOnDelete();
            $table->string('driving_side', 5)->nullable()->after('parent_country_id');
            $table->json('travel_info')->nullable()->after('driving_side');
            $table->index('territory_type');
        });

        Schema::create('country_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            // hero = Titelbild (hoechstens eines je Land), gallery = Galerie
            $table->string('kind', 20)->default('gallery');
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->string('thumb_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            // Bildschwerpunkt fuer Zuschnitte: 0–1 je Achse, Mitte = 0.5
            $table->decimal('focal_x', 4, 3)->default(0.5);
            $table->decimal('focal_y', 4, 3)->default(0.5);
            $table->json('alt_translations')->nullable();
            $table->json('caption_translations')->nullable();
            $table->string('credit')->nullable();
            $table->string('license', 100)->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['country_id', 'kind', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_images');

        Schema::table('countries', function (Blueprint $table) {
            $table->dropForeign(['parent_country_id']);
            $table->dropIndex(['territory_type']);
            $table->dropColumn(['territory_type', 'parent_country_id', 'driving_side', 'travel_info']);
        });
    }
};
