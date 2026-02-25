<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;

it('belongs to a category', function () {
    $category = BlogCategory::factory()->create();
    $post = BlogPost::factory()->create(['blog_category_id' => $category->id]);

    expect($post->category)->toBeInstanceOf(BlogCategory::class);
    expect($post->category->id)->toBe($category->id);
});

it('belongs to a creator', function () {
    $user = User::factory()->create();
    $post = BlogPost::factory()->create(['created_by' => $user->id]);

    expect($post->creator)->toBeInstanceOf(User::class);
    expect($post->creator->id)->toBe($user->id);
});

it('auto-generates a slug from the title', function () {
    $post = BlogPost::factory()->create(['title' => 'Cara Belajar Mandarin', 'slug' => null]);

    expect($post->slug)->not->toBeEmpty();
    expect($post->slug)->toContain('cara-belajar-mandarin');
});

it('category has many posts', function () {
    $category = BlogCategory::factory()->create();
    BlogPost::factory()->count(3)->create(['blog_category_id' => $category->id]);

    expect($category->posts)->toHaveCount(3);
});

it('casts is_published to boolean', function () {
    $post = BlogPost::factory()->create(['is_published' => true]);

    expect($post->is_published)->toBeBool();
    expect($post->is_published)->toBeTrue();
});

it('casts published_at to datetime', function () {
    $post = BlogPost::factory()->create(['published_at' => '2026-01-01 12:00:00']);

    expect($post->published_at)->toBeInstanceOf(\Carbon\CarbonImmutable::class);
});
