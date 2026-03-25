<?php

namespace App\Filament\Pages;

use App\Models\NotificationQueueLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class NotificationQueueMonitor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Notification Queues';

    protected static ?string $title = 'Notification Queue Monitor';

    protected static ?int $navigationSort = 101;

    public string $activeTab = 'all';

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public function getView(): string
    {
        return 'filament.pages.notification-queue-monitor';
    }

    public static function getNavigationBadge(): ?string
    {
        $running = NotificationQueueLog::where('status', 'running')->count();

        return $running > 0 ? (string) $running : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $failed = NotificationQueueLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return $failed > 0 ? 'danger' : 'success';
    }

    public function getViewData(): array
    {
        $query = NotificationQueueLog::query()->orderBy('started_at', 'desc');

        if ($this->activeTab !== 'all') {
            $query->where('queue_name', $this->activeTab);
        }

        return [
            'logs' => $query->paginate(25),
            'stats' => $this->getStats(),
        ];
    }

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        return [
            'gtm' => [
                'last_run' => NotificationQueueLog::forQueue('gtm-notifications')
                    ->latest('started_at')->first(),
                'today_runs' => NotificationQueueLog::forQueue('gtm-notifications')
                    ->where('started_at', '>=', $today)->count(),
                'today_sent' => NotificationQueueLog::forQueue('gtm-notifications')
                    ->where('started_at', '>=', $today)->sum('notifications_sent'),
                'today_errors' => NotificationQueueLog::forQueue('gtm-notifications')
                    ->where('started_at', '>=', $today)->sum('errors'),
                'interval' => config('notifications.gtm_interval', 5),
            ],
            'travel_alert' => [
                'last_run' => NotificationQueueLog::forQueue('travel-alert-notifications')
                    ->latest('started_at')->first(),
                'today_runs' => NotificationQueueLog::forQueue('travel-alert-notifications')
                    ->where('started_at', '>=', $today)->count(),
                'today_sent' => NotificationQueueLog::forQueue('travel-alert-notifications')
                    ->where('started_at', '>=', $today)->sum('notifications_sent'),
                'today_errors' => NotificationQueueLog::forQueue('travel-alert-notifications')
                    ->where('started_at', '>=', $today)->sum('errors'),
                'interval' => config('notifications.travel_alert_interval', 5),
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runGtm')
                ->label('GTM jetzt ausführen')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('notifications:process-gtm');

                    Notification::make()
                        ->title('GTM-Queue wurde ausgeführt')
                        ->body(Artisan::output())
                        ->success()
                        ->send();
                }),

            Action::make('runTravelAlert')
                ->label('Travel Alert jetzt ausführen')
                ->icon('heroicon-o-play')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('notifications:process-travel-alert');

                    Notification::make()
                        ->title('Travel-Alert-Queue wurde ausgeführt')
                        ->body(Artisan::output())
                        ->success()
                        ->send();
                }),

            Action::make('refresh')
                ->label('Aktualisieren')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => null),
        ];
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
}
