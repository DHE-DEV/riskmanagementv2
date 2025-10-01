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
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name der Aufgabe (z.B. "Risikobewertung erstellen")
            $table->text('description')->nullable(); // Beschreibung der Aufgabe
            $table->string('model_type'); // z.B. "Country", "City", "CustomEvent"
            $table->text('prompt_template'); // Der Prompt mit Platzhaltern wie {name}, {iso_code}
            $table->string('category')->nullable(); // Kategorie für Gruppierung
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_prompts');
    }
};
