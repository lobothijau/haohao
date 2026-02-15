<?php

use App\Models\Category;
use App\Models\DictionaryEntry;
use App\Models\ReadingProgress;
use App\Models\SentenceWord;
use App\Models\Story;
use App\Models\StorySentence;
use App\Models\User;

it('displays the stories index page to guests', function () {
    Story::factory()->count(3)->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Stories/Index')
        ->has('stories.data', 3)
        ->has('categories')
        ->has('filters')
    );
});

it('displays the stories index page to authenticated users', function () {
    $user = User::factory()->create();
    Story::factory()->count(3)->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Stories/Index')
        ->has('stories.data', 3)
    );
});

it('only shows published stories', function () {
    Story::factory()->count(2)->create();
    Story::factory()->draft()->create();

    $response = $this->get(route('home'));

    $response->assertInertia(fn ($page) => $page
        ->has('stories.data', 2)
    );
});

it('filters stories by hsk level', function () {
    Story::factory()->create(['hsk_level' => 1]);
    Story::factory()->create(['hsk_level' => 3]);

    $response = $this->get(route('home', ['hsk_level' => 1]));

    $response->assertInertia(fn ($page) => $page
        ->has('stories.data', 1)
        ->where('stories.data.0.hsk_level', 1)
    );
});

it('filters stories by category', function () {
    $category = Category::factory()->create();
    $storyWithCategory = Story::factory()->create();
    $storyWithCategory->categories()->attach($category);
    Story::factory()->create();

    $response = $this->get(route('home', ['category' => $category->slug]));

    $response->assertInertia(fn ($page) => $page
        ->has('stories.data', 1)
    );
});

it('searches stories by title', function () {
    Story::factory()->create(['title_zh' => '你好世界', 'title_id' => 'Halo Dunia']);
    Story::factory()->create(['title_zh' => '再见', 'title_id' => 'Selamat Tinggal']);

    $response = $this->get(route('home', ['search' => '你好']));

    $response->assertInertia(fn ($page) => $page
        ->has('stories.data', 1)
    );
});

it('paginates stories at 12 per page', function () {
    Story::factory()->count(15)->create();

    $response = $this->get(route('home'));

    $response->assertInertia(fn ($page) => $page
        ->has('stories.data', 12)
        ->where('stories.last_page', 2)
    );
});

it('displays the story show page to guests', function () {
    $story = Story::factory()->create();
    $sentence = StorySentence::factory()->create(['story_id' => $story->id, 'position' => 1]);
    $entry = DictionaryEntry::factory()->create();
    SentenceWord::factory()->create([
        'story_sentence_id' => $sentence->id,
        'dictionary_entry_id' => $entry->id,
        'position' => 1,
    ]);

    $response = $this->get(route('stories.show', $story->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Stories/Show')
        ->has('story')
        ->has('sentences', 1)
        ->has('sentences.0.words', 1)
        ->has('sentences.0.words.0.dictionary_entry')
        ->has('savedVocabularyIds')
    );
});

it('displays the story show page with user data when authenticated', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();
    $sentence = StorySentence::factory()->create(['story_id' => $story->id, 'position' => 1]);
    $entry = DictionaryEntry::factory()->create();
    SentenceWord::factory()->create([
        'story_sentence_id' => $sentence->id,
        'dictionary_entry_id' => $entry->id,
        'position' => 1,
    ]);

    $response = $this->actingAs($user)->get(route('stories.show', $story->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Stories/Show')
        ->has('story')
        ->has('sentences', 1)
        ->has('savedVocabularyIds')
    );
});

it('returns 404 for unpublished stories', function () {
    $story = Story::factory()->draft()->create();

    $response = $this->get(route('stories.show', $story->slug));

    $response->assertNotFound();
});

it('sets isNewUser to false for guests', function () {
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('isNewUser', false)
        );
});

it('sets isNewUser to true for authenticated users with no reading progress', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('isNewUser', true)
        );
});

it('sets isNewUser to false for authenticated users with reading progress', function () {
    $user = User::factory()->create();
    ReadingProgress::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('isNewUser', false)
        );
});
