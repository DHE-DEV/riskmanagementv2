<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rechnungsempfaenger einer Kooperation (An, CC, BCC, je mehrere) statt der
     * einzelnen E-Mail-Adresse; vorhandene Adressen landen unter „An“.
     */
    public function up(): void
    {
        Schema::table('cooperations', function (Blueprint $table) {
            $table->json('invoice_emails')->nullable()->after('kickback_value');
        });

        DB::table('cooperations')->whereNotNull('contact_email')->orderBy('id')->each(function ($cooperation) {
            DB::table('cooperations')->where('id', $cooperation->id)->update([
                'invoice_emails' => json_encode(['to' => [$cooperation->contact_email], 'cc' => [], 'bcc' => []]),
            ]);
        });

        Schema::table('cooperations', function (Blueprint $table) {
            $table->dropColumn('contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('cooperations', function (Blueprint $table) {
            $table->string('contact_email')->nullable()->after('kickback_value');
        });

        DB::table('cooperations')->whereNotNull('invoice_emails')->orderBy('id')->each(function ($cooperation) {
            $emails = json_decode($cooperation->invoice_emails, true);
            DB::table('cooperations')->where('id', $cooperation->id)->update(['contact_email' => $emails['to'][0] ?? null]);
        });

        Schema::table('cooperations', function (Blueprint $table) {
            $table->dropColumn('invoice_emails');
        });
    }
};
