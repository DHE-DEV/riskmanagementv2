<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kooperationen (Admin v2 › System › Kooperationen), je Kunde die
     * Zeitraeume, in denen er selbststaendig oder ueber eine Kooperation
     * abgerechnet wird, und die Zeitraeume des Abrechnungsintervalls – fuer
     * alle abonnierten Dienste oder einzelne Module. Zeitraeume derselben Art
     * ueberschneiden sich nicht.
     */
    public function up(): void
    {
        Schema::create('cooperations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Eigener Account der Kooperation auf der Plattform (z. B. die Zentrale)
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            // Wie Mitglieder standardmaessig abgerechnet werden: cooperation | self_payer
            $table->string('member_billing', 20)->default('cooperation');
            $table->string('billing_interval', 20)->default('monthly');
            $table->string('kickback_type', 20)->default('none');
            $table->decimal('kickback_value', 10, 2)->nullable();
            $table->string('contact_email')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_billing_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            // Ohne Kooperation: selbststaendig
            $table->foreignId('cooperation_id')->nullable()->constrained('cooperations')->restrictOnDelete();
            // self_payer | cooperation – mit Kooperation auch einzeln abrechenbar
            $table->string('billing_mode', 20);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'starts_on']);
        });

        Schema::create('customer_billing_intervals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            // null = alle abonnierten Dienste; sonst ein Modul (TravelInformationService::MODULES)
            $table->string('module', 50)->nullable();
            // monthly | half_yearly | yearly
            $table->string('interval', 20);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'module', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_billing_intervals');
        Schema::dropIfExists('customer_billing_periods');
        Schema::dropIfExists('cooperations');
    }
};
