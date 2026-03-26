<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backup existing data (table may not exist on fresh deployments)
        $existingData = Schema::hasTable('notification_logs')
            ? DB::table('notification_logs')->get()
            : collect();

        Schema::dropIfExists('notification_logs');

        // Build partition clauses: monthly from 2026-01 to 2060-12
        $partitions = [];
        for ($year = 2026; $year <= 2060; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $nextMonth = $month === 12 ? 1 : $month + 1;
                $nextYear = $month === 12 ? $year + 1 : $year;
                $partName = sprintf('p%d_%02d', $year, $month);
                $boundary = sprintf('%d-%02d-01', $nextYear, $nextMonth);
                $partitions[] = "PARTITION {$partName} VALUES LESS THAN ('{$boundary}')";
            }
        }
        $partitions[] = 'PARTITION p_future VALUES LESS THAN MAXVALUE';

        $partitionSql = implode(",\n            ", $partitions);

        // Recreate with DATETIME columns and partitioning
        // Note: Foreign keys not supported on partitioned tables in MySQL,
        // replaced with regular indexes for referential lookups.
        DB::statement("
            CREATE TABLE notification_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                notification_rule_id BIGINT UNSIGNED NULL,
                customer_id BIGINT UNSIGNED NOT NULL,
                event_id BIGINT UNSIGNED NULL,
                event_type VARCHAR(255) NULL,
                recipient_email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                template_name VARCHAR(255) NULL,
                rule_name VARCHAR(255) NULL,
                is_test TINYINT(1) NOT NULL DEFAULT 0,
                status ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
                error_message TEXT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id, created_at),
                INDEX idx_notification_rule_id (notification_rule_id),
                INDEX idx_customer_id (customer_id),
                INDEX idx_event (event_id, event_type),
                INDEX idx_status (status),
                INDEX idx_customer_created (customer_id, created_at),
                UNIQUE INDEX idx_rule_event_unique (notification_rule_id, event_id, event_type, created_at)
            )
            PARTITION BY RANGE COLUMNS(created_at) (
                {$partitionSql}
            )
        ");

        // Restore existing data
        if ($existingData->isNotEmpty()) {
            foreach ($existingData->chunk(500) as $chunk) {
                DB::table('notification_logs')->insert(
                    $chunk->map(fn ($row) => (array) $row)->toArray()
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');

        Schema::create('notification_logs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('notification_rule_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('event_id')->nullable();
            $table->string('event_type')->nullable();
            $table->string('recipient_email');
            $table->string('subject');
            $table->string('template_name')->nullable();
            $table->string('rule_name')->nullable();
            $table->boolean('is_test')->default(false);
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['notification_rule_id', 'event_id', 'event_type'], 'notification_logs_rule_event_unique');
            $table->index('customer_id');
            $table->index(['event_id', 'event_type']);

            $table->foreign('notification_rule_id')->references('id')->on('notification_rules')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }
};
