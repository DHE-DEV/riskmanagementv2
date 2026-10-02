<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Teams der Mitarbeiter im Admin-Bereich. Eine Aufgabe kann statt einer
     * Person ein Team als Verantwortlichen oder naechsten Bearbeiter haben.
     */
    public function up(): void
    {
        Schema::create('admin_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            // Zentrale Adresse des Teams.
            $table->string('email')->nullable();
            // Wer bei Benachrichtigungen angeschrieben wird: members | team_email
            $table->string('notify_mode', 20)->default('members');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('admin_team_user', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained('admin_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['team_id', 'user_id']);
        });

        foreach (['admin_tasks', 'admin_task_recurrences'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('responsible_team_id')->nullable()->after('responsible_id')->constrained('admin_teams')->nullOnDelete();
                $table->foreignId('next_assignee_team_id')->nullable()->after('next_assignee_id')->constrained('admin_teams')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['admin_tasks', 'admin_task_recurrences'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('responsible_team_id');
                $table->dropConstrainedForeignId('next_assignee_team_id');
            });
        }

        Schema::dropIfExists('admin_team_user');
        Schema::dropIfExists('admin_teams');
    }
};
