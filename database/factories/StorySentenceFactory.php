<?php

namespace Database\Factories;

use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StorySentence>
 */
class StorySentenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'story_id' => Story::factory(),
            'position' => fake()->numberBetween(1, 50),
            'paragraph' => 1,
            'text_zh' => fake()->sentence(),
            'translation_id' => fake()->sentence(),
            'translation_en' => fake()->sentence(),
            'audio_url' => null,
        ];
    }
}
