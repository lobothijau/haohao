<?php

namespace App\Services;

use App\Support\PinyinConverter;
use Generator;

/**
 * Parses CC-CEDICT dictionary files during one-time dictionary import.
 *
 * @see \App\Console\Commands\DictionaryImportCommand
 */
class CedictParser
{
    /**
     * Parse a CC-CEDICT file and yield entries.
     *
     * @return Generator<int, array{traditional: string, simplified: string, pinyin: string, pinyin_numbered: string, meaning_en: string}>
     */
    public function parseFile(string $filePath): Generator
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                $entry = $this->parseLine($line);

                if ($entry !== null) {
                    yield $entry;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Parse a single CC-CEDICT line.
     *
     * Format: traditional simplified [pinyin] /english1/english2/
     *
     * @return array{traditional: string, simplified: string, pinyin: string, pinyin_numbered: string, meaning_en: string}|null
     */
    public function parseLine(string $line): ?array
    {
        if (! preg_match('/^(\S+)\s+(\S+)\s+\[([^\]]+)\]\s+\/(.+)\/$/', $line, $matches)) {
            return null;
        }

        $traditional = $matches[1];
        $simplified = $matches[2];
        $pinyinNumbered = $matches[3];
        $definitions = $matches[4];

        $pinyin = PinyinConverter::convert($pinyinNumbered);
        $meaningEn = implode('; ', explode('/', $definitions));

        return [
            'traditional' => $traditional,
            'simplified' => $simplified,
            'pinyin' => $pinyin,
            'pinyin_numbered' => $pinyinNumbered,
            'meaning_en' => $meaningEn,
        ];
    }
}
