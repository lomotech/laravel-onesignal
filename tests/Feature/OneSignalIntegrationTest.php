<?php

use Berkayk\OneSignal\Http\HttpClient;
use Berkayk\OneSignal\OneSignal;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

function createOneSignal(array $responses, ?array &$history = null): OneSignal
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

    return new OneSignal(
        appId: 'test-app-id',
        restApiKey: 'test-key',
        organizationApiKey: 'test-org-key',
        httpClient: $http,
    );
}

it('sends notification to all via convenience method', function () {
    $history = [];
    $os = createOneSignal([
        new Response(200, [], json_encode(['id' => 'notif-1', 'recipients' => 100])),
    ], $history);

    $result = $os->sendNotificationToAll('Hello everyone!');

    expect($result['id'])->toBe('notif-1');

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['contents'])->toBe(['en' => 'Hello everyone!']);
    expect($body['included_segments'])->toBe(['All']);
    expect($body['app_id'])->toBe('test-app-id');
});

it('sends notification to segment via convenience method', function () {
    $history = [];
    $os = createOneSignal([
        new Response(200, [], json_encode(['id' => 'notif-2'])),
    ], $history);

    $os->sendNotificationToSegment('Hello segment!', 'Active Users');

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['included_segments'])->toBe(['Active Users']);
});

it('sends custom notification with additional params', function () {
    $history = [];
    $os = createOneSignal([
        new Response(200, [], json_encode(['id' => 'notif-3'])),
    ], $history);

    $os->setParam('priority', 10)
        ->sendNotificationCustom([
            'contents' => ['en' => 'Custom'],
            'included_segments' => ['All'],
        ]);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['priority'])->toBe(10);
});

it('defaults to All segment when no targeting specified in custom notification', function () {
    $history = [];
    $os = createOneSignal([
        new Response(200, [], json_encode(['id' => 'notif-4'])),
    ], $history);

    $os->sendNotificationCustom(['contents' => ['en' => 'Test']]);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['included_segments'])->toBe(['All']);
});

it('sends notification with all optional params', function () {
    $history = [];
    $os = createOneSignal([
        new Response(200, [], json_encode(['id' => 'notif-5'])),
    ], $history);

    $os->sendNotificationToAll(
        message: 'Hello!',
        url: 'https://example.com',
        data: ['key' => 'value'],
        buttons: [['id' => 'btn1', 'text' => 'Click']],
        schedule: '2026-01-01 12:00:00 UTC',
        headings: 'Important',
        subtitle: 'Read this',
    );

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['url'])->toBe('https://example.com');
    expect($body['data'])->toBe(['key' => 'value']);
    expect($body['buttons'])->toBe([['id' => 'btn1', 'text' => 'Click']]);
    expect($body['send_after'])->toBe('2026-01-01 12:00:00 UTC');
    expect($body['headings'])->toBe(['en' => 'Important']);
    expect($body['subtitle'])->toBe(['en' => 'Read this']);
});

it('gets a notification', function () {
    $os = createOneSignal([
        new Response(200, [], json_encode(['id' => 'notif-1', 'contents' => ['en' => 'test']])),
    ]);

    $result = $os->getNotification('notif-1');

    expect($result['id'])->toBe('notif-1');
});

it('lists notifications', function () {
    $os = createOneSignal([
        new Response(200, [], json_encode(['notifications' => [], 'total_count' => 0])),
    ]);

    $result = $os->getNotifications(limit: 10);

    expect($result)->toHaveKey('total_count');
});

it('deletes a notification', function () {
    $history = [];
    $os = createOneSignal([
        new Response(200, [], json_encode(['success' => true])),
    ], $history);

    $os->deleteNotification('notif-1');

    expect($history[0]['request']->getMethod())->toBe('DELETE');
});

it('uses sub-api for user management', function () {
    $history = [];
    $os = createOneSignal([
        new Response(200, [], json_encode(['identity' => ['external_id' => 'user-1']])),
    ], $history);

    $result = $os->users()->create(
        properties: ['tags' => ['level' => '5']],
        identity: 'external_id',
        identityValue: 'user-1',
    );

    expect($result['identity']['external_id'])->toBe('user-1');
});

it('triggers deprecation warning for sendNotificationToUser', function () {
    $os = createOneSignal([
        new Response(200, [], json_encode(['id' => 'notif-dep'])),
    ]);

    $deprecationTriggered = false;
    set_error_handler(function ($errno) use (&$deprecationTriggered) {
        if ($errno === E_USER_DEPRECATED) {
            $deprecationTriggered = true;
        }

        return true;
    });

    $os->sendNotificationToUser('Test', 'player-id-1');

    restore_error_handler();

    expect($deprecationTriggered)->toBeTrue();
});

it('triggers deprecation warning for sendNotificationToExternalUser', function () {
    $os = createOneSignal([
        new Response(200, [], json_encode(['id' => 'notif-dep'])),
    ]);

    $deprecationTriggered = false;
    set_error_handler(function ($errno) use (&$deprecationTriggered) {
        if ($errno === E_USER_DEPRECATED) {
            $deprecationTriggered = true;
        }

        return true;
    });

    $os->sendNotificationToExternalUser('Test', 'ext-user-1');

    restore_error_handler();

    expect($deprecationTriggered)->toBeTrue();
});

it('supports async mode via fluent interface', function () {
    $os = createOneSignal([
        new Response(200, [], json_encode(['id' => 'notif-async'])),
    ]);

    $promise = $os->async()->sendNotificationToAll('Async test');

    expect($promise)->toBeInstanceOf(GuzzleHttp\Promise\PromiseInterface::class);
    $result = $promise->wait();
    expect($result['id'])->toBe('notif-async');
});
