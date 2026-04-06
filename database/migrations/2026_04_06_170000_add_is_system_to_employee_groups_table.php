<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_groups', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('description');
        });

        // Mark all "Administratoren" groups as system groups
        \App\Models\EmployeeGroup::where('name', 'Administratoren')->update(['is_system' => true]);
    }

    public function down(): void
    {
        Schema::table('employee_groups', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
