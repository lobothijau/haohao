<?php

namespace App\Services;

use App\Enums\ReadingStatus;
use App\Enums\SubscriptionStatus;
use App\Models\ReadingProgress;
use App\Models\SrsReviewLog;
use App\Models\Story;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /** @var array<int, SubscriptionStatus> */
    private const array PAID_STATUSES = [SubscriptionStatus::Active, SubscriptionStatus::Expired];

    public function totalUsers(): int
    {
        return User::query()->count();
    }

    public function activeUsers(int $days = 7): int
    {
        $since = Carbon::now()->subDays($days);

        $reviewUsers = SrsReviewLog::query()
            ->where('reviewed_at', '>=', $since)
            ->distinct('user_id')
            ->pluck('user_id');

        $readingUsers = ReadingProgress::query()
            ->where('started_at', '>=', $since)
            ->distinct('user_id')
            ->pluck('user_id');

        return $reviewUsers->merge($readingUsers)->unique()->count();
    }

    public function reviewsToday(): int
    {
        return SrsReviewLog::query()
            ->whereDate('reviewed_at', Carbon::today())
            ->count();
    }

    public function premiumUsers(): int
    {
        return User::query()
            ->where('is_premium', true)
            ->count();
    }

    /**
     * @return Collection<int, array{date: string, count: int}>
     */
    public function userRegistrations(int $days = 30): Collection
    {
        $since = Carbon::now()->subDays($days)->startOfDay();

        return User::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count]);
    }

    /**
     * @return Collection<int, array{date: string, count: int}>
     */
    public function dailyActiveUsers(int $days = 30): Collection
    {
        $since = Carbon::now()->subDays($days)->startOfDay();
        $results = collect();

        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();

            $reviewUsers = SrsReviewLog::query()
                ->whereDate('reviewed_at', $date)
                ->distinct('user_id')
                ->pluck('user_id');

            $readingUsers = ReadingProgress::query()
                ->whereDate('started_at', $date)
                ->distinct('user_id')
                ->pluck('user_id');

            $results->push([
                'date' => $date,
                'count' => $reviewUsers->merge($readingUsers)->unique()->count(),
            ]);
        }

        return $results;
    }

    /**
     * @return Collection<int, array{date: string, reviews: int, accuracy: float}>
     */
    public function dailyReviewActivity(int $days = 30): Collection
    {
        $since = Carbon::now()->subDays($days)->startOfDay();

        return SrsReviewLog::query()
            ->where('reviewed_at', '>=', $since)
            ->selectRaw('DATE(reviewed_at) as date')
            ->selectRaw('COUNT(*) as reviews')
            ->selectRaw('SUM(CASE WHEN rating IN (3, 4) THEN 1 ELSE 0 END) as correct')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'reviews' => (int) $row->reviews,
                'accuracy' => $row->reviews > 0
                    ? round(((int) $row->correct / (int) $row->reviews) * 100, 1)
                    : 0.0,
            ]);
    }

    /**
     * @return Collection<int, array{id: int, title: string, readers_count: int, completion_rate: float}>
     */
    public function topStories(int $limit = 10): Collection
    {
        return Story::query()
            ->withCount(['readingProgress as readers_count'])
            ->withCount(['readingProgress as completed_count' => function ($query) {
                $query->where('status', ReadingStatus::Completed);
            }])
            ->where('is_published', true)
            ->orderByDesc('readers_count')
            ->limit($limit)
            ->get()
            ->map(fn (Story $story) => [
                'id' => $story->id,
                'title' => $story->title_id,
                'hsk_level' => $story->hsk_level,
                'readers_count' => (int) $story->readers_count,
                'completion_rate' => $story->readers_count > 0
                    ? round(((int) $story->completed_count / (int) $story->readers_count) * 100, 1)
                    : 0.0,
            ]);
    }

    /**
     * @return array{active_subscriptions: int, conversion_rate: float, monthly_revenue: int, churn_30d: int}
     */
    public function premiumMetrics(): array
    {
        $activeSubscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->count();

        $totalUsers = $this->totalUsers();
        $conversionRate = $totalUsers > 0
            ? round(($this->premiumUsers() / $totalUsers) * 100, 1)
            : 0.0;

        $monthlyRevenue = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->sum('amount');

        $churn30d = Subscription::query()
            ->where('status', SubscriptionStatus::Cancelled)
            ->where('cancelled_at', '>=', Carbon::now()->subDays(30))
            ->count();

        return [
            'active_subscriptions' => $activeSubscriptions,
            'conversion_rate' => $conversionRate,
            'monthly_revenue' => (int) $monthlyRevenue,
            'churn_30d' => $churn30d,
        ];
    }

    /**
     * Monthly revenue grouped by plan, measuring actual cash collected per month.
     *
     * @return Collection<int, array{month: string, plan: string, revenue: int}>
     */
    public function monthlyRevenue(int $months = 12): Collection
    {
        $since = Carbon::now()->subMonths($months)->startOfMonth();

        $driver = Subscription::query()->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $yearExpr = "strftime('%Y', subscriptions.starts_at)";
            $monthExpr = "strftime('%m', subscriptions.starts_at)";
        } else {
            $yearExpr = 'YEAR(subscriptions.starts_at)';
            $monthExpr = 'MONTH(subscriptions.starts_at)';
        }

        return Subscription::query()
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->whereIn('subscriptions.status', self::PAID_STATUSES)
            ->whereNotNull('subscriptions.starts_at')
            ->where('subscriptions.starts_at', '>=', $since)
            ->selectRaw("{$yearExpr} as year")
            ->selectRaw("{$monthExpr} as month_num")
            ->selectRaw('plans.label as plan_label')
            ->selectRaw('subscriptions.plan_id')
            ->selectRaw('SUM(subscriptions.amount) as revenue')
            ->groupBy('year', 'month_num', 'subscriptions.plan_id', 'plans.label')
            ->orderBy('year')
            ->orderBy('month_num')
            ->get()
            ->map(fn ($row) => [
                'month' => Carbon::createFromDate((int) $row->year, (int) $row->month_num, 1)->format('M Y'),
                'plan' => $row->plan_label,
                'revenue' => (int) $row->revenue,
            ]);
    }

    /**
     * Count distinct users with active, non-expired subscriptions.
     */
    public function activePaidUsers(): int
    {
        return Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Calculate repurchase rate for given day windows.
     *
     * @return array{expired: int, repurchased: int, rate: float}
     */
    public function repurchaseRate(int $days = 30): array
    {
        $now = Carbon::now();

        $expiredSubs = Subscription::query()
            ->where('status', SubscriptionStatus::Expired)
            ->whereBetween('expires_at', [$now->copy()->subDays($days), $now])
            ->get();

        $expired = $expiredSubs->count();
        $repurchased = 0;

        foreach ($expiredSubs as $sub) {
            $hasRenewal = Subscription::query()
                ->where('user_id', $sub->user_id)
                ->where('id', '!=', $sub->id)
                ->where('starts_at', '>=', $sub->expires_at)
                ->exists();

            if ($hasRenewal) {
                $repurchased++;
            }
        }

        return [
            'expired' => $expired,
            'repurchased' => $repurchased,
            'rate' => $expired > 0 ? round(($repurchased / $expired) * 100, 1) : 0.0,
        ];
    }

    /**
     * Segment buyers into new vs returning per month.
     *
     * @return Collection<int, array{month: string, new_buyers: int, returning_buyers: int}>
     */
    public function newVsReturningBuyers(int $months = 6): Collection
    {
        $results = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $subscriptions = Subscription::query()
                ->whereIn('status', self::PAID_STATUSES)
                ->whereBetween('starts_at', [$monthStart, $monthEnd])
                ->get();

            $newBuyers = 0;
            $returningBuyers = 0;

            foreach ($subscriptions as $sub) {
                $hasPrior = Subscription::query()
                    ->where('user_id', $sub->user_id)
                    ->whereIn('status', self::PAID_STATUSES)
                    ->where('starts_at', '<', $sub->starts_at)
                    ->exists();

                if ($hasPrior) {
                    $returningBuyers++;
                } else {
                    $newBuyers++;
                }
            }

            $results->push([
                'month' => $monthStart->format('M Y'),
                'new_buyers' => $newBuyers,
                'returning_buyers' => $returningBuyers,
            ]);
        }

        return $results;
    }

    /**
     * Total revenue per plan (all-time active subscriptions).
     *
     * @return Collection<int, array{plan: string, revenue: int, count: int}>
     */
    public function revenueByPlan(): Collection
    {
        return Subscription::query()
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->whereIn('subscriptions.status', self::PAID_STATUSES)
            ->selectRaw('plans.label as plan_label')
            ->selectRaw('SUM(subscriptions.amount) as revenue')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('subscriptions.plan_id', 'plans.label')
            ->get()
            ->map(fn ($row) => [
                'plan' => $row->plan_label,
                'revenue' => (int) $row->revenue,
                'count' => (int) $row->count,
            ]);
    }

    /**
     * Count active subscriptions expiring in 30/60/90 day windows.
     *
     * @return array{next_30d: int, next_60d: int, next_90d: int}
     */
    public function upcomingExpirations(): array
    {
        $now = Carbon::now();

        $countExpiring = fn (int $days) => Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereBetween('expires_at', [$now, $now->copy()->addDays($days)])
            ->count();

        return [
            'next_30d' => $countExpiring(30),
            'next_60d' => $countExpiring(60),
            'next_90d' => $countExpiring(90),
        ];
    }

    /**
     * Returns weekly retention cohort data.
     *
     * @return array<int, array{cohort: string, users: int, d1: float, d7: float, d30: float}>
     */
    public function retentionCohorts(int $weeks = 8): array
    {
        $cohorts = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = CarbonImmutable::now()->subWeeks($i + 1)->startOfWeek();
            $weekEnd = $weekStart->endOfWeek();

            $cohortUsers = User::query()
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->pluck('id');

            $userCount = $cohortUsers->count();

            if ($userCount === 0) {
                $cohorts[] = [
                    'cohort' => $weekStart->format('M d'),
                    'users' => 0,
                    'd1' => 0.0,
                    'd7' => 0.0,
                    'd30' => 0.0,
                ];

                continue;
            }

            $d1 = $this->retainedUsers($cohortUsers, $weekEnd, 1);
            $d7 = $this->retainedUsers($cohortUsers, $weekEnd, 7);
            $d30 = $this->retainedUsers($cohortUsers, $weekEnd, 30);

            $cohorts[] = [
                'cohort' => $weekStart->format('M d'),
                'users' => $userCount,
                'd1' => round(($d1 / $userCount) * 100, 1),
                'd7' => round(($d7 / $userCount) * 100, 1),
                'd30' => round(($d30 / $userCount) * 100, 1),
            ];
        }

        return $cohorts;
    }

    /**
     * @param  Collection<int, int>  $userIds
     */
    private function retainedUsers(Collection $userIds, CarbonImmutable $after, int $daysLater): int
    {
        $targetDate = $after->addDays($daysLater);

        if ($targetDate->isFuture()) {
            return 0;
        }

        $reviewUsers = SrsReviewLog::query()
            ->whereIn('user_id', $userIds)
            ->whereDate('reviewed_at', $targetDate)
            ->distinct('user_id')
            ->pluck('user_id');

        $readingUsers = ReadingProgress::query()
            ->whereIn('user_id', $userIds)
            ->whereDate('started_at', $targetDate)
            ->distinct('user_id')
            ->pluck('user_id');

        return $reviewUsers->merge($readingUsers)->unique()->count();
    }
}
