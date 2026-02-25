<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            ['slug' => 'founder', 'label' => 'Founder Edition', 'price' => 149_000, 'duration_months' => 12, 'sort_order' => 0],
            ['slug' => 'monthly', 'label' => '1 Bulan', 'price' => 49_000, 'duration_months' => 1, 'sort_order' => 1],
            ['slug' => 'six_month', 'label' => '6 Bulan', 'price' => 249_000, 'duration_months' => 6, 'sort_order' => 2],
            ['slug' => 'yearly', 'label' => '1 Tahun', 'price' => 399_000, 'duration_months' => 12, 'sort_order' => 3],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
