<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add SSO Fields to Customers Table
 *
 * Fügt SSO-bezogene Felder zur customers-Tabelle hinzu
 * Adds SSO-related fields to the customers table
 *
 * Neue Felder / New fields:
 * - agent_id: ID des Agenten/Vermittlers aus Service 1
 * - service1_customer_id: Kunden-ID aus Service 1 (pds-homepage)
 * - phone: Telefonnummer des Kunden
 * - address: Adresse des Kunden (JSON)
 * - account_type: Account-Typ (z.B. standard, premium)
 *
 * Unique Constraint:
 * - Kombination aus agent_id und service1_customer_id muss eindeutig sein
 * - Combination of agent_id and service1_customer_id must be unique
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fügt die SSO-Felder zur customers-Tabelle hinzu
     * Adds SSO fields to the customers table
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Agent ID from Service 1 / Agenten-ID aus Service 1
            // Identifies which agent/broker the customer belongs to
            // Identifiziert, zu welchem Agenten/Vermittler der Kunde gehört
            $table->string('agent_id')->nullable()->after('id');

            // Customer ID from Service 1 / Kunden-ID aus Service 1
            // Links this customer to their account in pds-homepage
            // Verknüpft diesen Kunden mit seinem Account in pds-homepage
            $table->string('service1_customer_id')->nullable()->after('agent_id');

            // Phone number / Telefonnummer
            $table->string('phone')->nullable()->after('email');

            // Address stored as JSON / Adresse als JSON gespeichert
            // Structure: { "street": "...", "city": "...", "zip": "...", "country": "..." }
            // Struktur: { "street": "...", "city": "...", "zip": "...", "country": "..." }
            $table->json('address')->nullable()->after('phone');

            // Account type / Account-Typ
            // e.g., "standard", "premium", "vip"
            // z.B. "standard", "premium", "vip"
            $table->string('account_type')->default('standard')->after('address');

            // Unique constraint on agent_id + service1_customer_id
            // Eindeutigkeits-Constraint für agent_id + service1_customer_id
            // Ensures each customer from Service 1 is unique per agent
            // Stellt sicher, dass jeder Kunde aus Service 1 pro Agent eindeutig ist
            $table->unique(['agent_id', 'service1_customer_id'], 'unique_agent_customer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Entfernt die SSO-Felder aus der customers-Tabelle
     * Removes SSO fields from the customers table
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Drop unique constraint first / Unique Constraint zuerst entfernen
            $table->dropUnique('unique_agent_customer');

            // Drop columns / Spalten entfernen
            $table->dropColumn([
                'agent_id',
                'service1_customer_id',
                'phone',
                'address',
                'account_type',
            ]);
        });
    }
};
