<?php

use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

it('can list blog posts', function () {
    $posts = BlogPost::factory()->count(3)->create();

    Livewire::test(ListBlogPosts::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($posts);
});

it('can create a blog post', function () {
    Livewire::test(CreateBlogPost::class)
        ->fillForm([
            'title' => 'Cara Belajar Mandarin',
            'body' => '<p>Konten artikel</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(BlogPost::where('title', 'Cara Belajar Mandarin')->exists())->toBeTrue();
});

it('can edit a blog post', function () {
    $post = BlogPost::factory()->create();

    Livewire::test(EditBlogPost::class, ['record' => $post->getRouteKey()])
        ->fillForm([
            'title' => 'Updated Title',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $post->refresh();
    expect($post->title)->toBe('Updated Title');
});

it('can delete a blog post', function () {
    $post = BlogPost::factory()->create();

    Livewire::test(EditBlogPost::class, ['record' => $post->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect(BlogPost::find($post->id))->toBeNull();
});

it('requires title', function () {
    Livewire::test(CreateBlogPost::class)
        ->fillForm([
            'title' => '',
            'body' => '<p>Some content</p>',
        ])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required']);
});

it('requires body', function () {
    Livewire::test(CreateBlogPost::class)
        ->fillForm([
            'title' => 'Some title',
            'body' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['body']);
});

it('can assign a category to a blog post', function () {
    $category = BlogCategory::factory()->create();

    Livewire::test(CreateBlogPost::class)
        ->fillForm([
            'title' => 'Post with Category',
            'body' => '<p>Content</p>',
            'blog_category_id' => $category->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = BlogPost::where('title', 'Post with Category')->first();
    expect($post->blog_category_id)->toBe($category->id);
});
