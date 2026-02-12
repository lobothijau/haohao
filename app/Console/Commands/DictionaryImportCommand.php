<?php

namespace App\Console\Commands;

use App\Models\DictionaryEntry;
use App\Services\CedictParser;
use App\Services\HskWordListLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DictionaryImportCommand extends Command
{
    protected $signature = 'dictionary:import
        {file : Path to CC-CEDICT file}
        {--hsk= : Path to HSK word list CSV}
        {--chunk=500 : Number of entries per upsert batch}';

    protected $description = 'Import dictionary entries from a CC-CEDICT file';

    public function handle(CedictParser $parser): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        $hskMap = [];
        if ($hskPath = $this->option('hsk')) {
            if (! file_exists($hskPath)) {
                $this->error("HSK file not found: {$hskPath}");

                return self::FAILURE;
            }

            $this->info('Loading HSK word list...');
            $hskMap = (new HskWordListLoader)->load($hskPath);
            $this->info(sprintf('Loaded %d HSK words.', count($hskMap)));
        }

        $chunkSize = (int) $this->option('chunk');
        $this->info("Importing from: {$filePath}");

        $batch = [];
        $imported = 0;

        foreach ($parser->parseFile($filePath) as $entry) {
            $batch[] = [
                'simplified' => $entry['simplified'],
                'traditional' => $entry['traditional'],
                'pinyin' => $entry['pinyin'],
                'pinyin_numbered' => $entry['pinyin_numbered'],
                'meaning_en' => $entry['meaning_en'],
                'hsk_level' => $hskMap[$entry['simplified']] ?? null,
            ];

            if (count($batch) >= $chunkSize) {
                $this->upsertBatch($batch);
                $imported += count($batch);
                $batch = [];
            }
        }

        // Flush remaining
        if (count($batch) > 0) {
            $this->upsertBatch($batch);
            $imported += count($batch);
        }

        $this->newLine();
        $this->info("Import complete: {$imported} entries processed.");

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $batch
     */
    private function upsertBatch(array $batch): void
    {
        DB::transaction(function () use ($batch) {
            DictionaryEntry::upsert(
                $batch,
                ['simplified', 'pinyin'],
                ['traditional', 'pinyin_numbered', 'meaning_en', 'hsk_level']
            );
        });
    }
}
