<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taxi-Apps (Uber, Bolt, FreeNow, …) als Stammdaten unter System und ihre
 * Zuordnung zu Laendern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('description_translations')->nullable();
            $table->string('logo_url', 2048)->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->string('app_store_url', 2048)->nullable();
            $table->string('play_store_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('country_taxi_app', function (Blueprint $table) {
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('taxi_app_id')->constrained('taxi_apps')->cascadeOnDelete();
            $table->primary(['country_id', 'taxi_app_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_taxi_app');
        Schema::dropIfExists('taxi_apps');
    }
};
