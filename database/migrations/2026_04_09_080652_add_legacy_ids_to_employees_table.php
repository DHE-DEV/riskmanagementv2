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
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('legacy_usersweb_id')->nullable()->unique()->after('notes');
            $table->unsignedInteger('legacy_client_account_id')->nullable()->unique()->after('legacy_usersweb_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['legacy_usersweb_id', 'legacy_client_account_id']);
        });
    }
};
