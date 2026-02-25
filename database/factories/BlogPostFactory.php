<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogPost>
 */
class BlogPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'slug' => fake()->unique()->slug(),
            'excerpt' => fake()->paragraph(),
            'body' => fake()->paragraphs(5, true),
            'featured_image_url' => null,
            'blog_category_id' => null,
            'created_by' => null,
            'is_published' => true,
            'published_at' => now(),
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the post has a category.
     */
    public function withCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'blog_category_id' => \App\Models\BlogCategory::factory(),
        ]);
    }
}
