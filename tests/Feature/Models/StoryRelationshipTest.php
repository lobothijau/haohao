<?php

use App\Enums\ContentSource;
use App\Models\Category;
use App\Models\ReadingProgress;
use App\Models\Story;
use App\Models\StorySentence;
use App\Models\User;

it('has sentences relationship ordered by position', function () {
    $story = Story::factory()->create();
    StorySentence::factory()->create(['story_id' => $story->id, 'position' => 2]);
    StorySentence::factory()->create(['story_id' => $story->id, 'position' => 1]);

    $sentences = $story->sentences;

    expect($sentences)->toHaveCount(2)
        ->and($sentences->first()->position)->toBe(1)
        ->and($sentences->last()->position)->toBe(2);
});

it('has categories relationship', function () {
    $story = Story::factory()->create();
    $category = Category::factory()->create();
    $story->categories()->attach($category);

    expect($story->categories)->toHaveCount(1)
        ->and($story->categories->first())->toBeInstanceOf(Category::class);
});

it('has creator relationship', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create(['created_by' => $user->id]);

    expect($story->creator)->toBeInstanceOf(User::class)
        ->and($story->creator->id)->toBe($user->id);
});

it('has reading progress relationship', function () {
    $story = Story::factory()->create();
    ReadingProgress::factory()->create(['story_id' => $story->id]);

    expect($story->readingProgress)->toHaveCount(1)
        ->and($story->readingProgress->first())->toBeInstanceOf(ReadingProgress::class);
});

it('casts content_source to enum', function () {
    $story = Story::factory()->create(['content_source' => ContentSource::Manual]);

    expect($story->content_source)->toBeInstanceOf(ContentSource::class)
        ->and($story->content_source)->toBe(ContentSource::Manual);
});

it('casts is_premium to boolean', function () {
    $story = Story::factory()->premium()->create();

    expect($story->is_premium)->toBeBool()->toBeTrue();
});

it('generates slug from title_id', function () {
    $story = Story::factory()->create(['title_id' => 'Belajar Bahasa Mandarin', 'slug' => null]);

    expect($story->slug)->toBe('belajar-bahasa-mandarin');
});
