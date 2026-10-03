<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KI-Pruefungen: Prompts, die an einem Abschnitt eines Stammdaten-Formulars
 * (oder bereichsweit) ausgefuehrt werden – mit eigenem Modell je Prompt.
 * Die bisherigen KI-Prompts der Stammdaten (ai_prompts) werden uebernommen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_checks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            /** Bereich, z. B. countries */
            $table->string('area', 40);
            /** Abschnitt im Formular; leer = in allen Abschnitten des Bereichs */
            $table->string('section', 40)->nullable();
            $table->text('prompt');
            /** Modell nur fuer diese Pruefung; leer = Standardmodell */
            $table->string('model', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['area', 'section']);
        });

        if (! Schema::hasTable('ai_prompts')) {
            return;
        }

        $areas = ['Continent' => 'continents', 'Country' => 'countries', 'Region' => 'regions', 'City' => 'cities', 'Airport' => 'airports'];

        foreach (DB::table('ai_prompts')->whereIn('model_type', array_keys($areas))->orderBy('sort_order')->orderBy('id')->get() as $prompt) {
            DB::table('ai_checks')->insert([
                'name' => mb_substr($prompt->name, 0, 100),
                'description' => $prompt->description ? mb_substr($prompt->description, 0, 255) : null,
                'area' => $areas[$prompt->model_type],
                'section' => null,
                'prompt' => $prompt->prompt_template,
                'model' => null,
                'is_active' => (bool) $prompt->is_active,
                'sort_order' => (int) $prompt->sort_order,
                'created_at' => $prompt->created_at ?? now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_checks');
    }
};
