<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vertraege mit Kunden (Admin v2 › Kunde › Abo): Laufzeit, Bedingungen und
     * individuelle Preise je Auspraegung – ohne Eintrag gilt der Standardpreis
     * aus dem Produktkatalog. Ein Preis kann an eine Anzahl PAX gebunden sein.
     * Vertraege eines Kunden ueberschneiden sich nicht. Eine vereinbarte jaehrliche
     * Preiserhoehung loest nur eine Erinnerungsmail aus (contracts:price-increase-reminders).
     */
    public function up(): void
    {
        Schema::create('customer_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->unsignedSmallInteger('minimum_term_months')->nullable();
            $table->string('notice_period')->nullable();
            $table->boolean('auto_renews')->default(false);
            // Vereinbarte Preiserhoehung in % pro Vertragsjahr – nur Erinnerung, nichts automatisch
            $table->decimal('annual_increase_percent', 5, 2)->nullable();
            // Jahrestag, fuer den zuletzt erinnert wurde
            $table->date('increase_reminded_for')->nullable();
            // Liste der Bedingungen, an die der Vertrag gebunden ist
            $table->json('conditions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'starts_on']);
        });

        Schema::create('customer_contract_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_contract_id')->constrained('customer_contracts')->cascadeOnDelete();
            $table->foreignId('product_version_id')->constrained('product_versions')->cascadeOnDelete();
            $table->decimal('price_monthly', 10, 2)->nullable();
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->decimal('price_one_time', 10, 2)->nullable();
            $table->decimal('price_setup', 10, 2)->nullable();
            $table->boolean('is_free')->default(false);
            // Der Preis gilt fuer diese Anzahl Reisende (PAX), z. B. 10.000.000
            $table->unsignedBigInteger('pax')->nullable();
            $table->timestamps();

            $table->unique(['customer_contract_id', 'product_version_id'], 'customer_contract_prices_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contract_prices');
        Schema::dropIfExists('customer_contracts');
    }
};
