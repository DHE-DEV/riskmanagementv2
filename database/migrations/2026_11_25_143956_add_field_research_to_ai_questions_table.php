<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * KI-Feldrecherche im selben Protokoll wie die freien Fragen: welches Feld,
     * welche Vorlage, was genau gesendet wurde, der Vorschlag – und wer ihn
     * wann uebernommen (und gespeichert) hat.
     */
    public function up(): void
    {
        // Das Protokoll bleibt dauerhaft – auch wenn der Benutzer geloescht wird.
        Schema::table('ai_questions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('ai_questions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('field', 80)->nullable()->after('section');
            $table->foreignId('ai_field_prompt_id')->nullable()->after('field')->constrained('ai_field_prompts')->nullOnDelete();
            $table->longText('prompt')->nullable()->after('question');
            $table->text('value')->nullable()->after('answer');
            $table->text('note')->nullable()->after('value');
            $table->text('previous_value')->nullable()->after('note');
            $table->text('applied_value')->nullable()->after('previous_value');
            $table->timestamp('applied_at')->nullable()->after('finished_at');
            $table->foreignId('applied_by')->nullable()->after('applied_at')->constrained('users')->nullOnDelete();
            $table->timestamp('saved_at')->nullable()->after('applied_by');

            $table->index(['subject_type', 'subject_id', 'field'], 'ai_questions_subject_field_index');
        });
    }

    public function down(): void
    {
        Schema::table('ai_questions', function (Blueprint $table) {
            $table->dropIndex('ai_questions_subject_field_index');
            $table->dropConstrainedForeignId('ai_field_prompt_id');
            $table->dropConstrainedForeignId('applied_by');
            $table->dropColumn(['field', 'prompt', 'value', 'note', 'previous_value', 'applied_value', 'applied_at', 'saved_at']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('ai_questions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
