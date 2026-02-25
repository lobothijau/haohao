<?php

use App\Services\PinyinService;

beforeEach(function () {
    $this->service = new PinyinService;
});

it('converts a single character', function () {
    expect($this->service->convert('你'))->toBe('nǐ');
});

it('converts a multi-character word', function () {
    expect($this->service->convert('你好'))->toBe('nǐ hǎo');
});

it('handles mixed chinese and non-chinese input', function () {
    expect($this->service->convert('hello你好'))->toBe('hello nǐ hǎo');
});
