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
        Schema::create('folder_label', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('folder_id')->constrained('folder_folders')->cascadeOnDelete();
            $table->foreignUuid('label_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['folder_id', 'label_id']);
        });

        Schema::create('custom_event_label', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_event_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('label_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['custom_event_id', 'label_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_event_label');
        Schema::dropIfExists('folder_label');
    }
};
