<?php

namespace App\Jobs;

use App\Models\Story;
use App\Services\StoryProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessStoryManualJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    /**
     * @param  list<string>  $translationsId
     * @param  list<string>  $translationsEn
     */
    public function __construct(
        public Story $story,
        public string $rawChinese,
        public array $translationsId,
        public array $translationsEn = [],
    ) {}

    public function handle(StoryProcessingService $service): void
    {
        Log::info('ProcessStoryManualJob: starting', ['story_id' => $this->story->id]);

        $service->process(
            $this->story,
            $this->rawChinese,
            $this->translationsId,
            $this->translationsEn,
        );

        Log::info('ProcessStoryManualJob: completed', ['story_id' => $this->story->id]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('ProcessStoryManualJob: failed', [
            'story_id' => $this->story->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
