<?php

namespace App\Jobs;

use App\Models\Story;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateStoryAudioJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Story $story) {}

    public function handle(): void
    {
        $this->story->load('sentences.words.dictionaryEntry');

        foreach ($this->story->sentences as $sentence) {
            if ($sentence->audio_url === null) {
                GenerateSentenceAudioJob::dispatch($sentence);
            }

            foreach ($sentence->words as $word) {
                if ($word->dictionaryEntry->audio_url === null) {
                    GenerateWordAudioJob::dispatch($word->dictionaryEntry);
                }
            }
        }
    }
}
