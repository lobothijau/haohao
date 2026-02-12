<?php

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
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

it('can list categories', function () {
    $categories = Category::factory()->count(3)->create();

    Livewire::test(ListCategories::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($categories);
});

it('can create a category', function () {
    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name_id' => 'Makanan',
            'name_en' => 'Food',
            'icon' => 'utensils',
            'sort_order' => 5,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Category::where('name_id', 'Makanan')->exists())->toBeTrue();
});

it('can edit a category', function () {
    $category = Category::factory()->create();

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm([
            'name_id' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $category->refresh();
    expect($category->name_id)->toBe('Updated Name');
});

it('can delete a category', function () {
    $category = Category::factory()->create();

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect(Category::find($category->id))->toBeNull();
});

it('auto-generates slug from name_id', function () {
    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name_id' => 'Kehidupan Sehari-hari',
            'name_en' => 'Daily Life',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = Category::where('name_id', 'Kehidupan Sehari-hari')->first();
    expect($category->slug)->not->toBeEmpty();
});

it('requires name_id', function () {
    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name_id' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['name_id' => 'required']);
});
