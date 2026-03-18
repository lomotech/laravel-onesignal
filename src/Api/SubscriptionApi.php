<?php

namespace Berkayk\OneSignal\Api;

use Berkayk\OneSignal\Http\HttpClient;

class SubscriptionApi
{
    public function __construct(
        protected HttpClient $http,
        protected string $appId,
    ) {}

    public function create(string $aliasLabel, string $aliasId, array $subscription): array
    {
        return $this->http->post(
            "/apps/{$this->appId}/users/by/{$aliasLabel}/{$aliasId}/subscriptions",
            ['subscription' => $subscription],
        );
    }

    public function update(string $subscriptionId, array $subscription): array
    {
        return $this->http->patch(
            "/apps/{$this->appId}/subscriptions/{$subscriptionId}",
            ['subscription' => $subscription],
        );
    }

    public function delete(string $subscriptionId): array
    {
        return $this->http->delete("/apps/{$this->appId}/subscriptions/{$subscriptionId}");
    }

    public function viewByToken(string $type, string $token): array
    {
        return $this->http->get("/apps/{$this->appId}/subscriptions/type/{$type}/token/{$token}");
    }

    public function transfer(string $subscriptionId, string $aliasLabel, string $aliasId): array
    {
        return $this->http->patch(
            "/apps/{$this->appId}/subscriptions/{$subscriptionId}/owner",
            ['identity' => [$aliasLabel => $aliasId]],
        );
    }
}
