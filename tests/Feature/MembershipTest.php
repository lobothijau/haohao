<?php

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

it('allows guests to view the membership page', function () {
    Plan::factory()->monthly()->create();

    $this->get(route('membership.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Membership/Index'));
});

it('allows authenticated users to view the membership page', function () {
    Plan::factory()->founder()->create();
    Plan::factory()->monthly()->create();
    Plan::factory()->sixMonthly()->create();
    Plan::factory()->yearly()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('membership.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Membership/Index')
            ->has('plans', 4)
        );
});

it('redirects guests when trying to subscribe', function () {
    $this->post(route('membership.subscribe'), ['plan' => 'monthly'])
        ->assertRedirect(route('login'));
});

it('creates a pending subscription when subscribing', function () {
    $plan = Plan::factory()->monthly()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('membership.subscribe'), ['plan' => 'monthly'])
        ->assertRedirect();

    $subscription = $user->subscriptions()->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe(SubscriptionStatus::Pending)
        ->and($subscription->plan_id)->toBe($plan->id)
        ->and($subscription->amount)->toBe(49_000)
        ->and($subscription->midtrans_order_id)->not->toBeNull();
});

it('validates the plan field when subscribing', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('membership.subscribe'), ['plan' => 'invalid'])
        ->assertSessionHasErrors('plan');
});

it('redirects guests when trying to access checkout', function () {
    $subscription = Subscription::factory()->pending()->create();

    $this->get(route('membership.checkout', $subscription))
        ->assertRedirect(route('login'));
});

it('prevents users from accessing another users checkout', function () {
    $subscription = Subscription::factory()->pending()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get(route('membership.checkout', $subscription))
        ->assertForbidden();
});

it('shows the checkout page for pending subscriptions', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->pending()->for($user)->create();

    $this->actingAs($user)
        ->get(route('membership.checkout', $subscription))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Membership/Checkout'));
});

it('activates subscription and marks user premium after payment', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->monthly()->create();
    $subscription = Subscription::factory()->pending()->for($user)->create([
        'plan_id' => $plan->id,
        'amount' => 49_000,
        'midtrans_order_id' => 'ORDER-TEST123',
    ]);

    $this->actingAs($user)
        ->post(route('membership.process-payment', $subscription))
        ->assertRedirect(route('membership.index'));

    $subscription->refresh();
    $user->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->midtrans_transaction_id)->not->toBeNull()
        ->and($subscription->starts_at)->not->toBeNull()
        ->and($subscription->expires_at)->not->toBeNull()
        ->and($user->is_premium)->toBeTrue()
        ->and($user->premium_expires_at)->not->toBeNull();
});

it('prevents processing payment for non-pending subscriptions', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->for($user)->create([
        'status' => SubscriptionStatus::Active,
    ]);

    $this->actingAs($user)
        ->post(route('membership.process-payment', $subscription))
        ->assertNotFound();
});
