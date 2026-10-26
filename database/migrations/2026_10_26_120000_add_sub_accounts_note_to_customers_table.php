<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hinweis zu den Unteraccounts einer Agentur, z. B. "Reisebuerokette –
     * die Unteraccounts sind eigenstaendige Bueros mit eigenen Logins".
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('sub_accounts_note')->nullable()->after('assign_to');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('sub_accounts_note');
        });
    }
};
