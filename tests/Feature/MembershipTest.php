<?php

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MidtransService;

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

    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('createSnapTransaction')
        ->once()
        ->andReturn(['snap_token' => 'snap-token-123', 'order_id' => 'NIHAO-SUB-1-123456']);

    $this->actingAs($user)
        ->post(route('membership.subscribe'), ['plan' => 'monthly'])
        ->assertRedirect();

    $subscription = $user->subscriptions()->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe(SubscriptionStatus::Pending)
        ->and($subscription->plan_id)->toBe($plan->id)
        ->and($subscription->amount)->toBe(49_000)
        ->and($subscription->snap_token)->toBe('snap-token-123')
        ->and($subscription->midtrans_order_id)->toBe('NIHAO-SUB-1-123456');
});

it('redirects to existing pending subscription instead of creating duplicate', function () {
    $plan = Plan::factory()->monthly()->create();
    $user = User::factory()->create();

    $existing = Subscription::factory()->pending()->for($user)->create([
        'plan_id' => $plan->id,
        'snap_token' => 'existing-snap-token',
    ]);

    $this->actingAs($user)
        ->post(route('membership.subscribe'), ['plan' => 'monthly'])
        ->assertRedirect(route('membership.checkout', $existing));

    expect($user->subscriptions()->count())->toBe(1);
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

    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('clientKey')->andReturn('client-key');
    $mock->shouldReceive('isProduction')->andReturn(false);

    $this->actingAs($otherUser)
        ->get(route('membership.checkout', $subscription))
        ->assertForbidden();
});

it('shows the checkout page for pending subscriptions', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->pending()->for($user)->create([
        'snap_token' => 'test-snap-token',
    ]);

    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('clientKey')->andReturn('client-key');
    $mock->shouldReceive('isProduction')->andReturn(false);

    $this->actingAs($user)
        ->get(route('membership.checkout', $subscription))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Membership/Checkout')
            ->where('snapToken', 'test-snap-token')
            ->where('clientKey', 'client-key')
            ->where('isProduction', false)
        );
});

it('deletes subscription and redirects back on midtrans failure', function () {
    $plan = Plan::factory()->monthly()->create();
    $user = User::factory()->create();

    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('createSnapTransaction')
        ->once()
        ->andThrow(new \Exception('Midtrans error'));

    $this->actingAs($user)
        ->post(route('membership.subscribe'), ['plan' => 'monthly'])
        ->assertRedirect(route('membership.index'))
        ->assertSessionHas('error');

    expect($user->subscriptions()->count())->toBe(0);
});
