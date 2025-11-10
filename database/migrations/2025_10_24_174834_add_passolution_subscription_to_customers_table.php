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
            $table->string('passolution_subscription_type')->nullable()->after('passolution_refresh_token_expires_at');
            $table->json('passolution_features')->nullable()->after('passolution_subscription_type');
            $table->timestamp('passolution_subscription_updated_at')->nullable()->after('passolution_features');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'passolution_subscription_type',
                'passolution_features',
                'passolution_subscription_updated_at'
            ]);
        });
    }
};
