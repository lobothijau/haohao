<?php

use App\Services\CedictParser;

beforeEach(function () {
    $this->parser = new CedictParser;
});

it('parses a standard CC-CEDICT line', function () {
    $result = $this->parser->parseLine('你好 你好 [ni3 hao3] /hello/hi/');

    expect($result)
        ->toBeArray()
        ->and($result['traditional'])->toBe('你好')
        ->and($result['simplified'])->toBe('你好')
        ->and($result['pinyin'])->toBe('nǐ hǎo')
        ->and($result['pinyin_numbered'])->toBe('ni3 hao3')
        ->and($result['meaning_en'])->toBe('hello; hi');
});

it('returns null for comment lines', function () {
    expect($this->parser->parseLine('# this is a comment'))->toBeNull();
});

it('returns null for empty lines', function () {
    expect($this->parser->parseLine(''))->toBeNull();
});

it('joins multiple definitions with semicolons', function () {
    $result = $this->parser->parseLine('大 大 [da4] /big/large/great/');

    expect($result['meaning_en'])->toBe('big; large; great');
});

it('converts pinyin numbers to tone marks', function () {
    $result = $this->parser->parseLine('中國 中国 [Zhong1 guo2] /China/');

    expect($result['pinyin'])->toBe('Zhōng guó');
    expect($result['pinyin_numbered'])->toBe('Zhong1 guo2');
});

it('parses a file with mixed content', function () {
    $fixturePath = dirname(__DIR__, 2).'/Fixtures/test-cedict.txt';
    $entries = iterator_to_array($this->parser->parseFile($fixturePath));

    // The fixture has 15 valid entries (comments and empty lines are skipped)
    expect($entries)->toHaveCount(15);
    expect($entries[0]['simplified'])->toBe('你好');
    expect($entries[0]['meaning_en'])->toBe('hello; hi');
});
