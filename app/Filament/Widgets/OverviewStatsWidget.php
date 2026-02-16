<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverviewStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $analytics = app(AnalyticsService::class);

        return [
            Stat::make('Total Users', number_format($analytics->totalUsers())),
            Stat::make('Active Users (7d)', number_format($analytics->activeUsers(7))),
            Stat::make('Reviews Today', number_format($analytics->reviewsToday())),
            Stat::make('Premium Users', number_format($analytics->premiumUsers())),
        ];
    }
}
