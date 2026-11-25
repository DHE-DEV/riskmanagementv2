<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Freie Fragen an die KI je Abschnitt eines Eintrags (im Hintergrund per Queue).
     */
    public function up(): void
    {
        Schema::create('ai_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('area', 40);
            $table->string('section', 60);
            $table->nullableMorphs('subject');
            $table->string('title')->nullable();
            $table->string('provider', 20);
            $table->string('model', 80);
            $table->text('question');
            $table->string('status', 20)->default('queued');
            $table->longText('answer')->nullable();
            $table->json('sources')->nullable();
            $table->json('usage')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'area', 'section', 'subject_type', 'subject_id'], 'ai_questions_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_questions');
    }
};
