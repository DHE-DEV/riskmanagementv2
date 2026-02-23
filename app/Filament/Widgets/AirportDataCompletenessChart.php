<?php

namespace App\Filament\Widgets;

use App\Models\Airport;
use Filament\Widgets\ChartWidget;

class AirportDataCompletenessChart extends ChartWidget
{
    protected ?string $heading = 'Datenvollständigkeit der Flughäfen';

    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $total = Airport::count();

        if ($total === 0) {
            return [
                'datasets' => [['label' => '%', 'data' => []]],
                'labels' => [],
            ];
        }

        $fields = [
            'Website' => Airport::whereNotNull('website')->where('website', '!=', '')->count(),
            'Lounges' => Airport::whereNotNull('lounges')->count(),
            'Hotels' => Airport::whereNotNull('nearby_hotels')->count(),
            'Mobilität' => Airport::whereNotNull('mobility_options')->count(),
            'Security-URL' => Airport::whereNotNull('security_timeslot_url')->where('security_timeslot_url', '!=', '')->count(),
            'Koordinaten' => Airport::whereNotNull('lat')->whereNotNull('lng')->count(),
            'Zeitzone' => Airport::whereNotNull('timezone')->where('timezone', '!=', '')->count(),
        ];

        $labels = array_keys($fields);
        $data = array_map(fn ($count) => round(($count / $total) * 100, 1), array_values($fields));

        // Color gradient from red to green based on percentage
        $colors = array_map(function ($percent) {
            if ($percent >= 75) {
                return 'rgba(16, 185, 129, 0.7)'; // green
            } elseif ($percent >= 50) {
                return 'rgba(245, 158, 11, 0.7)'; // amber
            } else {
                return 'rgba(239, 68, 68, 0.7)'; // red
            }
        }, $data);

        return [
            'datasets' => [
                [
                    'label' => 'Vollständigkeit (%)',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'callback' => '{{value}}%',
                    ],
                ],
            ],
        ];
    }
}
