<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade'); // the account being accessed
            $table->foreignId('accessor_customer_id')->constrained('customers')->onDelete('cascade'); // the user who gets access
            $table->timestamps();

            $table->unique(['customer_id', 'accessor_customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_access');
    }
};
