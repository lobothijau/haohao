<?php

use App\Models\ReadingProgress;
use App\Models\SrsCard;
use App\Models\SrsReviewLog;
use App\Models\Story;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\UserVocabulary;

it('has vocabularies relationship', function () {
    $user = User::factory()->create();
    $entry = \App\Models\DictionaryEntry::factory()->create();
    UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    expect($user->vocabularies)->toHaveCount(1)
        ->and($user->vocabularies->first())->toBeInstanceOf(UserVocabulary::class);
});

it('has srs cards relationship', function () {
    $user = User::factory()->create();
    $entry = \App\Models\DictionaryEntry::factory()->create();
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);
    SrsCard::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
        'user_vocabulary_id' => $vocab->id,
    ]);

    expect($user->srsCards)->toHaveCount(1)
        ->and($user->srsCards->first())->toBeInstanceOf(SrsCard::class);
});

it('has review logs relationship', function () {
    $user = User::factory()->create();
    $card = SrsCard::factory()->create(['user_id' => $user->id]);
    SrsReviewLog::factory()->create([
        'user_id' => $user->id,
        'srs_card_id' => $card->id,
    ]);

    expect($user->reviewLogs)->toHaveCount(1)
        ->and($user->reviewLogs->first())->toBeInstanceOf(SrsReviewLog::class);
});

it('has reading progress relationship', function () {
    $user = User::factory()->create();
    ReadingProgress::factory()->create(['user_id' => $user->id]);

    expect($user->readingProgress)->toHaveCount(1)
        ->and($user->readingProgress->first())->toBeInstanceOf(ReadingProgress::class);
});

it('has preference relationship', function () {
    $user = User::factory()->create();
    UserPreference::factory()->create(['user_id' => $user->id]);

    expect($user->preference)->toBeInstanceOf(UserPreference::class);
});

it('has subscriptions relationship', function () {
    $user = User::factory()->create();
    Subscription::factory()->create(['user_id' => $user->id]);

    expect($user->subscriptions)->toHaveCount(1)
        ->and($user->subscriptions->first())->toBeInstanceOf(Subscription::class);
});

it('has created stories relationship', function () {
    $user = User::factory()->create();
    Story::factory()->create(['created_by' => $user->id]);

    expect($user->createdStories)->toHaveCount(1)
        ->and($user->createdStories->first())->toBeInstanceOf(Story::class);
});

it('casts hsk_level to integer', function () {
    $user = User::factory()->create(['hsk_level' => 3]);

    expect($user->hsk_level)->toBeInt()->toBe(3);
});

it('casts is_premium to boolean', function () {
    $user = User::factory()->create(['is_premium' => true]);

    expect($user->is_premium)->toBeBool()->toBeTrue();
});

it('casts premium_expires_at to datetime', function () {
    $user = User::factory()->premium()->create();

    expect($user->premium_expires_at)->toBeInstanceOf(\DateTimeInterface::class);
});

it('casts streak_last_date to date', function () {
    $user = User::factory()->withStreak(5)->create();

    expect($user->streak_last_date)->toBeInstanceOf(\DateTimeInterface::class);
});
