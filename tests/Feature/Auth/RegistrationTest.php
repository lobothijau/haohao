<?php

use App\Models\UserPreference;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'hsk_level' => 1,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});

test('user preference is created on registration', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'hsk_level' => 1,
    ]);

    $this->assertAuthenticated();
    expect(UserPreference::where('user_id', auth()->id())->exists())->toBeTrue();
});

test('hsk_level validation rejects invalid values', function (mixed $hskLevel) {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'hsk_level' => $hskLevel,
    ]);

    $response->assertSessionHasErrors('hsk_level');
})->with([
    'zero' => 0,
    'seven' => 7,
    'negative' => -1,
    'string' => 'abc',
]);
