<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * username aus dem SSO-userinfo-Response (Laravel Passport) am Customer ablegen.
 * Der Wert entspricht dem Login-Namen der Plattform (kann von der E-Mail
 * abweichen) und wird bislang beim Login verworfen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('username')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
