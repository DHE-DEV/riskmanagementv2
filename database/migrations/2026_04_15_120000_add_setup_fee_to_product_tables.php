<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_setup', 10, 2)->nullable()->after('price_one_time');
        });

        Schema::table('product_versions', function (Blueprint $table) {
            $table->decimal('price_setup', 10, 2)->nullable()->after('price_one_time');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->decimal('price_setup', 10, 2)->nullable()->after('price_one_time');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price_setup');
        });

        Schema::table('product_versions', function (Blueprint $table) {
            $table->dropColumn('price_setup');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('price_setup');
        });
    }
};
