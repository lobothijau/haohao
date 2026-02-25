<?php

use App\Filament\Resources\BlogCategories\Pages\CreateBlogCategory;
use App\Filament\Resources\BlogCategories\Pages\EditBlogCategory;
use App\Filament\Resources\BlogCategories\Pages\ListBlogCategories;
use App\Models\BlogCategory;
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

it('can list blog categories', function () {
    $categories = BlogCategory::factory()->count(3)->create();

    Livewire::test(ListBlogCategories::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($categories);
});

it('can create a blog category', function () {
    Livewire::test(CreateBlogCategory::class)
        ->fillForm([
            'name' => 'Tips Belajar',
            'description' => 'Tips belajar Mandarin',
            'sort_order' => 5,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(BlogCategory::where('name', 'Tips Belajar')->exists())->toBeTrue();
});

it('can edit a blog category', function () {
    $category = BlogCategory::factory()->create();

    Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $category->refresh();
    expect($category->name)->toBe('Updated Name');
});

it('can delete a blog category', function () {
    $category = BlogCategory::factory()->create();

    Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect(BlogCategory::find($category->id))->toBeNull();
});

it('auto-generates slug from name', function () {
    Livewire::test(CreateBlogCategory::class)
        ->fillForm([
            'name' => 'Tips Belajar Mandarin',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = BlogCategory::where('name', 'Tips Belajar Mandarin')->first();
    expect($category->slug)->not->toBeEmpty();
});

it('requires name', function () {
    Livewire::test(CreateBlogCategory::class)
        ->fillForm([
            'name' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});
