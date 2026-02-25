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

it('creates stub dictionary entries for unknown words', function () {
    $story = Story::factory()->create();

    $service = app(StoryProcessingService::class);
    $service->process(
        $story,
        '未知。',
        ['Tidak diketahui'],
    );

    // '未' and '知' should have stub entries created
    expect(DictionaryEntry::where('simplified', '未')->exists())->toBeTrue();
    expect(DictionaryEntry::where('simplified', '知')->exists())->toBeTrue();
});

it('generates sentence pinyin from word-level dictionary lookups', function () {
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
    expect($sentence->text_pinyin)->toBe('nǐ hǎo');
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

it('processes from parsed AI output with provided pinyin', function () {
    DictionaryEntry::factory()->create(['simplified' => '你', 'pinyin' => 'nǐ']);
    DictionaryEntry::factory()->create(['simplified' => '好', 'pinyin' => 'hǎo']);

    $story = Story::factory()->create();
    $service = app(StoryProcessingService::class);

    $parsed = [
        [
            'text_zh' => '你好。',
            'text_pinyin' => 'Nǐ hǎo.',
            'translation_id' => 'Halo.',
            'translation_en' => 'Hello.',
        ],
    ];

    $stats = $service->processFromParsed($story, $parsed);

    expect($stats['sentence_count'])->toBe(1);
    expect($stats['word_count'])->toBe(2);

    $sentence = StorySentence::where('story_id', $story->id)->first();
    expect($sentence->text_zh)->toBe('你好。');
    expect($sentence->text_pinyin)->toBe('Nǐ hǎo.');
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
            'text_pinyin' => 'Xiǎo Míng.',
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
            'text_pinyin' => 'Nǐ hǎo.',
            'translation_id' => 'Halo.',
            'translation_en' => 'Hello.',
        ],
        [
            'text_zh' => '世界。',
            'text_pinyin' => 'Shìjiè.',
            'translation_id' => 'Dunia.',
            'translation_en' => 'World.',
        ],
    ];

    $service->processFromParsed($story, $parsed);
    expect(StorySentence::where('story_id', $story->id)->count())->toBe(2);
});
