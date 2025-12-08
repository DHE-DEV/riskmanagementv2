<?php

namespace App\Filament\Widgets;

use App\Models\TravelDetail\TdTrip;
use App\Models\TravelDetail\TdImportLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TravelDetailStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return config('travel_detail.enabled', false);
    }

    protected function getStats(): array
    {
        // Active trips (currently traveling or upcoming)
        $activeTrips = TdTrip::where('status', 'active')
            ->where('computed_end_at', '>=', now())
            ->count();

        // Currently traveling (in transit right now)
        $currentlyTraveling = TdTrip::currentlyTraveling()->count();

        // Upcoming trips (starting within next 7 days)
        $upcomingTrips = TdTrip::upcoming()
            ->where('computed_start_at', '<=', now()->addDays(7))
            ->count();

        // Completed this month
        $completedThisMonth = TdTrip::where('status', 'completed')
            ->whereMonth('computed_end_at', now()->month)
            ->whereYear('computed_end_at', now()->year)
            ->count();

        // Imports today
        $importsToday = TdImportLog::whereDate('created_at', today())->count();
        $successfulImportsToday = TdImportLog::whereDate('created_at', today())
            ->where('status', 'success')
            ->count();
        $failedImportsToday = TdImportLog::whereDate('created_at', today())
            ->where('status', 'failed')
            ->count();

        // Weekly trends
        $importsThisWeek = TdImportLog::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();

        $importsLastWeek = TdImportLog::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek()
        ])->count();

        $weeklyTrend = $importsLastWeek > 0
            ? round((($importsThisWeek - $importsLastWeek) / $importsLastWeek) * 100, 1)
            : ($importsThisWeek > 0 ? 100 : 0);

        // Archived trips
        $archivedTrips = TdTrip::where('is_archived', true)->count();

        // Total trips
        $totalTrips = TdTrip::count();

        return [
            Stat::make('Aktuell unterwegs', number_format($currentlyTraveling))
                ->description("{$activeTrips} aktive Reisen gesamt")
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success')
                ->chart($this->getWeeklyActiveChart()),

            Stat::make('Bevorstehend (7 Tage)', number_format($upcomingTrips))
                ->description('Reisen starten bald')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Imports heute', number_format($importsToday))
                ->description($failedImportsToday > 0
                    ? "{$successfulImportsToday} erfolgreich, {$failedImportsToday} fehlgeschlagen"
                    : "{$successfulImportsToday} erfolgreich")
                ->descriptionIcon($failedImportsToday > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($failedImportsToday > 0 ? 'warning' : 'success'),

            Stat::make('Diese Woche', number_format($importsThisWeek))
                ->description($weeklyTrend > 0
                    ? "+{$weeklyTrend}% vs. letzte Woche"
                    : ($weeklyTrend < 0 ? "{$weeklyTrend}% vs. letzte Woche" : "Wie letzte Woche"))
                ->descriptionIcon($weeklyTrend > 0 ? 'heroicon-m-arrow-trending-up' : ($weeklyTrend < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                ->color($weeklyTrend > 0 ? 'success' : ($weeklyTrend < 0 ? 'warning' : 'gray'))
                ->chart($this->getDailyImportsChart()),

            Stat::make('Abgeschlossen (Monat)', number_format($completedThisMonth))
                ->description('Reisen beendet')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('gray'),

            Stat::make('Gesamt / Archiviert', "{$totalTrips} / {$archivedTrips}")
                ->description($archivedTrips > 0
                    ? round(($archivedTrips / max($totalTrips, 1)) * 100, 1) . "% archiviert"
                    : "Keine archivierten Reisen")
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('gray'),
        ];
    }

    protected function getWeeklyActiveChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = TdTrip::where('status', 'active')
                ->where('computed_start_at', '<=', $date)
                ->where('computed_end_at', '>=', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    protected function getDailyImportsChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = TdImportLog::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }
}
