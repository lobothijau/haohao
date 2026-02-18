<?php

use App\Jobs\GenerateSentenceAudioJob;
use App\Jobs\GenerateStoryAudioJob;
use App\Jobs\GenerateWordAudioJob;
use App\Models\DictionaryEntry;
use App\Models\Story;
use App\Models\StorySentence;
use Illuminate\Support\Facades\Queue;

it('dispatches word audio jobs for entries without audio', function () {
    Queue::fake();

    DictionaryEntry::factory()->count(3)->create(['audio_url' => null]);
    DictionaryEntry::factory()->create(['audio_url' => '/existing.mp3']);

    $this->artisan('audio:generate', ['--type' => 'words'])
        ->assertSuccessful();

    Queue::assertPushed(GenerateWordAudioJob::class, 3);
});

it('dispatches sentence audio jobs for sentences without audio', function () {
    Queue::fake();

    $story = Story::factory()->create();
    StorySentence::factory()->count(2)->create(['story_id' => $story->id, 'audio_url' => null]);
    StorySentence::factory()->create(['story_id' => $story->id, 'audio_url' => '/existing.mp3']);

    $this->artisan('audio:generate', ['--type' => 'sentences'])
        ->assertSuccessful();

    Queue::assertPushed(GenerateSentenceAudioJob::class, 2);
});

it('dispatches both word and sentence jobs when type is all', function () {
    Queue::fake();

    DictionaryEntry::factory()->create(['audio_url' => null]);
    $story = Story::factory()->create();
    StorySentence::factory()->create(['story_id' => $story->id, 'audio_url' => null]);

    $this->artisan('audio:generate', ['--type' => 'all'])
        ->assertSuccessful();

    Queue::assertPushed(GenerateWordAudioJob::class, 1);
    Queue::assertPushed(GenerateSentenceAudioJob::class, 1);
});

it('dispatches story audio job for specific story', function () {
    Queue::fake();

    $story = Story::factory()->create();

    $this->artisan('audio:generate', ['--story' => $story->id])
        ->assertSuccessful();

    Queue::assertPushed(GenerateStoryAudioJob::class, 1);
});

it('fails for non-existent story', function () {
    $this->artisan('audio:generate', ['--story' => 99999])
        ->assertFailed();
});

it('respects the limit option', function () {
    Queue::fake();

    DictionaryEntry::factory()->count(5)->create(['audio_url' => null]);

    $this->artisan('audio:generate', ['--type' => 'words', '--limit' => '2'])
        ->assertSuccessful();

    Queue::assertPushed(GenerateWordAudioJob::class, 2);
});

it('regenerates audio when force option is used', function () {
    Queue::fake();

    DictionaryEntry::factory()->create(['audio_url' => '/existing.mp3']);

    $this->artisan('audio:generate', ['--type' => 'words', '--force' => true])
        ->assertSuccessful();

    Queue::assertPushed(GenerateWordAudioJob::class, 1);
});
