<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plugin_email_verifications', function (Blueprint $table) {
            $table->text('form_data')->change();
        });
    }

    public function down(): void
    {
        Schema::table('plugin_email_verifications', function (Blueprint $table) {
            $table->json('form_data')->change();
        });
    }
};
