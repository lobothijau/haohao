<?php

namespace App\Services;

class HskWordListLoader
{
    /**
     * Load HSK word list from a CSV file.
     *
     * Expects CSV with columns: simplified, level
     *
     * @return array<string, int> Map of simplified word to HSK level
     */
    public function load(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open HSK file: {$filePath}");
        }

        $map = [];

        try {
            // Skip header row
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 2) {
                    continue;
                }

                $simplified = trim($row[0]);
                $level = (int) trim($row[1]);

                if ($simplified !== '' && $level >= 1 && $level <= 9) {
                    $map[$simplified] = $level;
                }
            }
        } finally {
            fclose($handle);
        }

        return $map;
    }
}
