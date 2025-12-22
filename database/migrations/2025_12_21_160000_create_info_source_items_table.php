<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('info_source_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('info_source_id')->constrained()->onDelete('cascade');
            $table->string('external_id')->nullable()->comment('ID aus der Quelle');
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->text('link')->nullable();
            $table->string('author')->nullable();
            $table->json('categories')->nullable();
            $table->json('countries')->nullable()->comment('Erkannte Länder-Codes');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('updated_at_source')->nullable();
            $table->enum('status', ['new', 'reviewed', 'imported', 'ignored'])->default('new');
            $table->foreignId('imported_as_event_id')->nullable()->comment('ID des erstellten CustomEvents');
            $table->json('raw_data')->nullable()->comment('Originaldaten aus der Quelle');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['info_source_id', 'external_id']);
            $table->index(['status', 'created_at']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('info_source_items');
    }
};
