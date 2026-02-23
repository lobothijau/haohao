<?php

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('founder_claimed_count');
});

it('counts unique founder subscribers with active and expired status', function () {
    $founderPlan = Plan::factory()->founder()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Subscription::factory()->for($user1)->create([
        'plan_id' => $founderPlan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->for($user2)->create([
        'plan_id' => $founderPlan->id,
        'status' => SubscriptionStatus::Expired,
    ]);

    Cache::forget('founder_claimed_count');

    expect(Plan::founderClaimedCount())->toBe(2);
});

it('does not count pending or cancelled founder subscriptions', function () {
    $founderPlan = Plan::factory()->founder()->create();

    Subscription::factory()->pending()->create([
        'plan_id' => $founderPlan->id,
    ]);

    Subscription::factory()->create([
        'plan_id' => $founderPlan->id,
        'status' => SubscriptionStatus::Cancelled,
    ]);

    Cache::forget('founder_claimed_count');

    expect(Plan::founderClaimedCount())->toBe(0);
});

it('counts each user only once even with multiple founder subscriptions', function () {
    $founderPlan = Plan::factory()->founder()->create();
    $user = User::factory()->create();

    Subscription::factory()->for($user)->create([
        'plan_id' => $founderPlan->id,
        'status' => SubscriptionStatus::Expired,
    ]);

    Subscription::factory()->for($user)->create([
        'plan_id' => $founderPlan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Cache::forget('founder_claimed_count');

    expect(Plan::founderClaimedCount())->toBe(1);
});

it('shares founderCounter via inertia props on the membership page', function () {
    Plan::factory()->founder()->create();

    $this->get(route('membership.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Membership/Index')
            ->has('founderCounter')
            ->where('founderCounter.claimed', 0)
            ->where('founderCounter.limit', 100)
        );
});

it('prevents subscribing to founder plan when limit is reached', function () {
    config(['app.founder_limit' => 2]);

    $founderPlan = Plan::factory()->founder()->create();

    Subscription::factory()->count(2)->create([
        'plan_id' => $founderPlan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Cache::forget('founder_claimed_count');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('membership.subscribe'), ['plan' => 'founder'])
        ->assertRedirect(route('membership.index'))
        ->assertSessionHas('error');

    $founderPlan->refresh();

    expect($founderPlan->is_active)->toBeFalse();
});

it('allows subscribing to founder plan when under limit', function () {
    config(['app.founder_limit' => 100]);

    $founderPlan = Plan::factory()->founder()->create();
    $user = User::factory()->create();

    Cache::forget('founder_claimed_count');

    $this->actingAs($user)
        ->post(route('membership.subscribe'), ['plan' => 'founder'])
        ->assertRedirect();

    expect($user->subscriptions()->count())->toBe(1);
});

it('auto-deactivates founder plan when last spot is taken via payment', function () {
    config(['app.founder_limit' => 1]);

    $founderPlan = Plan::factory()->founder()->create();
    $user = User::factory()->create();

    $subscription = Subscription::factory()->pending()->for($user)->create([
        'plan_id' => $founderPlan->id,
        'amount' => 149_000,
        'midtrans_order_id' => 'ORDER-FOUNDER-LAST',
    ]);

    Cache::forget('founder_claimed_count');

    $this->actingAs($user)
        ->post(route('membership.process-payment', $subscription))
        ->assertRedirect(route('membership.index'));

    $founderPlan->refresh();

    expect($founderPlan->is_active)->toBeFalse();
});
