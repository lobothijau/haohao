<?php

use App\Jobs\GenerateSentenceAudioJob;
use App\Jobs\GenerateStoryAudioJob;
use App\Jobs\GenerateWordAudioJob;
use App\Models\DictionaryEntry;
use App\Models\SentenceWord;
use App\Models\Story;
use App\Models\StorySentence;
use App\Services\GoogleTtsService;
use Illuminate\Support\Facades\Queue;

it('generates word audio and updates dictionary entry', function () {
    $entry = DictionaryEntry::factory()->create(['audio_url' => null, 'simplified' => '你好']);

    $mockService = mock(GoogleTtsService::class);
    $mockService->shouldReceive('generateWordAudio')
        ->with('你好')
        ->once()
        ->andReturn('/storage/audio/words/test.mp3');

    app()->instance(GoogleTtsService::class, $mockService);

    (new GenerateWordAudioJob($entry))->handle($mockService);

    $entry->refresh();
    expect($entry->audio_url)->toBe('/storage/audio/words/test.mp3');
});

it('skips word audio generation when audio_url already set', function () {
    $entry = DictionaryEntry::factory()->create(['audio_url' => '/existing.mp3']);

    $mockService = mock(GoogleTtsService::class);
    $mockService->shouldNotReceive('generateWordAudio');

    (new GenerateWordAudioJob($entry))->handle($mockService);

    $entry->refresh();
    expect($entry->audio_url)->toBe('/existing.mp3');
});

it('generates sentence audio and updates story sentence', function () {
    $story = Story::factory()->create();
    $sentence = StorySentence::factory()->create([
        'story_id' => $story->id,
        'audio_url' => null,
        'text_zh' => '你好世界。',
    ]);

    $mockService = mock(GoogleTtsService::class);
    $mockService->shouldReceive('generateSentenceAudio')
        ->with('你好世界。', $sentence->id)
        ->once()
        ->andReturn('/storage/audio/sentences/sentence_1.mp3');

    (new GenerateSentenceAudioJob($sentence))->handle($mockService);

    $sentence->refresh();
    expect($sentence->audio_url)->toBe('/storage/audio/sentences/sentence_1.mp3');
});

it('skips sentence audio generation when audio_url already set', function () {
    $story = Story::factory()->create();
    $sentence = StorySentence::factory()->create([
        'story_id' => $story->id,
        'audio_url' => '/existing.mp3',
    ]);

    $mockService = mock(GoogleTtsService::class);
    $mockService->shouldNotReceive('generateSentenceAudio');

    (new GenerateSentenceAudioJob($sentence))->handle($mockService);

    $sentence->refresh();
    expect($sentence->audio_url)->toBe('/existing.mp3');
});

it('dispatches word and sentence jobs for a story', function () {
    Queue::fake();

    $story = Story::factory()->create();
    $sentence = StorySentence::factory()->create([
        'story_id' => $story->id,
        'audio_url' => null,
    ]);

    $entry = DictionaryEntry::factory()->create(['audio_url' => null]);
    SentenceWord::factory()->create([
        'story_sentence_id' => $sentence->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    (new GenerateStoryAudioJob($story))->handle();

    Queue::assertPushed(GenerateSentenceAudioJob::class);
    Queue::assertPushed(GenerateWordAudioJob::class);
});

it('does not dispatch jobs for entries that already have audio', function () {
    Queue::fake();

    $story = Story::factory()->create();
    $sentence = StorySentence::factory()->create([
        'story_id' => $story->id,
        'audio_url' => '/existing.mp3',
    ]);

    $entry = DictionaryEntry::factory()->create(['audio_url' => '/existing.mp3']);
    SentenceWord::factory()->create([
        'story_sentence_id' => $sentence->id,
        'dictionary_entry_id' => $entry->id,
    ]);

    (new GenerateStoryAudioJob($story))->handle();

    Queue::assertNotPushed(GenerateSentenceAudioJob::class);
    Queue::assertNotPushed(GenerateWordAudioJob::class);
});
