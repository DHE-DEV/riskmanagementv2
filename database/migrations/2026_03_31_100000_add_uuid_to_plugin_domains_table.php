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
        Schema::table('plugin_domains', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Generate UUIDs for existing records
        DB::table('plugin_domains')->whereNull('uuid')->eachById(function ($domain) {
            DB::table('plugin_domains')
                ->where('id', $domain->id)
                ->update(['uuid' => Str::uuid()->toString()]);
        });

        Schema::table('plugin_domains', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('plugin_domains', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
