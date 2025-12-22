<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_client_id')->constrained('plugin_clients')->cascadeOnDelete();
            $table->string('public_key', 64);
            $table->string('domain');
            $table->string('path')->nullable();
            $table->string('event_type')->default('page_load');
            $table->json('meta')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['plugin_client_id', 'created_at']);
            $table->index(['public_key', 'created_at']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_usage_events');
    }
};
