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

it('splitWithParagraphs treats single block as one paragraph', function () {
    $result = $this->splitter->splitWithParagraphs('你好。世界。');

    expect($result)->toBe([
        ['paragraph' => 1, 'sentences' => ['你好。', '世界。']],
    ]);
});

it('splitWithParagraphs splits multiple paragraphs by blank lines', function () {
    $text = "第一段第一句。第一段第二句。\n\n第二段第一句。\n\n第三段第一句。第三段第二句！";

    $result = $this->splitter->splitWithParagraphs($text);

    expect($result)->toHaveCount(3);
    expect($result[0])->toBe(['paragraph' => 1, 'sentences' => ['第一段第一句。', '第一段第二句。']]);
    expect($result[1])->toBe(['paragraph' => 2, 'sentences' => ['第二段第一句。']]);
    expect($result[2])->toBe(['paragraph' => 3, 'sentences' => ['第三段第一句。', '第三段第二句！']]);
});

it('splitWithParagraphs handles extra whitespace between paragraphs', function () {
    $text = "第一句。\n  \n  \n第二句。";

    $result = $this->splitter->splitWithParagraphs($text);

    expect($result)->toHaveCount(2);
    expect($result[0]['paragraph'])->toBe(1);
    expect($result[1]['paragraph'])->toBe(2);
});

it('splitWithParagraphs skips empty blocks', function () {
    $text = "\n\n你好。\n\n\n\n世界。\n\n";

    $result = $this->splitter->splitWithParagraphs($text);

    expect($result)->toHaveCount(2);
    expect($result[0]['paragraph'])->toBe(1);
    expect($result[1]['paragraph'])->toBe(2);
});
