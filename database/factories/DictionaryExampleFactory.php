<?php

namespace Database\Factories;

use App\Models\DictionaryEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DictionaryExample>
 */
class DictionaryExampleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dictionary_entry_id' => DictionaryEntry::factory(),
            'sentence_zh' => fake()->sentence(),
            'sentence_pinyin' => fake()->sentence(),
            'sentence_id' => fake()->sentence(),
        ];
    }
}
