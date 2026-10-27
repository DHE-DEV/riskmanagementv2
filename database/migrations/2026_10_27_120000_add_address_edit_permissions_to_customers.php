<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Darf der Kunde seine Adresse selbst aendern, gilt das auch fuer seine
     * Unteraccounts, und bekommen Zentrale und Buero eine Mail bei Aenderungen?
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('can_edit_own_address')->default(false)->after('show_sub_accounts_as_branches');
            $table->boolean('sub_accounts_can_edit_own_address')->default(false)->after('can_edit_own_address');
            $table->boolean('notify_address_changes')->default(false)->after('sub_accounts_can_edit_own_address');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['can_edit_own_address', 'sub_accounts_can_edit_own_address', 'notify_address_changes']);
        });
    }
};
