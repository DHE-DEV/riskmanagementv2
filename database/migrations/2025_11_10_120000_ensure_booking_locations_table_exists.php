<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('booking_locations')) {
            Schema::create('booking_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
                $table->string('type'); // 'online' oder 'stationary'
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('url')->nullable();
                $table->string('address')->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('city')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        } else {
            // Tabelle existiert, füge fehlende Spalten hinzu falls nötig
            Schema::table('booking_locations', function (Blueprint $table) {
                if (!Schema::hasColumn('booking_locations', 'customer_id')) {
                    $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nichts tun - wir wollen die Tabelle nicht löschen wenn sie vorher schon existierte
    }
};
