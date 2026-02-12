<?php

namespace App\Support;

class PinyinConverter
{
    /**
     * Mapping of vowel + tone number to tone-marked character.
     *
     * @var array<string, array<int, string>>
     */
    private const TONE_MAP = [
        'a' => [1 => 'ā', 2 => 'á', 3 => 'ǎ', 4 => 'à'],
        'e' => [1 => 'ē', 2 => 'é', 3 => 'ě', 4 => 'è'],
        'i' => [1 => 'ī', 2 => 'í', 3 => 'ǐ', 4 => 'ì'],
        'o' => [1 => 'ō', 2 => 'ó', 3 => 'ǒ', 4 => 'ò'],
        'u' => [1 => 'ū', 2 => 'ú', 3 => 'ǔ', 4 => 'ù'],
        'ü' => [1 => 'ǖ', 2 => 'ǘ', 3 => 'ǚ', 4 => 'ǜ'],
    ];

    /**
     * Convert numbered pinyin string to tone-marked pinyin.
     *
     * Example: "ni3 hao3" → "nǐ hǎo"
     */
    public static function convert(string $numbered): string
    {
        // Split by spaces, convert each syllable, rejoin
        $syllables = preg_split('/\s+/', trim($numbered));

        $result = array_map(fn (string $syllable) => self::convertSyllable($syllable), $syllables);

        return implode(' ', $result);
    }

    /**
     * Convert a single numbered pinyin syllable to tone-marked.
     */
    private static function convertSyllable(string $syllable): string
    {
        // Replace u: or v with ü before processing
        $syllable = str_replace(['u:', 'v'], 'ü', $syllable);

        // Extract tone number (1-5 or none) from end
        if (preg_match('/^([a-züA-ZÜ]+)([1-5])$/', $syllable, $matches)) {
            $letters = $matches[1];
            $tone = (int) $matches[2];
        } else {
            // No tone number — return as-is
            return $syllable;
        }

        // Tone 5 (neutral tone) — just return letters without number
        if ($tone === 5) {
            return $letters;
        }

        // Find the vowel to place the tone mark on using standard rules:
        // 1. If there is an 'a' or 'e', it takes the mark
        // 2. If there is 'ou', the 'o' takes the mark
        // 3. Otherwise, the last vowel takes the mark
        $lowered = mb_strtolower($letters);
        $vowelIndex = self::findToneVowelIndex($lowered);

        if ($vowelIndex === null) {
            return $letters;
        }

        $chars = mb_str_split($letters);
        $loweredChar = mb_strtolower($chars[$vowelIndex]);
        $replacement = self::TONE_MAP[$loweredChar][$tone] ?? $chars[$vowelIndex];

        $chars[$vowelIndex] = $replacement;

        return implode('', $chars);
    }

    /**
     * Find the index of the vowel that should receive the tone mark.
     */
    private static function findToneVowelIndex(string $lowered): ?int
    {
        $chars = mb_str_split($lowered);
        $vowels = ['a', 'e', 'i', 'o', 'u', 'ü'];

        // Rule 1: 'a' or 'e' always take the mark
        foreach ($chars as $i => $char) {
            if ($char === 'a' || $char === 'e') {
                return $i;
            }
        }

        // Rule 2: 'ou' — 'o' takes the mark
        $ouPos = mb_strpos($lowered, 'ou');
        if ($ouPos !== false) {
            return $ouPos;
        }

        // Rule 3: last vowel takes the mark
        $lastVowelIndex = null;
        foreach ($chars as $i => $char) {
            if (in_array($char, $vowels, true)) {
                $lastVowelIndex = $i;
            }
        }

        return $lastVowelIndex;
    }
}
