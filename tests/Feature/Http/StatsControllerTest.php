<?php

use App\Enums\ReadingStatus;
use App\Models\DictionaryEntry;
use App\Models\ReadingProgress;
use App\Models\User;
use App\Models\UserVocabulary;

it('redirects guests to login', function () {
    $this->get(route('stats.index'))
        ->assertRedirect(route('login'));
});

it('displays the stats page for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('stats.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Stats/Index')
            ->has('streakCount')
            ->has('hskLevel')
            ->has('totalWordsLearned')
            ->has('totalStoriesRead')
        );
});

it('returns correct word count', function () {
    $user = User::factory()->create();
    $entries = DictionaryEntry::factory()->count(3)->create();

    foreach ($entries as $entry) {
        UserVocabulary::factory()->create([
            'user_id' => $user->id,
            'dictionary_entry_id' => $entry->id,
        ]);
    }

    $this->actingAs($user)
        ->get(route('stats.index'))
        ->assertInertia(fn ($page) => $page
            ->where('totalWordsLearned', 3)
        );
});

it('returns correct completed stories count', function () {
    $user = User::factory()->create();

    ReadingProgress::factory()->count(2)->create([
        'user_id' => $user->id,
        'status' => ReadingStatus::Completed,
    ]);

    ReadingProgress::factory()->create([
        'user_id' => $user->id,
        'status' => ReadingStatus::InProgress,
    ]);

    $this->actingAs($user)
        ->get(route('stats.index'))
        ->assertInertia(fn ($page) => $page
            ->where('totalStoriesRead', 2)
        );
});

it('returns streak data from user model', function () {
    $user = User::factory()->withStreak(5)->create();

    $this->actingAs($user)
        ->get(route('stats.index'))
        ->assertInertia(fn ($page) => $page
            ->where('streakCount', 5)
        );
});

it('returns hsk level from user model', function () {
    $user = User::factory()->withHskLevel(3)->create();

    $this->actingAs($user)
        ->get(route('stats.index'))
        ->assertInertia(fn ($page) => $page
            ->where('hskLevel', 3)
        );
});

it('does not include deferred props on initial page load', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('stats.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Stats/Index')
            ->has('streakCount')
            ->has('totalWordsLearned')
            ->missing('reviewAccuracy')
            ->missing('weeklyActivity')
            ->missing('hskProgress')
        );
});
