<?php

use App\Models\DictionaryEntry;
use App\Models\SentenceWord;
use App\Models\Story;
use App\Models\StorySentence;
use App\Services\ChineseSegmenter;
use App\Services\StoryProcessingService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // Fake the queue to prevent audio generation jobs from running
    Queue::fake();

    // Mock the ChineseSegmenter to avoid Python dependency in CI
    $this->mock(ChineseSegmenter::class, function ($mock) {
        $mock->shouldReceive('segment')
            ->andReturnUsing(function (string $text) {
                // Simple character-by-character split for testing
                return mb_str_split(preg_replace('/[。！？；\s]/u', '', $text));
            });
    });
});

it('creates sentences from Chinese text', function () {
    $story = Story::factory()->create();

    $service = app(StoryProcessingService::class);
    $stats = $service->process(
        $story,
        '你好。世界。',
        ['Halo', 'Dunia'],
    );

    expect(StorySentence::where('story_id', $story->id)->count())->toBe(2);
    expect($stats['sentence_count'])->toBe(2);
});

it('creates sentence words linked to dictionary entries', function () {
    // Pre-create dictionary entries
    DictionaryEntry::factory()->create(['simplified' => '你', 'pinyin' => 'nǐ']);
    DictionaryEntry::factory()->create(['simplified' => '好', 'pinyin' => 'hǎo']);

    $story = Story::factory()->create();

    $service = app(StoryProcessingService::class);
    $service->process(
        $story,
        '你好。',
        ['Halo'],
    );

    $sentence = StorySentence::where('story_id', $story->id)->first();
    $words = SentenceWord::where('story_sentence_id', $sentence->id)->get();

    expect($words)->toHaveCount(2);
    expect($words[0]->surface_form)->toBe('你');
    expect($words[1]->surface_form)->toBe('好');
});

it('skips words not found in dictionary without creating stubs', function () {
    $story = Story::factory()->create();

    $service = app(StoryProcessingService::class);
    $service->process(
        $story,
        '未知。',
        ['Tidak diketahui'],
    );

    // No stub entries should be created — words not in CC-CEDICT are skipped
    $sentence = StorySentence::where('story_id', $story->id)->first();
    $words = SentenceWord::where('story_sentence_id', $sentence->id)->get();
    expect($words)->toHaveCount(0);
    expect(DictionaryEntry::where('simplified', '未')->exists())->toBeFalse();
    expect(DictionaryEntry::where('simplified', '知')->exists())->toBeFalse();
});

it('ignores existing stub entries and splits into characters instead', function () {
    // Simulate a pre-existing stub entry where pinyin is hanzi (from old processing)
    DictionaryEntry::factory()->create(['simplified' => '家有', 'pinyin' => '家有']);

    // Real dictionary entries for individual characters
    DictionaryEntry::factory()->create(['simplified' => '家', 'pinyin' => 'jiā']);
    DictionaryEntry::factory()->create(['simplified' => '有', 'pinyin' => 'yǒu']);

    $this->mock(ChineseSegmenter::class, function ($mock) {
        $mock->shouldReceive('segment')->andReturn(['家有']);
    });

    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $service->process($story, '家有。', ['Rumah punya']);

    $sentence = StorySentence::where('story_id', $story->id)->first();
    $words = SentenceWord::where('story_sentence_id', $sentence->id)->orderBy('position')->get();

    // Should split into characters, not use the stub
    expect($words)->toHaveCount(2);
    expect($words[0]->surface_form)->toBe('家');
    expect($words[1]->surface_form)->toBe('有');
});

it('calculates story stats correctly', function () {
    DictionaryEntry::factory()->create(['simplified' => '你', 'pinyin' => 'nǐ', 'hsk_level' => 1]);
    DictionaryEntry::factory()->create(['simplified' => '好', 'pinyin' => 'hǎo', 'hsk_level' => 1]);

    $story = Story::factory()->create();

    $service = app(StoryProcessingService::class);
    $stats = $service->process(
        $story,
        '你好。',
        ['Halo'],
    );

    expect($stats['word_count'])->toBe(2);
    expect($stats['unique_word_count'])->toBe(2);
    expect($stats['sentence_count'])->toBe(1);
    expect($stats['estimated_minutes'])->toBeGreaterThanOrEqual(1);

    $story->refresh();
    expect($story->word_count)->toBe(2);
    expect($story->unique_word_count)->toBe(2);
    expect($story->sentence_count)->toBe(1);
});

it('supports re-processing by deleting existing sentences', function () {
    DictionaryEntry::factory()->create(['simplified' => '你', 'pinyin' => 'nǐ']);
    DictionaryEntry::factory()->create(['simplified' => '好', 'pinyin' => 'hǎo']);

    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    // First processing
    $service->process($story, '你好。', ['Halo']);
    expect(StorySentence::where('story_id', $story->id)->count())->toBe(1);

    // Re-processing with different content
    $service->process($story, '你好。好。', ['Halo', 'Baik']);
    expect(StorySentence::where('story_id', $story->id)->count())->toBe(2);
});

it('throws exception on translation count mismatch', function () {
    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $service->process(
        $story,
        '你好。世界。',
        ['Only one translation'],
    );
})->throws(InvalidArgumentException::class, 'Translation count mismatch');

it('stores English translations when provided', function () {
    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $service->process(
        $story,
        '你好。',
        ['Halo'],
        ['Hello'],
    );

    $sentence = StorySentence::where('story_id', $story->id)->first();
    expect($sentence->translation_id)->toBe('Halo');
    expect($sentence->translation_en)->toBe('Hello');
});

it('calculates difficulty score from HSK levels', function () {
    DictionaryEntry::factory()->create(['simplified' => '你', 'pinyin' => 'nǐ', 'hsk_level' => 1]);
    DictionaryEntry::factory()->create(['simplified' => '好', 'pinyin' => 'hǎo', 'hsk_level' => 3]);

    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $stats = $service->process($story, '你好。', ['Halo']);

    // Average of HSK 1 and 3 = 2.0
    expect($stats['difficulty_score'])->toBe(2.0);
});

it('processes from parsed AI output', function () {
    DictionaryEntry::factory()->create(['simplified' => '你', 'pinyin' => 'nǐ']);
    DictionaryEntry::factory()->create(['simplified' => '好', 'pinyin' => 'hǎo']);

    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $parsed = [
        [
            'text_zh' => '你好。',
            'translation_id' => 'Halo.',
            'translation_en' => 'Hello.',
        ],
    ];

    $stats = $service->processFromParsed($story, $parsed);

    expect($stats['sentence_count'])->toBe(1);
    expect($stats['word_count'])->toBe(2);

    $sentence = StorySentence::where('story_id', $story->id)->first();
    expect($sentence->text_zh)->toBe('你好。');
    expect($sentence->translation_id)->toBe('Halo.');
    expect($sentence->translation_en)->toBe('Hello.');
});

it('processFromParsed creates sentence words and stats', function () {
    DictionaryEntry::factory()->create(['simplified' => '小', 'pinyin' => 'xiǎo', 'hsk_level' => 1]);
    DictionaryEntry::factory()->create(['simplified' => '明', 'pinyin' => 'míng', 'hsk_level' => 2]);

    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $parsed = [
        [
            'text_zh' => '小明。',
            'translation_id' => 'Xiao Ming.',
            'translation_en' => 'Xiao Ming.',
        ],
    ];

    $stats = $service->processFromParsed($story, $parsed);

    expect($stats['word_count'])->toBe(2);
    expect($stats['unique_word_count'])->toBe(2);

    $story->refresh();
    expect($story->word_count)->toBe(2);
    expect($story->sentence_count)->toBe(1);
});

it('process assigns paragraph numbers from blank lines', function () {
    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $rawChinese = "你好。\n\n世界。";
    $service->process($story, $rawChinese, ['Halo', 'Dunia']);

    $sentences = StorySentence::where('story_id', $story->id)->orderBy('position')->get();
    expect($sentences)->toHaveCount(2);
    expect($sentences[0]->paragraph)->toBe(1);
    expect($sentences[1]->paragraph)->toBe(2);
});

it('processFromParsed uses paragraph field from parsed data', function () {
    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $parsed = [
        [
            'text_zh' => '你好。',
            'translation_id' => 'Halo.',
            'translation_en' => 'Hello.',
            'paragraph' => 1,
        ],
        [
            'text_zh' => '世界。',
            'translation_id' => 'Dunia.',
            'translation_en' => 'World.',
            'paragraph' => 2,
        ],
    ];

    $service->processFromParsed($story, $parsed);

    $sentences = StorySentence::where('story_id', $story->id)->orderBy('position')->get();
    expect($sentences[0]->paragraph)->toBe(1);
    expect($sentences[1]->paragraph)->toBe(2);
});

it('processFromParsed defaults paragraph to 1 when not provided', function () {
    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $parsed = [
        [
            'text_zh' => '你好。',
            'translation_id' => 'Halo.',
            'translation_en' => 'Hello.',
        ],
    ];

    $service->processFromParsed($story, $parsed);

    $sentence = StorySentence::where('story_id', $story->id)->first();
    expect($sentence->paragraph)->toBe(1);
});

it('splits unknown compound words into individual characters with correct pinyin', function () {
    // Simulate jieba returning a compound "家有" that is NOT in the dictionary
    // but individual characters "家" and "有" ARE in the dictionary
    $this->mock(ChineseSegmenter::class, function ($mock) {
        $mock->shouldReceive('segment')
            ->andReturn(['家有']);
    });

    DictionaryEntry::factory()->create(['simplified' => '家', 'pinyin' => 'jiā']);
    DictionaryEntry::factory()->create(['simplified' => '有', 'pinyin' => 'yǒu']);

    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $service->process($story, '家有。', ['Rumah punya']);

    $sentence = StorySentence::where('story_id', $story->id)->first();
    $words = SentenceWord::where('story_sentence_id', $sentence->id)->orderBy('position')->get();

    // Should be split into two separate words, not kept as one compound
    expect($words)->toHaveCount(2);
    expect($words[0]->surface_form)->toBe('家');
    expect($words[1]->surface_form)->toBe('有');

});

it('skips unknown words not found in dictionary', function () {
    $this->mock(ChineseSegmenter::class, function ($mock) {
        $mock->shouldReceive('segment')
            ->andReturn(['罕词']);
    });

    // Neither character is in the dictionary
    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $service->process($story, '罕词。', ['Kata langka']);

    $sentence = StorySentence::where('story_id', $story->id)->first();
    $words = SentenceWord::where('story_sentence_id', $sentence->id)->get();

    // Word should be skipped entirely — no SentenceWord, no stub DictionaryEntry
    expect($words)->toHaveCount(0);
    expect(DictionaryEntry::where('simplified', '罕词')->exists())->toBeFalse();
});

it('processFromParsed replaces existing sentences', function () {
    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    // First processing via manual
    $service->process($story, '你好。', ['Halo']);
    expect(StorySentence::where('story_id', $story->id)->count())->toBe(1);

    // Re-process via parsed
    $parsed = [
        [
            'text_zh' => '你好。',
            'translation_id' => 'Halo.',
            'translation_en' => 'Hello.',
        ],
        [
            'text_zh' => '世界。',
            'translation_id' => 'Dunia.',
            'translation_en' => 'World.',
        ],
    ];

    $service->processFromParsed($story, $parsed);
    expect(StorySentence::where('story_id', $story->id)->count())->toBe(2);
});
