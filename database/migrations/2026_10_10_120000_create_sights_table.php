<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sehenswuerdigkeiten und Unternehmungen (sights): haengen am Land, optional
 * an einer Region (z. B. Nationalparks) und an einer Stadt. Texte, Hinweise
 * und Verwaltungsangaben liegen in "info" – siehe App\Support\AdminV2\SightInfo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sights', function (Blueprint $table) {
            $table->id();
            $table->json('name_translations');
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 30)->default('other');
            $table->boolean('is_highlight')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('ticket_url', 500)->nullable();
            $table->json('info')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_id', 'region_id']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sights');
    }
};
