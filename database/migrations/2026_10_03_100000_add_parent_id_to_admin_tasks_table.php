<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unteraufgaben: eine Aufgabe kann zu einer uebergeordneten Aufgabe gehoeren.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_tasks', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('admin_tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
