<?php

use App\Support\PinyinConverter;

it('converts first tone', function () {
    expect(PinyinConverter::convert('ma1'))->toBe('mā');
});

it('converts second tone', function () {
    expect(PinyinConverter::convert('ma2'))->toBe('má');
});

it('converts third tone', function () {
    expect(PinyinConverter::convert('ma3'))->toBe('mǎ');
});

it('converts fourth tone', function () {
    expect(PinyinConverter::convert('ma4'))->toBe('mà');
});

it('converts neutral tone (5)', function () {
    expect(PinyinConverter::convert('de5'))->toBe('de');
});

it('converts multi-syllable pinyin', function () {
    expect(PinyinConverter::convert('ni3 hao3'))->toBe('nǐ hǎo');
});

it('converts pinyin with u: to ü', function () {
    expect(PinyinConverter::convert('nv3'))->toBe('nǚ');
});

it('places tone mark on a when present', function () {
    expect(PinyinConverter::convert('bai2'))->toBe('bái');
});

it('places tone mark on e when present', function () {
    expect(PinyinConverter::convert('mei2'))->toBe('méi');
});

it('places tone mark on o in ou combination', function () {
    expect(PinyinConverter::convert('gou3'))->toBe('gǒu');
});

it('places tone mark on last vowel otherwise', function () {
    expect(PinyinConverter::convert('gui4'))->toBe('guì');
});

it('handles complex multi-syllable input', function () {
    expect(PinyinConverter::convert('Zhong1 guo2'))->toBe('Zhōng guó');
});
