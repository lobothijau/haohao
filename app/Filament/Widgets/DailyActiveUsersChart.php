<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class DailyActiveUsersChart extends ChartWidget
{
    protected ?string $heading = 'Daily Active Users (30 Days)';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = app(AnalyticsService::class)->dailyActiveUsers(30);

        return [
            'datasets' => [
                [
                    'label' => 'DAU',
                    'data' => $data->pluck('count')->toArray(),
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
