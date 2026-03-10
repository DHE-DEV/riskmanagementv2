<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_unsubscribe_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique()->index();
            $table->string('email')->index();
            $table->foreignId('notification_rule_id')->nullable()->constrained('notification_rules')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_unsubscribe_tokens');
    }
};
