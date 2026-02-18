<?php

use App\Services\GoogleTtsService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('skips synthesis if file already exists on disk', function () {
    Storage::disk('public')->put('audio/words/test.mp3', 'existing-audio');

    $service = Mockery::mock(GoogleTtsService::class)->makePartial();
    $url = $service->synthesizeAndStore('你好', 'words', 'test.mp3');

    Storage::disk('public')->assertExists('audio/words/test.mp3');
    expect($url)->toContain('audio/words/test.mp3');
});

it('generates word audio filename from md5 of simplified text', function () {
    $service = Mockery::mock(GoogleTtsService::class)->makePartial();
    $service->shouldReceive('synthesizeAndStore')
        ->with('你好', 'words', md5('你好').'.mp3')
        ->once()
        ->andReturn('/storage/audio/words/'.md5('你好').'.mp3');

    $url = $service->generateWordAudio('你好');

    expect($url)->toContain(md5('你好').'.mp3');
});

it('generates sentence audio filename from sentence id', function () {
    $service = Mockery::mock(GoogleTtsService::class)->makePartial();
    $service->shouldReceive('synthesizeAndStore')
        ->with('你好世界。', 'sentences', 'sentence_42.mp3')
        ->once()
        ->andReturn('/storage/audio/sentences/sentence_42.mp3');

    $url = $service->generateSentenceAudio('你好世界。', 42);

    expect($url)->toContain('sentence_42.mp3');
});
