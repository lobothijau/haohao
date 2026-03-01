<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DeepSeekClient
{
    /**
     * Send a chat completion request to the DeepSeek API.
     *
     * @throws RuntimeException
     */
    public function chat(string $system, string $prompt, float $temperature = 0.7): string
    {
        Log::info('DeepSeek API request', [
            'model' => 'deepseek-chat',
            'temperature' => $temperature,
            'prompt_length' => strlen($prompt),
        ]);

        $response = Http::withToken(config('services.deepseek.api_key'))
            ->timeout(120)
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $temperature,
            ]);

        if ($response->failed()) {
            Log::error('DeepSeek API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                sprintf('DeepSeek API error [%d]: %s', $response->status(), $response->body())
            );
        }

        $content = $response->json('choices.0.message.content');

        Log::info('DeepSeek API response', [
            'content_length' => is_string($content) ? strlen($content) : 0,
            'usage' => $response->json('usage'),
        ]);

        if (! is_string($content) || $content === '') {
            Log::error('DeepSeek API unexpected response', [
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                sprintf('DeepSeek API returned unexpected response: %s', $response->body())
            );
        }

        return $content;
    }
}
