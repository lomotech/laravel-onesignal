<?php

use Berkayk\OneSignal\Api\AppApi;
use Berkayk\OneSignal\Http\HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

function createAppApi(array $responses, ?array &$history = null): AppApi
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);

    if ($history !== null) {
        $stack->push(Middleware::history($history));
    }

    $http = new HttpClient(
        baseUrl: 'https://api.onesignal.com',
        apiKey: 'test-key',
        organizationApiKey: 'test-org-key',
        handler: $stack,
    );

    return new AppApi($http, 'test-app-id');
}

it('lists apps using Bearer auth', function () {
    $history = [];
    $api = createAppApi([
        new Response(200, [], json_encode([['id' => 'app-1']])),
    ], $history);

    $api->list();

    expect($history[0]['request']->getHeaderLine('Authorization'))->toBe('Bearer test-org-key');
});

it('gets a specific app', function () {
    $history = [];
    $api = createAppApi([
        new Response(200, [], json_encode(['id' => 'app-1', 'name' => 'My App'])),
    ], $history);

    $result = $api->get('app-1');

    expect($result['name'])->toBe('My App');
    expect((string) $history[0]['request']->getUri())->toContain('/apps/app-1');
});

it('gets the default app when no ID provided', function () {
    $history = [];
    $api = createAppApi([
        new Response(200, [], json_encode(['id' => 'test-app-id'])),
    ], $history);

    $api->get();

    expect((string) $history[0]['request']->getUri())->toContain('/apps/test-app-id');
});

it('creates an app', function () {
    $history = [];
    $api = createAppApi([
        new Response(200, [], json_encode(['id' => 'new-app'])),
    ], $history);

    $api->create(['name' => 'New App']);

    expect($history[0]['request']->getMethod())->toBe('POST');
    expect($history[0]['request']->getHeaderLine('Authorization'))->toBe('Bearer test-org-key');
});

it('updates an app', function () {
    $history = [];
    $api = createAppApi([
        new Response(200, [], json_encode(['id' => 'test-app-id'])),
    ], $history);

    $api->update(['name' => 'Updated App']);

    expect($history[0]['request']->getMethod())->toBe('PUT');
    expect((string) $history[0]['request']->getUri())->toContain('/apps/test-app-id');
});
