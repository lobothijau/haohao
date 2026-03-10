<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiStoryParser
{
    public function __construct(
        private DeepSeekClient $client,
    ) {}

    /**
     * Parse raw Chinese text into structured sentences with translations.
     *
     * @return list<array{text_zh: string, translation_id: string, translation_en: string, paragraph: int}>
     *
     * @throws RuntimeException
     */
    public function parse(string $rawChinese, int $hskLevel = 3): array
    {
        $system = sprintf(
            'You are a Chinese language teaching assistant. Given Chinese text, split it into sentences and for each sentence provide:
- text_zh: the Chinese sentence (keep original punctuation)
- translation_id: Bahasa Indonesia translation
- translation_en: English translation

Sentences in the input are separated by empty lines (blank lines). Each block of text between blank lines is one sentence.

The target learner level is HSK %d. Keep translations natural and appropriate for this level.
Return a JSON object with a "sentences" key containing an array of sentence objects.',
            $hskLevel,
        );

        $prompt = sprintf(
            "Parse the following Chinese text:\n\n%s\n\nReturn format: {\"sentences\": [{\"text_zh\": \"...\", \"translation_id\": \"...\", \"translation_en\": \"...\"}]}",
            $rawChinese,
        );

        Log::info('AiStoryParser: starting parse', [
            'hsk_level' => $hskLevel,
            'text_length' => strlen($rawChinese),
        ]);

        $response = $this->client->chat($system, $prompt, 0.3);

        Log::info('AiStoryParser: received response', [
            'response_length' => strlen($response),
            'response_preview' => mb_substr($response, 0, 500),
        ]);

        $json = $this->extractJson($response);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            Log::error('AiStoryParser: JSON decode failed', [
                'json_error' => json_last_error_msg(),
                'response' => mb_substr($response, 0, 1000),
            ]);

            throw new RuntimeException("Invalid AI response: expected JSON, got: {$response}");
        }

        $sentences = $data['sentences'] ?? $data;

        if (! is_array($sentences) || empty($sentences)) {
            throw new RuntimeException("Invalid AI response: expected non-empty sentences array, got: {$response}");
        }

        $requiredKeys = ['text_zh', 'translation_id', 'translation_en'];

        foreach ($sentences as $i => $sentence) {
            foreach ($requiredKeys as $key) {
                if (! isset($sentence[$key]) || ! is_string($sentence[$key])) {
                    throw new RuntimeException(
                        sprintf("Invalid sentence at index %d: missing or invalid '%s' key, got: %s", $i, $key, $response)
                    );
                }
            }
        }

        $sentences = $this->assignParagraphs($rawChinese, $sentences);

        Log::info('AiStoryParser: parsed successfully', [
            'sentence_count' => count($sentences),
        ]);

        return $sentences;
    }

    /**
     * Assign paragraph numbers to sentences by matching them to blocks in the raw input.
     *
     * @param  list<array{text_zh: string, translation_id: string, translation_en: string}>  $sentences
     * @return list<array{text_zh: string, translation_id: string, translation_en: string, paragraph: int}>
     */
    private function assignParagraphs(string $rawChinese, array $sentences): array
    {
        $blocks = preg_split('/\n\s*\n/u', trim($rawChinese));
        $blocks = array_values(array_filter(array_map('trim', $blocks), fn (string $b): bool => $b !== ''));

        if (count($blocks) <= 1) {
            foreach ($sentences as &$sentence) {
                $sentence['paragraph'] = 1;
            }

            return $sentences;
        }

        $currentBlock = 0;

        foreach ($sentences as &$sentence) {
            $textZh = $sentence['text_zh'];
            // Take first few characters to match against blocks
            $prefix = mb_substr(preg_replace('/[\s\p{P}]/u', '', $textZh), 0, 4);

            // Search forward from the current block
            $matched = false;
            for ($i = $currentBlock; $i < count($blocks); $i++) {
                $blockClean = preg_replace('/[\s\p{P}]/u', '', $blocks[$i]);
                if (mb_strpos($blockClean, $prefix) !== false) {
                    $currentBlock = $i;
                    $matched = true;
                    break;
                }
            }

            $sentence['paragraph'] = ($matched ? $currentBlock : $currentBlock) + 1;
        }

        return $sentences;
    }

    /**
     * Extract JSON from a response that may be wrapped in markdown code fences.
     */
    private function extractJson(string $response): string
    {
        $trimmed = trim($response);

        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $trimmed, $matches)) {
            return trim($matches[1]);
        }

        return $trimmed;
    }
}
