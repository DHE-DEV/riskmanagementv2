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
        // First, convert existing string data to JSON arrays
        \DB::table('customers')
            ->whereNotNull('business_type')
            ->where('business_type', '!=', '')
            ->get()
            ->each(function ($customer) {
                \DB::table('customers')
                    ->where('id', $customer->id)
                    ->update([
                        'business_type' => json_encode([$customer->business_type])
                    ]);
            });

        // Then change the column type to JSON
        Schema::table('customers', function (Blueprint $table) {
            $table->json('business_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('business_type')->nullable()->change();
        });
    }
};
