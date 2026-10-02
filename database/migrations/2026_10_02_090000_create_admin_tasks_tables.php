<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aufgabenverwaltung der Mitarbeiter im Admin-Bereich.
     *
     * Eine Aufgabe kann frei stehen oder an einem Datensatz haengen (subject_*,
     * z. B. an einem Ereignis). Notizen und Aenderungen landen gemeinsam in
     * admin_task_activities – daraus entsteht der Verlauf der Aufgabe.
     */
    public function up(): void
    {
        Schema::create('admin_task_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('admin_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('admin_task_categories');
            $table->string('status', 20)->default('open')->index();
            // low | normal | high | urgent
            $table->string('priority', 20)->default('normal')->index();
            $table->date('due_date')->nullable()->index();
            // Wann die Mail "faellig und nicht erledigt" verschickt wurde.
            $table->dateTime('due_notified_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            // Erfasser, verantwortliche Person, naechster Bearbeiter.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('next_assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->nullableMorphs('subject');
            // Aufgaben zu einem noch nicht gespeicherten Datensatz: sie werden
            // ueber dieses Kennzeichen zugeordnet, sobald er gespeichert ist.
            $table->string('subject_token', 64)->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('admin_task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('admin_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // created | changed | note
            $table->string('type', 20);
            $table->text('body')->nullable();
            // Bei "changed": [{field, label, old, new}, ...] in lesbarer Form.
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        // Beliebig viele Erinnerungen je Aufgabe – auch fuer verschiedene Personen.
        Schema::create('admin_task_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('admin_tasks')->cascadeOnDelete();
            $table->dateTime('remind_at')->index();
            // null = die Person, bei der die Aufgabe beim Versand liegt.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            // Wann die Erinnerung verschickt wurde – verhindert doppelte Mails.
            $table->dateTime('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();

        DB::table('admin_task_categories')->insert(array_map(
            fn (string $name, int $index) => [
                'name' => $name,
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['Global Travel Monitor', 'Travel Alert', 'Stammdaten'],
            [0, 1, 2],
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_task_reminders');
        Schema::dropIfExists('admin_task_activities');
        Schema::dropIfExists('admin_tasks');
        Schema::dropIfExists('admin_task_categories');
    }
};
