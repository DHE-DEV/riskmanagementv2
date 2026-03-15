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
        Schema::create('custom_event_org_node', function (Blueprint $table) {
            $table->foreignId('custom_event_id')->constrained('custom_events')->cascadeOnDelete();
            $table->foreignId('org_node_id')->constrained('org_nodes')->cascadeOnDelete();
            $table->primary(['custom_event_id', 'org_node_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_event_org_node');
    }
};
