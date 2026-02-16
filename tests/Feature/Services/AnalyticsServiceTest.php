<?php

use App\Enums\ReadingStatus;
use App\Enums\SrsRating;
use App\Enums\SubscriptionStatus;
use App\Models\DictionaryEntry;
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

    Subscription::factory()->count(2)->create([
        'status' => SubscriptionStatus::Active,
        'amount' => 99000,
    ]);

    Subscription::factory()->create([
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
