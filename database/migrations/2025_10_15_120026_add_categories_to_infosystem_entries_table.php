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
            $table->json('categories')->nullable()->after('tagtype');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('infosystem_entries', function (Blueprint $table) {
            $table->dropColumn('categories');
        });
    }
};
