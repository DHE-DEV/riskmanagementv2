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
        Schema::create('entry_conditions_logs', function (Blueprint $table) {
            $table->id();
            $table->json('filters')->comment('Selected filter checkboxes');
            $table->string('nationality', 2)->comment('Selected nationality ISO code');
            $table->json('request_body')->comment('Full API request body sent to Passolution');
            $table->json('response_data')->nullable()->comment('API response from Passolution');
            $table->integer('response_status')->nullable()->comment('HTTP status code');
            $table->integer('results_count')->nullable()->comment('Number of destinations returned');
            $table->boolean('success')->default(false)->comment('Whether the request was successful');
            $table->text('error_message')->nullable()->comment('Error message if request failed');
            $table->timestamps();

            // Indexes
            $table->index('created_at');
            $table->index('nationality');
            $table->index('success');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_conditions_logs');
    }
};
