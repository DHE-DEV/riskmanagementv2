<?php

namespace App\Filament\Widgets;

use App\Models\CustomEvent;
use App\Models\EventClick;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class CustomEventStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record || !($this->record instanceof CustomEvent)) {
            return [];
        }

        $eventId = $this->record->id;

        // Calculate statistics for this specific event
        $totalClicks = EventClick::where('custom_event_id', $eventId)->count();
        $todayClicks = EventClick::where('custom_event_id', $eventId)
            ->whereDate('clicked_at', today())
            ->count();
        $weekClicks = EventClick::where('custom_event_id', $eventId)
            ->whereBetween('clicked_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count();
        $monthClicks = EventClick::where('custom_event_id', $eventId)
            ->whereMonth('clicked_at', now()->month)
            ->whereYear('clicked_at', now()->year)
            ->count();

        // Calculate trends
        $yesterdayClicks = EventClick::where('custom_event_id', $eventId)
            ->whereDate('clicked_at', today()->subDay())
            ->count();
        $lastWeekClicks = EventClick::where('custom_event_id', $eventId)
            ->whereBetween('clicked_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ])->count();
        $lastMonthClicks = EventClick::where('custom_event_id', $eventId)
            ->whereMonth('clicked_at', now()->subMonth()->month)
            ->whereYear('clicked_at', now()->subMonth()->year)
            ->count();

        $dailyTrend = $yesterdayClicks > 0
            ? round((($todayClicks - $yesterdayClicks) / $yesterdayClicks) * 100, 1)
            : ($todayClicks > 0 ? 100 : 0);

        $weeklyTrend = $lastWeekClicks > 0
            ? round((($weekClicks - $lastWeekClicks) / $lastWeekClicks) * 100, 1)
            : ($weekClicks > 0 ? 100 : 0);

        $monthlyTrend = $lastMonthClicks > 0
            ? round((($monthClicks - $lastMonthClicks) / $lastMonthClicks) * 100, 1)
            : ($monthClicks > 0 ? 100 : 0);

        // Click distribution by type
        $listClicks = EventClick::where('custom_event_id', $eventId)
            ->where('click_type', 'list')
            ->count();
        $mapClicks = EventClick::where('custom_event_id', $eventId)
            ->where('click_type', 'map_marker')
            ->count();
        $detailsClicks = EventClick::where('custom_event_id', $eventId)
            ->where('click_type', 'details_button')
            ->count();

        // Last click info
        $lastClick = EventClick::where('custom_event_id', $eventId)
            ->orderBy('clicked_at', 'desc')
            ->first();

        return [
            Stat::make('Gesamt-Interaktionen', number_format($totalClicks))
                ->description($monthlyTrend > 0 ? "{$monthlyTrend}% mehr als letzten Monat" : ($monthlyTrend < 0 ? abs($monthlyTrend) . "% weniger als letzten Monat" : "Keine Änderung"))
                ->descriptionIcon($monthlyTrend > 0 ? 'heroicon-m-arrow-trending-up' : ($monthlyTrend < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                ->color($monthlyTrend > 0 ? 'success' : ($monthlyTrend < 0 ? 'danger' : 'gray'))
                ->chart($this->getWeeklyChart($eventId)),

            Stat::make('Heute', number_format($todayClicks))
                ->description($dailyTrend > 0 ? "+{$dailyTrend}% vs. gestern" : ($dailyTrend < 0 ? "{$dailyTrend}% vs. gestern" : "Wie gestern"))
                ->descriptionIcon($dailyTrend > 0 ? 'heroicon-m-arrow-trending-up' : ($dailyTrend < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                ->color($dailyTrend > 0 ? 'success' : ($dailyTrend < 0 ? 'warning' : 'gray')),

            Stat::make('Diese Woche', number_format($weekClicks))
                ->description("Ø " . round($weekClicks / 7, 1) . " pro Tag")
                ->descriptionIcon($weeklyTrend > 0 ? 'heroicon-m-arrow-up' : ($weeklyTrend < 0 ? 'heroicon-m-arrow-down' : 'heroicon-m-minus'))
                ->color('info')
                ->chart($this->getDailyChartForWeek($eventId)),

            Stat::make('Diesen Monat', number_format($monthClicks))
                ->description("Ø " . round($monthClicks / now()->day, 1) . " pro Tag")
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Event-Liste', number_format($listClicks))
                ->description($totalClicks > 0 ? round(($listClicks / $totalClicks) * 100, 1) . "% aller Klicks" : "Keine Klicks")
                ->descriptionIcon('heroicon-m-list-bullet')
                ->color('gray'),

            Stat::make('Karten-Marker', number_format($mapClicks))
                ->description($totalClicks > 0 ? round(($mapClicks / $totalClicks) * 100, 1) . "% aller Klicks" : "Keine Klicks")
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success'),

            Stat::make('Details-Button', number_format($detailsClicks))
                ->description($totalClicks > 0 ? round(($detailsClicks / $totalClicks) * 100, 1) . "% aller Klicks" : "Keine Klicks")
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color('warning'),

            Stat::make('Letzter Klick', $lastClick ? $lastClick->clicked_at->diffForHumans() : 'Noch keine Klicks')
                ->description($lastClick ? "Typ: " . $this->getClickTypeLabel($lastClick->click_type) : "Warte auf ersten Klick")
                ->descriptionIcon('heroicon-m-clock')
                ->color($lastClick && $lastClick->clicked_at->isToday() ? 'success' : 'gray'),
        ];
    }

    protected function getWeeklyChart(int $eventId): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = EventClick::where('custom_event_id', $eventId)
                ->whereDate('clicked_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    protected function getDailyChartForWeek(int $eventId): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->startOfWeek()->addDays($i);
            $count = EventClick::where('custom_event_id', $eventId)
                ->whereDate('clicked_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    protected function getClickTypeLabel(string $type): string
    {
        return match($type) {
            'list' => 'Event-Liste',
            'map_marker' => 'Karten-Symbol',
            'details_button' => 'Details-Button',
            default => $type,
        };
    }
}