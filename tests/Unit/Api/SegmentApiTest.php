<?php

use Berkayk\OneSignal\Api\SegmentApi;
use Berkayk\OneSignal\Http\HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

function createSegmentApi(array $responses, ?array &$history = null): SegmentApi
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

    return new SegmentApi($http, 'test-app-id');
}

it('lists segments', function () {
    $history = [];
    $api = createSegmentApi([
        new Response(200, [], json_encode(['segments' => [['id' => 'seg-1', 'name' => 'Active']]])),
    ], $history);

    $result = $api->list();

    expect($history[0]['request']->getMethod())->toBe('GET');
    expect((string) $history[0]['request']->getUri())->toContain('/apps/test-app-id/segments');
    expect($result['segments'])->toHaveCount(1);
});

it('creates a segment', function () {
    $history = [];
    $api = createSegmentApi([
        new Response(200, [], json_encode(['id' => 'seg-2'])),
    ], $history);

    $api->create('VIP Users', [['field' => 'tag', 'key' => 'vip', 'value' => 'true']]);

    expect($history[0]['request']->getMethod())->toBe('POST');
    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['name'])->toBe('VIP Users');
    expect($body['filters'])->toHaveCount(1);
});

it('deletes a segment', function () {
    $history = [];
    $api = createSegmentApi([
        new Response(200, [], json_encode(['success' => true])),
    ], $history);

    $api->delete('seg-1');

    expect($history[0]['request']->getMethod())->toBe('DELETE');
    expect((string) $history[0]['request']->getUri())->toContain('/segments/seg-1');
});
