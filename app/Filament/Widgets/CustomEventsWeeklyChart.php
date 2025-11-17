<?php

namespace App\Filament\Widgets;

use App\Models\CustomEvent;
use App\Models\EventClick;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CustomEventsWeeklyChart extends ChartWidget
{
    protected ?string $heading = 'Ereignis-Interaktionen - Letzte 4 Wochen';

    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Letzte 4 Wochen (KW)
        $weeks = [];
        $clicksData = [];
        $eventsData = [];

        for ($i = 3; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            // KW Label (z.B. "KW 45")
            $weeks[] = 'KW ' . $weekStart->isoWeek();

            // Klicks in dieser Woche
            $clicks = EventClick::whereBetween('clicked_at', [
                $weekStart,
                $weekEnd
            ])->count();
            $clicksData[] = $clicks;

            // Neue Events in dieser Woche
            $events = CustomEvent::whereBetween('created_at', [
                $weekStart,
                $weekEnd
            ])->count();
            $eventsData[] = $events;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Event-Klicks',
                    'data' => $clicksData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)', // Blue
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Neue Events',
                    'data' => $eventsData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)', // Green
                    'borderColor' => 'rgb(16, 185, 129)',
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
