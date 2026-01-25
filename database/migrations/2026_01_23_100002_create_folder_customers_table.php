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
        Schema::create('folder_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('folder_id')->constrained('folder_folders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('salutation', ['mr', 'mrs', 'diverse'])->nullable();
            $table->string('title', 64)->nullable();
            $table->string('first_name', 128);
            $table->string('last_name', 128);
            $table->string('email', 255)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('mobile', 64)->nullable();
            $table->string('street', 255)->nullable();
            $table->string('house_number', 20)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->text('notes')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality', 2)->nullable();
            $table->timestamps();

            $table->index(['folder_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_customers');
    }
};
