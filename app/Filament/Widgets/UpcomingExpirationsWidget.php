<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UpcomingExpirationsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 12;

    protected function getHeading(): ?string
    {
        return 'Langganan Akan Berakhir';
    }

    protected function getStats(): array
    {
        $data = app(AnalyticsService::class)->upcomingExpirations();

        return [
            Stat::make('30 Hari', number_format($data['next_30d']))
                ->color('danger'),
            Stat::make('60 Hari', number_format($data['next_60d']))
                ->color('warning'),
            Stat::make('90 Hari', number_format($data['next_90d']))
                ->color('gray'),
        ];
    }
}
