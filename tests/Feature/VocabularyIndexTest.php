<?php

use App\Models\DictionaryEntry;
use App\Models\User;
use App\Models\UserVocabulary;

test('guests cannot access vocabulary index', function () {
    $this->get(route('vocabulary.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view their vocabulary', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create(['simplified' => '学习', 'pinyin' => 'xué xí']);
    UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    $this->actingAs($user)
        ->get(route('vocabulary.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vocabulary/Index')
            ->has('vocabularies.data', 1)
        );
});

test('vocabulary index only shows the authenticated users words', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    UserVocabulary::factory()->create(['user_id' => $user->id]);
    UserVocabulary::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get(route('vocabulary.index'))
        ->assertInertia(fn ($page) => $page
            ->has('vocabularies.data', 1)
        );
});

test('vocabulary index supports search filter', function () {
    $user = User::factory()->create();
    $matching = DictionaryEntry::factory()->create(['simplified' => '学习']);
    $nonMatching = DictionaryEntry::factory()->create(['simplified' => '吃饭']);

    UserVocabulary::factory()->create(['user_id' => $user->id, 'dictionary_entry_id' => $matching->id]);
    UserVocabulary::factory()->create(['user_id' => $user->id, 'dictionary_entry_id' => $nonMatching->id]);

    $this->actingAs($user)
        ->get(route('vocabulary.index', ['search' => '学习']))
        ->assertInertia(fn ($page) => $page
            ->has('vocabularies.data', 1)
        );
});

test('vocabulary store creates an srs card automatically', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create();

    $this->actingAs($user)
        ->post(route('vocabulary.store'), [
            'dictionary_entry_id' => $entry->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('user_vocabularies', [
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    $this->assertDatabaseHas('srs_cards', [
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
        'card_state' => 'new',
    ]);
});

test('vocabulary can be deleted', function () {
    $user = User::factory()->create();
    $vocab = UserVocabulary::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('vocabulary.destroy', $vocab))
        ->assertNoContent();

    $this->assertDatabaseMissing('user_vocabularies', ['id' => $vocab->id]);
});

test('users cannot delete other users vocabulary', function () {
    $user = User::factory()->create();
    $otherVocab = UserVocabulary::factory()->create();

    $this->actingAs($user)
        ->delete(route('vocabulary.destroy', $otherVocab))
        ->assertForbidden();
});
