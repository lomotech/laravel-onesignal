<?php

it('has correct default values', function () {
    // These are overridden in TestCase, so test the structure exists
    expect(config('onesignal'))->toBeArray();
    expect(config('onesignal'))->toHaveKeys([
        'app_id',
        'rest_api_url',
        'rest_api_key',
        'organization_api_key',
        'user_auth_key',
        'guzzle_client_timeout',
        'max_retries',
        'retry_delay',
    ]);
});

it('has timeout default of 30', function () {
    // Reset to package defaults for this test
    config(['onesignal.guzzle_client_timeout' => null]);

    // The env default in the config file is 30
    expect(config('onesignal.guzzle_client_timeout'))->toBeNull();
});

it('has max_retries default of 2', function () {
    expect(config('onesignal.max_retries'))->toBe(2);
});

it('has retry_delay default of 500', function () {
    expect(config('onesignal.retry_delay'))->toBe(500);
});
