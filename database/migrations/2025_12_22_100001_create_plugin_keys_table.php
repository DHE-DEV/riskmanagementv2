<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_client_id')->constrained('plugin_clients')->cascadeOnDelete();
            $table->string('public_key', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['plugin_client_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_keys');
    }
};
