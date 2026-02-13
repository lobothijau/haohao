<?php

use App\Enums\ReadingStatus;
use App\Models\ReadingProgress;
use App\Models\Story;
use App\Models\User;

it('creates reading progress for a story', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();

    $response = $this->actingAs($user)->post(route('stories.progress', $story), [
        'last_sentence_position' => 3,
        'status' => 'in_progress',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('reading_progress', [
        'user_id' => $user->id,
        'story_id' => $story->id,
        'status' => 'in_progress',
        'last_sentence_position' => 3,
    ]);
});

it('updates existing reading progress', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();

    ReadingProgress::factory()->create([
        'user_id' => $user->id,
        'story_id' => $story->id,
        'status' => ReadingStatus::InProgress,
        'last_sentence_position' => 2,
        'started_at' => now()->subMinutes(5),
    ]);

    $response = $this->actingAs($user)->post(route('stories.progress', $story), [
        'last_sentence_position' => 5,
        'status' => 'in_progress',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseCount('reading_progress', 1);
    $this->assertDatabaseHas('reading_progress', [
        'user_id' => $user->id,
        'story_id' => $story->id,
        'last_sentence_position' => 5,
    ]);
});

it('sets started_at on first progress save', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();

    $this->actingAs($user)->post(route('stories.progress', $story), [
        'last_sentence_position' => 1,
        'status' => 'in_progress',
    ]);

    $progress = ReadingProgress::where('user_id', $user->id)->first();
    expect($progress->started_at)->not->toBeNull();
});

it('sets completed_at when status is completed', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();

    $this->actingAs($user)->post(route('stories.progress', $story), [
        'last_sentence_position' => 10,
        'status' => 'completed',
    ]);

    $progress = ReadingProgress::where('user_id', $user->id)->first();
    expect($progress->completed_at)->not->toBeNull();
    expect($progress->status)->toBe(ReadingStatus::Completed);
});

it('requires authentication to save progress', function () {
    $story = Story::factory()->create();

    $response = $this->postJson(route('stories.progress', $story), [
        'last_sentence_position' => 1,
        'status' => 'in_progress',
    ]);

    $response->assertUnauthorized();
});

it('validates required fields', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();

    $response = $this->actingAs($user)->post(route('stories.progress', $story), []);

    $response->assertSessionHasErrors(['last_sentence_position', 'status']);
});

it('validates status is a valid enum value', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();

    $response = $this->actingAs($user)->post(route('stories.progress', $story), [
        'last_sentence_position' => 1,
        'status' => 'invalid_status',
    ]);

    $response->assertSessionHasErrors(['status']);
});
