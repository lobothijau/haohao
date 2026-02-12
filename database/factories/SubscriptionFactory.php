<?php

namespace Database\Factories;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
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
            'plan' => SubscriptionPlan::Monthly,
            'status' => SubscriptionStatus::Active,
            'midtrans_order_id' => null,
            'midtrans_transaction_id' => null,
            'payment_method' => null,
            'amount' => 99000,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'cancelled_at' => null,
        ];
    }
}
