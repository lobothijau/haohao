<?php

namespace App\Console\Commands;

use App\Jobs\GenerateElevenLabsAudioJob;
use App\Models\DictionaryEntry;
use Illuminate\Console\Command;

class GenerateElevenLabsAudioCommand extends Command
{
    protected $signature = 'elevenlabs:generate
        {--limit=0 : Limit the number of records to process (0 = unlimited)}
        {--force : Regenerate audio even if it already exists}
        {--hsk= : Filter by HSK level (e.g. 1, 2, 3)}';

    protected $description = 'Generate ElevenLabs TTS audio for HSK dictionary entries';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');
        $hskLevel = $this->option('hsk');

        $query = DictionaryEntry::query()->whereNotNull('hsk_level');

        if ($hskLevel !== null) {
            $query->where('hsk_level', $hskLevel);
        }

        if (! $force) {
            $query->whereNull('audio_url');
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $entries = $query->get();

        $this->info("Dispatching ElevenLabs audio generation for {$entries->count()} entries...");

        $this->withProgressBar($entries, function (DictionaryEntry $entry) use ($force) {
            if ($force) {
                $entry->update(['audio_url' => null]);
            }
            GenerateElevenLabsAudioJob::dispatch($entry);
        });

        $this->newLine();
        $this->info('Audio generation jobs dispatched.');

        return self::SUCCESS;
    }
}
