<?php

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MidtransService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->plan = Plan::factory()->monthly()->create();
});

function mockNotification(array $overrides = []): object
{
    return (object) array_merge([
        'order_id' => 'NIHAO-SUB-1-123456',
        'transaction_status' => 'settlement',
        'fraud_status' => null,
        'payment_type' => 'bank_transfer',
        'transaction_id' => 'txn-abc-123',
    ], $overrides);
}

it('activates subscription on settlement', function () {
    $subscription = Subscription::factory()->pending()->for($this->user)->create([
        'plan_id' => $this->plan->id,
        'midtrans_order_id' => 'NIHAO-SUB-1-123456',
        'amount' => 49_000,
    ]);

    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('parseNotification')
        ->once()
        ->andReturn(mockNotification());

    $this->postJson(route('webhooks.midtrans'))
        ->assertOk();

    $subscription->refresh();
    $this->user->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->midtrans_transaction_id)->toBe('txn-abc-123')
        ->and($subscription->payment_method)->toBe('bank_transfer')
        ->and($subscription->starts_at)->not->toBeNull()
        ->and($subscription->expires_at)->not->toBeNull()
        ->and($this->user->is_premium)->toBeTrue()
        ->and($this->user->premium_expires_at)->not->toBeNull();
});

it('activates subscription on capture with accepted fraud status', function () {
    $subscription = Subscription::factory()->pending()->for($this->user)->create([
        'plan_id' => $this->plan->id,
        'midtrans_order_id' => 'NIHAO-SUB-1-123456',
        'amount' => 49_000,
    ]);

    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('parseNotification')
        ->once()
        ->andReturn(mockNotification([
            'transaction_status' => 'capture',
            'fraud_status' => 'accept',
        ]));

    $this->postJson(route('webhooks.midtrans'))
        ->assertOk();

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Active);
});

it('marks subscription as failed on deny', function () {
    $subscription = Subscription::factory()->pending()->for($this->user)->create([
        'plan_id' => $this->plan->id,
        'midtrans_order_id' => 'NIHAO-SUB-1-123456',
        'amount' => 49_000,
    ]);

    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('parseNotification')
        ->once()
        ->andReturn(mockNotification(['transaction_status' => 'deny']));

    $this->postJson(route('webhooks.midtrans'))
        ->assertOk();

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Failed);
});

it('marks subscription as failed on expire', function () {
    $subscription = Subscription::factory()->pending()->for($this->user)->create([
        'plan_id' => $this->plan->id,
        'midtrans_order_id' => 'NIHAO-SUB-1-123456',
        'amount' => 49_000,
    ]);

    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('parseNotification')
        ->once()
        ->andReturn(mockNotification(['transaction_status' => 'expire']));

    $this->postJson(route('webhooks.midtrans'))
        ->assertOk();

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Failed);
});

it('does not change status on pending notification', function () {
    $subscription = Subscription::factory()->pending()->for($this->user)->create([
        'plan_id' => $this->plan->id,
        'midtrans_order_id' => 'NIHAO-SUB-1-123456',
        'amount' => 49_000,
    ]);

    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('parseNotification')
        ->once()
        ->andReturn(mockNotification(['transaction_status' => 'pending']));

    $this->postJson(route('webhooks.midtrans'))
        ->assertOk();

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Pending);
});

it('returns 404 for unknown order id', function () {
    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('parseNotification')
        ->once()
        ->andReturn(mockNotification(['order_id' => 'NIHAO-SUB-999-000000']));

    $this->postJson(route('webhooks.midtrans'))
        ->assertNotFound();
});

it('skips already active subscription (idempotency)', function () {
    $subscription = Subscription::factory()->for($this->user)->create([
        'plan_id' => $this->plan->id,
        'midtrans_order_id' => 'NIHAO-SUB-1-123456',
        'status' => SubscriptionStatus::Active,
        'starts_at' => now(),
        'expires_at' => now()->addMonth(),
    ]);

    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('parseNotification')
        ->once()
        ->andReturn(mockNotification());

    $this->postJson(route('webhooks.midtrans'))
        ->assertOk()
        ->assertJson(['message' => 'Already processed']);
});

it('activates founder plan and clears cache', function () {
    $founderPlan = Plan::factory()->founder()->create();

    $subscription = Subscription::factory()->pending()->for($this->user)->create([
        'plan_id' => $founderPlan->id,
        'midtrans_order_id' => 'NIHAO-SUB-1-123456',
        'amount' => 149_000,
    ]);

    $mock = $this->mock(MidtransService::class);
    $mock->shouldReceive('parseNotification')
        ->once()
        ->andReturn(mockNotification());

    $this->postJson(route('webhooks.midtrans'))
        ->assertOk();

    $subscription->refresh();
    $this->user->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($this->user->is_premium)->toBeTrue();
});
