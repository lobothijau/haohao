<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DictionaryEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = database_path('dictionary_entries.sql');

        if (! file_exists($filePath)) {
            $this->command->error("Dictionary SQL file not found: {$filePath}");

            return;
        }

        $this->command->info('Importing dictionary entries...');

        $handle = fopen($filePath, 'r');
        $batch = [];
        $imported = 0;
        $chunkSize = 500;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if ($line === '' || ! str_starts_with($line, 'INSERT INTO')) {
                continue;
            }

            $batch[] = $line;

            if (count($batch) >= $chunkSize) {
                DB::unprepared(implode("\n", $batch));
                $imported += count($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::unprepared(implode("\n", $batch));
            $imported += count($batch);
        }

        fclose($handle);

        $this->command->info("Imported {$imported} dictionary entries.");
    }
}
