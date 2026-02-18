<?php

use App\Jobs\GenerateStoryAudioJob;
use App\Models\Story;
use App\Services\ChineseSegmenter;
use App\Services\StoryProcessingService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->mock(ChineseSegmenter::class, function ($mock) {
        $mock->shouldReceive('segment')
            ->andReturnUsing(function (string $text) {
                return mb_str_split(preg_replace('/[。！？；\s]/u', '', $text));
            });
    });
});

it('dispatches audio generation job after processing a story', function () {
    Queue::fake();

    $story = Story::factory()->create();

    $service = app(StoryProcessingService::class);
    $service->process(
        $story,
        '你好。',
        ['Halo'],
    );

    Queue::assertPushed(GenerateStoryAudioJob::class, function ($job) use ($story) {
        return $job->story->id === $story->id;
    });
});
