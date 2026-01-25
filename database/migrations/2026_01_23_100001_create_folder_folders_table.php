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
        Schema::create('folder_folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('folder_number', 64)->unique();
            $table->string('folder_name', 255)->nullable();
            $table->date('travel_start_date')->nullable()->index();
            $table->date('travel_end_date')->nullable()->index();
            $table->json('destinations_visited')->nullable();
            $table->string('primary_destination', 255)->nullable()->index();
            $table->enum('status', ['draft', 'confirmed', 'active', 'completed', 'cancelled'])->default('draft')->index();
            $table->enum('travel_type', ['business', 'leisure', 'mixed'])->default('leisure');
            $table->string('agent_name', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('total_participants')->default(0);
            $table->unsignedInteger('total_itineraries')->default(0);
            $table->decimal('total_value', 12, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index(['customer_id', 'status']);
            $table->index(['travel_start_date', 'travel_end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_folders');
    }
};
