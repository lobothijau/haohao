<?php

namespace Database\Factories;

use App\Enums\CardState;
use App\Enums\SrsRating;
use App\Models\SrsCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SrsReviewLog>
 */
class SrsReviewLogFactory extends Factory
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
            'srs_card_id' => SrsCard::factory(),
            'rating' => SrsRating::Good,
            'previous_state' => CardState::Review,
            'new_state' => CardState::Review,
            'previous_interval' => 1,
            'new_interval' => 3,
            'previous_ease' => 2.50,
            'new_ease' => 2.50,
            'time_taken_ms' => fake()->numberBetween(1000, 30000),
            'reviewed_at' => now(),
        ];
    }
}
