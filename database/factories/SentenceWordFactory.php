<?php

namespace Database\Factories;

use App\Models\DictionaryEntry;
use App\Models\StorySentence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SentenceWord>
 */
class SentenceWordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'story_sentence_id' => StorySentence::factory(),
            'dictionary_entry_id' => DictionaryEntry::factory(),
            'position' => fake()->numberBetween(1, 20),
            'surface_form' => fake()->lexify('??'),
        ];
    }
}
