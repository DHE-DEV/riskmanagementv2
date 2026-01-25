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
        Schema::create('folder_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('folder_id')->constrained('folder_folders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('salutation', ['mr', 'mrs', 'child', 'infant', 'diverse'])->nullable();
            $table->string('title', 64)->nullable();
            $table->string('first_name', 128);
            $table->string('last_name', 128);
            $table->date('birth_date')->nullable();
            $table->string('nationality', 2)->nullable()->index();
            $table->string('passport_number', 64)->nullable();
            $table->date('passport_issue_date')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('passport_issuing_country', 2)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 64)->nullable();
            $table->text('dietary_requirements')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_main_contact')->default(false);
            $table->enum('participant_type', ['adult', 'child', 'infant'])->default('adult');
            $table->timestamps();

            $table->index(['folder_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_participants');
    }
};
