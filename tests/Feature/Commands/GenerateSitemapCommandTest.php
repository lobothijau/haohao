<?php

use App\Models\BlogPost;
use App\Models\Series;
use App\Models\Story;

it('generates a sitemap file', function () {
    $this->artisan('sitemap:generate')
        ->assertSuccessful();

    expect(file_exists(public_path('sitemap.xml')))->toBeTrue();
});

it('includes static pages', function () {
    $this->artisan('sitemap:generate');

    $content = file_get_contents(public_path('sitemap.xml'));

    expect($content)->toContain('<loc>')
        ->and($content)->toContain('/blog')
        ->and($content)->toContain('/series')
        ->and($content)->toContain('/membership');
});

it('includes published stories', function () {
    $story = Story::factory()->create(['slug' => 'test-story']);

    $this->artisan('sitemap:generate');

    $content = file_get_contents(public_path('sitemap.xml'));

    expect($content)->toContain('/stories/test-story');
});

it('excludes unpublished stories', function () {
    Story::factory()->draft()->create(['slug' => 'draft-story']);

    $this->artisan('sitemap:generate');

    $content = file_get_contents(public_path('sitemap.xml'));

    expect($content)->not->toContain('/stories/draft-story');
});

it('includes published blog posts', function () {
    BlogPost::factory()->create(['slug' => 'test-blog-post']);

    $this->artisan('sitemap:generate');

    $content = file_get_contents(public_path('sitemap.xml'));

    expect($content)->toContain('/blog/test-blog-post');
});

it('excludes unpublished blog posts', function () {
    BlogPost::factory()->draft()->create(['slug' => 'draft-blog-post']);

    $this->artisan('sitemap:generate');

    $content = file_get_contents(public_path('sitemap.xml'));

    expect($content)->not->toContain('/blog/draft-blog-post');
});

it('includes published series', function () {
    Series::factory()->create(['slug' => 'test-series']);

    $this->artisan('sitemap:generate');

    $content = file_get_contents(public_path('sitemap.xml'));

    expect($content)->toContain('/series/test-series');
});

it('excludes unpublished series', function () {
    Series::factory()->draft()->create(['slug' => 'draft-series']);

    $this->artisan('sitemap:generate');

    $content = file_get_contents(public_path('sitemap.xml'));

    expect($content)->not->toContain('/series/draft-series');
});

afterEach(function () {
    $path = public_path('sitemap.xml');
    if (file_exists($path)) {
        unlink($path);
    }
});
