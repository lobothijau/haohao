<?php

use App\Services\ElevenLabsTtsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('do');

    config([
        'services.elevenlabs.api_key' => 'test-api-key',
        'services.elevenlabs.voice_id' => 'test-voice-id',
        'services.elevenlabs.model_id' => 'eleven_multilingual_v2',
    ]);
});

it('calls elevenlabs api and stores audio on do spaces', function () {
    Http::fake([
        'api.elevenlabs.io/*' => Http::response('fake-mp3-bytes', 200),
    ]);

    $service = new ElevenLabsTtsService;
    $url = $service->generateWordAudio('你好');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'text-to-speech/test-voice-id')
            && $request->hasHeader('xi-api-key', 'test-api-key')
            && $request['text'] === '你好'
            && $request['model_id'] === 'eleven_multilingual_v2';
    });

    $expectedPath = 'audio/words/'.md5('你好').'.mp3';
    Storage::disk('do')->assertExists($expectedPath);
    expect(Storage::disk('do')->get($expectedPath))->toBe('fake-mp3-bytes');
});

it('skips api call if file already exists on do spaces', function () {
    Http::fake();

    $expectedPath = 'audio/words/'.md5('你好').'.mp3';
    Storage::disk('do')->put($expectedPath, 'existing-audio');

    $service = new ElevenLabsTtsService;
    $url = $service->generateWordAudio('你好');

    Http::assertNothingSent();
    expect($url)->toContain($expectedPath);
});

it('throws on api failure', function () {
    Http::fake([
        'api.elevenlabs.io/*' => Http::response('error', 500),
    ]);

    $service = new ElevenLabsTtsService;
    $service->generateWordAudio('你好');
})->throws(Illuminate\Http\Client\RequestException::class);
