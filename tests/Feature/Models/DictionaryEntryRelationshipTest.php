<?php

use App\Models\DictionaryEntry;
use App\Models\DictionaryExample;
use App\Models\SentenceWord;
use App\Models\StorySentence;
use App\Models\UserVocabulary;

it('has examples relationship', function () {
    $entry = DictionaryEntry::factory()->create();
    DictionaryExample::factory()->create(['dictionary_entry_id' => $entry->id]);

    expect($entry->examples)->toHaveCount(1)
        ->and($entry->examples->first())->toBeInstanceOf(DictionaryExample::class);
});

it('has sentence words relationship', function () {
    $entry = DictionaryEntry::factory()->create();
    $sentence = StorySentence::factory()->create();
    SentenceWord::factory()->create([
        'story_sentence_id' => $sentence->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    expect($entry->sentenceWords)->toHaveCount(1)
        ->and($entry->sentenceWords->first())->toBeInstanceOf(SentenceWord::class);
});

it('has user vocabularies relationship', function () {
    $entry = DictionaryEntry::factory()->create();
    UserVocabulary::factory()->create(['dictionary_entry_id' => $entry->id]);

    expect($entry->userVocabularies)->toHaveCount(1)
        ->and($entry->userVocabularies->first())->toBeInstanceOf(UserVocabulary::class);
});
