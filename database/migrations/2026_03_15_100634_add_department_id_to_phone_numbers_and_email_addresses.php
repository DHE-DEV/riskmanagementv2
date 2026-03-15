<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('notes')->constrained('departments')->nullOnDelete();
        });
        Schema::table('email_addresses', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('notes')->constrained('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
        Schema::table('email_addresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
