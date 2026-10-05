<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KI-Pruefungen koennen Aufgaben anlegen: trifft die hinterlegte Bedingung bei
 * einem Datensatz zu, entsteht unter der Sammelaufgabe der Pruefung eine
 * Unteraufgabe mit Bezug auf diesen Datensatz. Ein Sammellauf fuehrt die
 * Pruefung fuer alle Datensaetze eines Bereichs aus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_checks', function (Blueprint $table) {
            $table->boolean('task_enabled')->default(false)->after('is_active');
            /** Bedingung in eigenen Worten – die KI beurteilt je Datensatz, ob sie zutrifft */
            $table->text('task_condition')->nullable()->after('task_enabled');
            /** Sammelaufgabe, unter der die Unteraufgaben entstehen */
            $table->foreignId('task_parent_id')->nullable()->after('task_condition')->constrained('admin_tasks')->nullOnDelete();
        });

        Schema::table('admin_tasks', function (Blueprint $table) {
            /** Pruefung, zu der die Aufgabe gehoert: an der Sammelaufgabe und an ihren Unteraufgaben */
            $table->foreignId('ai_check_id')->nullable()->after('recurrence_id')->constrained('ai_checks')->nullOnDelete();
        });

        Schema::create('ai_check_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_check_id')->constrained('ai_checks')->cascadeOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            /** running | finished | cancelled | failed */
            $table->string('status', 20)->default('running');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            /** Datensaetze, bei denen die Bedingung zutraf */
            $table->unsignedInteger('matched')->default(0);
            /** neu angelegte Unteraufgaben */
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('failed')->default(0);
            /** zuletzt bearbeiteter Datensatz – dort geht es weiter */
            $table->unsignedBigInteger('last_record_id')->default(0);
            $table->unsignedBigInteger('total_tokens')->default(0);
            $table->decimal('cost', 10, 4)->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['ai_check_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_check_runs');

        Schema::table('admin_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_check_id');
        });

        Schema::table('ai_checks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('task_parent_id');
            $table->dropColumn(['task_enabled', 'task_condition']);
        });
    }
};
