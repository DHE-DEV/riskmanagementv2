<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eine KI-Pruefung legt ihre Aufgaben entweder als Unteraufgaben einer
 * Sammelaufgabe an ("parent") oder als Einzelaufgabe je Datensatz ("single") –
 * dann mit eigenen Einstellungen (Rubrik, Prioritaet, Verantwortung, Faelligkeit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_checks', function (Blueprint $table) {
            $table->string('task_mode', 20)->default('parent')->after('task_enabled');
            /** Einstellungen der Einzelaufgaben: category_id, priority, responsible, next_assignee, due_days */
            $table->json('task_settings')->nullable()->after('task_parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('ai_checks', function (Blueprint $table) {
            $table->dropColumn(['task_mode', 'task_settings']);
        });
    }
};
