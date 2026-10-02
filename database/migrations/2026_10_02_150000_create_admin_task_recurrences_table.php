<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wiederkehrende Aufgaben: eine Vorlage samt Rhythmus. Der Zeitplaner legt
     * daraus zum jeweiligen Termin eine gewoehnliche Aufgabe an.
     */
    public function up(): void
    {
        Schema::create('admin_task_recurrences', function (Blueprint $table) {
            $table->id();

            // Vorlage der Aufgabe. Im Titel sind Platzhalter moeglich ({datum}, {monat}, …).
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('admin_task_categories');
            $table->string('priority', 20)->default('normal');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('next_assignee_id')->nullable()->constrained('users')->nullOnDelete();

            // Rhythmus: daily | weekly | monthly | yearly, alle "interval" Einheiten.
            $table->string('frequency', 20);
            $table->unsignedSmallInteger('interval')->default(1);
            // weekly: Wochentage 1 (Mo) bis 7 (So).
            $table->json('weekdays')->nullable();
            // monthly/yearly: "day" = fester Tag, "weekday" = z. B. zweiter Dienstag.
            $table->string('monthly_mode', 20)->default('day');
            // 1–31; 0 = letzter Tag des Monats. Gibt es den Tag im Monat nicht, gilt der letzte.
            $table->unsignedTinyInteger('day_of_month')->default(1);
            // 1–4 = erster bis vierter, -1 = letzter.
            $table->tinyInteger('nth')->default(1);
            $table->unsignedTinyInteger('nth_weekday')->default(1);
            // yearly: Monat 1–12.
            $table->unsignedTinyInteger('month')->nullable();
            // Termin am Wochenende: keep | skip | before (Freitag davor) | after (Montag danach).
            $table->string('weekend_mode', 20)->default('keep');
            // Uhrzeit, zu der die Aufgabe angelegt wird.
            $table->time('create_time')->default('07:00:00');

            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('max_occurrences')->nullable();
            $table->unsignedInteger('occurrences_count')->default(0);

            // Faelligkeit und Erinnerung der angelegten Aufgabe, relativ zum Termin.
            $table->unsignedSmallInteger('due_in_days')->nullable();
            $table->unsignedSmallInteger('remind_days_before')->nullable();
            $table->time('remind_time')->default('09:00:00');

            // Keine neue Aufgabe, solange die vorige aus dieser Serie noch offen ist.
            $table->boolean('skip_if_open')->default(false);
            $table->boolean('is_active')->default(true);

            // Naechster Termin; null = pausiert oder beendet.
            $table->dateTime('next_run_at')->nullable()->index();
            $table->dateTime('last_run_at')->nullable();
            $table->dateTime('last_skipped_at')->nullable();
            $table->string('last_error')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('admin_tasks', function (Blueprint $table) {
            // Aus welcher wiederkehrenden Aufgabe diese Aufgabe stammt.
            $table->foreignId('recurrence_id')->nullable()->after('subject_token')
                ->constrained('admin_task_recurrences')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurrence_id');
        });

        Schema::dropIfExists('admin_task_recurrences');
    }
};
