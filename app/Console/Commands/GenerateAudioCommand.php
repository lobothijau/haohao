<?php

namespace App\Console\Commands;

use App\Jobs\GenerateSentenceAudioJob;
use App\Jobs\GenerateStoryAudioJob;
use App\Jobs\GenerateWordAudioJob;
use App\Models\DictionaryEntry;
use App\Models\Story;
use App\Models\StorySentence;
use Illuminate\Console\Command;

class GenerateAudioCommand extends Command
{
    protected $signature = 'audio:generate
        {--type=all : Type of audio to generate (words, sentences, all)}
        {--story= : Generate audio for a specific story ID}
        {--limit=0 : Limit the number of records to process (0 = unlimited)}
        {--force : Regenerate audio even if it already exists}';

    protected $description = 'Generate TTS audio for dictionary words and story sentences';

    public function handle(): int
    {
        $type = $this->option('type');
        $storyId = $this->option('story');
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        if ($storyId) {
            return $this->generateForStory((int) $storyId);
        }

        if (in_array($type, ['words', 'all'])) {
            $this->generateWords($limit, $force);
        }

        if (in_array($type, ['sentences', 'all'])) {
            $this->generateSentences($limit, $force);
        }

        $this->info('Audio generation jobs dispatched.');

        return self::SUCCESS;
    }

    private function generateForStory(int $storyId): int
    {
        $story = Story::find($storyId);

        if (! $story) {
            $this->error("Story not found: {$storyId}");

            return self::FAILURE;
        }

        $this->info("Dispatching audio generation for story: {$story->title_id}");
        GenerateStoryAudioJob::dispatch($story);

        $this->info('Story audio generation job dispatched.');

        return self::SUCCESS;
    }

    private function generateWords(int $limit, bool $force): void
    {
        $query = DictionaryEntry::query();

        if (! $force) {
            $query->whereNull('audio_url');
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $entries = $query->get();

        $this->info("Dispatching audio generation for {$entries->count()} words...");

        $this->withProgressBar($entries, function (DictionaryEntry $entry) use ($force) {
            if ($force) {
                $entry->update(['audio_url' => null]);
            }
            GenerateWordAudioJob::dispatch($entry);
        });

        $this->newLine();
    }

    private function generateSentences(int $limit, bool $force): void
    {
        $query = StorySentence::query();

        if (! $force) {
            $query->whereNull('audio_url');
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $sentences = $query->get();

        $this->info("Dispatching audio generation for {$sentences->count()} sentences...");

        $this->withProgressBar($sentences, function (StorySentence $sentence) use ($force) {
            if ($force) {
                $sentence->update(['audio_url' => null]);
            }
            GenerateSentenceAudioJob::dispatch($sentence);
        });

        $this->newLine();
    }
}
