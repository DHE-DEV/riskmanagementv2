<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gespeicherte Prompts der KI-Feldrecherche: je Feld eines Bereichs, mit
     * Platzhaltern (z. B. {name}) und damit fuer jeden Eintrag nutzbar.
     */
    public function up(): void
    {
        Schema::create('ai_field_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('area', 40);
            $table->string('field', 80);
            $table->string('name', 100);
            $table->text('prompt');
            $table->string('provider', 20);
            $table->string('model', 80);
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['area', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_field_prompts');
    }
};
