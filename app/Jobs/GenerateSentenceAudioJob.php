<?php

namespace App\Jobs;

use App\Models\StorySentence;
use App\Services\GoogleTtsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateSentenceAudioJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public StorySentence $storySentence) {}

    public function handle(GoogleTtsService $ttsService): void
    {
        if ($this->storySentence->audio_url !== null) {
            return;
        }

        $url = $ttsService->generateSentenceAudio(
            $this->storySentence->text_zh,
            $this->storySentence->id,
        );

        $this->storySentence->update(['audio_url' => $url]);
    }
}
