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
        $paragraphGroups = $this->splitter->splitWithParagraphs($rawChinese);

        $sentences = [];
        $paragraphMap = [];
        foreach ($paragraphGroups as $group) {
            foreach ($group['sentences'] as $sentence) {
                $paragraphMap[] = $group['paragraph'];
                $sentences[] = $sentence;
            }
        }

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

        $result = DB::transaction(function () use ($story, $sentences, $translationsId, $translationsEn, $paragraphMap) {
            // Delete existing sentences (supports re-processing)
            $story->sentences()->delete();

            $totalWords = 0;
            $allDictionaryEntryIds = [];

            foreach ($sentences as $position => $textZh) {
                // Segment the sentence
                $words = $this->segmenter->segment($textZh);

                $sentence = StorySentence::create([
                    'story_id' => $story->id,
                    'position' => $position + 1,
                    'paragraph' => $paragraphMap[$position],
                    'text_zh' => $textZh,
                    'translation_id' => $translationsId[$position],
                    'translation_en' => $translationsEn[$position] ?? null,
                ]);

                // Process each word
                $wordPositionCounter = 0;

                foreach ($words as $surfaceForm) {
                    $resolved = $this->resolveWords($surfaceForm);

                    foreach ($resolved as $entry) {
                        $allDictionaryEntryIds[] = $entry['dictionary_entry']->id;

                        SentenceWord::create([
                            'story_sentence_id' => $sentence->id,
                            'dictionary_entry_id' => $entry['dictionary_entry']->id,
                            'position' => ++$wordPositionCounter,
                            'surface_form' => $entry['surface_form'],
                        ]);
                    }
                }

                $totalWords += $wordPositionCounter;
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
     * Process a story from pre-parsed AI output (with pinyin and translations already provided).
     *
     * @param  list<array{text_zh: string, translation_id: string, translation_en: string}>  $parsedSentences
     * @return array{sentence_count: int, word_count: int, unique_word_count: int, difficulty_score: float, estimated_minutes: int}
     */
    public function processFromParsed(Story $story, array $parsedSentences): array
    {
        return DB::transaction(function () use ($story, $parsedSentences) {
            $story->sentences()->delete();

            $totalWords = 0;
            $allDictionaryEntryIds = [];

            foreach ($parsedSentences as $position => $parsed) {
                $textZh = $parsed['text_zh'];
                $words = $this->segmenter->segment($textZh);

                $sentence = StorySentence::create([
                    'story_id' => $story->id,
                    'position' => $position + 1,
                    'paragraph' => $parsed['paragraph'] ?? 1,
                    'text_zh' => $textZh,
                    'translation_id' => $parsed['translation_id'],
                    'translation_en' => $parsed['translation_en'] ?: null,
                ]);

                $wordPositionCounter = 0;

                foreach ($words as $surfaceForm) {
                    $resolved = $this->resolveWords($surfaceForm);

                    foreach ($resolved as $entry) {
                        $allDictionaryEntryIds[] = $entry['dictionary_entry']->id;

                        SentenceWord::create([
                            'story_sentence_id' => $sentence->id,
                            'dictionary_entry_id' => $entry['dictionary_entry']->id,
                            'position' => ++$wordPositionCounter,
                            'surface_form' => $entry['surface_form'],
                        ]);
                    }
                }

                $totalWords += $wordPositionCounter;
            }

            $uniqueEntryIds = array_unique($allDictionaryEntryIds);
            $uniqueWordCount = count($uniqueEntryIds);
            $sentenceCount = count($parsedSentences);
            $difficultyScore = $this->calculateDifficultyScore($uniqueEntryIds);

            $totalChars = mb_strlen(implode('', array_column($parsedSentences, 'text_zh')));
            $estimatedMinutes = max(1, (int) ceil($totalChars / 150));

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
    }

    /**
     * Resolve a segmented token into one or more word entries.
     *
     * If the word exists in the dictionary, returns a single-element array.
     * If not found but all individual characters exist, returns multiple elements (one per character).
     * If no dictionary match at all, returns empty array (word is skipped).
     *
     * @return list<array{surface_form: string, dictionary_entry: DictionaryEntry}>
     */
    private function resolveWords(string $surfaceForm): array
    {
        // Try exact match first (skip stubs where pinyin contains Chinese characters)
        $entry = DictionaryEntry::query()
            ->where('simplified', $surfaceForm)
            ->orderBy('frequency_rank')
            ->first();

        if ($entry !== null && ! preg_match('/\p{Han}/u', $entry->pinyin)) {
            return [['surface_form' => $surfaceForm, 'dictionary_entry' => $entry]];
        }

        // For multi-character tokens not in dictionary, try character-by-character split
        if (mb_strlen($surfaceForm) > 1) {
            $characters = mb_str_split($surfaceForm);
            $charEntries = [];

            foreach ($characters as $char) {
                $charEntry = DictionaryEntry::query()
                    ->where('simplified', $char)
                    ->orderBy('frequency_rank')
                    ->first();

                if ($charEntry === null) {
                    break;
                }

                $charEntries[] = ['surface_form' => $char, 'dictionary_entry' => $charEntry];
            }

            if (count($charEntries) === count($characters)) {
                return $charEntries;
            }
        }

        // No dictionary match — skip this word entirely
        return [];
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
