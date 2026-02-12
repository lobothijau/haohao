<?php

use App\Enums\CardState;
use App\Enums\CardType;
use App\Models\DictionaryEntry;
use App\Models\SrsCard;
use App\Models\SrsReviewLog;
use App\Models\User;
use App\Models\UserVocabulary;

it('belongs to user', function () {
    $card = SrsCard::factory()->create();

    expect($card->user)->toBeInstanceOf(User::class);
});

it('belongs to dictionary entry', function () {
    $card = SrsCard::factory()->create();

    expect($card->dictionaryEntry)->toBeInstanceOf(DictionaryEntry::class);
});

it('belongs to user vocabulary', function () {
    $card = SrsCard::factory()->create();

    expect($card->userVocabulary)->toBeInstanceOf(UserVocabulary::class);
});

it('has review logs relationship', function () {
    $card = SrsCard::factory()->create();
    SrsReviewLog::factory()->create([
        'user_id' => $card->user_id,
        'srs_card_id' => $card->id,
    ]);

    expect($card->reviewLogs)->toHaveCount(1)
        ->and($card->reviewLogs->first())->toBeInstanceOf(SrsReviewLog::class);
});

it('casts card_state to enum', function () {
    $card = SrsCard::factory()->create(['card_state' => CardState::Learning]);

    expect($card->card_state)->toBeInstanceOf(CardState::class)
        ->and($card->card_state)->toBe(CardState::Learning);
});

it('casts card_type to enum', function () {
    $card = SrsCard::factory()->create(['card_type' => CardType::Recall]);

    expect($card->card_type)->toBeInstanceOf(CardType::class)
        ->and($card->card_type)->toBe(CardType::Recall);
});

it('casts due_at to datetime', function () {
    $card = SrsCard::factory()->create();

    expect($card->due_at)->toBeInstanceOf(\DateTimeInterface::class);
});

it('casts ease_factor to decimal', function () {
    $card = SrsCard::factory()->create(['ease_factor' => 2.50]);

    expect((float) $card->ease_factor)->toBe(2.50);
});
