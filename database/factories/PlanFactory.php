<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => 'monthly',
            'label' => '1 Bulan',
            'price' => 49_000,
            'duration_months' => 1,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'monthly',
            'label' => '1 Bulan',
            'price' => 49_000,
            'duration_months' => 1,
            'sort_order' => 1,
        ]);
    }

    public function sixMonthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'six_month',
            'label' => '6 Bulan',
            'price' => 249_000,
            'duration_months' => 6,
            'sort_order' => 2,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'yearly',
            'label' => '1 Tahun',
            'price' => 399_000,
            'duration_months' => 12,
            'sort_order' => 3,
        ]);
    }

    public function founder(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'founder',
            'label' => 'Founder Edition',
            'price' => 149_000,
            'duration_months' => 12,
            'sort_order' => 0,
        ]);
    }
}
