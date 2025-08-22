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
        Schema::create('infosystem_entries', function (Blueprint $table) {
            $table->id();
            
            // API-spezifische Felder
            $table->unsignedBigInteger('api_id')->unique();
            $table->integer('position')->default(0);
            $table->integer('appearance')->default(0);
            
            // Länder-Information
            $table->string('country_code', 2);
            $table->json('country_names'); // JSON für alle Sprachen
            
            // Sprach-Information
            $table->string('lang', 2)->default('de');
            $table->string('language_content')->nullable();
            $table->string('language_code', 2)->nullable();
            
            // Tag-Information
            $table->integer('tagtype')->nullable();
            $table->string('tagtext')->nullable();
            $table->date('tagdate');
            
            // Content
            $table->string('header');
            $table->text('content');
            
            // Status-Felder
            $table->boolean('archive')->default(false);
            $table->boolean('active')->default(true);
            
            // API Metadaten
            $table->timestamp('api_created_at')->nullable();
            $table->string('request_id')->nullable();
            $table->integer('response_time')->nullable();
            
            $table->timestamps();
            
            // Indizes für bessere Performance
            $table->index(['country_code', 'lang']);
            $table->index(['active', 'archive']);
            $table->index('tagdate');
            $table->index('tagtype');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infosystem_entries');
    }
};
