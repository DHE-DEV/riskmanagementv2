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
        Schema::table('folder_folders', function (Blueprint $table) {
            $table->string('custom_field_1_label', 100)->nullable()->after('notes');
            $table->text('custom_field_1_value')->nullable()->after('custom_field_1_label');

            $table->string('custom_field_2_label', 100)->nullable()->after('custom_field_1_value');
            $table->text('custom_field_2_value')->nullable()->after('custom_field_2_label');

            $table->string('custom_field_3_label', 100)->nullable()->after('custom_field_2_value');
            $table->text('custom_field_3_value')->nullable()->after('custom_field_3_label');

            $table->string('custom_field_4_label', 100)->nullable()->after('custom_field_3_value');
            $table->text('custom_field_4_value')->nullable()->after('custom_field_4_label');

            $table->string('custom_field_5_label', 100)->nullable()->after('custom_field_4_value');
            $table->text('custom_field_5_value')->nullable()->after('custom_field_5_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folder_folders', function (Blueprint $table) {
            $table->dropColumn([
                'custom_field_1_label',
                'custom_field_1_value',
                'custom_field_2_label',
                'custom_field_2_value',
                'custom_field_3_label',
                'custom_field_3_value',
                'custom_field_4_label',
                'custom_field_4_value',
                'custom_field_5_label',
                'custom_field_5_value',
            ]);
        });
    }
};
