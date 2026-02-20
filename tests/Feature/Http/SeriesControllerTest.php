<?php

use App\Enums\ReadingStatus;
use App\Models\ReadingProgress;
use App\Models\Series;
use App\Models\Story;
use App\Models\User;

it('displays the series index page', function () {
    Series::factory()->count(3)->create();

    $response = $this->get(route('series.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Series/Index')
        ->has('series.data', 3)
        ->has('filters')
    );
});

it('only shows published series', function () {
    Series::factory()->count(2)->create();
    Series::factory()->draft()->create();

    $response = $this->get(route('series.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('series.data', 2)
    );
});

it('filters series by hsk level', function () {
    Series::factory()->create(['hsk_level' => 1]);
    Series::factory()->create(['hsk_level' => 3]);

    $response = $this->get(route('series.index', ['hsk_level' => 1]));

    $response->assertInertia(fn ($page) => $page
        ->has('series.data', 1)
        ->where('series.data.0.hsk_level', 1)
    );
});

it('searches series by title', function () {
    Series::factory()->create(['title_zh' => '冒险故事', 'title_id' => 'Cerita Petualangan']);
    Series::factory()->create(['title_zh' => '爱情故事', 'title_id' => 'Cerita Cinta']);

    $response = $this->get(route('series.index', ['search' => '冒险']));

    $response->assertInertia(fn ($page) => $page
        ->has('series.data', 1)
    );
});

it('paginates series at 12 per page', function () {
    Series::factory()->count(15)->create();

    $response = $this->get(route('series.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('series.data', 12)
        ->where('series.last_page', 2)
    );
});

it('displays the series show page with chapters', function () {
    $series = Series::factory()->create();
    Story::factory()->count(3)->create([
        'series_id' => $series->id,
        'series_order' => fn () => fake()->unique()->numberBetween(1, 10),
    ]);

    $response = $this->get(route('series.show', $series->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Series/Show')
        ->has('series')
        ->has('stories', 3)
        ->has('chapterProgress')
    );
});

it('only shows published chapters in series', function () {
    $series = Series::factory()->create();
    Story::factory()->count(2)->create([
        'series_id' => $series->id,
        'series_order' => fn () => fake()->unique()->numberBetween(1, 10),
    ]);
    Story::factory()->draft()->create([
        'series_id' => $series->id,
        'series_order' => 3,
    ]);

    $response = $this->get(route('series.show', $series->slug));

    $response->assertInertia(fn ($page) => $page
        ->has('stories', 2)
    );
});

it('returns 404 for unpublished series', function () {
    $series = Series::factory()->draft()->create();

    $response = $this->get(route('series.show', $series->slug));

    $response->assertNotFound();
});

it('includes chapter progress for authenticated users', function () {
    $user = User::factory()->create();
    $series = Series::factory()->create();
    $story = Story::factory()->create([
        'series_id' => $series->id,
        'series_order' => 1,
    ]);
    ReadingProgress::factory()->create([
        'user_id' => $user->id,
        'story_id' => $story->id,
        'status' => ReadingStatus::Completed,
    ]);

    $response = $this->actingAs($user)->get(route('series.show', $series->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('chapterProgress')
    );
});

it('returns empty chapter progress for guests', function () {
    $series = Series::factory()->create();
    Story::factory()->create([
        'series_id' => $series->id,
        'series_order' => 1,
    ]);

    $response = $this->get(route('series.show', $series->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('chapterProgress', [])
    );
});

it('includes featured series on homepage', function () {
    Series::factory()->count(3)->create();
    Story::factory()->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('featuredSeries', 3)
    );
});

it('story belongs to series relationship', function () {
    $series = Series::factory()->create();
    $story = Story::factory()->create([
        'series_id' => $series->id,
        'series_order' => 1,
    ]);

    $story->refresh();

    expect($story->series_id)->toBe($series->id);
    expect($story->series->id)->toBe($series->id);
});
