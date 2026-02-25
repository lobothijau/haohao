<?php

use App\Filament\Resources\Series\Pages\CreateSeries;
use App\Filament\Resources\Series\Pages\EditSeries;
use App\Filament\Resources\Series\Pages\ListSeries;
use App\Models\Series;
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

it('can list series', function () {
    $series = Series::factory()->count(3)->create();

    Livewire::test(ListSeries::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($series);
});

it('can create a series', function () {
    Livewire::test(CreateSeries::class)
        ->fillForm([
            'title_zh' => '我的系列',
            'title_pinyin' => 'wǒ de xìliè',
            'title_id' => 'Seri Saya',
            'hsk_level' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Series::where('title_zh', '我的系列')->exists())->toBeTrue();
});

it('can edit a series', function () {
    $series = Series::factory()->create();

    Livewire::test(EditSeries::class, ['record' => $series->getRouteKey()])
        ->fillForm([
            'title_zh' => 'Updated Title',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $series->refresh();
    expect($series->title_zh)->toBe('Updated Title');
});

it('can delete a series', function () {
    $series = Series::factory()->create();

    Livewire::test(EditSeries::class, ['record' => $series->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect(Series::find($series->id))->toBeNull();
});

it('requires title fields', function () {
    Livewire::test(CreateSeries::class)
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
