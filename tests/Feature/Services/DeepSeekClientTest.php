<?php

use App\Services\DeepSeekClient;
use Illuminate\Support\Facades\Http;

it('sends correct request to the DeepSeek API', function () {
    Http::fake([
        'api.deepseek.com/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => '{"sentences": []}']],
            ],
        ]),
    ]);

    config()->set('services.deepseek.api_key', 'test-api-key');

    $client = new DeepSeekClient;
    $client->chat('system prompt', 'user prompt', 0.5);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.deepseek.com/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-api-key')
            && $request['model'] === 'deepseek-chat'
            && $request['messages'][0]['role'] === 'system'
            && $request['messages'][0]['content'] === 'system prompt'
            && $request['messages'][1]['role'] === 'user'
            && $request['messages'][1]['content'] === 'user prompt'
            && $request['temperature'] === 0.5;
    });
});

it('returns the content string from the API response', function () {
    Http::fake([
        'api.deepseek.com/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Hello from DeepSeek']],
            ],
        ]),
    ]);

    $client = new DeepSeekClient;
    $result = $client->chat('system', 'prompt');

    expect($result)->toBe('Hello from DeepSeek');
});

it('throws on API failure', function () {
    Http::fake([
        'api.deepseek.com/chat/completions' => Http::response('Internal Server Error', 500),
    ]);

    $client = new DeepSeekClient;
    $client->chat('system', 'prompt');
})->throws(RuntimeException::class, 'DeepSeek API error [500]');

it('throws when response has no content', function () {
    Http::fake([
        'api.deepseek.com/chat/completions' => Http::response([
            'choices' => [],
        ]),
    ]);

    $client = new DeepSeekClient;
    $client->chat('system', 'prompt');
})->throws(RuntimeException::class, 'DeepSeek API returned unexpected response');
