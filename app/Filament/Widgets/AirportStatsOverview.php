<?php

namespace App\Filament\Widgets;

use App\Models\Airport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AirportStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $total = Airport::count();
        $deleted = Airport::onlyTrashed()->count();
        $inactive = Airport::where('is_active', false)->count();

        // This month created
        $thisMonth = Airport::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lastMonth = Airport::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $monthlyTrend = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1)
            : ($thisMonth > 0 ? 100 : 0);

        // Data quality: airports with website
        $withWebsite = Airport::whereNotNull('website')->where('website', '!=', '')->count();
        $qualityPercent = $total > 0 ? round(($withWebsite / $total) * 100) : 0;

        // Airlines linked
        $airlineLinks = DB::table('airline_airport')
            ->whereIn('airport_id', Airport::select('id'))
            ->count();
        $airportsWithAirlines = DB::table('airline_airport')
            ->whereIn('airport_id', Airport::select('id'))
            ->distinct('airport_id')
            ->count('airport_id');

        return [
            Stat::make('Gesamt', number_format($total))
                ->description("davon {$deleted} gelöscht, {$inactive} inaktiv")
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary')
                ->chart($this->getDailyCreationsChart()),

            Stat::make('Diesen Monat angelegt', number_format($thisMonth))
                ->description($monthlyTrend > 0
                    ? "+{$monthlyTrend}% vs. letzter Monat"
                    : ($monthlyTrend < 0 ? "{$monthlyTrend}% vs. letzter Monat" : "Wie letzter Monat"))
                ->descriptionIcon($monthlyTrend > 0 ? 'heroicon-m-arrow-trending-up' : ($monthlyTrend < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                ->color($monthlyTrend > 0 ? 'success' : ($monthlyTrend < 0 ? 'warning' : 'gray'))
                ->chart($this->getDailyCreationsChart()),

            Stat::make('Datenqualität', "{$qualityPercent}%")
                ->description("{$withWebsite}/{$total} mit Website")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($qualityPercent >= 75 ? 'success' : ($qualityPercent >= 50 ? 'warning' : 'danger')),

            Stat::make('Airlines verknüpft', number_format($airlineLinks))
                ->description("an {$airportsWithAirlines} Flughäfen")
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('info'),
        ];
    }

    protected function getDailyCreationsChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Airport::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }
}
