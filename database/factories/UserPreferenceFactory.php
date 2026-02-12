<?php

namespace Database\Factories;

use App\Enums\CardOrder;
use App\Enums\CharacterSet;
use App\Enums\FontSize;
use App\Enums\ReadingMode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPreference>
 */
class UserPreferenceFactory extends Factory
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
            'show_pinyin' => true,
            'show_translation' => false,
            'font_size' => FontSize::Medium,
            'reading_mode' => ReadingMode::Full,
            'character_set' => CharacterSet::Simplified,
            'new_cards_per_day' => 20,
            'max_reviews_per_day' => 100,
            'card_order' => CardOrder::Mixed,
            'daily_reminder' => false,
            'reminder_time' => '09:00:00',
        ];
    }
}
