<?php

use App\Models\User;
use App\Models\UserPreference;

test('guests cannot update preferences', function () {
    $this->patchJson(route('preferences.update'), ['show_pinyin' => false])
        ->assertUnauthorized();
});

test('authenticated users can update preferences', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('preferences.update'), [
            'show_pinyin' => false,
            'show_translation' => true,
        ])
        ->assertOk();

    $this->assertDatabaseHas('user_preferences', [
        'user_id' => $user->id,
        'show_pinyin' => false,
        'show_translation' => true,
    ]);
});

test('preferences are updated not duplicated', function () {
    $user = User::factory()->create();
    UserPreference::factory()->create([
        'user_id' => $user->id,
        'show_pinyin' => true,
    ]);

    $this->actingAs($user)
        ->patchJson(route('preferences.update'), [
            'show_pinyin' => false,
        ])
        ->assertOk();

    expect(UserPreference::where('user_id', $user->id)->count())->toBe(1);
    expect(UserPreference::where('user_id', $user->id)->first()->show_pinyin)->toBeFalse();
});

test('partial preference updates are allowed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('preferences.update'), [
            'show_pinyin' => false,
        ])
        ->assertOk();

    $this->assertDatabaseHas('user_preferences', [
        'user_id' => $user->id,
        'show_pinyin' => false,
    ]);
});
