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
        Schema::create('event_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_event_id')->constrained('custom_events')->onDelete('cascade');
            $table->enum('click_type', ['list', 'map_marker', 'details_button']);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('clicked_at');
            $table->timestamps();

            // Indexes for performance
            $table->index(['custom_event_id', 'click_type']);
            $table->index('clicked_at');
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_clicks');
    }
};
