<?php

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

it('marks expired active subscriptions as expired', function () {
    $plan = Plan::factory()->create();

    $expired = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->subDay(),
    ]);

    $active = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addMonth(),
    ]);

    $this->artisan('subscriptions:expire')
        ->expectsOutputToContain('Expired 1 subscription(s)')
        ->assertSuccessful();

    expect($expired->fresh()->status)->toBe(SubscriptionStatus::Expired);
    expect($active->fresh()->status)->toBe(SubscriptionStatus::Active);
});

it('revokes premium from users with no remaining active subscriptions', function () {
    $user = User::factory()->premium()->create();
    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('subscriptions:expire')->assertSuccessful();

    expect($user->fresh()->is_premium)->toBeFalse();
});

it('keeps premium for users with another active subscription', function () {
    $user = User::factory()->premium()->create();
    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->subDay(),
    ]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addMonth(),
    ]);

    $this->artisan('subscriptions:expire')->assertSuccessful();

    expect($user->fresh()->is_premium)->toBeTrue();
});

it('outputs zero message when nothing to expire', function () {
    $this->artisan('subscriptions:expire')
        ->expectsOutputToContain('No subscriptions to expire')
        ->assertSuccessful();
});

it('does not touch non-active subscriptions', function () {
    $plan = Plan::factory()->create();

    $pending = Subscription::factory()->pending()->create([
        'plan_id' => $plan->id,
    ]);

    $cancelled = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Cancelled,
        'expires_at' => now()->subDay(),
        'cancelled_at' => now()->subDays(2),
    ]);

    $this->artisan('subscriptions:expire')
        ->expectsOutputToContain('No subscriptions to expire')
        ->assertSuccessful();

    expect($pending->fresh()->status)->toBe(SubscriptionStatus::Pending);
    expect($cancelled->fresh()->status)->toBe(SubscriptionStatus::Cancelled);
});
