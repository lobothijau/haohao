<?php

use App\Filament\Resources\Stories\Pages\CreateStory;
use App\Filament\Resources\Stories\Pages\EditStory;
use App\Filament\Resources\Stories\Pages\ListStories;
use App\Models\Category;
use App\Models\Story;
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

it('can list stories', function () {
    $stories = Story::factory()->count(3)->create();

    Livewire::test(ListStories::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($stories);
});

it('can create a story', function () {
    Livewire::test(CreateStory::class)
        ->fillForm([
            'title_zh' => '我的故事',
            'title_pinyin' => 'wǒ de gùshì',
            'title_id' => 'Cerita Saya',
            'hsk_level' => 1,
            'content_source' => 'manual',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Story::where('title_zh', '我的故事')->exists())->toBeTrue();
});

it('can edit a story', function () {
    $story = Story::factory()->create();

    Livewire::test(EditStory::class, ['record' => $story->getRouteKey()])
        ->fillForm([
            'title_zh' => 'Updated Title',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $story->refresh();
    expect($story->title_zh)->toBe('Updated Title');
});

it('can assign categories to a story', function () {
    $story = Story::factory()->create();
    $categories = Category::factory()->count(2)->create();

    Livewire::test(EditStory::class, ['record' => $story->getRouteKey()])
        ->fillForm([
            'categories' => $categories->pluck('id')->toArray(),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $story->refresh();
    expect($story->categories)->toHaveCount(2);
});

it('sets published_at when is_published toggled on', function () {
    $story = Story::factory()->draft()->create();

    Livewire::test(EditStory::class, ['record' => $story->getRouteKey()])
        ->fillForm([
            'is_published' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $story->refresh();
    expect($story->is_published)->toBeTrue();
});

it('can delete a story', function () {
    $story = Story::factory()->create();

    Livewire::test(EditStory::class, ['record' => $story->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect(Story::find($story->id))->toBeNull();
});

it('displays processing stats on edit page', function () {
    $story = Story::factory()->create([
        'sentence_count' => 5,
        'word_count' => 50,
        'unique_word_count' => 30,
        'difficulty_score' => 2.5,
        'estimated_minutes' => 3,
    ]);

    Livewire::test(EditStory::class, ['record' => $story->getRouteKey()])
        ->assertSuccessful();
});

it('requires title fields', function () {
    Livewire::test(CreateStory::class)
        ->fillForm([
            'title_zh' => '',
            'title_pinyin' => '',
            'title_id' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'title_zh' => 'required',
            'title_pinyin' => 'required',
            'title_id' => 'required',
        ]);
});
