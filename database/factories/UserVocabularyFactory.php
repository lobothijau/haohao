<?php

namespace Database\Factories;

use App\Models\DictionaryEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserVocabulary>
 */
class UserVocabularyFactory extends Factory
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
            'source_story_id' => null,
            'source_sentence_id' => null,
            'user_note' => null,
        ];
    }
}
