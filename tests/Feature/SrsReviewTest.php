<?php

use App\Enums\CardState;
use App\Models\DictionaryEntry;
use App\Models\SrsCard;
use App\Models\User;
use App\Models\UserVocabulary;

test('guests cannot access review page', function () {
    $this->get(route('review.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the review page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('review.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Review/Index')
            ->has('dueCount')
        );
});

test('review page shows correct due count', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create();
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    SrsCard::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
        'user_vocabulary_id' => $vocab->id,
        'due_at' => now()->subHour(),
    ]);

    // Not due yet
    $entry2 = DictionaryEntry::factory()->create();
    $vocab2 = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry2->id,
    ]);
    SrsCard::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry2->id,
        'user_vocabulary_id' => $vocab2->id,
        'due_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->get(route('review.index'))
        ->assertInertia(fn ($page) => $page
            ->where('dueCount', 1)
        );
});

test('cards endpoint returns due cards', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create();
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    SrsCard::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
        'user_vocabulary_id' => $vocab->id,
        'due_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->getJson(route('review.cards'))
        ->assertOk()
        ->assertJsonCount(1, 'cards')
        ->assertJsonStructure(['cards' => [['id', 'dictionary_entry']]]);
});

test('users can review a card with a rating', function () {
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
        'card_state' => CardState::New,
        'due_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->postJson(route('review.review', $card), [
            'rating' => 3,
            'time_taken_ms' => 5000,
        ])
        ->assertOk()
        ->assertJsonStructure(['card', 'log']);

    $this->assertDatabaseHas('srs_review_logs', [
        'user_id' => $user->id,
        'srs_card_id' => $card->id,
        'rating' => 3,
    ]);
});

test('users cannot review another users card', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $entry = DictionaryEntry::factory()->create();
    $vocab = UserVocabulary::factory()->create([
        'user_id' => $otherUser->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    $card = SrsCard::factory()->create([
        'user_id' => $otherUser->id,
        'dictionary_entry_id' => $entry->id,
        'user_vocabulary_id' => $vocab->id,
    ]);

    $this->actingAs($user)
        ->postJson(route('review.review', $card), ['rating' => 3])
        ->assertForbidden();
});

test('review requires a valid rating', function () {
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

    $this->actingAs($user)
        ->postJson(route('review.review', $card), ['rating' => 5])
        ->assertUnprocessable();

    $this->actingAs($user)
        ->postJson(route('review.review', $card), [])
        ->assertUnprocessable();
});
