<?php

use App\Enums\ReadingStatus;
use App\Enums\SrsRating;
use App\Enums\SubscriptionStatus;
use App\Models\DictionaryEntry;
use App\Models\Plan;
use App\Models\ReadingProgress;
use App\Models\SrsCard;
use App\Models\SrsReviewLog;
use App\Models\Story;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserVocabulary;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->analytics = new AnalyticsService;
});

it('counts total users', function () {
    User::factory()->count(5)->create();

    expect($this->analytics->totalUsers())->toBe(5);
});

it('counts active users from review logs', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create();
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);
    $card = SrsCard::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
        'user_vocabulary_id' => $vocab->id,
    ]);

    SrsReviewLog::factory()->create([
        'user_id' => $user->id,
        'srs_card_id' => $card->id,
        'reviewed_at' => now()->subDay(),
    ]);

    // Inactive user (no activity)
    User::factory()->create();

    expect($this->analytics->activeUsers(7))->toBe(1);
});

it('counts active users from reading progress', function () {
    $user = User::factory()->create();

    ReadingProgress::factory()->create([
        'user_id' => $user->id,
        'started_at' => now()->subDays(2),
    ]);

    User::factory()->create();

    expect($this->analytics->activeUsers(7))->toBe(1);
});

it('deduplicates active users across review and reading', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create();
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);
    $card = SrsCard::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
        'user_vocabulary_id' => $vocab->id,
    ]);

    SrsReviewLog::factory()->create([
        'user_id' => $user->id,
        'srs_card_id' => $card->id,
        'reviewed_at' => now()->subDay(),
    ]);

    ReadingProgress::factory()->create([
        'user_id' => $user->id,
        'started_at' => now()->subDay(),
    ]);

    expect($this->analytics->activeUsers(7))->toBe(1);
});

it('counts reviews today', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create();
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);
    $card = SrsCard::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
        'user_vocabulary_id' => $vocab->id,
    ]);

    SrsReviewLog::factory()->count(3)->create([
        'user_id' => $user->id,
        'srs_card_id' => $card->id,
        'reviewed_at' => now(),
    ]);

    SrsReviewLog::factory()->create([
        'user_id' => $user->id,
        'srs_card_id' => $card->id,
        'reviewed_at' => now()->subDay(),
    ]);

    expect($this->analytics->reviewsToday())->toBe(3);
});

it('counts premium users', function () {
    User::factory()->count(2)->premium()->create();
    User::factory()->count(3)->create();

    expect($this->analytics->premiumUsers())->toBe(2);
});

it('returns user registrations per day', function () {
    User::factory()->create(['created_at' => now()->subDays(2)]);
    User::factory()->count(2)->create(['created_at' => now()->subDay()]);

    $registrations = $this->analytics->userRegistrations(7);

    expect($registrations)->toHaveCount(2);
    expect($registrations->last()['count'])->toBe(2);
});

it('returns daily review activity with accuracy', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create();
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);
    $card = SrsCard::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
        'user_vocabulary_id' => $vocab->id,
    ]);

    // 2 correct (Good=3, Easy=4), 1 incorrect (Again=1)
    SrsReviewLog::factory()->create([
        'user_id' => $user->id,
        'srs_card_id' => $card->id,
        'rating' => SrsRating::Good,
        'reviewed_at' => now(),
    ]);
    SrsReviewLog::factory()->create([
        'user_id' => $user->id,
        'srs_card_id' => $card->id,
        'rating' => SrsRating::Easy,
        'reviewed_at' => now(),
    ]);
    SrsReviewLog::factory()->create([
        'user_id' => $user->id,
        'srs_card_id' => $card->id,
        'rating' => SrsRating::Again,
        'reviewed_at' => now(),
    ]);

    $activity = $this->analytics->dailyReviewActivity(7);

    expect($activity)->toHaveCount(1);
    expect($activity->first()['reviews'])->toBe(3);
    expect($activity->first()['accuracy'])->toBe(66.7);
});

it('returns top stories by reader count', function () {
    $popularStory = Story::factory()->create();
    ReadingProgress::factory()->count(5)->create(['story_id' => $popularStory->id]);

    $lessPopularStory = Story::factory()->create();
    ReadingProgress::factory()->count(2)->create(['story_id' => $lessPopularStory->id]);

    $stories = $this->analytics->topStories(10);

    expect($stories)->toHaveCount(2);
    expect($stories->first()['readers_count'])->toBe(5);
});

it('calculates story completion rate', function () {
    $story = Story::factory()->create();
    ReadingProgress::factory()->count(3)->create([
        'story_id' => $story->id,
        'status' => ReadingStatus::Completed,
    ]);
    ReadingProgress::factory()->count(2)->create([
        'story_id' => $story->id,
        'status' => ReadingStatus::InProgress,
    ]);

    $stories = $this->analytics->topStories(10);

    expect($stories->first()['completion_rate'])->toBe(60.0);
});

it('returns premium metrics', function () {
    User::factory()->count(10)->create();
    User::factory()->count(2)->premium()->create();

    $plan = Plan::factory()->create();

    Subscription::factory()->count(2)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'amount' => 99000,
    ]);

    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Cancelled,
        'cancelled_at' => now()->subDays(10),
    ]);

    $metrics = $this->analytics->premiumMetrics();

    expect($metrics['active_subscriptions'])->toBe(2);
    expect($metrics['conversion_rate'])->toBeGreaterThan(0);
    expect($metrics['monthly_revenue'])->toBe(198000);
    expect($metrics['churn_30d'])->toBe(1);
});

it('returns retention cohort data', function () {
    $cohorts = $this->analytics->retentionCohorts(4);

    expect($cohorts)->toHaveCount(4);
    expect($cohorts[0])->toHaveKeys(['cohort', 'users', 'd1', 'd7', 'd30']);
});

it('returns monthly revenue grouped by plan', function () {
    $monthly = Plan::factory()->monthly()->create();
    $founder = Plan::factory()->founder()->create();

    Subscription::factory()->create([
        'plan_id' => $monthly->id,
        'status' => SubscriptionStatus::Active,
        'amount' => 49_000,
        'starts_at' => now()->subMonth(),
    ]);

    Subscription::factory()->create([
        'plan_id' => $founder->id,
        'status' => SubscriptionStatus::Active,
        'amount' => 149_000,
        'starts_at' => now()->subMonth(),
    ]);

    Subscription::factory()->create([
        'plan_id' => $monthly->id,
        'status' => SubscriptionStatus::Active,
        'amount' => 49_000,
        'starts_at' => now(),
    ]);

    // Expired sub should still count in revenue
    Subscription::factory()->expired()->create([
        'plan_id' => $monthly->id,
        'amount' => 49_000,
        'starts_at' => now()->subMonths(2),
    ]);

    $result = $this->analytics->monthlyRevenue(12);

    expect($result)->toHaveCount(4);
    expect($result->sum('revenue'))->toBe(296_000);
});

it('counts active paid users', function () {
    $plan = Plan::factory()->create();

    // Active, non-expired
    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addMonth(),
    ]);

    // Expired subscription
    Subscription::factory()->expired()->create([
        'plan_id' => $plan->id,
    ]);

    // Cancelled
    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Cancelled,
        'expires_at' => now()->addMonth(),
    ]);

    expect($this->analytics->activePaidUsers())->toBe(1);
});

it('calculates repurchase rate', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Expired subscription (expired 10 days ago)
    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Expired,
        'starts_at' => now()->subMonths(2),
        'expires_at' => now()->subDays(10),
    ]);

    // Renewal subscription
    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now()->subDays(10),
        'expires_at' => now()->addMonths(1),
    ]);

    // Another user with expired sub but no renewal
    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Expired,
        'starts_at' => now()->subMonths(2),
        'expires_at' => now()->subDays(5),
    ]);

    $result = $this->analytics->repurchaseRate(30);

    expect($result['expired'])->toBe(2);
    expect($result['repurchased'])->toBe(1);
    expect($result['rate'])->toBe(50.0);
});

it('segments new vs returning buyers', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // First subscription for this user (2 months ago, now expired)
    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Expired,
        'starts_at' => now()->subMonths(2),
        'expires_at' => now()->subMonth(),
    ]);

    // Returning buyer subscription (this month)
    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now(),
        'expires_at' => now()->addMonth(),
    ]);

    // New buyer (this month)
    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now(),
        'expires_at' => now()->addMonth(),
    ]);

    $result = $this->analytics->newVsReturningBuyers(1);

    expect($result)->toHaveCount(1);
    expect($result->first()['new_buyers'])->toBe(1);
    expect($result->first()['returning_buyers'])->toBe(1);
});

it('returns revenue by plan breakdown', function () {
    $monthly = Plan::factory()->monthly()->create();
    $founder = Plan::factory()->founder()->create();

    Subscription::factory()->count(3)->create([
        'plan_id' => $monthly->id,
        'status' => SubscriptionStatus::Active,
        'amount' => 49_000,
    ]);

    Subscription::factory()->count(2)->create([
        'plan_id' => $founder->id,
        'status' => SubscriptionStatus::Active,
        'amount' => 149_000,
    ]);

    // Expired sub should still count in revenue
    Subscription::factory()->expired()->create([
        'plan_id' => $monthly->id,
        'amount' => 49_000,
    ]);

    $result = $this->analytics->revenueByPlan();

    expect($result)->toHaveCount(2);

    $monthlyResult = $result->firstWhere('plan', '1 Bulan');
    expect($monthlyResult['revenue'])->toBe(196_000);
    expect($monthlyResult['count'])->toBe(4);

    $founderResult = $result->firstWhere('plan', 'Founder Edition');
    expect($founderResult['revenue'])->toBe(298_000);
    expect($founderResult['count'])->toBe(2);
});

it('counts upcoming expirations by window', function () {
    $plan = Plan::factory()->create();

    // Expires in 15 days (within 30d window)
    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(15),
    ]);

    // Expires in 45 days (within 60d window but not 30d)
    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(45),
    ]);

    // Expires in 75 days (within 90d window but not 60d)
    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(75),
    ]);

    // Already expired (should not count)
    Subscription::factory()->expired()->create([
        'plan_id' => $plan->id,
    ]);

    $result = $this->analytics->upcomingExpirations();

    expect($result['next_30d'])->toBe(1);
    expect($result['next_60d'])->toBe(2);
    expect($result['next_90d'])->toBe(3);
});
