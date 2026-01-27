<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        Schema::table('disaster_events', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Generate UUIDs for existing records
        DB::table('custom_events')->whereNull('uuid')->eachById(function ($event) {
            DB::table('custom_events')->where('id', $event->id)->update(['uuid' => Str::uuid()]);
        });

        DB::table('disaster_events')->whereNull('uuid')->eachById(function ($event) {
            DB::table('disaster_events')->where('id', $event->id)->update(['uuid' => Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('disaster_events', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
