<?php

use Berkayk\OneSignal\OneSignal;

it('registers onesignal singleton', function () {
    $instance1 = app('onesignal');
    $instance2 = app('onesignal');

    expect($instance1)->toBeInstanceOf(OneSignal::class);
    expect($instance1)->toBe($instance2);
});

it('aliases onesignal to OneSignal class', function () {
    $instance = app(OneSignal::class);

    expect($instance)->toBeInstanceOf(OneSignal::class);
});

it('merges config from package', function () {
    expect(config('onesignal.app_id'))->toBe('test-app-id');
    expect(config('onesignal.rest_api_key'))->toBe('test-rest-api-key');
});
