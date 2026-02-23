<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class MonthlyRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue Bulanan';

    protected static ?int $sort = 8;

    protected function getData(): array
    {
        $data = app(AnalyticsService::class)->monthlyRevenue(12);

        $months = $data->pluck('month')->unique()->values();
        $plans = $data->pluck('plan')->unique()->values();

        $colors = [
            '#3b82f6',
            '#10b981',
            '#f97316',
            '#8b5cf6',
            '#ef4444',
        ];

        $datasets = [];
        foreach ($plans as $index => $plan) {
            $planData = $data->where('plan', $plan);
            $datasets[] = [
                'label' => $plan,
                'data' => $months->map(fn (string $month) => $planData->firstWhere('month', $month)['revenue'] ?? 0)->toArray(),
                'backgroundColor' => $colors[$index % count($colors)],
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true],
            ],
        ];
    }
}
