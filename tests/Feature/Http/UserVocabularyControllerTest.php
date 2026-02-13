<?php

use App\Models\DictionaryEntry;
use App\Models\Story;
use App\Models\User;
use App\Models\UserVocabulary;

it('saves a word to vocabulary', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create();
    $story = Story::factory()->create();

    $response = $this->actingAs($user)->post(route('vocabulary.store'), [
        'dictionary_entry_id' => $entry->id,
        'source_story_id' => $story->id,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('user_vocabularies', [
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
        'source_story_id' => $story->id,
    ]);
});

it('prevents duplicate vocabulary entries', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create();

    UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    $response = $this->actingAs($user)->post(route('vocabulary.store'), [
        'dictionary_entry_id' => $entry->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('user_vocabularies', 1);
});

it('removes a word from vocabulary', function () {
    $user = User::factory()->create();
    $vocabulary = UserVocabulary::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson(route('vocabulary.destroy', $vocabulary));

    $response->assertNoContent();
    $this->assertDatabaseMissing('user_vocabularies', ['id' => $vocabulary->id]);
});

it('prevents users from deleting other users vocabulary', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $vocabulary = UserVocabulary::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->deleteJson(route('vocabulary.destroy', $vocabulary));

    $response->assertForbidden();
    $this->assertDatabaseHas('user_vocabularies', ['id' => $vocabulary->id]);
});

it('requires authentication to save vocabulary', function () {
    $entry = DictionaryEntry::factory()->create();

    $response = $this->post(route('vocabulary.store'), [
        'dictionary_entry_id' => $entry->id,
    ]);

    $response->assertRedirect(route('login'));
});

it('requires authentication to delete vocabulary', function () {
    $vocabulary = UserVocabulary::factory()->create();

    $response = $this->deleteJson(route('vocabulary.destroy', $vocabulary));

    $response->assertUnauthorized();
});

it('validates dictionary_entry_id exists', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('vocabulary.store'), [
        'dictionary_entry_id' => 99999,
    ]);

    $response->assertSessionHasErrors(['dictionary_entry_id']);
});
