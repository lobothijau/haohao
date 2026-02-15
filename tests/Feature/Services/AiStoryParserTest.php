<?php

use App\Services\AiStoryParser;
use App\Services\DeepSeekClient;

function fakeParserResponse(): string
{
    return json_encode(['sentences' => [
        [
            'text_zh' => '小明每天早上六点起床。',
            'text_pinyin' => 'Xiǎo Míng měitiān zǎoshang liù diǎn qǐchuáng.',
            'translation_id' => 'Xiao Ming bangun setiap hari jam enam pagi.',
            'translation_en' => 'Xiao Ming wakes up at six every morning.',
        ],
        [
            'text_zh' => '他喜欢喝咖啡。',
            'text_pinyin' => 'Tā xǐhuān hē kāfēi.',
            'translation_id' => 'Dia suka minum kopi.',
            'translation_en' => 'He likes to drink coffee.',
        ],
    ]]);
}

it('parses Chinese text into structured sentences', function () {
    $mock = $this->mock(DeepSeekClient::class);
    $mock->shouldReceive('chat')->once()->andReturn(fakeParserResponse());

    $parser = new AiStoryParser($mock);
    $result = $parser->parse('小明每天早上六点起床。他喜欢喝咖啡。');

    expect($result)->toHaveCount(2);
    expect($result[0]['text_zh'])->toBe('小明每天早上六点起床。');
    expect($result[0]['text_pinyin'])->toBe('Xiǎo Míng měitiān zǎoshang liù diǎn qǐchuáng.');
    expect($result[0]['translation_id'])->toBe('Xiao Ming bangun setiap hari jam enam pagi.');
    expect($result[0]['translation_en'])->toBe('Xiao Ming wakes up at six every morning.');
});

it('passes hsk level and low temperature to the client', function () {
    $mock = $this->mock(DeepSeekClient::class);
    $mock->shouldReceive('chat')
        ->once()
        ->withArgs(function (string $system, string $prompt, float $temp) {
            return str_contains($system, 'HSK 2') && $temp === 0.3;
        })
        ->andReturn(fakeParserResponse());

    $parser = new AiStoryParser($mock);
    $parser->parse('你好。', 2);
});

it('throws on non-JSON response', function () {
    $mock = $this->mock(DeepSeekClient::class);
    $mock->shouldReceive('chat')->once()->andReturn('not json at all');

    $parser = new AiStoryParser($mock);
    $parser->parse('你好。');
})->throws(RuntimeException::class, 'expected JSON');

it('throws on empty sentences array', function () {
    $mock = $this->mock(DeepSeekClient::class);
    $mock->shouldReceive('chat')->once()->andReturn(json_encode(['sentences' => []]));

    $parser = new AiStoryParser($mock);
    $parser->parse('你好。');
})->throws(RuntimeException::class, 'expected non-empty sentences array');

it('throws when sentence is missing required keys', function () {
    $mock = $this->mock(DeepSeekClient::class);
    $mock->shouldReceive('chat')->once()->andReturn(json_encode(['sentences' => [
        ['text_zh' => '你好。', 'text_pinyin' => 'Nǐ hǎo.'],
    ]]));

    $parser = new AiStoryParser($mock);
    $parser->parse('你好。');
})->throws(RuntimeException::class, "missing or invalid 'translation_id' key");

it('handles response without sentences wrapper key', function () {
    $mock = $this->mock(DeepSeekClient::class);
    $response = json_encode([
        [
            'text_zh' => '你好。',
            'text_pinyin' => 'Nǐ hǎo.',
            'translation_id' => 'Halo.',
            'translation_en' => 'Hello.',
        ],
    ]);
    $mock->shouldReceive('chat')->once()->andReturn($response);

    $parser = new AiStoryParser($mock);
    $result = $parser->parse('你好。');

    expect($result)->toHaveCount(1);
    expect($result[0]['text_zh'])->toBe('你好。');
});
