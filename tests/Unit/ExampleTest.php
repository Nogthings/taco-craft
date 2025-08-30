<?php

test('example test passes', function () {
    expect(true)->toBeTrue();
});

test('basic math operations work', function () {
    expect(1 + 1)->toBe(2);
    expect(2 * 3)->toBe(6);
    expect(10 / 2)->toBe(5);
});

test('string operations work', function () {
    expect('hello')->toBe('hello');
    expect(strlen('test'))->toBe(4);
    expect(strtoupper('hello'))->toBe('HELLO');
});

test('array operations work', function () {
    $array = [1, 2, 3];

    expect($array)->toHaveCount(3);
    expect($array)->toContain(2);
    expect(array_sum($array))->toBe(6);
});
