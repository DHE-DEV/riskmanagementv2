<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('td_trips', function (Blueprint $table) {
            $table->boolean('is_test_data')->default(false)->after('is_archived')->index();
        });
    }

    public function down(): void
    {
        Schema::table('td_trips', function (Blueprint $table) {
            $table->dropColumn('is_test_data');
        });
    }
};
