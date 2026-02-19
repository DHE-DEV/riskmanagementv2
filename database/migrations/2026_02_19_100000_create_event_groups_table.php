<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('include_passolution_events')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('api_client_event_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_group_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['api_client_id', 'event_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_client_event_group');
        Schema::dropIfExists('event_groups');
    }
};
