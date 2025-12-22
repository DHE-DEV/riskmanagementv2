<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('info_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['rss', 'api', 'rss_api'])->default('rss');
            $table->text('url')->nullable();
            $table->text('api_endpoint')->nullable();
            $table->string('api_key')->nullable();
            $table->json('api_config')->nullable();
            $table->enum('content_type', ['travel_advisory', 'health', 'disaster', 'conflict', 'general'])->default('general');
            $table->string('country_code', 2)->nullable()->comment('ISO-2 Code für länderspezifische Quellen');
            $table->string('language', 5)->default('en');
            $table->integer('refresh_interval')->default(3600)->comment('Aktualisierungsintervall in Sekunden');
            $table->boolean('is_active')->default(false);
            $table->boolean('auto_import')->default(false)->comment('Events automatisch importieren');
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('info_sources');
    }
};
