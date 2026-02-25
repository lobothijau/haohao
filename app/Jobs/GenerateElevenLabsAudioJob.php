<?php

namespace App\Jobs;

use App\Models\DictionaryEntry;
use App\Services\ElevenLabsTtsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateElevenLabsAudioJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public DictionaryEntry $dictionaryEntry) {}

    public function handle(ElevenLabsTtsService $ttsService): void
    {
        if ($this->dictionaryEntry->audio_url !== null) {
            return;
        }

        $url = $ttsService->generateWordAudio($this->dictionaryEntry->simplified);

        $this->dictionaryEntry->update(['audio_url' => $url]);
    }
}
