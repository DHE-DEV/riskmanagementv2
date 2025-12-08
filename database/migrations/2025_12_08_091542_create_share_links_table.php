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
        Schema::create('share_links', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('type')->nullable()->index(); // z.B. 'entry-conditions', 'cruise', 'custom-event'
            $table->longText('data'); // JSON Daten
            $table->string('title')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedBigInteger('views')->default(0);
            $table->string('created_by_ip')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_links');
    }
};
