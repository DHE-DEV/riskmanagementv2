<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_queue_logs', function (Blueprint $table) {
            $table->id();
            $table->string('queue_name', 50);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('events_processed')->default(0);
            $table->unsignedInteger('notifications_sent')->default(0);
            $table->unsignedInteger('errors')->default(0);
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index('queue_name');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_queue_logs');
    }
};
