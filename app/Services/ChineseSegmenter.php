<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class ChineseSegmenter
{
    /**
     * Segment Chinese text into individual words using jieba.
     *
     * @return list<string>
     */
    public function segment(string $text): array
    {
        $scriptPath = base_path('scripts/segment.py');

        $venvPython = base_path('.venv/bin/python3');
        $python = file_exists($venvPython) ? $venvPython : 'python3';

        $process = new Process([$python, $scriptPath, $text]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'Chinese segmentation failed: '.$process->getErrorOutput()
            );
        }

        $output = trim($process->getOutput());

        /** @var list<string> $words */
        $words = json_decode($output, true);

        if (! is_array($words)) {
            throw new RuntimeException('Invalid segmentation output: '.$output);
        }

        return $words;
    }
}
