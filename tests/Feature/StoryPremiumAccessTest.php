<?php

use App\Models\Story;
use App\Models\User;

it('allows anyone to access non-premium stories', function () {
    $story = Story::factory()->create(['is_premium' => false, 'is_published' => true]);

    $this->get(route('stories.show', $story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Stories/Show'));
});

it('shows premium required page for guests accessing premium stories', function () {
    $story = Story::factory()->premium()->create(['is_published' => true]);

    $this->get(route('stories.show', $story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Membership/PremiumRequired'));
});

it('shows premium required page for non-premium users accessing premium stories', function () {
    $user = User::factory()->create(['is_premium' => false]);
    $story = Story::factory()->premium()->create(['is_published' => true]);

    $this->actingAs($user)
        ->get(route('stories.show', $story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Membership/PremiumRequired'));
});

it('allows premium users to access premium stories', function () {
    $user = User::factory()->premium()->create();
    $story = Story::factory()->premium()->create(['is_published' => true]);

    $this->actingAs($user)
        ->get(route('stories.show', $story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Stories/Show'));
});

it('allows anyone to access HSK-2+ stories with is_premium set to false', function () {
    $story = Story::factory()->create([
        'hsk_level' => 3,
        'is_premium' => false,
        'is_published' => true,
    ]);

    $this->get(route('stories.show', $story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Stories/Show'));
});

it('shows premium required for expired premium users', function () {
    $user = User::factory()->create([
        'is_premium' => true,
        'premium_expires_at' => now()->subDay(),
    ]);
    $story = Story::factory()->premium()->create(['is_published' => true]);

    $this->actingAs($user)
        ->get(route('stories.show', $story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Membership/PremiumRequired'));
});
