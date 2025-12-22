<?php

namespace App\Filament\Resources\PluginClientResource\Widgets;

use App\Models\PluginClient;
use App\Models\PluginUsageEvent;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PluginStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalClients = PluginClient::count();
        $activeClients = PluginClient::where('status', 'active')->count();
        $totalEvents = PluginUsageEvent::count();
        $eventsToday = PluginUsageEvent::whereDate('created_at', today())->count();
        $eventsThisMonth = PluginUsageEvent::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $newClientsThisMonth = PluginClient::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            Stat::make('Plugin-Kunden', $totalClients)
                ->description($activeClients . ' aktiv')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('primary')
                ->icon('heroicon-o-users'),

            Stat::make('Neue Kunden (Monat)', $newClientsThisMonth)
                ->description('Diesen Monat registriert')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->icon('heroicon-o-user-plus'),

            Stat::make('Aufrufe heute', number_format($eventsToday))
                ->description(number_format($eventsThisMonth) . ' diesen Monat')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('info')
                ->icon('heroicon-o-cursor-arrow-rays'),

            Stat::make('Aufrufe gesamt', number_format($totalEvents))
                ->description('Alle Plugin-Aufrufe')
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color('warning')
                ->icon('heroicon-o-signal'),
        ];
    }
}
