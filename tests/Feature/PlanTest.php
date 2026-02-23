<?php

use App\Models\Plan;

it('can create a plan with all attributes', function () {
    $plan = Plan::factory()->monthly()->create();

    expect($plan->slug)->toBe('monthly')
        ->and($plan->label)->toBe('1 Bulan')
        ->and($plan->price)->toBe(49_000)
        ->and($plan->duration_months)->toBe(1)
        ->and($plan->is_active)->toBeTrue();
});

it('has factory states for all plan types', function () {
    $monthly = Plan::factory()->monthly()->create();
    $sixMonthly = Plan::factory()->sixMonthly()->create();
    $yearly = Plan::factory()->yearly()->create();
    $founder = Plan::factory()->founder()->create();

    expect($monthly->slug)->toBe('monthly')
        ->and($sixMonthly->slug)->toBe('six_month')
        ->and($yearly->slug)->toBe('yearly')
        ->and($founder->slug)->toBe('founder');
});

it('casts price to integer', function () {
    $plan = Plan::factory()->create(['price' => 49000]);

    expect($plan->price)->toBeInt();
});

it('casts is_active to boolean', function () {
    $plan = Plan::factory()->create(['is_active' => true]);

    expect($plan->is_active)->toBeBool();
});
