<?php

use App\Jobs\GenerateElevenLabsAudioJob;
use App\Models\DictionaryEntry;
use App\Services\ElevenLabsTtsService;

it('generates elevenlabs audio and updates dictionary entry', function () {
    $entry = DictionaryEntry::factory()->create(['audio_url' => null, 'simplified' => '你好']);

    $mockService = mock(ElevenLabsTtsService::class);
    $mockService->shouldReceive('generateWordAudio')
        ->with('你好')
        ->once()
        ->andReturn('https://cdn.example.com/audio/words/test.mp3');

    (new GenerateElevenLabsAudioJob($entry))->handle($mockService);

    $entry->refresh();
    expect($entry->audio_url)->toBe('https://cdn.example.com/audio/words/test.mp3');
});

it('skips elevenlabs audio generation when audio_url already set', function () {
    $entry = DictionaryEntry::factory()->create(['audio_url' => 'https://cdn.example.com/existing.mp3']);

    $mockService = mock(ElevenLabsTtsService::class);
    $mockService->shouldNotReceive('generateWordAudio');

    (new GenerateElevenLabsAudioJob($entry))->handle($mockService);

    $entry->refresh();
    expect($entry->audio_url)->toBe('https://cdn.example.com/existing.mp3');
});
