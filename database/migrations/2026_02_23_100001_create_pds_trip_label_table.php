<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pds_trip_label', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('pds_tid', 128);
            $table->foreignUuid('label_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_id', 'pds_tid', 'label_id']);
            $table->index(['customer_id', 'pds_tid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pds_trip_label');
    }
};
