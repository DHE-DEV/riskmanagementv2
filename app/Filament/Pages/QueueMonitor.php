<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class QueueMonitor extends Page
{

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Queue';

    protected static ?string $title = 'Queue Monitor';

    protected static ?int $navigationSort = 100;

    public string $activeTab = 'pending';

    public array $selectedPendingJobs = [];

    public array $selectedFailedJobs = [];

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public function getView(): string
    {
        return 'filament.pages.queue-monitor';
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();

        if ($failed > 0) {
            return "{$pending} / {$failed}";
        }

        return $pending ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $failed = DB::table('failed_jobs')->count();

        return $failed > 0 ? 'danger' : 'success';
    }

    public function getViewData(): array
    {
        return [
            'pendingJobs' => $this->getPendingJobs(),
            'failedJobs' => $this->getFailedJobs(),
            'stats' => $this->getStats(),
            'workerStatus' => $this->getWorkerStatus(),
        ];
    }

    protected function getPendingJobs(): array
    {
        return DB::table('jobs')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);

                return [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'job_name' => $this->extractJobName($payload),
                    'attempts' => $job->attempts,
                    'created_at' => \Carbon\Carbon::createFromTimestamp($job->created_at)->format('d.m.Y H:i:s'),
                    'available_at' => \Carbon\Carbon::createFromTimestamp($job->available_at)->format('d.m.Y H:i:s'),
                    'reserved_at' => $job->reserved_at ? \Carbon\Carbon::createFromTimestamp($job->reserved_at)->format('d.m.Y H:i:s') : null,
                ];
            })
            ->toArray();
    }

    protected function getFailedJobs(): array
    {
        return DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);

                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'queue' => $job->queue,
                    'job_name' => $this->extractJobName($payload),
                    'failed_at' => \Carbon\Carbon::parse($job->failed_at)->format('d.m.Y H:i:s'),
                    'exception' => \Illuminate\Support\Str::limit($job->exception, 200),
                ];
            })
            ->toArray();
    }

    protected function getStats(): array
    {
        return [
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
            'processing' => DB::table('jobs')->whereNotNull('reserved_at')->count(),
            'queues' => DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->pluck('count', 'queue')
                ->toArray(),
        ];
    }

    public function getWorkerStatus(): array
    {
        $processing = DB::table('jobs')->whereNotNull('reserved_at')->count();
        $pending = DB::table('jobs')->count();

        // Check if worker process is running
        $isRunning = $this->checkWorkerProcess();

        return [
            'is_running' => $isRunning,
            'processing' => $processing,
            'pending' => $pending,
        ];
    }

    protected function checkWorkerProcess(): bool
    {
        // Check for queue:work processes
        $output = shell_exec('pgrep -f "queue:work" 2>/dev/null');

        return !empty(trim($output ?? ''));
    }

    public function processNextJob(): void
    {
        $pendingCount = DB::table('jobs')->count();

        if ($pendingCount === 0) {
            Notification::make()
                ->title('Keine Jobs in der Queue')
                ->warning()
                ->send();

            return;
        }

        // Process one job
        Artisan::call('queue:work', [
            '--once' => true,
            '--tries' => 3,
        ]);

        Notification::make()
            ->title('Job verarbeitet')
            ->success()
            ->send();
    }

    public function processAllJobs(): void
    {
        $pendingCount = DB::table('jobs')->count();

        if ($pendingCount === 0) {
            Notification::make()
                ->title('Keine Jobs in der Queue')
                ->warning()
                ->send();

            return;
        }

        // Process all jobs (with stop-when-empty)
        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 3,
        ]);

        Notification::make()
            ->title('Alle Jobs wurden verarbeitet')
            ->success()
            ->send();
    }

    protected function extractJobName(array $payload): string
    {
        $displayName = $payload['displayName'] ?? $payload['job'] ?? 'Unknown';

        // Extract just the class name without namespace
        if (str_contains($displayName, '\\')) {
            $parts = explode('\\', $displayName);

            return end($parts);
        }

        return $displayName;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retryAll')
                ->label('Alle fehlgeschlagenen erneut versuchen')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('queue:retry', ['id' => 'all']);

                    Notification::make()
                        ->title('Alle fehlgeschlagenen Jobs werden erneut versucht')
                        ->success()
                        ->send();
                })
                ->visible(fn () => DB::table('failed_jobs')->count() > 0),

            Action::make('flushFailed')
                ->label('Fehlgeschlagene löschen')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Alle fehlgeschlagenen Jobs löschen?')
                ->modalDescription('Diese Aktion kann nicht rückgängig gemacht werden.')
                ->action(function () {
                    Artisan::call('queue:flush');

                    Notification::make()
                        ->title('Alle fehlgeschlagenen Jobs wurden gelöscht')
                        ->success()
                        ->send();
                })
                ->visible(fn () => DB::table('failed_jobs')->count() > 0),

            Action::make('refresh')
                ->label('Aktualisieren')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => null),
        ];
    }

    public function retryJob(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        Notification::make()
            ->title('Job wird erneut versucht')
            ->success()
            ->send();
    }

    public function deleteFailedJob(string $uuid): void
    {
        Artisan::call('queue:forget', ['id' => $uuid]);

        Notification::make()
            ->title('Job wurde gelöscht')
            ->success()
            ->send();
    }

    public function deletePendingJob(int $id): void
    {
        DB::table('jobs')->where('id', $id)->delete();

        Notification::make()
            ->title('Job wurde aus der Queue entfernt')
            ->success()
            ->send();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->selectedPendingJobs = [];
        $this->selectedFailedJobs = [];
    }

    public function toggleSelectAllPending(): void
    {
        $allIds = collect($this->getPendingJobs())->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        if (count($this->selectedPendingJobs) === count($allIds)) {
            $this->selectedPendingJobs = [];
        } else {
            $this->selectedPendingJobs = $allIds;
        }
    }

    public function toggleSelectAllFailed(): void
    {
        $allUuids = collect($this->getFailedJobs())->pluck('uuid')->toArray();

        if (count($this->selectedFailedJobs) === count($allUuids)) {
            $this->selectedFailedJobs = [];
        } else {
            $this->selectedFailedJobs = $allUuids;
        }
    }

    public function deleteSelectedPendingJobs(): void
    {
        if (empty($this->selectedPendingJobs)) {
            return;
        }

        $count = count($this->selectedPendingJobs);
        DB::table('jobs')->whereIn('id', $this->selectedPendingJobs)->delete();
        $this->selectedPendingJobs = [];

        Notification::make()
            ->title("{$count} Jobs aus der Queue entfernt")
            ->success()
            ->send();
    }

    public function deleteSelectedFailedJobs(): void
    {
        if (empty($this->selectedFailedJobs)) {
            return;
        }

        $count = count($this->selectedFailedJobs);

        foreach ($this->selectedFailedJobs as $uuid) {
            Artisan::call('queue:forget', ['id' => $uuid]);
        }

        $this->selectedFailedJobs = [];

        Notification::make()
            ->title("{$count} fehlgeschlagene Jobs gelöscht")
            ->success()
            ->send();
    }

    public function retrySelectedFailedJobs(): void
    {
        if (empty($this->selectedFailedJobs)) {
            return;
        }

        $count = count($this->selectedFailedJobs);
        Artisan::call('queue:retry', ['id' => $this->selectedFailedJobs]);
        $this->selectedFailedJobs = [];

        Notification::make()
            ->title("{$count} Jobs werden erneut versucht")
            ->success()
            ->send();
    }
}
