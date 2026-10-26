<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zentrale: Feature-Ueberschreibungen an die zugeordneten Unteraccounts
     * (customers.assign_to) vererben.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('inherit_features_to_sub_accounts')->default(false)->after('show_sub_accounts_as_branches');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('inherit_features_to_sub_accounts');
        });
    }
};
