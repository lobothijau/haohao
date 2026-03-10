<?php

use App\Jobs\ProcessStoryWithAiJob;
use App\Models\Story;
use App\Services\AiStoryParser;
use App\Services\StoryProcessingService;

it('calls parser then processing service', function () {
    $story = Story::factory()->create(['hsk_level' => 3]);
    $rawChinese = '你好。世界。';
    $hskLevel = 3;

    $parsed = [
        [
            'text_zh' => '你好。',
            'translation_id' => 'Halo.',
            'translation_en' => 'Hello.',
            'paragraph' => 1,
        ],
    ];

    $parser = $this->mock(AiStoryParser::class);
    $parser->shouldReceive('parse')
        ->once()
        ->with($rawChinese, $hskLevel)
        ->andReturn($parsed);

    $service = $this->mock(StoryProcessingService::class);
    $service->shouldReceive('processFromParsed')
        ->once()
        ->with(
            \Mockery::on(fn ($s) => $s->id === $story->id),
            $parsed,
        )
        ->andReturn([
            'sentence_count' => 1,
            'word_count' => 2,
            'unique_word_count' => 2,
            'difficulty_score' => 1.0,
            'estimated_minutes' => 1,
        ]);

    $job = new ProcessStoryWithAiJob($story, $rawChinese, $hskLevel);
    $job->handle($parser, $service);
});

it('is dispatched to the queue', function () {
    $story = Story::factory()->create();

    ProcessStoryWithAiJob::dispatch($story, '你好。', 3);

    expect(true)->toBeTrue();
});
