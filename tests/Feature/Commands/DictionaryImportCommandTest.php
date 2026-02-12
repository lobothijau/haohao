<?php

use App\Models\DictionaryEntry;

it('imports entries from a CC-CEDICT fixture file', function () {
    $this->artisan('dictionary:import', [
        'file' => base_path('tests/Fixtures/test-cedict.txt'),
    ])
        ->assertSuccessful();

    expect(DictionaryEntry::count())->toBe(15);
});

it('skips comment and empty lines', function () {
    $this->artisan('dictionary:import', [
        'file' => base_path('tests/Fixtures/test-cedict.txt'),
    ])
        ->assertSuccessful();

    // Comments should be skipped, only valid entries imported
    expect(DictionaryEntry::count())->toBe(15);
});

it('tags HSK levels from CSV', function () {
    $this->artisan('dictionary:import', [
        'file' => base_path('tests/Fixtures/test-cedict.txt'),
        '--hsk' => base_path('tests/Fixtures/test-hsk.csv'),
    ])
        ->assertSuccessful();

    $nihao = DictionaryEntry::where('simplified', '你好')->first();
    expect($nihao->hsk_level)->toBe(1);

    $zhongguo = DictionaryEntry::where('simplified', '中国')->first();
    expect($zhongguo->hsk_level)->toBe(2);
});

it('upserts duplicate entries', function () {
    // Import twice — should not create duplicates
    $this->artisan('dictionary:import', [
        'file' => base_path('tests/Fixtures/test-cedict.txt'),
    ])->assertSuccessful();

    $this->artisan('dictionary:import', [
        'file' => base_path('tests/Fixtures/test-cedict.txt'),
    ])->assertSuccessful();

    expect(DictionaryEntry::count())->toBe(15);
});

it('fails when file not found', function () {
    $this->artisan('dictionary:import', [
        'file' => '/nonexistent/file.txt',
    ])
        ->assertFailed();
});

it('fails when HSK file not found', function () {
    $this->artisan('dictionary:import', [
        'file' => base_path('tests/Fixtures/test-cedict.txt'),
        '--hsk' => '/nonexistent/hsk.csv',
    ])
        ->assertFailed();
});

it('sets meaning_en and leaves meaning_id null', function () {
    $this->artisan('dictionary:import', [
        'file' => base_path('tests/Fixtures/test-cedict.txt'),
    ])->assertSuccessful();

    $entry = DictionaryEntry::where('simplified', '好')->first();
    expect($entry->meaning_en)->toBe('good; well; proper');
    expect($entry->meaning_id)->toBeNull();
});

it('stores pinyin with tone marks and numbered form', function () {
    $this->artisan('dictionary:import', [
        'file' => base_path('tests/Fixtures/test-cedict.txt'),
    ])->assertSuccessful();

    $entry = DictionaryEntry::where('simplified', '你好')->first();
    expect($entry->pinyin)->toBe('nǐ hǎo');
    expect($entry->pinyin_numbered)->toBe('ni3 hao3');
});
