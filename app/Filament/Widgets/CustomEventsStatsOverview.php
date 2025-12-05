<?php

namespace App\Filament\Widgets;

use App\Models\CustomEvent;
use App\Models\EventClick;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomEventsStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Calculate statistics
        $totalClicks = EventClick::count();
        $todayClicks = EventClick::whereDate('clicked_at', today())->count();
        $weekClicks = EventClick::whereBetween('clicked_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();

        // Calculate trends
        $yesterdayClicks = EventClick::whereDate('clicked_at', today()->subDay())->count();
        $lastWeekClicks = EventClick::whereBetween('clicked_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek()
        ])->count();

        $dailyTrend = $yesterdayClicks > 0
            ? round((($todayClicks - $yesterdayClicks) / $yesterdayClicks) * 100, 1)
            : ($todayClicks > 0 ? 100 : 0);

        $weeklyTrend = $lastWeekClicks > 0
            ? round((($weekClicks - $lastWeekClicks) / $lastWeekClicks) * 100, 1)
            : ($weekClicks > 0 ? 100 : 0);

        // Most clicked event
        $topEvent = CustomEvent::withCount('clicks')
            ->orderBy('clicks_count', 'desc')
            ->first();

        // Click distribution
        $listClicks = EventClick::where('click_type', 'list')->count();
        $mapClicks = EventClick::where('click_type', 'map_marker')->count();
        $detailsClicks = EventClick::where('click_type', 'details_button')->count();

        // Direct link statistics
        $directLinkTotal = EventClick::where('click_type', 'direct_link')->count();
        $directLinkToday = EventClick::where('click_type', 'direct_link')
            ->whereDate('clicked_at', today())->count();
        $directLinkYesterday = EventClick::where('click_type', 'direct_link')
            ->whereDate('clicked_at', today()->subDay())->count();
        $directLinkWeek = EventClick::where('click_type', 'direct_link')
            ->whereBetween('clicked_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        return [
            Stat::make('Gesamt-Interaktionen', number_format($totalClicks))
                ->description($weeklyTrend > 0 ? "{$weeklyTrend}% mehr als letzte Woche" : ($weeklyTrend < 0 ? abs($weeklyTrend) . "% weniger als letzte Woche" : "Keine Änderung"))
                ->descriptionIcon($weeklyTrend > 0 ? 'heroicon-m-arrow-trending-up' : ($weeklyTrend < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                ->color($weeklyTrend > 0 ? 'success' : ($weeklyTrend < 0 ? 'danger' : 'gray'))
                ->chart($this->getWeeklyChart()),

            Stat::make('Heute', number_format($todayClicks))
                ->description("Gestern: " . number_format($yesterdayClicks))
                ->descriptionIcon($dailyTrend > 0 ? 'heroicon-m-arrow-trending-up' : ($dailyTrend < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                ->color($dailyTrend > 0 ? 'success' : ($dailyTrend < 0 ? 'warning' : 'gray')),

            Stat::make('Diese Woche', number_format($weekClicks))
                ->description("Ø " . round($weekClicks / 7, 1) . " pro Tag")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Top Event', $topEvent && $topEvent->clicks_count > 0 ? "{$topEvent->clicks_count} Klicks" : 'Keine Daten')
                ->description($topEvent ? substr($topEvent->title, 0, 40) : 'Noch kein Top Event')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('Direktlinks', number_format($directLinkTotal))
                ->description("Heute: {$directLinkToday} | Diese Woche: {$directLinkWeek}")
                ->descriptionIcon('heroicon-m-link')
                ->color('primary'),
        ];
    }

    protected function getWeeklyChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = EventClick::whereDate('clicked_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }
}