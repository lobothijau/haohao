<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows admin users to access the admin panel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertOk();
});

it('denies non-admin users access to the admin panel', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->get('/admin');

    $response->assertForbidden();
});

it('redirects guests to login', function () {
    $response = $this->get('/admin');

    $response->assertRedirect();
});
