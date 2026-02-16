<?php

use App\Models\User;

it('requires authentication', function () {
    $response = $this->getJson(route('pinyin.convert', ['text' => '你好']));

    $response->assertUnauthorized();
});

it('converts hanzi to pinyin', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('pinyin.convert', ['text' => '你好']));

    $response->assertSuccessful()
        ->assertJson(['pinyin' => 'nǐ hǎo']);
});

it('requires text parameter', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('pinyin.convert'));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('text');
});

it('validates text max length', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('pinyin.convert', [
        'text' => str_repeat('你', 51),
    ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('text');
});
