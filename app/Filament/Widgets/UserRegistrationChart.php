<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class UserRegistrationChart extends ChartWidget
{
    protected ?string $heading = 'New Users (30 Days)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = app(AnalyticsService::class)->userRegistrations(30);

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $data->pluck('count')->toArray(),
                    'borderColor' => '#f97316',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.1)',
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
