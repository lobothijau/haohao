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
}
