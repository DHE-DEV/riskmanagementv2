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
        Schema::create('event_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // earthquake, flood, etc.
            $table->string('name'); // Erdbeben, Überschwemmung, etc.
            $table->string('description')->nullable();
            $table->string('color')->default('#3B82F6'); // Standardfarbe
            $table->string('icon')->default('fa-exclamation-triangle'); // FontAwesome Icon
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_types');
    }
};
