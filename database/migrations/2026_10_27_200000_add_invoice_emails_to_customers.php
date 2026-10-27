<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rechnungsadresse: „Wie Firmeninformation“ (wird beim Speichern
     * uebernommen) und E-Mail-Empfaenger fuer Rechnungen (An, CC, BCC).
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('billing_same_as_company')->default(false)->after('billing_country');
            $table->json('invoice_emails')->nullable()->after('billing_same_as_company');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['billing_same_as_company', 'invoice_emails']);
        });
    }
};
