<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name');
            $table->string('contact_email');
            $table->string('logo_path')->nullable();
            $table->string('status')->default('active'); // active, inactive, suspended
            $table->boolean('auto_approve_events')->default(false);
            $table->integer('rate_limit')->default(60);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
