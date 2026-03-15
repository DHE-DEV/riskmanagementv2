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
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->string('template_name')->nullable()->after('subject');
            $table->string('rule_name')->nullable()->after('template_name');
            $table->boolean('is_test')->default(false)->after('rule_name');
        });

        // notification_rule_id nullable machen für Test-Mails
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('notification_rule_id')->nullable()->change();
            $table->unsignedBigInteger('event_id')->nullable()->change();
            $table->string('event_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropColumn(['template_name', 'rule_name', 'is_test']);
        });
    }
};
