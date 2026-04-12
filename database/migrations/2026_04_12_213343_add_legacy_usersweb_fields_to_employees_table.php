<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_usersweb_assignto')->nullable()->after('legacy_client_account_id');
            $table->unsignedBigInteger('legacy_usersweb_idpaymentuser')->nullable()->after('legacy_usersweb_assignto');
            $table->unsignedBigInteger('legacy_usersweb_idcontact')->nullable()->after('legacy_usersweb_idpaymentuser');
            $table->string('legacy_usersweb_username')->nullable()->after('legacy_usersweb_idcontact');
            $table->string('legacy_usersweb_role')->nullable()->after('legacy_usersweb_username');
            $table->string('legacy_usersweb_revised')->nullable()->after('legacy_usersweb_role');
            $table->string('legacy_usersweb_level')->nullable()->after('legacy_usersweb_revised');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'legacy_usersweb_assignto',
                'legacy_usersweb_idpaymentuser',
                'legacy_usersweb_idcontact',
                'legacy_usersweb_username',
                'legacy_usersweb_role',
                'legacy_usersweb_revised',
                'legacy_usersweb_level',
            ]);
        });
    }
};
