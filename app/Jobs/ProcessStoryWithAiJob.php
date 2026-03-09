<?php

namespace App\Jobs;

use App\Models\Story;
use App\Services\AiStoryParser;
use App\Services\StoryProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessStoryWithAiJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public Story $story,
        public string $rawChinese,
        public int $hskLevel,
    ) {}

    public function handle(AiStoryParser $parser, StoryProcessingService $service): void
    {
        Log::info('ProcessStoryWithAiJob: starting', ['story_id' => $this->story->id]);

        $parsed = $parser->parse($this->rawChinese, $this->hskLevel);

        $service->processFromParsed($this->story, $parsed);

        Log::info('ProcessStoryWithAiJob: completed', ['story_id' => $this->story->id]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('ProcessStoryWithAiJob: failed', [
            'story_id' => $this->story->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
