<?php

use App\Enums\CardType;
use App\Models\DictionaryEntry;
use App\Models\SrsCard;
use App\Models\User;
use App\Models\UserVocabulary;
use App\Services\SrsService;

it('creates recognition card for vocabulary', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create(['audio_url' => null]);
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    $srsService = app(SrsService::class);
    $cards = $srsService->createCardsForVocabulary($vocab);

    expect($cards)->toHaveCount(1);
    expect($cards[0]->card_type)->toBe(CardType::Recognition);
});

it('creates both recognition and listening cards when audio exists', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create(['audio_url' => '/audio/test.mp3']);
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    $srsService = app(SrsService::class);
    $cards = $srsService->createCardsForVocabulary($vocab);

    expect($cards)->toHaveCount(2);

    $cardTypes = collect($cards)->pluck('card_type')->all();
    expect($cardTypes)->toContain(CardType::Recognition);
    expect($cardTypes)->toContain(CardType::Listening);
});

it('does not create duplicate listening cards', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create(['audio_url' => '/audio/test.mp3']);
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    $srsService = app(SrsService::class);
    $srsService->createCardsForVocabulary($vocab);
    $srsService->createCardsForVocabulary($vocab);

    $cardCount = SrsCard::where('user_id', $user->id)
        ->where('dictionary_entry_id', $entry->id)
        ->count();

    expect($cardCount)->toBe(2);
});

it('does not create listening card when audio url is null', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create(['audio_url' => null]);
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    $srsService = app(SrsService::class);
    $cards = $srsService->createCardsForVocabulary($vocab);

    $listeningCards = SrsCard::where('user_id', $user->id)
        ->where('card_type', CardType::Listening)
        ->count();

    expect($listeningCards)->toBe(0);
});
