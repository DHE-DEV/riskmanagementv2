<?php

namespace App\Filament\Widgets;

use App\Models\EventClick;
use Filament\Widgets\ChartWidget;

class CustomEventsClickTypeChart extends ChartWidget
{
    protected ?string $heading = 'Klick-Typen Verteilung - Letzte 4 Wochen';

    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        // Letzte 4 Wochen (KW)
        $weeks = [];
        $listClicksData = [];
        $mapClicksData = [];
        $detailsClicksData = [];

        for ($i = 3; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            // KW Label
            $weeks[] = 'KW ' . $weekStart->isoWeek();

            // Liste-Klicks
            $listClicks = EventClick::where('click_type', 'list')
                ->whereBetween('clicked_at', [$weekStart, $weekEnd])
                ->count();
            $listClicksData[] = $listClicks;

            // Karten-Marker Klicks
            $mapClicks = EventClick::where('click_type', 'map_marker')
                ->whereBetween('clicked_at', [$weekStart, $weekEnd])
                ->count();
            $mapClicksData[] = $mapClicks;

            // Details-Button Klicks
            $detailsClicks = EventClick::where('click_type', 'details_button')
                ->whereBetween('clicked_at', [$weekStart, $weekEnd])
                ->count();
            $detailsClicksData[] = $detailsClicks;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Listen-Ansicht',
                    'data' => $listClicksData,
                    'backgroundColor' => 'rgba(139, 92, 246, 0.5)', // Purple
                    'borderColor' => 'rgb(139, 92, 246)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Karten-Marker',
                    'data' => $mapClicksData,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.5)', // Amber
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Details-Button',
                    'data' => $detailsClicksData,
                    'backgroundColor' => 'rgba(236, 72, 153, 0.5)', // Pink
                    'borderColor' => 'rgb(236, 72, 153)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $weeks,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
