<?php

namespace App\Services;

use RuntimeException;

class AiStoryParser
{
    public function __construct(
        private DeepSeekClient $client,
    ) {}

    /**
     * Parse raw Chinese text into structured sentences with pinyin and translations.
     *
     * @return list<array{text_zh: string, text_pinyin: string, translation_id: string, translation_en: string}>
     *
     * @throws RuntimeException
     */
    public function parse(string $rawChinese, int $hskLevel = 3): array
    {
        $system = sprintf(
            'You are a Chinese language teaching assistant. Given Chinese text, split it into sentences and for each sentence provide:
- text_zh: the Chinese sentence (keep original punctuation)
- text_pinyin: romanized pinyin with tone marks (e.g. "nǐ hǎo", not "ni3 hao3")
- translation_id: Bahasa Indonesia translation
- translation_en: English translation

The target learner level is HSK %d. Keep translations natural and appropriate for this level.
Return a JSON object with a "sentences" key containing an array of sentence objects.',
            $hskLevel,
        );

        $prompt = sprintf(
            "Parse the following Chinese text:\n\n%s\n\nReturn format: {\"sentences\": [{\"text_zh\": \"...\", \"text_pinyin\": \"...\", \"translation_id\": \"...\", \"translation_en\": \"...\"}]}",
            $rawChinese,
        );

        $response = $this->client->chat($system, $prompt, 0.3);
        $data = json_decode($response, true);

        if (! is_array($data)) {
            throw new RuntimeException("Invalid AI response: expected JSON, got: {$response}");
        }

        $sentences = $data['sentences'] ?? $data;

        if (! is_array($sentences) || empty($sentences)) {
            throw new RuntimeException("Invalid AI response: expected non-empty sentences array, got: {$response}");
        }

        $requiredKeys = ['text_zh', 'text_pinyin', 'translation_id', 'translation_en'];

        foreach ($sentences as $i => $sentence) {
            foreach ($requiredKeys as $key) {
                if (! isset($sentence[$key]) || ! is_string($sentence[$key])) {
                    throw new RuntimeException(
                        sprintf("Invalid sentence at index %d: missing or invalid '%s' key, got: %s", $i, $key, $response)
                    );
                }
            }
        }

        return $sentences;
    }
}
