<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Speichert die SSO-Provider-ID am Employee, damit der Login deterministisch
 * per unveränderlicher PDS-User-ID matchen kann statt (kollisionsanfällig)
 * per E-Mail. Erst wenn kein provider_id-Match möglich ist, greift der
 * E-Mail-Fallback – und der wird beim Erst-Login zusätzlich per Log-Warnung
 * abgesichert (siehe KeycloakAuthController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'provider')) {
                $table->string('provider')->nullable()->after('personnel_number');
            }
            if (! Schema::hasColumn('employees', 'provider_id')) {
                $table->string('provider_id')->nullable()->after('provider');
                $table->index(['provider', 'provider_id'], 'employees_provider_provider_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'provider_id')) {
                $table->dropIndex('employees_provider_provider_id_index');
                $table->dropColumn('provider_id');
            }
            if (Schema::hasColumn('employees', 'provider')) {
                $table->dropColumn('provider');
            }
        });
    }
};
