<?php

use Berkayk\OneSignal\Api\SubscriptionApi;
use Berkayk\OneSignal\Http\HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

function createSubscriptionApi(array $responses, ?array &$history = null): SubscriptionApi
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);

    if ($history !== null) {
        $stack->push(Middleware::history($history));
    }

    $http = new HttpClient(
        baseUrl: 'https://api.onesignal.com',
        apiKey: 'test-key',
        handler: $stack,
    );

    return new SubscriptionApi($http, 'test-app-id');
}

it('creates a subscription', function () {
    $history = [];
    $api = createSubscriptionApi([
        new Response(200, [], json_encode(['subscription' => ['id' => 'sub-1']])),
    ], $history);

    $api->create('external_id', 'user-1', ['type' => 'iOSPush', 'token' => 'push-token']);

    expect($history[0]['request']->getMethod())->toBe('POST');
    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['subscription'])->toBe(['type' => 'iOSPush', 'token' => 'push-token']);
});

it('updates a subscription', function () {
    $history = [];
    $api = createSubscriptionApi([
        new Response(200, [], json_encode([])),
    ], $history);

    $api->update('sub-1', ['enabled' => false]);

    expect($history[0]['request']->getMethod())->toBe('PATCH');
    expect((string) $history[0]['request']->getUri())->toContain('/subscriptions/sub-1');
});

it('deletes a subscription', function () {
    $history = [];
    $api = createSubscriptionApi([
        new Response(200, [], json_encode([])),
    ], $history);

    $api->delete('sub-1');

    expect($history[0]['request']->getMethod())->toBe('DELETE');
});

it('views subscription by token', function () {
    $history = [];
    $api = createSubscriptionApi([
        new Response(200, [], json_encode(['subscriptions' => [['id' => 'sub-1', 'enabled' => true]]])),
    ], $history);

    $result = $api->viewByToken('iOSPush', 'some-push-token');

    expect($history[0]['request']->getMethod())->toBe('GET');
    expect((string) $history[0]['request']->getUri())->toContain('/subscriptions/type/iOSPush/token/some-push-token');
    expect($result['subscriptions'][0]['enabled'])->toBeTrue();
});

it('transfers a subscription to another user', function () {
    $history = [];
    $api = createSubscriptionApi([
        new Response(200, [], json_encode([])),
    ], $history);

    $api->transfer('sub-1', 'external_id', 'user-2');

    expect($history[0]['request']->getMethod())->toBe('PATCH');
    expect((string) $history[0]['request']->getUri())->toContain('/subscriptions/sub-1/owner');
    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['identity'])->toBe(['external_id' => 'user-2']);
});
