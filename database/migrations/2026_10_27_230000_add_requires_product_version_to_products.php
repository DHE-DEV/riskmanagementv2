<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voraussetzung auf eine bestimmte Auspraegung eingrenzen, z. B. nur mit
     * Travel Requirements Service Premium (leer = beliebige Auspraegung).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('requires_product_version_id')->nullable()->after('requires_product_id')->constrained('product_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requires_product_version_id');
        });
    }
};
