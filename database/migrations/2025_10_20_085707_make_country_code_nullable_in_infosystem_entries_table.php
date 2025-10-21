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
            $table->string('country_code', 2)->nullable()->change();
            $table->json('country_names')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('infosystem_entries', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable(false)->change();
            $table->json('country_names')->nullable(false)->change();
        });
    }
};
