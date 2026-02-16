<?php

namespace App\Http\Controllers;

use App\Enums\ReadingStatus;
use App\Models\DictionaryEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Stats/Index', [
            'streakCount' => $user->streak_count,
            'streakLastDate' => $user->streak_last_date?->toDateString(),
            'hskLevel' => $user->hsk_level,
            'totalWordsLearned' => $user->vocabularies()->count(),
            'totalStoriesRead' => $user->readingProgress()
                ->where('status', ReadingStatus::Completed)
                ->count(),
            'reviewAccuracy' => Inertia::defer(fn () => $this->getReviewAccuracy($user)),
            'weeklyActivity' => Inertia::defer(fn () => $this->getWeeklyActivity($user)),
            'hskProgress' => Inertia::defer(fn () => $this->getHskProgress($user)),
        ]);
    }

    /**
     * @return array{all_time: float, last_7_days: float}
     */
    private function getReviewAccuracy(mixed $user): array
    {
        $allTime = $this->calculateAccuracy(
            $user->reviewLogs()
        );

        $last7Days = $this->calculateAccuracy(
            $user->reviewLogs()->where('reviewed_at', '>=', Carbon::now()->subDays(7))
        );

        return [
            'all_time' => $allTime,
            'last_7_days' => $last7Days,
        ];
    }

    private function calculateAccuracy(mixed $query): float
    {
        $total = $query->count();

        if ($total === 0) {
            return 0.0;
        }

        $correct = (clone $query)->whereIn('rating', [3, 4])->count();

        return round(($correct / $total) * 100, 1);
    }

    /**
     * @return array<int, array{date: string, count: int}>
     */
    private function getWeeklyActivity(mixed $user): array
    {
        $days = 84; // 12 weeks
        $activity = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();

            $reviewCount = $user->reviewLogs()
                ->whereDate('reviewed_at', $date)
                ->count();

            $readingCount = $user->readingProgress()
                ->whereDate('started_at', $date)
                ->count();

            $activity[] = [
                'date' => $date,
                'count' => $reviewCount + $readingCount,
            ];
        }

        return $activity;
    }

    /**
     * @return array<int, array{level: int, learned: int, total: int}>
     */
    private function getHskProgress(mixed $user): array
    {
        $progress = [];
        $learnedEntryIds = $user->vocabularies()->pluck('dictionary_entry_id');

        for ($level = 1; $level <= 6; $level++) {
            $total = DictionaryEntry::query()
                ->where('hsk_level', $level)
                ->count();

            $learned = DictionaryEntry::query()
                ->where('hsk_level', $level)
                ->whereIn('id', $learnedEntryIds)
                ->count();

            $progress[] = [
                'level' => $level,
                'learned' => $learned,
                'total' => $total,
            ];
        }

        return $progress;
    }
}
