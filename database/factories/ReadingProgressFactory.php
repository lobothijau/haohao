<?php

namespace Database\Factories;

use App\Enums\ReadingStatus;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReadingProgress>
 */
class ReadingProgressFactory extends Factory
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
            'story_id' => Story::factory(),
            'status' => ReadingStatus::NotStarted,
            'last_sentence_position' => 0,
            'words_saved' => 0,
            'started_at' => null,
            'completed_at' => null,
            'time_spent_seconds' => 0,
        ];
    }
}
