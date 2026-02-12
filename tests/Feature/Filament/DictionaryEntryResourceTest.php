<?php

use App\Filament\Resources\DictionaryEntries\DictionaryEntryResource;
use App\Filament\Resources\DictionaryEntries\Pages\EditDictionaryEntry;
use App\Filament\Resources\DictionaryEntries\Pages\ListDictionaryEntries;
use App\Models\DictionaryEntry;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

it('can list dictionary entries', function () {
    $entries = DictionaryEntry::factory()->count(3)->create();

    Livewire::test(ListDictionaryEntries::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($entries);
});

it('can search by simplified', function () {
    DictionaryEntry::factory()->create(['simplified' => '你好']);
    DictionaryEntry::factory()->create(['simplified' => '世界']);

    Livewire::test(ListDictionaryEntries::class)
        ->searchTable('你好')
        ->assertCanSeeTableRecords(DictionaryEntry::where('simplified', '你好')->get())
        ->assertCanNotSeeTableRecords(DictionaryEntry::where('simplified', '世界')->get());
});

it('can filter by HSK level', function () {
    $hsk1 = DictionaryEntry::factory()->create(['hsk_level' => 1]);
    $hsk3 = DictionaryEntry::factory()->create(['hsk_level' => 3]);

    Livewire::test(ListDictionaryEntries::class)
        ->filterTable('hsk_level', 1)
        ->assertCanSeeTableRecords([$hsk1])
        ->assertCanNotSeeTableRecords([$hsk3]);
});

it('can edit a dictionary entry', function () {
    $entry = DictionaryEntry::factory()->create();

    Livewire::test(EditDictionaryEntry::class, ['record' => $entry->getRouteKey()])
        ->fillForm([
            'meaning_id' => 'Terjemahan baru',
            'meaning_en' => 'New translation',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $entry->refresh();
    expect($entry->meaning_id)->toBe('Terjemahan baru');
    expect($entry->meaning_en)->toBe('New translation');
});

it('does not have a create page', function () {
    expect(DictionaryEntryResource::getPages())
        ->not->toHaveKey('create');
});

it('has pagination set to 25 per page', function () {
    DictionaryEntry::factory()->count(30)->create();

    Livewire::test(ListDictionaryEntries::class)
        ->assertSuccessful();
});
