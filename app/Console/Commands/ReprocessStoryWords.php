<?php

namespace App\Console\Commands;

use App\Models\Story;
use App\Models\StorySentence;
use App\Services\ChineseSegmenter;
use App\Services\StoryProcessingService;
use Illuminate\Console\Command;

class ReprocessStoryWords extends Command
{
    protected $signature = 'app:reprocess-story-words
                            {story? : The story ID to reprocess (omit for all stories)}
                            {--all : Reprocess all stories}';

    protected $description = 'Re-segment and re-link words for existing stories using updated dictionary logic';

    public function handle(ChineseSegmenter $segmenter, StoryProcessingService $service): int
    {
        if ($this->argument('story')) {
            $stories = Story::where('id', $this->argument('story'))->get();
        } elseif ($this->option('all')) {
            $stories = Story::whereHas('sentences')->get();
        } else {
            $this->error('Please provide a story ID or use --all.');

            return self::FAILURE;
        }

        if ($stories->isEmpty()) {
            $this->warn('No stories found.');

            return self::SUCCESS;
        }

        $this->info("Reprocessing {$stories->count()} story(ies)...");

        $bar = $this->output->createProgressBar($stories->count());

        foreach ($stories as $story) {
            $sentences = $story->sentences()->orderBy('position')->get();

            $parsed = $sentences->map(fn (StorySentence $s) => [
                'text_zh' => $s->text_zh,
                'translation_id' => $s->translation_id,
                'translation_en' => $s->translation_en ?? '',
                'paragraph' => $s->paragraph,
            ])->all();

            $service->processFromParsed($story, $parsed);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
