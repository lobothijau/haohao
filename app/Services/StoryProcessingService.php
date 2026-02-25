<?php

namespace App\Services;

use App\Models\DictionaryEntry;
use App\Models\SentenceWord;
use App\Models\Story;
use App\Models\StorySentence;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StoryProcessingService
{
    public function __construct(
        private ChineseSentenceSplitter $splitter,
        private ChineseSegmenter $segmenter,
    ) {}

    /**
     * Process a story: split into sentences, segment words, link to dictionary.
     *
     * @param  list<string>  $translationsId  Indonesian translations, one per sentence
     * @param  list<string>  $translationsEn  English translations, one per sentence (optional)
     * @return array{sentence_count: int, word_count: int, unique_word_count: int, difficulty_score: float, estimated_minutes: int}
     */
    public function process(
        Story $story,
        string $rawChinese,
        array $translationsId,
        array $translationsEn = [],
    ): array {
        $sentences = $this->splitter->split($rawChinese);

        if (count($sentences) !== count($translationsId)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Translation count mismatch: %d sentences but %d Indonesian translations.',
                    count($sentences),
                    count($translationsId),
                )
            );
        }

        if (count($translationsEn) > 0 && count($translationsEn) !== count($sentences)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Translation count mismatch: %d sentences but %d English translations.',
                    count($sentences),
                    count($translationsEn),
                )
            );
        }

        $result = DB::transaction(function () use ($story, $sentences, $translationsId, $translationsEn) {
            // Delete existing sentences (supports re-processing)
            $story->sentences()->delete();

            $totalWords = 0;
            $allDictionaryEntryIds = [];

            foreach ($sentences as $position => $textZh) {
                // Segment the sentence
                $words = $this->segmenter->segment($textZh);

                // Create the sentence record (text_pinyin set to placeholder, updated after word processing)
                $sentence = StorySentence::create([
                    'story_id' => $story->id,
                    'position' => $position + 1,
                    'text_zh' => $textZh,
                    'text_pinyin' => '',
                    'translation_id' => $translationsId[$position],
                    'translation_en' => $translationsEn[$position] ?? null,
                ]);

                // Process each word
                $sentencePinyinParts = [];

                foreach ($words as $wordPosition => $surfaceForm) {
                    $dictionaryEntry = $this->findOrCreateDictionaryEntry($surfaceForm);
                    $allDictionaryEntryIds[] = $dictionaryEntry->id;

                    SentenceWord::create([
                        'story_sentence_id' => $sentence->id,
                        'dictionary_entry_id' => $dictionaryEntry->id,
                        'position' => $wordPosition + 1,
                        'surface_form' => $surfaceForm,
                    ]);

                    $sentencePinyinParts[] = $dictionaryEntry->pinyin;
                }

                // Update sentence pinyin from word-level lookups
                $sentence->update([
                    'text_pinyin' => implode(' ', $sentencePinyinParts),
                ]);

                $totalWords += count($words);
            }

            // Calculate stats
            $uniqueEntryIds = array_unique($allDictionaryEntryIds);
            $uniqueWordCount = count($uniqueEntryIds);
            $sentenceCount = count($sentences);

            // Calculate difficulty score from HSK levels of unique words
            $difficultyScore = $this->calculateDifficultyScore($uniqueEntryIds);

            // Estimate reading time: total characters / 150 (learner reading speed)
            $totalChars = mb_strlen(implode('', $sentences));
            $estimatedMinutes = max(1, (int) ceil($totalChars / 150));

            // Update story stats
            $story->update([
                'word_count' => $totalWords,
                'unique_word_count' => $uniqueWordCount,
                'sentence_count' => $sentenceCount,
                'difficulty_score' => $difficultyScore,
                'estimated_minutes' => $estimatedMinutes,
            ]);

            return [
                'sentence_count' => $sentenceCount,
                'word_count' => $totalWords,
                'unique_word_count' => $uniqueWordCount,
                'difficulty_score' => $difficultyScore,
                'estimated_minutes' => $estimatedMinutes,
            ];
        });

        return $result;
    }

    /**
     * Find a dictionary entry by simplified form, or create a stub.
     */
    private function findOrCreateDictionaryEntry(string $surfaceForm): DictionaryEntry
    {
        $entry = DictionaryEntry::query()
            ->where('simplified', $surfaceForm)
            ->orderBy('frequency_rank')
            ->first();

        if ($entry !== null) {
            return $entry;
        }

        // Create a stub entry for unknown words
        return DictionaryEntry::create([
            'simplified' => $surfaceForm,
            'pinyin' => $surfaceForm,
        ]);
    }

    /**
     * Calculate weighted difficulty score from HSK levels.
     *
     * @param  list<int>  $dictionaryEntryIds
     */
    private function calculateDifficultyScore(array $dictionaryEntryIds): float
    {
        if (empty($dictionaryEntryIds)) {
            return 0.0;
        }

        $entries = DictionaryEntry::query()
            ->whereIn('id', $dictionaryEntryIds)
            ->whereNotNull('hsk_level')
            ->pluck('hsk_level');

        if ($entries->isEmpty()) {
            return 0.0;
        }

        return round($entries->avg(), 2);
    }
}
