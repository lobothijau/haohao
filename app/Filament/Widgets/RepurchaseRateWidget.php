<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RepurchaseRateWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 11;

    protected function getHeading(): ?string
    {
        return 'Tingkat Pembelian Ulang';
    }

    protected function getStats(): array
    {
        $analytics = app(AnalyticsService::class);

        $rate30 = $analytics->repurchaseRate(30);
        $rate60 = $analytics->repurchaseRate(60);
        $rate90 = $analytics->repurchaseRate(90);

        return [
            Stat::make('Repurchase 30d', $rate30['rate'].'%')
                ->description($rate30['repurchased'].' dari '.$rate30['expired'].' expired'),
            Stat::make('Repurchase 60d', $rate60['rate'].'%')
                ->description($rate60['repurchased'].' dari '.$rate60['expired'].' expired'),
            Stat::make('Repurchase 90d', $rate90['rate'].'%')
                ->description($rate90['repurchased'].' dari '.$rate90['expired'].' expired'),
        ];
    }
}
