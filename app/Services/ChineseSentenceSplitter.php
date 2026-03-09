<?php

namespace App\Services;

class ChineseSentenceSplitter
{
    /**
     * Split Chinese text into sentences by punctuation.
     *
     * Keeps the delimiter attached to the preceding sentence.
     *
     * @return list<string>
     */
    public function split(string $text): array
    {
        // Split by Chinese sentence-ending punctuation, keeping delimiter attached
        $parts = preg_split('/([。！？；])/u', trim($text), -1, PREG_SPLIT_DELIM_CAPTURE);

        $sentences = [];
        $current = '';

        foreach ($parts as $part) {
            if (preg_match('/^[。！？；]$/u', $part)) {
                $current .= $part;
                $trimmed = trim($current);
                if ($trimmed !== '') {
                    $sentences[] = $trimmed;
                }
                $current = '';
            } else {
                $current = $part;
            }
        }

        // If there's remaining text without a delimiter
        $trimmed = trim($current);
        if ($trimmed !== '') {
            $sentences[] = $trimmed;
        }

        return $sentences;
    }

    /**
     * Split Chinese text into paragraphs, then sentences within each paragraph.
     *
     * @return list<array{paragraph: int, sentences: list<string>}>
     */
    public function splitWithParagraphs(string $text): array
    {
        $blocks = preg_split('/\n\s*\n/u', trim($text));
        $result = [];

        foreach ($blocks as $index => $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            $sentences = $this->split($block);
            if (! empty($sentences)) {
                $result[] = [
                    'paragraph' => count($result) + 1,
                    'sentences' => $sentences,
                ];
            }
        }

        return $result;
    }
}
