<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airline_airport_code', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('airport_code_id');
            $table->enum('direction', ['from', 'to', 'both'])->default('both');
            $table->string('terminal', 50)->nullable();
            $table->timestamps();

            $table->foreign('airport_code_id')
                ->references('id')
                ->on('airport_codes_1')
                ->onDelete('cascade');

            $table->unique(['airline_id', 'airport_code_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airline_airport_code');
    }
};
