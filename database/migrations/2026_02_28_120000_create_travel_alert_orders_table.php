<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_alert_orders', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->string('phone', 50);
            $table->string('street');
            $table->string('postal_code', 20);
            $table->string('city');
            $table->string('country');
            $table->enum('existing_billing', ['ja', 'nein']);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_alert_orders');
    }
};
