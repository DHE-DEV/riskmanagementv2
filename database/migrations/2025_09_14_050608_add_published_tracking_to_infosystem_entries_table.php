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
        Schema::table('infosystem_entries', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('archive');
            $table->timestamp('published_at')->nullable()->after('is_published');
            $table->unsignedBigInteger('published_as_event_id')->nullable()->after('published_at');
            $table->index('is_published');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('infosystem_entries', function (Blueprint $table) {
            $table->dropIndex(['is_published']);
            $table->dropIndex(['published_at']);
            $table->dropColumn(['is_published', 'published_at', 'published_as_event_id']);
        });
    }
};