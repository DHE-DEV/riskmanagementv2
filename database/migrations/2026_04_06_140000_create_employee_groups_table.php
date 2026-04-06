<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_employee_group', function (Blueprint $table) {
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_group_id')->constrained()->cascadeOnDelete();
            $table->primary(['employee_id', 'employee_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_employee_group');
        Schema::dropIfExists('employee_groups');
    }
};
