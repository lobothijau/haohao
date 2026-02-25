<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Series>
 */
class SeriesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_zh' => fake()->sentence(3),
            'title_pinyin' => fake()->sentence(3),
            'title_id' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'description_id' => fake()->paragraph(),
            'cover_image_url' => null,
            'hsk_level' => fake()->numberBetween(1, 6),
            'is_published' => true,
            'published_at' => now(),
            'created_by' => null,
        ];
    }

    /**
     * Indicate that the series is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
