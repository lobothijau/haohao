<?php

use App\Jobs\GenerateElevenLabsAudioJob;
use App\Models\DictionaryEntry;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('dispatches jobs for hsk entries without audio', function () {
    $entry = DictionaryEntry::factory()->create(['hsk_level' => 1, 'audio_url' => null]);
    DictionaryEntry::factory()->create(['hsk_level' => null, 'audio_url' => null]);

    $this->artisan('elevenlabs:generate')
        ->assertSuccessful();

    Queue::assertPushed(GenerateElevenLabsAudioJob::class, 1);
    Queue::assertPushed(GenerateElevenLabsAudioJob::class, fn ($job) => $job->dictionaryEntry->is($entry));
});

it('skips entries that already have audio_url', function () {
    DictionaryEntry::factory()->create(['hsk_level' => 1, 'audio_url' => 'https://cdn.example.com/existing.mp3']);

    $this->artisan('elevenlabs:generate')
        ->assertSuccessful();

    Queue::assertNotPushed(GenerateElevenLabsAudioJob::class);
});

it('includes entries with audio_url when force is used', function () {
    DictionaryEntry::factory()->create(['hsk_level' => 1, 'audio_url' => 'https://cdn.example.com/existing.mp3']);

    $this->artisan('elevenlabs:generate --force')
        ->assertSuccessful();

    Queue::assertPushed(GenerateElevenLabsAudioJob::class, 1);
});

it('filters by hsk level', function () {
    DictionaryEntry::factory()->create(['hsk_level' => 1, 'audio_url' => null]);
    DictionaryEntry::factory()->create(['hsk_level' => 2, 'audio_url' => null]);
    DictionaryEntry::factory()->create(['hsk_level' => 3, 'audio_url' => null]);

    $this->artisan('elevenlabs:generate --hsk=2')
        ->assertSuccessful();

    Queue::assertPushed(GenerateElevenLabsAudioJob::class, 1);
});

it('respects the limit option', function () {
    DictionaryEntry::factory()->count(5)->create(['hsk_level' => 1, 'audio_url' => null]);

    $this->artisan('elevenlabs:generate --limit=2')
        ->assertSuccessful();

    Queue::assertPushed(GenerateElevenLabsAudioJob::class, 2);
});
