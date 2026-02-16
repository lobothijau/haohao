<?php

namespace App\Services;

use Overtrue\Pinyin\Pinyin;

class PinyinService
{
    /**
     * Convert Chinese characters (Hanzi) to tone-marked pinyin.
     *
     * Example: "你好" → "nǐ hǎo"
     */
    public function convert(string $text): string
    {
        return Pinyin::phrase($text)->join(' ');
    }
}
