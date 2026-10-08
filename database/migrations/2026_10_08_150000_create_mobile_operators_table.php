<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobilfunkanbieter als Stammdaten unter System und ihre Zuordnung zu Laendern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('description_translations')->nullable();
            $table->string('logo_url', 2048)->nullable();
            $table->string('website_url', 2048)->nullable();
            // Seite mit Prepaid-/Touristentarifen, falls es eine gibt
            $table->string('prepaid_url', 2048)->nullable();
            $table->boolean('offers_esim')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('name');
        });

        Schema::create('country_mobile_operator', function (Blueprint $table) {
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('mobile_operator_id')->constrained('mobile_operators')->cascadeOnDelete();
            $table->primary(['country_id', 'mobile_operator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_mobile_operator');
        Schema::dropIfExists('mobile_operators');
    }
};
