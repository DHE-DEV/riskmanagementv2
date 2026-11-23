<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auftraege der KI-Feldpruefung (Websuche im Hintergrund).
     */
    public function up(): void
    {
        Schema::create('ai_field_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('area', 40);
            $table->string('section', 60);
            $table->nullableMorphs('subject');
            $table->string('title')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('status', 20)->default('queued');
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_field_reviews');
    }
};
