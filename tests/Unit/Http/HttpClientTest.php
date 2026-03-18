<?php

use Berkayk\OneSignal\Enums\AuthType;
use Berkayk\OneSignal\Exceptions\AuthenticationException;
use Berkayk\OneSignal\Exceptions\NotFoundException;
use Berkayk\OneSignal\Exceptions\OneSignalException;
use Berkayk\OneSignal\Exceptions\RateLimitException;
use Berkayk\OneSignal\Http\HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

function createHttpClient(array $responses, ?array &$history = null, ?string $orgKey = 'test-org-key'): HttpClient
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);

    if ($history !== null) {
        $stack->push(Middleware::history($history));
    }

    return new HttpClient(
        baseUrl: 'https://api.onesignal.com',
        apiKey: 'test-api-key',
        organizationApiKey: $orgKey,
        handler: $stack,
    );
}

it('sends GET requests with Key auth header', function () {
    $history = [];
    $client = createHttpClient([
        new Response(200, [], json_encode(['success' => true])),
    ], $history);

    $result = $client->get('/test');

    expect($result)->toBe(['success' => true]);
    expect($history[0]['request']->getHeaderLine('Authorization'))->toBe('Key test-api-key');
});

it('sends POST requests with JSON body', function () {
    $history = [];
    $client = createHttpClient([
        new Response(200, [], json_encode(['id' => '123'])),
    ], $history);

    $result = $client->post('/test', ['foo' => 'bar']);

    expect($result)->toBe(['id' => '123']);
    expect($history[0]['request']->getHeaderLine('Content-Type'))->toBe('application/json');

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body)->toBe(['foo' => 'bar']);
});

it('adds idempotency key header to POST requests', function () {
    $history = [];
    $client = createHttpClient([
        new Response(200, [], json_encode([])),
    ], $history);

    $client->post('/test', ['data' => 'value']);

    expect($history[0]['request']->hasHeader('Idempotency-Key'))->toBeTrue();
    expect($history[0]['request']->getHeaderLine('Idempotency-Key'))->not->toBeEmpty();
});

it('does not add idempotency key to GET requests', function () {
    $history = [];
    $client = createHttpClient([
        new Response(200, [], json_encode([])),
    ], $history);

    $client->get('/test');

    expect($history[0]['request']->hasHeader('Idempotency-Key'))->toBeFalse();
});

it('uses Bearer auth when specified', function () {
    $history = [];
    $client = createHttpClient([
        new Response(200, [], json_encode([])),
    ], $history);

    $client->get('/apps', AuthType::Bearer);

    expect($history[0]['request']->getHeaderLine('Authorization'))->toBe('Bearer test-org-key');
});

it('throws AuthenticationException when Bearer auth without org key', function () {
    $client = createHttpClient([
        new Response(200, [], json_encode([])),
    ], orgKey: null);

    $client->get('/apps', AuthType::Bearer);
})->throws(AuthenticationException::class, 'Organization API key is required');

it('throws RateLimitException on 429 response', function () {
    $client = createHttpClient([
        new Response(429, ['Retry-After' => '30'], json_encode(['errors' => ['Rate limit exceeded']])),
    ]);

    $client->get('/test');
})->throws(RateLimitException::class);

it('extracts Retry-After from 429 response', function () {
    $client = createHttpClient([
        new Response(429, ['Retry-After' => '45'], json_encode(['errors' => ['Rate limit exceeded']])),
    ]);

    try {
        $client->get('/test');
    } catch (RateLimitException $e) {
        expect($e->retryAfter)->toBe(45);
    }
});

it('throws AuthenticationException on 401 response', function () {
    $client = createHttpClient([
        new Response(401, [], json_encode(['errors' => ['Invalid auth']])),
    ]);

    $client->get('/test');
})->throws(AuthenticationException::class);

it('throws AuthenticationException on 403 response', function () {
    $client = createHttpClient([
        new Response(403, [], json_encode(['errors' => ['Forbidden']])),
    ]);

    $client->get('/test');
})->throws(AuthenticationException::class);

it('throws NotFoundException on 404 response', function () {
    $client = createHttpClient([
        new Response(404, [], json_encode(['errors' => ['Not found']])),
    ]);

    $client->get('/test');
})->throws(NotFoundException::class);

it('throws OneSignalException on other error responses', function () {
    $client = createHttpClient([
        new Response(500, [], json_encode(['errors' => ['Server error']])),
    ]);

    $client->get('/test');
})->throws(OneSignalException::class);

it('sends PUT requests', function () {
    $history = [];
    $client = createHttpClient([
        new Response(200, [], json_encode(['updated' => true])),
    ], $history);

    $result = $client->put('/test', ['key' => 'value']);

    expect($result)->toBe(['updated' => true]);
    expect($history[0]['request']->getMethod())->toBe('PUT');
});

it('sends PATCH requests', function () {
    $history = [];
    $client = createHttpClient([
        new Response(200, [], json_encode(['patched' => true])),
    ], $history);

    $result = $client->patch('/test', ['key' => 'value']);

    expect($result)->toBe(['patched' => true]);
    expect($history[0]['request']->getMethod())->toBe('PATCH');
});

it('sends DELETE requests', function () {
    $history = [];
    $client = createHttpClient([
        new Response(200, [], json_encode(['deleted' => true])),
    ], $history);

    $result = $client->delete('/test');

    expect($result)->toBe(['deleted' => true]);
    expect($history[0]['request']->getMethod())->toBe('DELETE');
});

it('supports async requests', function () {
    $client = createHttpClient([
        new Response(200, [], json_encode(['async' => true])),
    ]);

    $promise = $client->get('/test', async: true);

    expect($promise)->toBeInstanceOf(GuzzleHttp\Promise\PromiseInterface::class);

    $result = $promise->wait();
    expect($result)->toBe(['async' => true]);
});

it('supports async requests with callback', function () {
    $client = createHttpClient([
        new Response(200, [], json_encode(['value' => 42])),
    ]);

    $promise = $client->get('/test', async: true, callback: fn ($result) => $result['value'] * 2);

    expect($promise->wait())->toBe(84);
});
