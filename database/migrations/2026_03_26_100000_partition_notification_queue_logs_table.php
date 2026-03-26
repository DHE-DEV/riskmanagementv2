<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backup existing data
        $existingData = DB::table('notification_queue_logs')->get();

        Schema::dropIfExists('notification_queue_logs');

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
        // Catch-all for anything beyond 2060
        $partitions[] = 'PARTITION p_future VALUES LESS THAN MAXVALUE';

        $partitionSql = implode(",\n            ", $partitions);

        DB::statement("
            CREATE TABLE notification_queue_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                queue_name VARCHAR(50) NOT NULL,
                started_at TIMESTAMP NOT NULL,
                completed_at TIMESTAMP NULL,
                events_processed INT UNSIGNED NOT NULL DEFAULT 0,
                notifications_sent INT UNSIGNED NOT NULL DEFAULT 0,
                errors INT UNSIGNED NOT NULL DEFAULT 0,
                status ENUM('running', 'completed', 'failed') NOT NULL DEFAULT 'running',
                error_message TEXT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                PRIMARY KEY (id, started_at),
                INDEX idx_queue_name (queue_name),
                INDEX idx_status (status),
                INDEX idx_queue_started (queue_name, started_at)
            )
            PARTITION BY RANGE COLUMNS(started_at) (
                {$partitionSql}
            )
        ");

        // Restore existing data
        if ($existingData->isNotEmpty()) {
            foreach ($existingData->chunk(500) as $chunk) {
                DB::table('notification_queue_logs')->insert(
                    $chunk->map(fn ($row) => (array) $row)->toArray()
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_queue_logs');

        Schema::create('notification_queue_logs', function ($table) {
            $table->id();
            $table->string('queue_name', 50);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('events_processed')->default(0);
            $table->unsignedInteger('notifications_sent')->default(0);
            $table->unsignedInteger('errors')->default(0);
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index('queue_name');
            $table->index('started_at');
        });
    }
};
