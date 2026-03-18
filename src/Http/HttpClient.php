<?php

namespace Berkayk\OneSignal\Http;

use Berkayk\OneSignal\Enums\AuthType;
use Berkayk\OneSignal\Exceptions\AuthenticationException;
use Berkayk\OneSignal\Exceptions\NotFoundException;
use Berkayk\OneSignal\Exceptions\OneSignalException;
use Berkayk\OneSignal\Exceptions\RateLimitException;
use Berkayk\OneSignal\Support\IdempotencyKey;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;

class HttpClient
{
    protected Client $client;

    public function __construct(
        protected string $baseUrl,
        protected string $apiKey,
        protected ?string $organizationApiKey = null,
        protected int $timeout = 30,
        protected int $maxRetries = 2,
        protected int $retryDelay = 500,
        ?HandlerStack $handler = null,
    ) {
        $this->client = new Client([
            'handler' => $handler ?? $this->createHandler(),
            'timeout' => $this->timeout,
        ]);
    }

    protected function createHandler(): HandlerStack
    {
        return tap(HandlerStack::create(new CurlHandler), function (HandlerStack $stack) {
            $stack->push(Middleware::retry(function (int $retries, Psr7Request $request, ?Psr7Response $response = null, ConnectException|RequestException|null $exception = null): bool {
                if ($retries >= $this->maxRetries) {
                    return false;
                }

                if ($exception instanceof ConnectException) {
                    return true;
                }

                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }

                return false;
            }, function (int $retries): int {
                return $this->retryDelay * (2 ** $retries);
            }));
        });
    }

    public function get(string $endpoint, AuthType $auth = AuthType::Key, bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        return $this->request('GET', $endpoint, auth: $auth, async: $async, callback: $callback);
    }

    public function post(string $endpoint, array $data = [], AuthType $auth = AuthType::Key, bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        return $this->request('POST', $endpoint, $data, $auth, $async, $callback);
    }

    public function put(string $endpoint, array $data = [], AuthType $auth = AuthType::Key, bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        return $this->request('PUT', $endpoint, $data, $auth, $async, $callback);
    }

    public function patch(string $endpoint, array $data = [], AuthType $auth = AuthType::Key, bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        return $this->request('PATCH', $endpoint, $data, $auth, $async, $callback);
    }

    public function delete(string $endpoint, AuthType $auth = AuthType::Key, bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        return $this->request('DELETE', $endpoint, auth: $auth, async: $async, callback: $callback);
    }

    protected function request(string $method, string $endpoint, array $data = [], AuthType $auth = AuthType::Key, bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        $url = $this->baseUrl . $endpoint;

        $options = [
            'headers' => $this->buildHeaders($auth, $method),
            'verify' => false,
        ];

        if (! empty($data)) {
            $options['json'] = $data;
        }

        if ($async) {
            $promise = $this->client->requestAsync($method, $url, $options)
                ->then(fn (Psr7Response $response) => $this->decodeResponse($response));

            return $callback ? $promise->then($callback) : $promise;
        }

        try {
            $response = $this->client->request($method, $url, $options);

            return $this->decodeResponse($response);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    protected function buildHeaders(AuthType $auth, string $method): array
    {
        $key = match ($auth) {
            AuthType::Key => $this->apiKey,
            AuthType::Bearer => $this->organizationApiKey ?? throw new AuthenticationException(
                'Organization API key is required for this operation',
                401,
            ),
        };

        $headers = [
            'Authorization' => $auth->value . ' ' . $key,
            'Content-Type' => 'application/json',
        ];

        if ($method === 'POST') {
            $headers['Idempotency-Key'] = IdempotencyKey::generate();
        }

        return $headers;
    }

    protected function decodeResponse(Psr7Response $response): array
    {
        $body = (string) $response->getBody();

        return json_decode($body, true) ?? [];
    }

    protected function handleRequestException(RequestException $e): never
    {
        $response = $e->getResponse();

        if (! $response) {
            throw new OneSignalException($e->getMessage(), 0, $e);
        }

        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true) ?? [];
        $errors = $body['errors'] ?? null;
        $message = is_array($errors) ? implode(', ', $errors) : ($e->getMessage());

        match (true) {
            $statusCode === 429 => throw new RateLimitException(
                message: $message,
                retryAfter: (int) ($response->getHeaderLine('Retry-After') ?: 60),
                previous: $e,
            ),
            in_array($statusCode, [401, 403]) => throw new AuthenticationException($message, $statusCode, $e, $errors),
            $statusCode === 404 => throw new NotFoundException($message, $statusCode, $e, $errors),
            default => throw new OneSignalException($message, $statusCode, $e, $errors),
        };
    }
}
