<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_client_id')->constrained('plugin_clients')->cascadeOnDelete();
            $table->string('domain');
            $table->timestamps();

            $table->unique(['plugin_client_id', 'domain']);
            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_domains');
    }
};
