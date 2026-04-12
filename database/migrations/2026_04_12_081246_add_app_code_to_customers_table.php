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
            $table->string('app_code', 4)->nullable()->unique()->after('id');
        });

        // Generate unique app codes for all existing customers
        $customers = \DB::table('customers')->whereNull('app_code')->get();
        foreach ($customers as $customer) {
            $appCode = $this->generateUniqueAppCode();
            \DB::table('customers')->where('id', $customer->id)->update(['app_code' => $appCode]);
        }

        // Make the field not nullable (unique index already exists from above)
        Schema::table('customers', function (Blueprint $table) {
            $table->string('app_code', 4)->nullable(false)->change();
        });
    }

    /**
     * Generate a unique 4-character alphanumeric app code.
     */
    private function generateUniqueAppCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        } while (
            \DB::table('customers')->where('app_code', $code)->exists() ||
            \DB::table('branches')->where('app_code', $code)->exists()
        );

        return $code;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('app_code');
        });
    }
};
