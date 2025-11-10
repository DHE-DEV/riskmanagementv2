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
        Schema::table('customers', function (Blueprint $table) {
            $table->text('passolution_access_token')->nullable()->after('billing_country');
            $table->timestamp('passolution_token_expires_at')->nullable()->after('passolution_access_token');
            $table->text('passolution_refresh_token')->nullable()->after('passolution_token_expires_at');
            $table->timestamp('passolution_refresh_token_expires_at')->nullable()->after('passolution_refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'passolution_access_token',
                'passolution_token_expires_at',
                'passolution_refresh_token',
                'passolution_refresh_token_expires_at'
            ]);
        });
    }
};
