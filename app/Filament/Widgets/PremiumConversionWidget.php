<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PremiumConversionWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 6;

    protected function getStats(): array
    {
        $metrics = app(AnalyticsService::class)->premiumMetrics();

        return [
            Stat::make('Active Subscriptions', number_format($metrics['active_subscriptions'])),
            Stat::make('Conversion Rate', $metrics['conversion_rate'].'%'),
            Stat::make('Monthly Revenue', 'Rp '.number_format($metrics['monthly_revenue'])),
            Stat::make('Churn (30d)', number_format($metrics['churn_30d'])),
        ];
    }
}
