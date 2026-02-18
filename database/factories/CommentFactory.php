<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commentable_type' => Story::class,
            'commentable_id' => Story::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }

    public function reply(Comment $parent): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent->id,
            'commentable_type' => $parent->commentable_type,
            'commentable_id' => $parent->commentable_id,
        ]);
    }
}
