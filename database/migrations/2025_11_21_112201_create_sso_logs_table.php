<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates the sso_logs table for comprehensive SSO (Single Sign-On) logging.
     * It tracks every step of the SSO authentication flow including JWT validation,
     * customer lookup/creation, login attempts, and any errors that occur.
     */
    public function up(): void
    {
        Schema::create('sso_logs', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Request tracking - unique identifier for each SSO attempt
            // All logs with the same request_id belong to the same SSO flow
            $table->string('request_id', 100)->index()->comment('Unique identifier for tracking a complete SSO flow');

            // Step tracking - which step in the SSO flow this log entry represents
            // Possible values: exchange_request, jwt_validation, customer_lookup, customer_creation, login_attempt, redirect, etc.
            $table->string('step', 50)->index()->comment('Current step in SSO flow');

            // Status of this step - success, error, warning, info
            $table->enum('status', ['success', 'error', 'warning', 'info'])->index()->comment('Status of this SSO step');

            // HTTP request details
            $table->string('method', 10)->nullable()->comment('HTTP method (GET, POST, etc.)');
            $table->text('url')->nullable()->comment('Full request URL');
            $table->string('ip_address', 45)->nullable()->comment('Client IP address (supports IPv4 and IPv6)');
            $table->text('user_agent')->nullable()->comment('Client user agent string');

            // JWT and authentication tokens
            $table->json('jwt_payload')->nullable()->comment('Decoded JWT payload as JSON');
            $table->text('jwt_token')->nullable()->comment('Raw JWT token string');
            $table->string('ott', 255)->nullable()->comment('One-time token for authentication');

            // Customer and agent tracking
            $table->unsignedBigInteger('customer_id')->nullable()->index()->comment('Foreign key to customers table');
            $table->string('agent_id', 100)->nullable()->index()->comment('Agent identifier from SSO provider');
            $table->string('service1_customer_id', 100)->nullable()->comment('Customer ID from Service1/external SSO provider');

            // Error tracking
            $table->text('error_message')->nullable()->comment('Error message if step failed');
            $table->longText('error_trace')->nullable()->comment('Full error stack trace for debugging');

            // Request and response data
            $table->json('request_data')->nullable()->comment('Full request data as JSON');
            $table->json('response_data')->nullable()->comment('Response data as JSON');

            // Performance tracking
            $table->integer('duration_ms')->nullable()->comment('Duration of this step in milliseconds');

            // Timestamps
            $table->timestamps();

            // Add foreign key constraint for customer_id
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('customers')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });

        // Add composite index for efficient querying of SSO flows by request_id and created_at
        Schema::table('sso_logs', function (Blueprint $table) {
            $table->index(['request_id', 'created_at'], 'idx_request_id_created_at');
            $table->index(['customer_id', 'created_at'], 'idx_customer_id_created_at');
            $table->index(['status', 'created_at'], 'idx_status_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sso_logs');
    }
};
