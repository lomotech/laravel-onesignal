<?php

use Berkayk\OneSignal\Api\UserApi;
use Berkayk\OneSignal\Http\HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

function createUserApi(array $responses, ?array &$history = null): UserApi
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

    return new UserApi($http, 'test-app-id');
}

it('creates a user with properties and identity', function () {
    $history = [];
    $api = createUserApi([
        new Response(200, [], json_encode(['identity' => ['external_id' => 'user-1']])),
    ], $history);

    $result = $api->create(
        properties: ['tags' => ['level' => '5']],
        identity: 'external_id',
        identityValue: 'user-1',
    );

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['properties'])->toBe(['tags' => ['level' => '5']]);
    expect($body['identity'])->toBe(['external_id' => 'user-1']);
    expect((string) $history[0]['request']->getUri())->toContain('/apps/test-app-id/users');
});

it('creates a user with subscriptions', function () {
    $history = [];
    $api = createUserApi([
        new Response(200, [], json_encode(['identity' => []])),
    ], $history);

    $api->create(subscriptions: [['type' => 'Email', 'token' => 'test@example.com']]);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['subscriptions'])->toBe([['type' => 'Email', 'token' => 'test@example.com']]);
});

it('gets a user by alias', function () {
    $history = [];
    $api = createUserApi([
        new Response(200, [], json_encode(['properties' => ['tags' => []]])),
    ], $history);

    $api->get('external_id', 'user-1');

    expect((string) $history[0]['request']->getUri())->toContain('/apps/test-app-id/users/by/external_id/user-1');
    expect($history[0]['request']->getMethod())->toBe('GET');
});

it('updates a user', function () {
    $history = [];
    $api = createUserApi([
        new Response(200, [], json_encode(['properties' => ['tags' => ['level' => '10']]])),
    ], $history);

    $api->update('external_id', 'user-1', ['tags' => ['level' => '10']]);

    expect($history[0]['request']->getMethod())->toBe('PATCH');
    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['properties'])->toBe(['tags' => ['level' => '10']]);
});

it('deletes a user', function () {
    $history = [];
    $api = createUserApi([
        new Response(200, [], json_encode([])),
    ], $history);

    $api->delete('external_id', 'user-1');

    expect($history[0]['request']->getMethod())->toBe('DELETE');
    expect((string) $history[0]['request']->getUri())->toContain('/apps/test-app-id/users/by/external_id/user-1');
});

it('adds aliases to a user', function () {
    $history = [];
    $api = createUserApi([
        new Response(200, [], json_encode(['identity' => ['my_alias' => 'alias-value']])),
    ], $history);

    $api->addAliases('external_id', 'user-1', ['my_alias' => 'alias-value']);

    expect($history[0]['request']->getMethod())->toBe('PATCH');
    expect((string) $history[0]['request']->getUri())->toContain('/identity');
    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['identity'])->toBe(['my_alias' => 'alias-value']);
});

it('removes an alias from a user', function () {
    $history = [];
    $api = createUserApi([
        new Response(200, [], json_encode([])),
    ], $history);

    $api->removeAlias('external_id', 'user-1', 'my_alias');

    expect($history[0]['request']->getMethod())->toBe('DELETE');
    expect((string) $history[0]['request']->getUri())->toContain('/identity/my_alias');
});
