<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class ReviewActivityChart extends ChartWidget
{
    protected ?string $heading = 'Review Activity (30 Days)';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $data = app(AnalyticsService::class)->dailyReviewActivity(30);

        return [
            'datasets' => [
                [
                    'label' => 'Reviews',
                    'data' => $data->pluck('reviews')->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'type' => 'bar',
                ],
                [
                    'label' => 'Accuracy %',
                    'data' => $data->pluck('accuracy')->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'type' => 'line',
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
