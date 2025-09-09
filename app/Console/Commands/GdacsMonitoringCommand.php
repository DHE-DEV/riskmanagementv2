<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class GdacsMonitoringCommand extends Command
{
    protected $signature = 'gdacs:monitoring {--days=7 : Number of days to analyze} {--json : Output as JSON}';
    protected $description = 'Show GDACS synchronization monitoring statistics';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $outputJson = $this->option('json');

        try {
            $stats = $this->analyzeGdacsLogs($days);
            
            if ($outputJson) {
                $this->line(json_encode($stats, JSON_PRETTY_PRINT));
                return self::SUCCESS;
            }

            $this->displayStats($stats, $days);
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to analyze GDACS logs: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function analyzeGdacsLogs(int $days): array
    {
        $logPath = storage_path('logs');
        $stats = [
            'period_days' => $days,
            'total_executions' => 0,
            'successful_executions' => 0,
            'failed_executions' => 0,
            'average_execution_time_ms' => 0,
            'total_events_fetched' => 0,
            'total_events_saved' => 0,
            'cache_hits' => 0,
            'last_execution' => null,
            'last_successful_execution' => null,
            'errors' => [],
            'daily_stats' => []
        ];

        // Analysiere GDACS Monitoring Logs
        $monitoringFiles = File::glob($logPath . '/gdacs-monitoring-*.log');
        $syncFiles = File::glob($logPath . '/gdacs-sync-*.log');
        
        $cutoffDate = Carbon::now()->subDays($days);
        $executionTimes = [];
        $dailyStats = [];

        foreach (array_merge($monitoringFiles, $syncFiles) as $file) {
            if (!File::exists($file)) continue;

            if ($this->option('verbose')) {
                $this->line("Reading file: " . basename($file));
            }

            $content = File::get($file);
            $lines = explode("\n", $content);

            if ($this->option('verbose')) {
                $this->line("  Found " . count($lines) . " lines");
            }

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                if ($this->option('verbose')) {
                    $this->line("  Checking line: " . trim($line));
                }

                // Parse log line (Laravel JSON format)
                if (preg_match('/\[(\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}:\d{2}[^\]]*)\].*?({.+})/', $line, $matches)) {
                    // Debug output
                    if ($this->option('verbose')) {
                        $this->line("Processing line: " . trim($line));
                    }
                    $timestamp = $matches[1];
                    $logData = json_decode($matches[2], true);
                    
                    if (!$logData) continue;

                    $logTime = Carbon::parse($timestamp);
                    if ($logTime->lt($cutoffDate)) continue;

                    $dayKey = $logTime->format('Y-m-d');
                    if (!isset($dailyStats[$dayKey])) {
                        $dailyStats[$dayKey] = [
                            'executions' => 0,
                            'successes' => 0,
                            'failures' => 0,
                            'events_fetched' => 0,
                            'events_saved' => 0,
                            'cache_hits' => 0
                        ];
                    }

                    // Job Execution Analysis
                    if (isset($logData['success'])) {
                        $stats['total_executions']++;
                        $dailyStats[$dayKey]['executions']++;
                        
                        if ($logData['success']) {
                            $stats['successful_executions']++;
                            $dailyStats[$dayKey]['successes']++;
                            $stats['last_successful_execution'] = $timestamp;
                        } else {
                            $stats['failed_executions']++;
                            $dailyStats[$dayKey]['failures']++;
                            
                            if (isset($logData['error'])) {
                                $stats['errors'][] = [
                                    'timestamp' => $timestamp,
                                    'error' => $logData['error']
                                ];
                            }
                        }

                        if (isset($logData['execution_time_ms'])) {
                            $executionTimes[] = $logData['execution_time_ms'];
                        }

                        if (isset($logData['events_fetched'])) {
                            $stats['total_events_fetched'] += $logData['events_fetched'];
                            $dailyStats[$dayKey]['events_fetched'] += $logData['events_fetched'];
                        }

                        if (isset($logData['events_saved'])) {
                            $stats['total_events_saved'] += $logData['events_saved'];
                            $dailyStats[$dayKey]['events_saved'] += $logData['events_saved'];
                        }

                        $stats['last_execution'] = $timestamp;
                    }

                    // Cache Hit Analysis
                    if (strpos($line, 'loaded from cache') !== false) {
                        $stats['cache_hits']++;
                        $dailyStats[$dayKey]['cache_hits']++;
                    }
                }
            }
        }

        if (!empty($executionTimes)) {
            $stats['average_execution_time_ms'] = round(array_sum($executionTimes) / count($executionTimes), 2);
        }

        $stats['daily_stats'] = $dailyStats;
        $stats['success_rate'] = $stats['total_executions'] > 0 
            ? round(($stats['successful_executions'] / $stats['total_executions']) * 100, 2) 
            : 0;

        return $stats;
    }

    private function displayStats(array $stats, int $days): void
    {
        $this->info("📊 GDACS Synchronization Monitoring Report (Last {$days} days)");
        $this->line('');

        // Summary Table
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Executions', $stats['total_executions']],
                ['Successful', $stats['successful_executions'] . ' (' . $stats['success_rate'] . '%)'],
                ['Failed', $stats['failed_executions']],
                ['Cache Hits', $stats['cache_hits']],
                ['Avg Execution Time', $stats['average_execution_time_ms'] . 'ms'],
                ['Total Events Fetched', number_format($stats['total_events_fetched'])],
                ['Total Events Saved', number_format($stats['total_events_saved'])],
                ['Last Execution', $stats['last_execution'] ?? 'Never'],
                ['Last Successful', $stats['last_successful_execution'] ?? 'Never'],
            ]
        );

        // Recent Errors
        if (!empty($stats['errors'])) {
            $this->line('');
            $this->error('❌ Recent Errors:');
            $recentErrors = array_slice($stats['errors'], -5); // Last 5 errors
            foreach ($recentErrors as $error) {
                $this->line("  [{$error['timestamp']}] {$error['error']}");
            }
        }

        // Daily Breakdown
        if (!empty($stats['daily_stats'])) {
            $this->line('');
            $this->info('📅 Daily Breakdown:');
            
            $dailyData = [];
            foreach ($stats['daily_stats'] as $date => $daily) {
                $dailyData[] = [
                    $date,
                    $daily['executions'],
                    $daily['successes'],
                    $daily['failures'],
                    number_format($daily['events_fetched']),
                    number_format($daily['events_saved']),
                    $daily['cache_hits']
                ];
            }

            $this->table(
                ['Date', 'Exec', 'Success', 'Fail', 'Fetched', 'Saved', 'Cache'],
                $dailyData
            );
        }

        // Health Status
        $this->line('');
        if ($stats['success_rate'] >= 95) {
            $this->info('✅ GDACS synchronization is healthy');
        } elseif ($stats['success_rate'] >= 80) {
            $this->warn('⚠️  GDACS synchronization has some issues');
        } else {
            $this->error('🚨 GDACS synchronization requires attention');
        }
    }
}