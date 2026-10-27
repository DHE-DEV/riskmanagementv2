<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Filialen bekommen Start- und Enddatum (Abrechnung bei Passolution) und
     * werden nur noch weich geloescht – mit Grund und wer es war. Filialen und
     * Unteraccounts sind entweder Agentur oder Reiseberater (location_type).
     * Die Organisation stellt unter Einstellungen › Organisationsstruktur ›
     * Einstellungen ein, wer bei Aenderungen ihrer Agenturen eine Mail bekommt.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('location_type', 20)->default('agency')->after('is_headquarters');
            $table->date('start_date')->nullable()->after('location_type');
            $table->date('end_date')->nullable()->after('start_date');
            $table->text('deletion_reason')->nullable()->after('scheduled_deletion_at');
            $table->string('deletion_requested_by')->nullable()->after('deletion_reason');
            $table->softDeletes();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->json('org_mail_settings')->nullable()->after('notify_address_changes');
            $table->string('location_type', 20)->default('agency')->after('org_mail_settings');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['location_type', 'start_date', 'end_date', 'deletion_reason', 'deletion_requested_by']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['org_mail_settings', 'location_type']);
        });
    }
};
