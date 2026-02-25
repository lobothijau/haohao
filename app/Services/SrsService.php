<?php

namespace App\Services;

use App\Enums\CardState;
use App\Enums\CardType;
use App\Enums\SrsRating;
use App\Models\SrsCard;
use App\Models\SrsReviewLog;
use App\Models\UserVocabulary;

class SrsService
{
    /**
     * Learning steps in minutes.
     *
     * @var list<int>
     */
    private const LEARNING_STEPS = [1, 10];

    private const GRADUATING_INTERVAL = 1;

    private const EASY_INTERVAL = 4;

    private const MINIMUM_EASE = 1.30;

    /**
     * @return list<SrsCard>
     */
    public function createCardsForVocabulary(UserVocabulary $vocabulary): array
    {
        $cards = [];

        $cards[] = SrsCard::firstOrCreate(
            [
                'user_id' => $vocabulary->user_id,
                'dictionary_entry_id' => $vocabulary->dictionary_entry_id,
                'card_type' => CardType::Recognition,
            ],
            [
                'user_vocabulary_id' => $vocabulary->id,
                'card_state' => CardState::New,
                'ease_factor' => 2.50,
                'interval_days' => 0,
                'repetitions' => 0,
                'lapses' => 0,
                'learning_step' => 0,
                'due_at' => now(),
            ],
        );

        $vocabulary->loadMissing('dictionaryEntry');

        if ($vocabulary->dictionaryEntry->audio_url !== null) {
            $cards[] = SrsCard::firstOrCreate(
                [
                    'user_id' => $vocabulary->user_id,
                    'dictionary_entry_id' => $vocabulary->dictionary_entry_id,
                    'card_type' => CardType::Listening,
                ],
                [
                    'user_vocabulary_id' => $vocabulary->id,
                    'card_state' => CardState::New,
                    'ease_factor' => 2.50,
                    'interval_days' => 0,
                    'repetitions' => 0,
                    'lapses' => 0,
                    'learning_step' => 0,
                    'due_at' => now(),
                ],
            );
        }

        return $cards;
    }

    /**
     * @return array{card: SrsCard, log: SrsReviewLog}
     */
    public function review(SrsCard $card, SrsRating $rating, ?int $timeTakenMs = null): array
    {
        $previousState = $card->card_state;
        $previousInterval = $card->interval_days;
        $previousEase = $card->ease_factor;

        match ($card->card_state) {
            CardState::New, CardState::Learning => $this->processLearningCard($card, $rating),
            CardState::Review => $this->processReviewCard($card, $rating),
            CardState::Relearning => $this->processRelearningCard($card, $rating),
        };

        $card->last_reviewed_at = now();
        $card->save();

        $log = SrsReviewLog::create([
            'user_id' => $card->user_id,
            'srs_card_id' => $card->id,
            'rating' => $rating,
            'previous_state' => $previousState,
            'new_state' => $card->card_state,
            'previous_interval' => $previousInterval,
            'new_interval' => $card->interval_days,
            'previous_ease' => $previousEase,
            'new_ease' => $card->ease_factor,
            'time_taken_ms' => $timeTakenMs,
            'reviewed_at' => now(),
        ]);

        return ['card' => $card, 'log' => $log];
    }

    private function processLearningCard(SrsCard $card, SrsRating $rating): void
    {
        match ($rating) {
            SrsRating::Again => $this->learningAgain($card),
            SrsRating::Hard => $this->learningHard($card),
            SrsRating::Good => $this->learningGood($card),
            SrsRating::Easy => $this->learningEasy($card),
        };
    }

    private function learningAgain(SrsCard $card): void
    {
        $card->learning_step = 0;
        $card->card_state = CardState::Learning;
        $card->due_at = now()->addMinutes(self::LEARNING_STEPS[0]);
    }

    private function learningHard(SrsCard $card): void
    {
        $card->card_state = CardState::Learning;
        $card->due_at = now()->addMinutes(self::LEARNING_STEPS[$card->learning_step] ?? self::LEARNING_STEPS[0]);
    }

    private function learningGood(SrsCard $card): void
    {
        $nextStep = $card->learning_step + 1;

        if ($nextStep >= count(self::LEARNING_STEPS)) {
            $this->graduateCard($card, self::GRADUATING_INTERVAL);

            return;
        }

        $card->learning_step = $nextStep;
        $card->card_state = CardState::Learning;
        $card->due_at = now()->addMinutes(self::LEARNING_STEPS[$nextStep]);
    }

    private function learningEasy(SrsCard $card): void
    {
        $this->graduateCard($card, self::EASY_INTERVAL);
    }

    private function graduateCard(SrsCard $card, int $intervalDays): void
    {
        $card->card_state = CardState::Review;
        $card->interval_days = $intervalDays;
        $card->repetitions = 1;
        $card->learning_step = 0;
        $card->graduated_at = now();
        $card->due_at = now()->addDays($intervalDays);
    }

    private function processReviewCard(SrsCard $card, SrsRating $rating): void
    {
        match ($rating) {
            SrsRating::Again => $this->reviewAgain($card),
            SrsRating::Hard => $this->reviewHard($card),
            SrsRating::Good => $this->reviewGood($card),
            SrsRating::Easy => $this->reviewEasy($card),
        };
    }

    private function reviewAgain(SrsCard $card): void
    {
        $card->lapses++;
        $card->card_state = CardState::Relearning;
        $card->learning_step = 0;
        $card->ease_factor = max(self::MINIMUM_EASE, $card->ease_factor - 0.20);
        $card->due_at = now()->addMinutes(self::LEARNING_STEPS[0]);
    }

    private function reviewHard(SrsCard $card): void
    {
        $card->ease_factor = max(self::MINIMUM_EASE, $card->ease_factor - 0.15);
        $card->interval_days = max(1, (int) round($card->interval_days * 1.2));
        $card->repetitions++;
        $card->due_at = now()->addDays($card->interval_days);
    }

    private function reviewGood(SrsCard $card): void
    {
        $card->interval_days = max(1, (int) round($card->interval_days * $card->ease_factor));
        $card->repetitions++;
        $card->due_at = now()->addDays($card->interval_days);
    }

    private function reviewEasy(SrsCard $card): void
    {
        $card->ease_factor += 0.15;
        $card->interval_days = max(1, (int) round($card->interval_days * $card->ease_factor * 1.3));
        $card->repetitions++;
        $card->due_at = now()->addDays($card->interval_days);
    }

    private function processRelearningCard(SrsCard $card, SrsRating $rating): void
    {
        match ($rating) {
            SrsRating::Again => $this->learningAgain($card),
            SrsRating::Hard => $this->learningHard($card),
            SrsRating::Good => $this->relearningGood($card),
            SrsRating::Easy => $this->relearningEasy($card),
        };
    }

    private function relearningGood(SrsCard $card): void
    {
        $card->card_state = CardState::Review;
        $card->interval_days = max(1, (int) round($card->interval_days * 0.7));
        $card->learning_step = 0;
        $card->due_at = now()->addDays($card->interval_days);
    }

    private function relearningEasy(SrsCard $card): void
    {
        $card->card_state = CardState::Review;
        $card->interval_days = max(1, $card->interval_days);
        $card->learning_step = 0;
        $card->ease_factor += 0.15;
        $card->due_at = now()->addDays($card->interval_days);
    }
}
