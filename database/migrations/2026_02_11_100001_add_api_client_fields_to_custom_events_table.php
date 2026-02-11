<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->foreignId('api_client_id')->nullable()->after('updated_by')
                ->constrained('api_clients')->nullOnDelete();
            $table->string('review_status')->default('approved')->after('api_client_id');
            $table->timestamp('reviewed_at')->nullable()->after('review_status');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                ->constrained('users')->nullOnDelete();

            $table->index('review_status');
            $table->index(['api_client_id', 'review_status']);
        });
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropForeign(['api_client_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['review_status']);
            $table->dropIndex(['api_client_id', 'review_status']);
            $table->dropColumn(['api_client_id', 'review_status', 'reviewed_at', 'reviewed_by']);
        });
    }
};
