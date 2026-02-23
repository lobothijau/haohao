<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class RevenueByPlanWidget extends ChartWidget
{
    protected ?string $heading = 'Revenue per Paket';

    protected static ?int $sort = 9;

    protected function getData(): array
    {
        $data = app(AnalyticsService::class)->revenueByPlan();

        $colors = [
            '#3b82f6',
            '#10b981',
            '#f97316',
            '#8b5cf6',
            '#ef4444',
        ];

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('revenue')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                ],
            ],
            'labels' => $data->pluck('plan')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
