<?php

use App\Services\ChineseSentenceSplitter;

beforeEach(function () {
    $this->splitter = new ChineseSentenceSplitter;
});

it('splits by 。', function () {
    $result = $this->splitter->split('你好。世界。');

    expect($result)->toBe(['你好。', '世界。']);
});

it('splits by ！', function () {
    $result = $this->splitter->split('太好了！');

    expect($result)->toBe(['太好了！']);
});

it('splits by ？', function () {
    $result = $this->splitter->split('你好吗？我很好。');

    expect($result)->toBe(['你好吗？', '我很好。']);
});

it('splits by ；', function () {
    $result = $this->splitter->split('第一句；第二句。');

    expect($result)->toBe(['第一句；', '第二句。']);
});

it('keeps delimiter attached to sentence', function () {
    $result = $this->splitter->split('你好！世界？');

    expect($result)->toBe(['你好！', '世界？']);
});

it('handles text without delimiters', function () {
    $result = $this->splitter->split('没有标点');

    expect($result)->toBe(['没有标点']);
});

it('filters empty results', function () {
    $result = $this->splitter->split('  你好。  ');

    expect($result)->toBe(['你好。']);
});

it('handles multiple punctuation types', function () {
    $result = $this->splitter->split('问题？答案！结束。');

    expect($result)->toBe(['问题？', '答案！', '结束。']);
});
