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

it('creates custom vocabulary with new dictionary entry', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('vocabulary.store-custom'), [
        'simplified' => '自定义',
        'pinyin' => 'zì dìng yì',
        'meaning_id' => 'kustom',
        'meaning_en' => 'custom',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('dictionary_entries', [
        'simplified' => '自定义',
        'pinyin' => 'zì dìng yì',
        'created_by_user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('user_vocabularies', [
        'user_id' => $user->id,
    ]);
});

it('reuses existing dictionary entry when simplified and pinyin match', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create([
        'simplified' => '你好',
        'pinyin' => 'nǐ hǎo',
    ]);

    $response = $this->actingAs($user)->post(route('vocabulary.store-custom'), [
        'simplified' => '你好',
        'pinyin' => 'nǐ hǎo',
        'meaning_id' => 'halo',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('dictionary_entries', 1);

    $this->assertDatabaseHas('user_vocabularies', [
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);
});

it('prevents duplicate user vocabulary for custom entries', function () {
    $user = User::factory()->create();
    $entry = DictionaryEntry::factory()->create([
        'simplified' => '你好',
        'pinyin' => 'nǐ hǎo',
    ]);

    UserVocabulary::factory()->create([
        'user_id' => $user->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    $response = $this->actingAs($user)->post(route('vocabulary.store-custom'), [
        'simplified' => '你好',
        'pinyin' => 'nǐ hǎo',
        'meaning_id' => 'halo',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('user_vocabularies', 1);
});

it('validates required fields for custom vocabulary', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('vocabulary.store-custom'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['simplified', 'pinyin', 'meaning_id']);
});

it('requires authentication to store custom vocabulary', function () {
    $response = $this->post(route('vocabulary.store-custom'), [
        'simplified' => '你好',
        'pinyin' => 'nǐ hǎo',
        'meaning_id' => 'halo',
    ]);

    $response->assertRedirect(route('login'));
});

it('sets created_by_user_id on new dictionary entries from custom vocabulary', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('vocabulary.store-custom'), [
        'simplified' => '新词',
        'pinyin' => 'xīn cí',
        'meaning_id' => 'kata baru',
    ]);

    $entry = DictionaryEntry::query()->where('simplified', '新词')->first();

    expect($entry->created_by_user_id)->toBe($user->id);
});
