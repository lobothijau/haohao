<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\Widget;

class RetentionCohortWidget extends Widget
{
    protected static ?int $sort = 7;

    protected string $view = 'filament.widgets.retention-cohort-widget';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, array{cohort: string, users: int, d1: float, d7: float, d30: float}>
     */
    public function getCohorts(): array
    {
        return app(AnalyticsService::class)->retentionCohorts(8);
    }
}
