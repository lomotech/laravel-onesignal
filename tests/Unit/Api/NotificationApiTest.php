<?php

use Berkayk\OneSignal\Api\NotificationApi;
use Berkayk\OneSignal\Exceptions\ValidationException;
use Berkayk\OneSignal\Http\HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

function createNotificationApi(array $responses, ?array &$history = null): NotificationApi
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

    return new NotificationApi($http, 'test-app-id');
}

it('sends notification to all with app_id', function () {
    $history = [];
    $api = createNotificationApi([
        new Response(200, [], json_encode(['id' => 'notif-1'])),
    ], $history);

    $result = $api->sendToAll('Hello');

    expect($result)->toBe(['id' => 'notif-1']);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['app_id'])->toBe('test-app-id');
    expect($body['contents'])->toBe(['en' => 'Hello']);
    expect($body['included_segments'])->toBe(['All']);
});

it('sends notification to segment', function () {
    $history = [];
    $api = createNotificationApi([
        new Response(200, [], json_encode(['id' => 'notif-2'])),
    ], $history);

    $api->sendToSegment('Hello', 'Active Users');

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['included_segments'])->toBe(['Active Users']);
});

it('sends notification to aliases', function () {
    $history = [];
    $api = createNotificationApi([
        new Response(200, [], json_encode(['id' => 'notif-3'])),
    ], $history);

    $api->sendToAliases('Hello', ['external_id' => ['user-1', 'user-2']]);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['include_aliases'])->toBe(['external_id' => ['user-1', 'user-2']]);
});

it('sends notification to subscription ids', function () {
    $history = [];
    $api = createNotificationApi([
        new Response(200, [], json_encode(['id' => 'notif-4'])),
    ], $history);

    $api->sendToSubscriptionIds('Hello', ['sub-1', 'sub-2']);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['include_subscription_ids'])->toBe(['sub-1', 'sub-2']);
});

it('sends notification with filters', function () {
    $history = [];
    $api = createNotificationApi([
        new Response(200, [], json_encode(['id' => 'notif-5'])),
    ], $history);

    $filters = [['field' => 'tag', 'key' => 'level', 'relation' => '>', 'value' => '10']];
    $api->sendWithFilters('Hello', $filters);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['filters'])->toBe($filters);
});

it('throws ValidationException when no contents provided', function () {
    $api = createNotificationApi([
        new Response(200, [], json_encode([])),
    ]);

    $api->send(['app_id' => 'test']);
})->throws(ValidationException::class);

it('allows content_available without contents', function () {
    $api = createNotificationApi([
        new Response(200, [], json_encode(['id' => 'notif-6'])),
    ]);

    $result = $api->send(['content_available' => true, 'included_segments' => ['All']]);
    expect($result)->toHaveKey('id');
});

it('allows template_id without contents', function () {
    $api = createNotificationApi([
        new Response(200, [], json_encode(['id' => 'notif-7'])),
    ]);

    $result = $api->send(['template_id' => 'tmpl-1', 'included_segments' => ['All']]);
    expect($result)->toHaveKey('id');
});

it('gets a notification', function () {
    $history = [];
    $api = createNotificationApi([
        new Response(200, [], json_encode(['id' => 'notif-1', 'contents' => ['en' => 'test']])),
    ], $history);

    $result = $api->get('notif-1');

    expect($result['id'])->toBe('notif-1');
    expect((string) $history[0]['request']->getUri())->toContain('/notifications/notif-1?app_id=test-app-id');
});

it('lists notifications', function () {
    $history = [];
    $api = createNotificationApi([
        new Response(200, [], json_encode(['notifications' => [], 'total_count' => 0])),
    ], $history);

    $api->list(limit: 10, offset: 5);

    $uri = (string) $history[0]['request']->getUri();
    expect($uri)->toContain('limit=10');
    expect($uri)->toContain('offset=5');
});

it('cancels a notification', function () {
    $history = [];
    $api = createNotificationApi([
        new Response(200, [], json_encode(['success' => true])),
    ], $history);

    $api->cancel('notif-1');

    expect($history[0]['request']->getMethod())->toBe('DELETE');
});

it('merges extra params when sending to all', function () {
    $history = [];
    $api = createNotificationApi([
        new Response(200, [], json_encode(['id' => 'notif-8'])),
    ], $history);

    $api->sendToAll('Hello', ['url' => 'https://example.com', 'data' => ['key' => 'value']]);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['url'])->toBe('https://example.com');
    expect($body['data'])->toBe(['key' => 'value']);
});
