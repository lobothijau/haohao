<?php

namespace Database\Factories;

use App\Enums\CardState;
use App\Enums\CardType;
use App\Models\DictionaryEntry;
use App\Models\User;
use App\Models\UserVocabulary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SrsCard>
 */
class SrsCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'dictionary_entry_id' => DictionaryEntry::factory(),
            'user_vocabulary_id' => UserVocabulary::factory(),
            'card_state' => CardState::New,
            'ease_factor' => 2.50,
            'interval_days' => 0,
            'repetitions' => 0,
            'lapses' => 0,
            'learning_step' => 0,
            'due_at' => now(),
            'last_reviewed_at' => null,
            'graduated_at' => null,
            'card_type' => CardType::Recognition,
        ];
    }
}
