<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DictionaryEntry>
 */
class DictionaryEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'simplified' => fake()->lexify('??'),
            'traditional' => null,
            'pinyin' => fake()->lexify('?? ??'),
            'pinyin_numbered' => null,
            'meaning_id' => fake()->sentence(3),
            'meaning_en' => fake()->sentence(3),
            'hsk_level' => fake()->numberBetween(1, 6),
            'word_type' => fake()->randomElement(['noun', 'verb', 'adjective', 'adverb']),
            'frequency_rank' => fake()->unique()->numberBetween(1, 10000),
            'audio_url' => null,
            'notes_id' => null,
            'hokkien_cognate' => null,
        ];
    }
}
