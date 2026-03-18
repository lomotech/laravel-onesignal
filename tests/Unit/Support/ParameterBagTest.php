<?php

use Berkayk\OneSignal\Support\ParameterBag;

it('sets and gets parameters', function () {
    $bag = new ParameterBag;

    $bag->set('key', 'value');

    expect($bag->get('key'))->toBe('value');
});

it('returns default when key not found', function () {
    $bag = new ParameterBag;

    expect($bag->get('missing', 'default'))->toBe('default');
    expect($bag->get('missing'))->toBeNull();
});

it('checks if key exists', function () {
    $bag = new ParameterBag;

    $bag->set('exists', 'yes');

    expect($bag->has('exists'))->toBeTrue();
    expect($bag->has('missing'))->toBeFalse();
});

it('merges parameters', function () {
    $bag = new ParameterBag;

    $bag->set('a', 1)->merge(['b' => 2, 'c' => 3]);

    expect($bag->toArray())->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
});

it('converts to array', function () {
    $bag = new ParameterBag;

    $bag->set('x', 'y')->set('z', 'w');

    expect($bag->toArray())->toBe(['x' => 'y', 'z' => 'w']);
});

it('supports fluent interface', function () {
    $bag = new ParameterBag;

    $result = $bag->set('a', 1)->set('b', 2)->merge(['c' => 3]);

    expect($result)->toBeInstanceOf(ParameterBag::class);
    expect($bag->toArray())->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
});

it('overwrites existing keys on merge', function () {
    $bag = new ParameterBag;

    $bag->set('key', 'old')->merge(['key' => 'new']);

    expect($bag->get('key'))->toBe('new');
});
