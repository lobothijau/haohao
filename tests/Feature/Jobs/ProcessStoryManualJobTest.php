<?php

use App\Jobs\ProcessStoryManualJob;
use App\Models\Story;
use App\Services\StoryProcessingService;

it('calls processing service with correct arguments', function () {
    $story = Story::factory()->create();
    $rawChinese = '你好。世界。';
    $translationsId = ['Halo', 'Dunia'];
    $translationsEn = ['Hello', 'World'];

    $service = $this->mock(StoryProcessingService::class);
    $service->shouldReceive('process')
        ->once()
        ->with(
            \Mockery::on(fn ($s) => $s->id === $story->id),
            $rawChinese,
            $translationsId,
            $translationsEn,
        )
        ->andReturn([
            'sentence_count' => 2,
            'word_count' => 4,
            'unique_word_count' => 4,
            'difficulty_score' => 1.0,
            'estimated_minutes' => 1,
        ]);

    $job = new ProcessStoryManualJob($story, $rawChinese, $translationsId, $translationsEn);
    $job->handle($service);
});

it('passes empty English translations by default', function () {
    $story = Story::factory()->create();
    $rawChinese = '你好。';
    $translationsId = ['Halo'];

    $service = $this->mock(StoryProcessingService::class);
    $service->shouldReceive('process')
        ->once()
        ->with(
            \Mockery::on(fn ($s) => $s->id === $story->id),
            $rawChinese,
            $translationsId,
            [],
        )
        ->andReturn([
            'sentence_count' => 1,
            'word_count' => 2,
            'unique_word_count' => 2,
            'difficulty_score' => 1.0,
            'estimated_minutes' => 1,
        ]);

    $job = new ProcessStoryManualJob($story, $rawChinese, $translationsId);
    $job->handle($service);
});
