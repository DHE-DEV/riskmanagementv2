<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_client_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained('api_clients')->cascadeOnDelete();
            $table->unsignedBigInteger('token_id')->nullable();
            $table->string('method', 10);
            $table->string('endpoint');
            $table->json('query_params')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->integer('response_status');
            $table->integer('response_time_ms')->nullable();
            $table->timestamp('created_at');

            $table->index('api_client_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_client_request_logs');
    }
};
