<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zugeordnete Agenturen (Unteraccounts) beim Kunden auch als Adressen
     * anzeigen – Einstellungen › Organisation und /branches.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('show_sub_accounts_as_branches')->default(false)->after('branch_management_active');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('show_sub_accounts_as_branches');
        });
    }
};
