<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;

it('displays the blog index page', function () {
    BlogPost::factory()->count(3)->create();

    $response = $this->get(route('blog.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Blog/Index')
        ->has('posts.data', 3)
        ->has('categories')
        ->has('filters')
    );
});

it('only shows published blog posts', function () {
    BlogPost::factory()->count(2)->create();
    BlogPost::factory()->draft()->create();

    $response = $this->get(route('blog.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('posts.data', 2)
    );
});

it('filters blog posts by category', function () {
    $category = BlogCategory::factory()->create();
    BlogPost::factory()->create(['blog_category_id' => $category->id]);
    BlogPost::factory()->create();

    $response = $this->get(route('blog.index', ['category' => $category->slug]));

    $response->assertInertia(fn ($page) => $page
        ->has('posts.data', 1)
    );
});

it('searches blog posts by title', function () {
    BlogPost::factory()->create(['title' => 'Cara Belajar Mandarin']);
    BlogPost::factory()->create(['title' => 'Resep Masakan']);

    $response = $this->get(route('blog.index', ['search' => 'Mandarin']));

    $response->assertInertia(fn ($page) => $page
        ->has('posts.data', 1)
    );
});

it('searches blog posts by excerpt', function () {
    BlogPost::factory()->create(['title' => 'Post A', 'excerpt' => 'Belajar kosakata dasar']);
    BlogPost::factory()->create(['title' => 'Post B', 'excerpt' => 'Tentang budaya']);

    $response = $this->get(route('blog.index', ['search' => 'kosakata']));

    $response->assertInertia(fn ($page) => $page
        ->has('posts.data', 1)
    );
});

it('paginates blog posts at 12 per page', function () {
    BlogPost::factory()->count(15)->create();

    $response = $this->get(route('blog.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('posts.data', 12)
        ->where('posts.last_page', 2)
    );
});

it('displays the blog show page', function () {
    $post = BlogPost::factory()->create();

    $response = $this->get(route('blog.show', $post->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Blog/Show')
        ->has('post')
        ->has('relatedPosts')
        ->has('seo')
    );
});

it('returns 404 for unpublished blog posts', function () {
    $post = BlogPost::factory()->draft()->create();

    $response = $this->get(route('blog.show', $post->slug));

    $response->assertNotFound();
});

it('includes seo data on the show page', function () {
    $post = BlogPost::factory()->create([
        'meta_title' => 'Custom SEO Title',
        'meta_description' => 'Custom SEO Description',
    ]);

    $response = $this->get(route('blog.show', $post->slug));

    $response->assertInertia(fn ($page) => $page
        ->where('seo.title', 'Custom SEO Title')
        ->where('seo.description', 'Custom SEO Description')
        ->where('seo.type', 'article')
    );
});

it('falls back to title and excerpt for seo when meta fields are null', function () {
    $post = BlogPost::factory()->create([
        'title' => 'My Blog Title',
        'excerpt' => 'My blog excerpt',
        'meta_title' => null,
        'meta_description' => null,
    ]);

    $response = $this->get(route('blog.show', $post->slug));

    $response->assertInertia(fn ($page) => $page
        ->where('seo.title', 'My Blog Title')
        ->where('seo.description', 'My blog excerpt')
    );
});

it('shows related posts from the same category', function () {
    $category = BlogCategory::factory()->create();
    $post = BlogPost::factory()->create(['blog_category_id' => $category->id]);
    BlogPost::factory()->count(3)->create(['blog_category_id' => $category->id]);
    BlogPost::factory()->create(); // different category

    $response = $this->get(route('blog.show', $post->slug));

    $response->assertInertia(fn ($page) => $page
        ->has('relatedPosts', 3)
    );
});

it('loads category and creator on show page', function () {
    $user = User::factory()->create();
    $category = BlogCategory::factory()->create();
    $post = BlogPost::factory()->create([
        'blog_category_id' => $category->id,
        'created_by' => $user->id,
    ]);

    $response = $this->get(route('blog.show', $post->slug));

    $response->assertInertia(fn ($page) => $page
        ->where('post.category.id', $category->id)
        ->where('post.creator.id', $user->id)
    );
});
