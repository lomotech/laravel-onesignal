<?php

namespace Berkayk\OneSignal\Api;

use Berkayk\OneSignal\Exceptions\ValidationException;
use Berkayk\OneSignal\Http\HttpClient;
use GuzzleHttp\Promise\PromiseInterface;

class NotificationApi
{
    public function __construct(
        protected HttpClient $http,
        protected string $appId,
    ) {}

    public function send(array $parameters, bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        $parameters['app_id'] ??= $this->appId;

        if (empty($parameters['contents']) && empty($parameters['content_available']) && empty($parameters['template_id'])) {
            throw new ValidationException('Notification must have contents, content_available, or template_id');
        }

        return $this->http->post('/notifications', $parameters, async: $async, callback: $callback);
    }

    public function sendToAll(string $message, array $extra = [], bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        return $this->send(array_merge([
            'contents' => ['en' => $message],
            'included_segments' => ['All'],
        ], $extra), $async, $callback);
    }

    public function sendToSegment(string $message, string|array $segments, array $extra = [], bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        return $this->send(array_merge([
            'contents' => ['en' => $message],
            'included_segments' => is_array($segments) ? $segments : [$segments],
        ], $extra), $async, $callback);
    }

    public function sendToAliases(string $message, array $aliases, array $extra = [], bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        return $this->send(array_merge([
            'contents' => ['en' => $message],
            'include_aliases' => $aliases,
        ], $extra), $async, $callback);
    }

    public function sendToSubscriptionIds(string $message, array $subscriptionIds, array $extra = [], bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        return $this->send(array_merge([
            'contents' => ['en' => $message],
            'include_subscription_ids' => $subscriptionIds,
        ], $extra), $async, $callback);
    }

    public function sendWithFilters(string $message, array $filters, array $extra = [], bool $async = false, ?callable $callback = null): array|PromiseInterface
    {
        return $this->send(array_merge([
            'contents' => ['en' => $message],
            'filters' => $filters,
        ], $extra), $async, $callback);
    }

    public function get(string $notificationId): array
    {
        return $this->http->get("/notifications/{$notificationId}?app_id={$this->appId}");
    }

    public function list(?int $limit = null, ?int $offset = null): array
    {
        $query = "app_id={$this->appId}";

        if ($limit !== null) {
            $query .= "&limit={$limit}";
        }

        if ($offset !== null) {
            $query .= "&offset={$offset}";
        }

        return $this->http->get("/notifications?{$query}");
    }

    public function cancel(string $notificationId): array
    {
        return $this->http->delete("/notifications/{$notificationId}?app_id={$this->appId}");
    }
}
