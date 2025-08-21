<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('event_type')->constrained('countries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
        });
    }
};


