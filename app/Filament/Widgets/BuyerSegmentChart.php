<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class BuyerSegmentChart extends ChartWidget
{
    protected ?string $heading = 'Pembeli Baru vs Kembali';

    protected static ?int $sort = 10;

    protected function getData(): array
    {
        $data = app(AnalyticsService::class)->newVsReturningBuyers(6);

        return [
            'datasets' => [
                [
                    'label' => 'Pembeli Baru',
                    'data' => $data->pluck('new_buyers')->toArray(),
                    'backgroundColor' => '#3b82f6',
                ],
                [
                    'label' => 'Pembeli Kembali',
                    'data' => $data->pluck('returning_buyers')->toArray(),
                    'backgroundColor' => '#10b981',
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
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
